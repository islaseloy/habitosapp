# Informe de Pruebas de Estrés — Sistema de Gestión de Hábitos

## 1. Objetivo

El objetivo de las pruebas de estrés es evaluar el comportamiento real de la aplicación bajo condiciones de concurrencia sobre el entorno desplegado (**Render Free + Neon PostgreSQL**), verificando tanto la correcta ejecución de los principales flujos funcionales como la capacidad de respuesta real de la infraestructura actual.

Las pruebas buscan obtener métricas válidas que permitan:

* Medir el rendimiento real del sistema ejecutando **100 % de operaciones productivas** (sin rechazos artificiales por el límite freemium).
* Observar el impacto de las mejoras implementadas (migración de SQLite a PostgreSQL en Neon y optimización de consultas en tracking/estadísticas).
* Dimensionar adecuadamente la infraestructura de producción propuesta en AWS a partir de datos empíricos reales.

---

## 2. Corrección Metodológica respecto a la Primera Corrida

En la primera evaluación de carga, los usuarios virtuales reutilizaban dos cuentas fijas (`gratis@example.com` y `premium@example.com`). Como consecuencia, una vez superados los 5 hábitos en la cuenta gratuita, el **74,47 % de las peticiones de creación eran rechazadas con HTTP 422 (`plan_limit_reached`)** y el script omitía las etapas de tracking y estadísticas. Esto subestimaba la carga real sobre el servidor.

Para corregir esa distorsión, el script `stress_test_habitos.js` fue reescrito bajo el criterio de **"un usuario nuevo por vuelta"**:

```text
GET /register + POST /register (usuario único por iteración)
  ↓
POST /api/habits (1.er hábito -> HTTP 201 al 100 %)
  ↓
POST /api/habit-tracking (tracking real en PostgreSQL -> HTTP 201)
  ↓
GET /api/habits/{id}/statistics (cálculo completo -> HTTP 200)
  ↓
Espera aleatoria (0,5–2 s) → Nueva iteración
```

Con esta metodología, **el 0 % de las operaciones es rechazado por validaciones de plan** y el **100 % de las iteraciones** ejecuta escritura criptográfica (`Bcrypt`), inserciones transaccionales en PostgreSQL y cálculos de estadísticas.

---

## 3. Prueba de Baseline — 1 VU (Entorno Actual: Render + Neon PostgreSQL)

Se ejecutaron 4 iteraciones completas con 1 usuario virtual concurrente contra `https://habitosapp-mfmb.onrender.com`:

### Resultados

| Métrica | Resultado |
|---|---:|
| VUs máximos | 1 |
| Iteraciones completadas | 4 (0 interrumpidas) |
| Requests HTTP totales | 24 |
| Throughput | **0,43 req/s** |
| p95 HTTP Global | **2,55 s** (avg 1,85 s) |
| p95 Registro + Login (`Bcrypt 12`) | **5,33 s** (avg 4,44 s) |
| p95 Creación de Hábito | **2,33 s** (avg 2,25 s) |
| p95 Tracking Diario | **2,34 s** (avg 2,20 s) |
| p95 Estadísticas | **2,09 s** (avg 1,94 s) |
| Checks funcionales | **100,00 %** (16/16) |
| Errores HTTP / Rechazos | **0,00 %** (0/24) |

### Lectura del Baseline
* **Cero rechazos:** Todas las iteraciones completaron las 4 etapas de negocio sin activar el gate freemium.
* **Impacto de infraestructura gratuita:** Cada operación de API toma entre **1,9 s y 2,3 s** en caliente debido al handshake TLS remoto entre Render y Neon (`us-east-1`) más el driver de sesión en base de datos, mientras que el registro/autenticación toma **~4,4 s** promedio por la carga de CPU de `Bcrypt` (costo 12) sobre `0,1 vCPU` compartida.

---

## 4. Prueba de Carga Progresiva — 15 VUs (Entorno Actual: Render + Neon PostgreSQL)

Se ejecutó el escenario `carga_progresiva` durante 2m 15s (rampa de 0 → 5 VUs en 30s, sostenimiento en 15 VUs durante 1m, ramp-down en 30s + 15s graceful):

### Resultados

| Métrica | Resultado |
|---|---:|
| VUs máximos | 15 |
| Iteraciones | 8 completadas / 13 interrumpidas por ramp-down |
| Requests HTTP totales | 79 |
| Throughput medido | **0,59 req/s** |
| p95 HTTP Global | **22,76 s** (avg 11,47 s / max 24,86 s) |
| p95 Registro + Login | **37,07 s** (avg 21,01 s) |
| p95 Creación de Hábito | **19,47 s** (avg 12,02 s) |
| p95 Tracking Diario | **19,06 s** (avg 12,16 s) |
| p95 Estadísticas | **23,83 s** (avg 13,64 s) |
| Checks funcionales | **100,00 %** (42/42) |
| Errores HTTP (`http_req_failed`) | **0,00 %** (0/79) |

---

## 5. Comparación: Medición Anterior (con 74 % de Rechazos) vs. Medición Real Actual

| Métrica | Corrida Anterior (SQLite, 74 % rebotes 422) | Corrida Actual (Neon PG, 0 % rebotes, 100 % trabajo real) |
|---|---:|---:|
| **Rechazos por Gate Freemium** | 74,47 % (70/94) | **0,00 % (0/11)** |
| **p95 HTTP (1 VU)** | 1,66 s | **2,55 s** |
| **p95 HTTP (15 VUs)** | 6,62 s | **22,76 s** |
| **Throughput (15 VUs)** | 1,18 req/s | **0,59 req/s** |
| **Checks funcionales** | 100 % | **100 %** |

### ¿Por qué empeoró el p95 al corregir el test?
El test anterior mostraba un `p95` artificialmente optimista (`6,62 s`) porque 3 de cada 4 peticiones morían inmediatamente en la validación de límite freemium y **no ejecutaban tracking ni estadísticas**. Al exigirle al servidor trabajo real en el 100 % de las iteraciones (incluyendo `Hash::make()` en registro y escrituras remotas en PostgreSQL sobre un servidor `php artisan serve` monohilo con **0,1 vCPU**), la cola de espera del único worker saturó la instancia:
1. El throughput quedó clavado en **~0,59 req/s** (cuello de botella de CPU y proceso único).
2. Los tiempos de espera (`http_req_waiting`) promediaron **11,45 s**, superando los umbrales de aceptación (`p95 < 2.000 ms`).
3. 13 iteraciones no lograron terminar antes del cierre de la ventana de 2 minutos.

---

## 6. Conclusiones para el Dimensionamiento

1. **Correctitud funcional del 100 %:** Incluso bajo saturación extrema (`p95 = 22,76 s`), la aplicación mantuvo **0 % de errores HTTP** y **100 % de checks funcionales aprobados** sobre Neon PostgreSQL.
2. **Inviabilidad del entorno gratuito para concurrencia:** El contenedor actual de Render Free (`0,1 vCPU`, `512 MB RAM`, servidor PHP monohilo) + conexión TLS externa por cada request hacia Neon no escala más allá de **~0,5–0,6 req/s** cuando ejecuta trabajo transaccional real.
3. **Justificación de la arquitectura AWS:** Esta nueva medición confirma de forma empírica la necesidad de migrar a **HAProxy + PHP-FPM multi-worker** sobre una instancia dedicada (**EC2 `t3.medium` con 2 vCPU**) junto a una base **RDS en la misma VPC privada** (eliminando el overhead de conexión TLS externa y el estrangulamiento de CPU).
