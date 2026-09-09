### 2.2 Modelo económico y punto de equilibrio

El modelo de ingresos se basa en una suscripción mensual Premium de aproximadamente **USD 4 por usuario**. El plan gratuito funciona como puerta de entrada al producto y se utiliza para adquirir usuarios que potencialmente pueden convertirse en clientes Premium.

El análisis económico debe considerar dos variables principales:

* **Cantidad total de usuarios.**
* **Tasa de conversión de usuarios gratuitos a usuarios Premium.**

La relación entre ambas variables es:

$$
Usuarios_{Premium} = Usuarios_{Totales} \times Tasa_{Conversión}
$$

Por lo tanto, los ingresos mensuales estimados pueden expresarse como:

$$
Ingresos = Usuarios_{Totales} \times Tasa_{Conversión} \times Precio_{Premium}
$$

Con un precio de aproximadamente **USD 4 mensuales**, algunos escenarios de conversión permiten observar rápidamente la escala necesaria para generar ingresos significativos.

| Usuarios totales | Conversión 2 % | Conversión 5 % | Conversión 10 % |
| ---------------: | -------------: | -------------: | --------------: |
|            1.000 |     20 Premium |     50 Premium |     100 Premium |
|            5.000 |    100 Premium |    250 Premium |     500 Premium |
|           10.000 |    200 Premium |    500 Premium |   1.000 Premium |
|           25.000 |    500 Premium |  1.250 Premium |   2.500 Premium |

Con un precio de USD 4 por mes, los ingresos brutos aproximados serían:

| Usuarios totales | 2 % conversión | 5 % conversión | 10 % conversión |
| ---------------: | -------------: | -------------: | --------------: |
|            1.000 |         USD 80 |        USD 200 |         USD 400 |
|            5.000 |        USD 400 |      USD 1.000 |       USD 2.000 |
|           10.000 |        USD 800 |      USD 2.000 |       USD 4.000 |
|           25.000 |      USD 2.000 |      USD 5.000 |      USD 10.000 |

Estos valores representan ingresos brutos y no contemplan impuestos, comisiones de los medios de pago, costos comerciales u otros gastos operativos.

### 2.3 Punto de equilibrio

El punto de equilibrio representa la cantidad mínima de usuarios Premium necesaria para cubrir los costos mensuales de operación.

Si se define:

* \(C\) = costo mensual total de infraestructura y operación.
* \(P\) = ingreso mensual neto por usuario Premium.

Entonces:

$$
Premium_{BE} = \frac{C}{P}
$$

En el caso simplificado de un precio de USD 4 mensuales, y suponiendo inicialmente que el ingreso neto coincide con el precio de venta:

$$
Premium_{BE} = \frac{C}{4}
$$

Por ejemplo, si la infraestructura tuviera un costo mensual de USD 100:

$$
Premium_{BE} = \frac{100}{4} = 25
$$

Serían necesarios **25 usuarios Premium** para alcanzar el punto de equilibrio de infraestructura.

La cantidad de usuarios totales dependerá de la tasa de conversión:

$$
Usuarios_{Totales} = \frac{Premium_{BE}}{Tasa_{Conversión}}
$$

Con una conversión del 5 %:

$$
Usuarios_{Totales} = \frac{25}{0,05} = 500
$$

Por lo tanto, con una infraestructura de USD 100 mensuales, un precio Premium de USD 4 y una conversión del 5 %, el producto necesitaría aproximadamente **500 usuarios totales**, de los cuales **25 serían Premium**, para cubrir el costo de infraestructura.

### 2.4 Relación entre usuarios y capacidad de infraestructura

El costo de infraestructura no debe establecerse independientemente de la cantidad de usuarios. Debe derivarse de la carga esperada sobre la aplicación.

Los resultados obtenidos mediante k6 permiten establecer una relación entre:

$$
Usuarios \rightarrow Carga \rightarrow RPS \rightarrow Recursos \rightarrow Costo
$$

El test realizado generó aproximadamente **0,53 requests por segundo con un VU**, bajo el patrón sintético de uso definido. Sin embargo, este valor no debe interpretarse como el consumo de un usuario real permanente, ya que cada VU ejecuta múltiples operaciones consecutivas y utiliza tiempos de espera artificiales.

Para realizar el dimensionamiento se utilizará posteriormente un modelo de concurrencia más representativo, estimando qué porcentaje de los usuarios totales se encuentra activo simultáneamente durante los períodos de mayor utilización.

El objetivo final será obtener una relación entre la cantidad de usuarios soportados y el costo mensual de infraestructura, de forma que pueda determinarse tanto la capacidad técnica como la viabilidad económica del producto.

### 2.5 Escenarios de crecimiento

A efectos del análisis se propone trabajar con tres escenarios:

**Escenario conservador:** baja cantidad de usuarios y baja conversión a Premium.

**Escenario esperado:** crecimiento moderado y una tasa de conversión razonable para un producto freemium.

**Escenario de crecimiento:** aumento significativo de usuarios y necesidad de escalar la infraestructura.

Cada escenario será evaluado en función de:

* Usuarios totales.
* Usuarios Premium.
* Ingresos mensuales.
* Carga estimada.
* Infraestructura necesaria.
* Costo mensual.
* Resultado económico.

Esto permitirá determinar no solo cuánto cuesta operar la plataforma, sino también **a partir de qué escala el modelo comienza a ser rentable y cuándo es necesario aumentar la capacidad de infraestructura**.
