# Propuesta de Infraestructura AWS — MVP para 500 Usuarios

## 1. Objetivo

A partir de los resultados obtenidos mediante las pruebas de carga con k6, se propone una arquitectura inicial de producción para el sistema de gestión de hábitos.

El objetivo es disponer de una infraestructura simple, de bajo costo y suficientemente preparada para el escenario objetivo de **500 usuarios registrados**, manteniendo un margen razonable dentro de un presupuesto máximo de **USD 100 mensuales**.

La región seleccionada para el análisis es **US East (N. Virginia) — us-east-1**.

---

## 2. Datos de entrada

### 2.1. Modelo de usuarios

El modelo de negocio utiliza una modalidad freemium:

- Plan gratuito: hasta 5 hábitos activos y estadísticas limitadas a los últimos 7 días.
- Plan Premium: aproximadamente **USD 4/mes**, con hábitos ilimitados, historial completo y exportación de datos.
- Escenario base de conversión: **5 %**.

Con 500 usuarios registrados y una conversión del 5 %:

\[
500 \times 0,05 = 25
\]

Se estiman aproximadamente **25 usuarios Premium** y **475 usuarios gratuitos**.

### 2.2. Resultados de k6

Las pruebas realizadas permitieron observar el comportamiento de la aplicación tanto sin carga significativa como bajo concurrencia.

| Escenario | VUs máximos | p95 HTTP | Checks funcionales |
|---|---:|---:|---:|
| Baseline | 1 | **1,66 s** | **100 %** |
| Carga | 15 | **6,62 s** | **100 %** |

La prueba de 15 VUs no debe interpretarse como el límite absoluto de la aplicación, sino como un punto a partir del cual se observó una degradación significativa del rendimiento.

Para el dimensionamiento inicial se adopta, de manera conservadora, un pico estimado de aproximadamente **25 usuarios concurrentes** sobre el escenario objetivo de 500 usuarios registrados.

---

## 3. Arquitectura propuesta

La arquitectura propuesta para el MVP es:

```text
                                  Internet
                                     │
                                     ▼
                                Route 53
                                     │
                                     ▼
                         ┌────────────────────┐
                         │ Application Load   │
                         │ Balancer (ALB)     │
                         └─────────┬──────────┘
                                   │
                                   ▼
                         ┌────────────────────┐
                         │ EC2 t3.medium      │
                         │ 2 vCPU / 4 GiB     │
                         │ Nginx + PHP-FPM    │
                         │ Laravel            │
                         └─────────┬──────────┘
                                   │
                                   │ VPC privada
                                   ▼
                         ┌────────────────────┐
                         │ RDS MySQL          │
                         │ db.t4g.micro       │
                         │ Single-AZ          │
                         └────────────────────┘
```

### 3.1. Nota sobre Availability Zones

Aunque el diseño utiliza una sola instancia EC2 y una base de datos RDS **Single-AZ**, un Application Load Balancer requiere subredes de al menos **dos Availability Zones diferentes**. Por lo tanto, el ALB debe desplegarse en dos subredes públicas, aunque inicialmente exista un único target EC2. AWS recomienda además disponer de al menos un target saludable en cada zona habilitada para aprovechar correctamente el balanceador.

Esto significa que el sistema **no es altamente disponible todavía**: si la única EC2 falla, no existe una segunda instancia de aplicación que pueda recibir tráfico. Las dos AZ son un requisito del ALB, no una indicación de que la aplicación ya tenga redundancia.

---

## 4. Componentes

### 4.1. Route 53

Route 53 se utiliza como servicio DNS autoritativo para asociar el dominio del producto con el Application Load Balancer.

AWS cobra **USD 0,50 mensuales por una zona hospedada** durante las primeras 25 zonas. Las consultas DNS estándar se cobran adicionalmente por volumen, aunque para una aplicación de este tamaño el costo esperado es muy bajo. 

**Estimación:** aproximadamente **USD 0,50–1/mes**.

---

### 4.2. Application Load Balancer

El ALB proporciona el punto de entrada de la aplicación y permite desacoplar el acceso público de la instancia EC2.

Esto tiene una ventaja importante para la evolución futura: actualmente puede existir un único target, pero posteriormente pueden agregarse más instancias EC2 sin modificar el punto de entrada del sistema.

En US East (N. Virginia), AWS publica un costo base de **USD 0,0225 por hora** para un Application Load Balancer, además del consumo de LCUs. Esto supone aproximadamente **USD 16,20/mes** de cargo horario para 720 horas, antes de LCUs. Un ejemplo oficial de AWS muestra un costo total de aproximadamente USD 22,42/mes para una carga determinada. 

**Estimación inicial:** **USD 17–23/mes**.

El costo real de LCUs dependerá del volumen de conexiones, bytes procesados y reglas configuradas.

---

### 4.3. EC2 t3.medium

La instancia de aplicación propuesta es una **EC2 t3.medium** con:

- 2 vCPU.
- 4 GiB de memoria.
- Rendimiento de CPU burstable.
- Linux/Unix On-Demand.

AWS publica para US East (N. Virginia) un precio On-Demand de **USD 0,0418/hora**, equivalente a aproximadamente **USD 30,10/mes** utilizando 720 horas. La instancia posee un baseline de CPU de 20 % por vCPU y utiliza créditos de CPU para absorber picos de utilización. 

La elección se justifica por el patrón de carga esperado: una aplicación web de hábitos presenta actividad intermitente con períodos de baja utilización y picos de actividad, por lo que una instancia burstable resulta adecuada para el MVP.

**Estimación:** aproximadamente **USD 30/mes**.

---

### 4.4. EBS

La EC2 utilizará almacenamiento **gp3** para el sistema operativo y la aplicación.

AWS publica para gp3 un precio de referencia de **USD 0,08 por GiB-mes**, incluyendo un nivel base de 3.000 IOPS y 125 MiB/s de throughput. 

Para un volumen inicial de 20 GiB:

\[
20 \times 0,08 = 1,60
\]

**Estimación:** aproximadamente **USD 1,60/mes**.

---

### 4.5. RDS MySQL

La base de datos se alojará en **Amazon RDS for MySQL**, evitando mantener y administrar manualmente el motor dentro de la EC2.

Como instancia inicial se propone **db.t4g.micro**, con:

- 2 vCPU.
- 1 GiB de memoria.
- Single-AZ.
- MySQL.

La documentación de AWS utiliza actualmente un ejemplo de **USD 0,019/hora** para una `db.t4g.micro`, equivalente a aproximadamente **USD 13,68/mes** por 720 horas.
Para almacenamiento se plantea inicialmente un volumen pequeño de propósito general. El tamaño podrá aumentar de acuerdo con el crecimiento real de los datos.

Como referencia de presupuesto, se puede reservar un margen adicional para almacenamiento y backups.

**Estimación de instancia:** aproximadamente **USD 14/mes**.

**Estimación con almacenamiento/backups:** aproximadamente **USD 16–20/mes**.

RDS también utiliza un modelo de CPU credits para las instancias T3/T4g y puede generar cargos adicionales de créditos si el uso sostenido supera el baseline durante períodos prolongados.
---

## 5. Red y ausencia de NAT Gateway

No se considera necesario utilizar un **NAT Gateway** en esta primera versión.

El motivo es económico y arquitectónico: la aplicación puede utilizar una EC2 con acceso público controlado mediante Security Groups, mientras que la base de datos permanece sin acceso público.

Una disposición posible es:

```text
VPC
│
├── Public Subnet AZ-a
│    ├── ALB
│    └── EC2
│
├── Public Subnet AZ-b
│    └── ALB
│
└── Private Subnets
     └── RDS MySQL
```

La EC2 puede comunicarse con RDS mediante su dirección privada dentro de la VPC.

El tráfico hacia la aplicación quedaría restringido de la siguiente manera:

```text
Internet
   │
   ▼
  ALB
   │
   │ HTTP/HTTPS
   ▼
  EC2
   │
   │ MySQL 3306
   ▼
  RDS
```

Los Security Groups deben permitir:

- ALB: entrada pública en HTTPS (y HTTP únicamente si se utiliza para redirección).
- EC2: entrada únicamente desde el Security Group del ALB.
- RDS: entrada al puerto 3306 únicamente desde el Security Group de la EC2.

Esta configuración evita exponer directamente la base de datos a Internet.

### Consideración operativa

Al no utilizar NAT Gateway, la EC2 pública puede disponer de conectividad de salida directa a Internet. Esto simplifica el acceso a actualizaciones y servicios externos, pero requiere aplicar una política de Security Groups restrictiva.

---

## 6. Estimación de costos mensuales

La siguiente estimación utiliza precios publicados por AWS y supone recursos funcionando durante aproximadamente 720 horas mensuales.

| Componente | Configuración | Estimación mensual |
|---|---|---:|
| Route 53 | 1 hosted zone | ~USD 0,50–1 |
| ALB | 1 ALB + bajo consumo de LCU | ~USD 17–23 |
| EC2 | t3.medium On-Demand | ~USD 30,10 |
| EBS | 20 GiB gp3 | ~USD 1,60 |
| RDS | db.t4g.micro Single-AZ | ~USD 13,68 |
| RDS storage/backups | Margen inicial | ~USD 2–6 |
| CloudWatch / otros | Bajo consumo | ~USD 0–5 |
| **Total estimado** | | **~USD 65–80/mes** |

Los valores son aproximados. El costo final dependerá principalmente del consumo del ALB, tráfico de red, almacenamiento y utilización de CPU de las instancias burstables.

Los precios de EC2, ALB, Route 53, EBS y RDS utilizados como referencia provienen de la documentación pública actual de AWS. 

---

## 7. Comparación con el presupuesto

Presupuesto máximo definido:

> **USD 100/mes**

Costo estimado:

> **USD 65–80/mes**

Margen disponible:

> **USD 20–35/mes**

Este margen permite absorber variaciones de consumo, principalmente en tráfico, LCUs, almacenamiento, backups y utilización de CPU.

Por lo tanto, la arquitectura propuesta resulta compatible con el presupuesto de USD 100 mensuales para el escenario inicial de 500 usuarios.

---

## 8. Escalabilidad futura

La arquitectura propuesta prioriza el bajo costo en la etapa inicial, pero mantiene un camino de crecimiento claro.

### Estado inicial — 500 usuarios

```text
                    ALB
                     │
                     ▼
                 EC2 t3.medium
                     │
                     ▼
              RDS MySQL Single-AZ
```

### Escenario de crecimiento

Ante un aumento sostenido de la carga, se pueden incorporar instancias adicionales:

```text
                       ALB
                    ┌────┴────┐
                    ▼         ▼
              EC2 instance  EC2 instance
                    │         │
                    └────┬────┘
                         ▼
                    RDS MySQL
```

El ALB permite distribuir el tráfico entre múltiples targets. En una etapa posterior podría incorporarse Auto Scaling para ajustar automáticamente la cantidad de instancias en función de CPU, requests u otras métricas.

La base de datos puede escalar verticalmente antes de incorporar mecanismos más complejos como réplicas de lectura, RDS Proxy o Aurora.

---

## 9. Relación entre capacidad, costo y usuarios

El objetivo del dimensionamiento no es maximizar la cantidad de recursos, sino encontrar el equilibrio entre capacidad y costo.

La relación que se utilizará para el análisis económico es:

```text
Usuarios totales
      ↓
Usuarios activos/concurrentes
      ↓
Requests por segundo
      ↓
Capacidad requerida
      ↓
Recursos AWS
      ↓
Costo mensual
      ↓
Ingresos Premium
      ↓
Rentabilidad
```

Con el escenario base planteado:

- **500 usuarios registrados.**
- **5 % de conversión a Premium.**
- **25 usuarios Premium.**
- **USD 4/mes por Premium.**

Los ingresos brutos mensuales serían:

\[
25 \times 4 = 100\ USD/mes
\]

Comparado con una infraestructura estimada en **USD 65–80/mes**, el escenario presenta un margen bruto aproximado de entre **USD 20 y USD 35 mensuales** antes de impuestos, comisiones de pago y otros costos del negocio.

Este resultado deberá analizarse con mayor profundidad en el modelo económico, particularmente porque una tasa de conversión distinta modifica fuertemente la cantidad de usuarios necesarios para sostener la infraestructura.

---

## 10. Conclusión

Para el escenario inicial de 500 usuarios, se propone una arquitectura AWS basada en:

- Route 53.
- Application Load Balancer.
- Una EC2 `t3.medium` On-Demand.
- RDS MySQL `db.t4g.micro` Single-AZ.
- EBS gp3.
- VPC con subredes públicas y privadas.
- Sin NAT Gateway.
- CloudWatch para monitorización básica.

La arquitectura mantiene los costos estimados dentro del límite de **USD 100 mensuales**, con un costo operativo esperado de aproximadamente **USD 65–80/mes**.

La principal limitación es que la aplicación mantiene un único servidor EC2 y una única instancia RDS, por lo que el diseño inicial prioriza el costo sobre la alta disponibilidad.

El ALB permite, sin embargo, evolucionar posteriormente hacia múltiples instancias de aplicación sin modificar el punto de entrada público del sistema.

Los resultados del stress test indican que la aplicación mantiene una correctitud funcional del 100 % bajo la carga evaluada, aunque presenta una degradación importante de latencia al aumentar la concurrencia. Por ello, la arquitectura propuesta deberá ser validada posteriormente con pruebas equivalentes sobre la infraestructura AWS para comprobar que el nuevo entorno mantiene un p95 aceptable dentro del escenario objetivo.

---

## Referencias de precios

- Amazon EC2 T3: https://aws.amazon.com/ec2/instance-types/t3/
- Elastic Load Balancing: https://aws.amazon.com/elasticloadbalancing/pricing/
- Amazon RDS for MySQL: https://aws.amazon.com/rds/mysql/pricing/
- Amazon Route 53: https://aws.amazon.com/route53/pricing/
- Amazon EBS: https://aws.amazon.com/ebs/pricing/
- Amazon VPC / IPv4: https://aws.amazon.com/vpc/pricing/
