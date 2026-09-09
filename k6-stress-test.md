# Informe de Pruebas de Estrés — Sistema de Gestión de Hábitos

## 1. Objetivo

El objetivo de las pruebas de estrés es evaluar el comportamiento de la aplicación bajo condiciones de concurrencia, verificando tanto la correcta ejecución de los principales flujos funcionales como la capacidad de respuesta de la infraestructura actual.

Las pruebas buscan obtener métricas que permitan:

* Identificar posibles problemas de rendimiento.
* Observar el comportamiento de la aplicación ante el incremento de usuarios concurrentes.
* Determinar una primera estimación de la capacidad de la plataforma.
* Obtener información para el posterior dimensionamiento de la infraestructura de producción.
* Utilizar los resultados como base para estimar los costos operativos y la rentabilidad del producto.

La aplicación evaluada corresponde a un sistema de gestión de hábitos desarrollado con Laravel, Blade y MySQL.

---

## 2. Herramienta utilizada

Para la ejecución de las pruebas se utilizó **k6**, una herramienta de pruebas de carga y rendimiento orientada a la generación de tráfico HTTP concurrente.

El escenario implementado simula usuarios que interactúan con los principales flujos de la aplicación:

1. Inicio de sesión.
2. Creación de hábitos.
3. Registro del cumplimiento diario de un hábito.
4. Consulta de estadísticas.

Cada usuario virtual mantiene su propia sesión mediante un *cookie jar*, permitiendo reproducir el comportamiento de múltiples usuarios autenticados de forma independiente.

---

## 3. Escenario de prueba

El test utiliza un escenario de carga progresiva (*ramping-vus*), incrementando gradualmente la cantidad de usuarios virtuales concurrentes.

La prueba principal alcanzó un máximo de **15 usuarios virtuales (VUs)**.

El flujo ejecutado por cada VU es:

```text
Login
  ↓
Creación de hábito
  ↓
Tracking diario
  ↓
Consulta de estadísticas
  ↓
Espera aleatoria
  ↓
Nueva iteración
```

La aplicación cuenta además con un límite de cinco hábitos activos para usuarios del plan gratuito. Este comportamiento fue incluido dentro de la prueba para verificar que el mecanismo de control del plan funcione correctamente bajo concurrencia.

---

## 4. Métricas evaluadas

Se utilizaron las siguientes métricas para evaluar el comportamiento del sistema.

### 4.1. Latencia HTTP

Se analizaron principalmente:

* Promedio.
* Mediana.
* Percentil 90 (p90).
* Percentil 95 (p95).
* Máximo.

El **p95** se utiliza como métrica principal para evaluar la experiencia de los usuarios, ya que representa el tiempo de respuesta por debajo del cual se encuentra el 95 % de las solicitudes.

### 4.2. Flujos funcionales

Se verificó el correcto funcionamiento de:

* Login.
* Creación de hábitos.
* Límite del plan gratuito.
* Tracking.
* Estadísticas.

### 4.3. Errores

Se analizaron tanto los errores funcionales detectados por los *checks* personalizados como los códigos HTTP devueltos por la aplicación.

Es importante distinguir las respuestas HTTP `422` correspondientes al límite del plan gratuito de errores reales de la aplicación. Estas respuestas representan un comportamiento esperado y fueron consideradas exitosas por el test funcional.

---

## 5. Prueba de baseline — 1 VU

Como punto de referencia se ejecutó una prueba con un único usuario virtual concurrente.

El objetivo de este escenario fue determinar el comportamiento de la aplicación sin una carga significativa y utilizarlo como referencia para comparar el efecto de la concurrencia.

### Resultados

| Métrica            |  Resultado |
| ------------------ | ---------: |
| VUs máximos        |          1 |
| Iteraciones        |          4 |
| Requests HTTP      |         20 |
| Throughput         | 0,53 req/s |
| p95 HTTP           | **1,66 s** |
| p95 Login          | **1,97 s** |
| p95 Tracking       | **266 ms** |
| p95 Estadísticas   | **260 ms** |
| Checks funcionales |  **100 %** |

La prueba no presentó errores funcionales.

El comportamiento del backend en las operaciones de tracking y estadísticas fue considerablemente más rápido que el login, mientras que la latencia HTTP global se mantuvo en torno a 1,66 segundos en el percentil 95.

Durante una ejecución anterior se observó un tiempo de respuesta de aproximadamente 24 segundos asociado a un *cold start* de la plataforma. La ejecución utilizada como baseline en este informe se realizó nuevamente y no presentó dicho comportamiento extremo.

---

## 6. Prueba de carga — 15 VUs

Posteriormente se ejecutó una prueba de carga progresiva alcanzando un máximo de **15 usuarios virtuales concurrentes**.

El escenario tuvo una duración aproximada de seis minutos y permitió observar el comportamiento de la aplicación a medida que aumentaba la concurrencia.

### Resultados

| Métrica            |   Resultado |
| ------------------ | ----------: |
| VUs máximos        |          15 |
| Iteraciones        |          94 |
| Requests HTTP      |         424 |
| p95 HTTP           |  **6,62 s** |
| p95 Login          | **11,23 s** |
| p95 Tracking       |  **6,54 s** |
| p95 Estadísticas   |  **4,88 s** |
| Checks funcionales |   **100 %** |
| Fallos funcionales |     **0 %** |

La tasa de activación del límite freemium fue del **74,47 %**. Esto significa que 70 de las 94 operaciones de creación de hábito fueron rechazadas mediante el mecanismo esperado de límite del plan gratuito, mientras que 24 hábitos fueron creados correctamente.

---

## 7. Comparación de resultados

La comparación entre ambos escenarios permite observar claramente el efecto de la concurrencia sobre los tiempos de respuesta.

| Métrica          |   1 VU |  15 VUs | Variación |
| ---------------- | -----: | ------: | --------: |
| p95 HTTP         | 1,66 s |  6,62 s |     ~4,0× |
| p95 Login        | 1,97 s | 11,23 s |     ~5,7× |
| p95 Tracking     | 0,27 s |  6,54 s |    ~24,2× |
| p95 Estadísticas | 0,26 s |  4,88 s |    ~18,8× |

El incremento de la latencia es significativamente superior al incremento de usuarios virtuales.

Mientras que la carga aumenta de 1 a 15 VUs, el p95 HTTP aumenta aproximadamente cuatro veces y algunas operaciones individuales presentan incrementos considerablemente mayores.

Esto indica que la aplicación comienza a experimentar una degradación de rendimiento cuando aumenta la concurrencia.

---

## 8. Análisis

Los resultados permiten separar dos aspectos diferentes del comportamiento de la plataforma.

### 8.1. Correctitud funcional

Desde el punto de vista funcional, el sistema presentó un comportamiento satisfactorio.

Los cuatro flujos principales fueron ejecutados correctamente y los *checks* funcionales alcanzaron:

> **100 % de éxito.**

El mecanismo de límite del plan gratuito también respondió correctamente, devolviendo el código `422` esperado cuando un usuario gratuito alcanzó el límite de hábitos activos.

Por lo tanto, la prueba no evidenció errores funcionales asociados a la concurrencia evaluada.

### 8.2. Rendimiento

El comportamiento de rendimiento es diferente.

Con un único VU, el p95 HTTP fue de aproximadamente **1,66 segundos**, mientras que con 15 VUs aumentó hasta aproximadamente **6,62 segundos**.

Además, el tiempo de espera del servidor (`http_req_waiting`) representa prácticamente la totalidad de la duración de las solicitudes, lo que indica que la mayor parte de la latencia se produce durante el procesamiento de la solicitud y no durante la transmisión de los datos.

Esto evidencia una degradación significativa del rendimiento bajo concurrencia.

---

## 9. Criterio preliminar de capacidad

Para realizar el posterior dimensionamiento de infraestructura se establece como referencia un objetivo de **p95 inferior o igual a 2 segundos** para las operaciones HTTP.

Bajo este criterio:

* El escenario de 1 VU se encuentra próximo al objetivo, con un p95 de 1,66 segundos.
* El escenario de 15 VUs supera ampliamente dicho objetivo, con un p95 de 6,62 segundos.

Por lo tanto, **15 VUs no debe interpretarse como la capacidad máxima de la aplicación**, sino como un punto de carga en el cual ya se observa una degradación importante del rendimiento.

La capacidad definitiva deberá estimarse posteriormente relacionando el *throughput* generado por el test con un modelo de comportamiento esperado de usuarios reales.

---

## 10. Limitaciones de la prueba

Los resultados deben interpretarse considerando las siguientes limitaciones:

* La prueba fue ejecutada sobre la infraestructura actual de la aplicación.
* La aplicación se encuentra desplegada sobre una plataforma con comportamiento de *cold start*, por lo que una instancia inactiva puede presentar una latencia inicial superior.
* El escenario utilizado es sintético y no representa necesariamente el comportamiento exacto de un usuario real.
* Cada iteración ejecuta varias operaciones consecutivas, por lo que un VU puede generar más tráfico que un usuario humano promedio.
* No se realizó todavía un análisis detallado de CPU, memoria, conexiones de base de datos o utilización de recursos internos.
* Por este motivo, los resultados deben utilizarse como referencia para dimensionamiento y no como una capacidad absoluta de usuarios finales.

---

## 11. Conclusiones

Las pruebas realizadas permiten concluir que la aplicación mantiene una **correctitud funcional del 100 %** bajo la carga evaluada, incluyendo autenticación, creación de hábitos, tracking, estadísticas y aplicación del límite freemium.

Sin embargo, se observa una degradación significativa del rendimiento al aumentar la concurrencia.

Con 1 VU se obtuvo un p95 HTTP de aproximadamente **1,66 segundos**, mientras que con 15 VUs el p95 aumentó hasta **6,62 segundos**.

El resultado demuestra que la plataforma puede procesar correctamente múltiples usuarios concurrentes, pero su rendimiento comienza a degradarse de manera significativa a medida que aumenta la carga.

Estos resultados serán utilizados como punto de partida para el siguiente análisis: **dimensionamiento de una infraestructura de producción capaz de soportar una cantidad estimada de usuarios, manteniendo niveles de rendimiento aceptables.**
