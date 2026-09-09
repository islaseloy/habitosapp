# Escalabilidad y Rentabilidad — Sistema de Gestión de Hábitos

## 1. Objetivo

A partir de los resultados obtenidos con k6 y del modelo de negocio freemium definido, se construye una primera estimación de escalabilidad económica y técnica.

El objetivo es relacionar:

```text
Usuarios totales
      ↓
Usuarios concurrentes estimados
      ↓
Carga HTTP esperada
      ↓
Infraestructura AWS
      ↓
Costo mensual
      ↓
Usuarios Premium
      ↓
Ingresos
      ↓
Resultado económico
```

Esta estimación se utiliza como modelo inicial para el dimensionamiento de infraestructura. No debe interpretarse como una capacidad garantizada hasta validar cada escalón con pruebas de carga sobre la infraestructura AWS propuesta.

---

## 2. Supuestos de cálculo

### 2.1. Modelo de ingresos

- Precio Premium: **USD 4/mes**.
- Conversión base Free → Premium: **5 %**.
- Equivalencia del escenario base: **1 usuario Premium cada 20 usuarios totales**.

Por lo tanto:

```text
Premium = Usuarios totales × 0,05
Ingresos = Premium × USD 4
```

### 2.2. Concurrencia

Como hipótesis inicial de dimensionamiento se considera que aproximadamente el **5 % de los usuarios registrados puede encontrarse activo de manera concurrente durante un período pico**.

Esta cifra es una hipótesis de diseño y no un dato medido por k6.

```text
Usuarios concurrentes = Usuarios totales × 0,05
```

### 2.3. Carga HTTP

La prueba de k6 con 15 VUs produjo aproximadamente **1,18 requests/s** durante el escenario completo.

Como aproximación inicial:

```text
1 VU ≈ 1,18 / 15
     ≈ 0,079 req/s
```

Por lo tanto, para dimensionamiento preliminar:

```text
RPS estimado ≈ VUs concurrentes × 0,079
```

Este valor debe interpretarse como una aproximación del workload definido por el test, no como el tráfico universal de un usuario real.

---

## 3. Arquitectura base

Para el escenario inicial de 500 usuarios se propone una arquitectura simple y económica:

```text
                         Internet
                            │
                            ▼
                       Route 53
                            │
                            ▼
                 Application Load Balancer
                            │
                            ▼
                    ┌───────────────┐
                    │ EC2 t3.medium │
                    │ 2 vCPU / 4 GB │
                    │ Nginx         │
                    │ PHP-FPM       │
                    │ Laravel       │
                    └───────┬───────┘
                            │
                            ▼
                    ┌───────────────┐
                    │ RDS MySQL     │
                    │ Single-AZ     │
                    │ db.t4g.micro  │
                    └───────────────┘
```

La EC2 puede ubicarse en una subnet pública y el RDS en una subnet privada. En este escenario no se requiere NAT Gateway para que la EC2 acceda al RDS dentro de la VPC.

El Application Load Balancer requiere subnets en al menos dos Availability Zones, aunque el backend pueda comenzar con una sola EC2. Esto permite agregar posteriormente instancias adicionales sin rediseñar el punto de entrada.

---

## 4. Estimación de costos de la arquitectura inicial

Los costos se expresan en USD/mes y utilizan aproximadamente 730 horas mensuales.

| Componente | Configuración inicial | Estimación mensual |
|---|---|---:|
| EC2 | 1 × t3.medium On-Demand | ~USD 30,5 |
| ALB | 1 Application Load Balancer | ~USD 16,4 + LCUs |
| RDS | 1 × db.t4g.micro, Single-AZ | ~USD 11,7 |
| EBS | Volumen pequeño para EC2 | ~USD 2–4 |
| RDS storage | 20–30 GiB | ~USD 2–3 |
| Route 53 | 1 hosted zone | ~USD 0,5 + consultas |
| CloudWatch / otros | Monitoreo básico | ~USD 0–5 |
| **Total estimado** | | **~USD 63–71/mes** |

La EC2 `t3.medium` tiene 2 vCPU, 4 GiB de memoria y un precio On-Demand publicado de USD 0,0418/h en US-East-1, equivalente a aproximadamente USD 30,5/mes con 730 horas. citeturn591386search1

AWS publica para el Application Load Balancer un cargo horario de USD 0,0225/h en US-East-1, además del componente variable de LCUs. Su ejemplo oficial de carga baja llega a aproximadamente USD 22,42/mes. citeturn241025search1

Para RDS se propone `db.t4g.micro` Single-AZ. AWS confirma que RDS factura por hora de instancia, almacenamiento y otros componentes, y que las T4g/T3 pueden generar cargos adicionales por CPU credits en modo Unlimited cuando superan su baseline. citeturn591386search0 Como referencia de precios actuales en us-east-1, `db.t4g.micro` ronda USD 0,016/h, aproximadamente USD 11,68/mes. citeturn183492search0

Route 53 cobra USD 0,50/mes por una hosted zone durante las primeras 25 zonas, más las consultas DNS. citeturn241025search0

> **Margen recomendado:** reservar aproximadamente USD 20–30 mensuales del presupuesto para variaciones de tráfico, almacenamiento, logs, transferencia y otros cargos.

---

## 5. Escenarios de escalabilidad

Utilizando la hipótesis de 5 % de concurrencia y el workload derivado de k6, se obtiene la siguiente tabla preliminar.

| Usuarios totales | Premium (5 %) | Usuarios concurrentes (5 %) | RPS estimado | Arquitectura propuesta | Costo infra estimado | Ingreso Premium | Resultado* |
|---:|---:|---:|---:|---|---:|---:|---:|
| 500 | 25 | 25 | ~1,96 | 1 × EC2 t3.medium + RDS + ALB | ~USD 70 | USD 100 | **+USD 30** |
| 1.000 | 50 | 50 | ~3,93 | 1 × EC2 t3.medium + RDS + ALB | ~USD 70 | USD 200 | **+USD 130** |
| 2.500 | 125 | 125 | ~9,83 | 2 × EC2 t3.medium + RDS + ALB | ~USD 100 | USD 500 | **+USD 400** |
| 5.000 | 250 | 250 | ~19,65 | 2 × EC2 t3.large + RDS escalado + ALB | ~USD 170 | USD 1.000 | **+USD 830** |
| 10.000 | 500 | 500 | ~39,30 | 3–4 × EC2 + RDS mayor + ALB | ~USD 300 | USD 2.000 | **+USD 1.700** |
| 25.000 | 1.250 | 1.250 | ~98,25 | Pool de EC2 + RDS escalado + alta disponibilidad | ~USD 500+ | USD 5.000 | **+USD 4.500+** |

\* Resultado simplificado: ingreso Premium menos infraestructura. No incluye impuestos, comisiones de pago, marketing, soporte, dominio, correo, observabilidad avanzada u otros costos de negocio.

Los tamaños y costos de los escalones superiores son **hipótesis de dimensionamiento** y deben validarse con nuevos tests de carga. La tabla representa la estrategia de escalabilidad, no una garantía de que una determinada cantidad de EC2 soportará exactamente la carga indicada.

---

## 6. Lectura de los resultados

La arquitectura inicial para 500 usuarios presenta un costo estimado de aproximadamente USD 63–71 mensuales, dejando margen dentro del presupuesto máximo establecido de **USD 100/mes**.

Con una conversión del 5 %, 500 usuarios producen aproximadamente 25 Premium y USD 100/mes de ingresos brutos.

Esto permite cubrir la infraestructura estimada en el escenario inicial, aunque con un margen reducido.

A medida que aumenta la base de usuarios, los ingresos crecen linealmente con la cantidad de usuarios mientras la infraestructura crece de manera escalonada.

Por ejemplo:

```text
500 usuarios
   ↓
25 Premium
   ↓
USD 100/mes

2.500 usuarios
   ↓
125 Premium
   ↓
USD 500/mes

10.000 usuarios
   ↓
500 Premium
   ↓
USD 2.000/mes
```

Este comportamiento hace que, bajo el supuesto de una conversión constante del 5 %, el margen potencial mejore a medida que crece la base de usuarios, siempre que la infraestructura pueda escalar sin que su costo crezca al mismo ritmo que los ingresos.

---

## 7. Relación con los resultados de k6

Los resultados reales obtenidos hasta el momento fueron:

| Escenario | p95 HTTP | Resultado funcional |
|---|---:|---:|
| 1 VU | **1,66 s** | 100 % |
| 15 VUs | **6,62 s** | 100 % |

La prueba mostró que la aplicación mantiene la correctitud funcional bajo la carga evaluada, pero experimenta una degradación importante de latencia cuando aumenta la concurrencia.

Por este motivo, no se toma 15 VUs como una capacidad máxima de usuarios finales. Se utiliza el resultado para calibrar el workload y justificar la necesidad de una infraestructura dedicada y escalable en producción.

La infraestructura propuesta busca eliminar la dependencia de un entorno compartido con *cold starts* y permite aumentar horizontalmente la capacidad del backend cuando los niveles de carga lo requieran.

---

## 8. Estrategia de escalamiento

### Etapa 1 — MVP / 500 usuarios

```text
ALB
 │
 └── EC2 t3.medium
       │
       └── RDS MySQL Single-AZ
```

Objetivo: mantener el costo por debajo de USD 100/mes y cubrir la etapa inicial del producto.

### Etapa 2 — Crecimiento / ~2.500 usuarios

```text
             ALB
           ┌───┴───┐
           ▼       ▼
        EC2-1    EC2-2
           └───┬───┘
               ▼
             RDS
```

Objetivo: incorporar redundancia de la capa de aplicación y aumentar la capacidad de procesamiento.

### Etapa 3 — Escala / 5.000+ usuarios

La aplicación puede continuar aumentando la cantidad de instancias detrás del ALB y escalar vertical u horizontalmente RDS según CPU, memoria, conexiones y tiempo de respuesta.

En esta etapa también sería razonable evaluar:

- Auto Scaling Groups.
- RDS Multi-AZ.
- Read Replicas si las consultas de estadísticas se convierten en un cuello de botella.
- Redis/ElastiCache para datos de sesión o caché si fuese necesario.
- S3 para archivos/exportaciones.
- CDN/CloudFront para contenido estático.

No se propone incorporar estos componentes en el MVP debido a su costo y complejidad adicionales.

---

## 9. Punto de equilibrio

Con el precio Premium fijado en USD 4/mes, el punto de equilibrio básico puede expresarse como:

```text
Premium necesarios = Costo mensual / USD 4
```

Para una infraestructura de aproximadamente USD 70/mes:

```text
USD 70 / USD 4
≈ 18 Premium
```

Con una conversión del 5 %:

```text
18 / 0,05
≈ 360 usuarios totales
```

Por lo tanto, en el escenario simplificado, el producto podría cubrir el costo de infraestructura con aproximadamente **360 usuarios totales**, equivalentes a unos **18 usuarios Premium**.

La cifra real de equilibrio deberá recalcularse en cada etapa de crecimiento, ya que la infraestructura se incrementa cuando la carga lo requiere.

---

## 10. Conclusión

El análisis preliminar muestra que una arquitectura AWS compuesta por Route 53, Application Load Balancer, una instancia EC2 `t3.medium` y una base de datos RDS MySQL Single-AZ resulta adecuada como punto de partida para el MVP de 500 usuarios, manteniéndose dentro del presupuesto máximo de USD 100 mensuales.

Los resultados del test de k6 demuestran que la aplicación funciona correctamente bajo concurrencia, pero que la infraestructura actual presenta una degradación considerable de latencia al pasar de 1 a 15 VUs.

La arquitectura propuesta busca resolver esta limitación mediante recursos dedicados y una capa de aplicación que pueda escalar horizontalmente a medida que aumente la demanda.

Desde el punto de vista económico, el modelo freemium con una conversión estimada del 5 % permite que los ingresos crezcan proporcionalmente con la base de usuarios, mientras que la infraestructura aumenta de forma escalonada. Esto genera un escenario potencialmente rentable siempre que la tasa de conversión y el costo de adquisición de usuarios se mantengan dentro de los supuestos del modelo.

Los siguientes pasos consisten en validar los escalones de infraestructura mediante pruebas de carga sobre AWS y refinar el modelo económico incorporando costos adicionales del negocio.
