# Sistema de Gestión de Hábitos
## Especificación Técnica, de Negocio y QA
**Versión final — 2025**

---

## Índice

1. [Problema y usuario objetivo](#1-problema-y-usuario-objetivo)
2. [Modelo de negocio](#2-modelo-de-negocio)
3. [Propuesta de valor y análisis competitivo](#3-propuesta-de-valor-y-análisis-competitivo)
4. [Alcance del MVP](#4-alcance-del-mvp)
5. [Arquitectura y justificación del stack](#5-arquitectura-y-justificación-del-stack)
6. [IDE y entorno de desarrollo](#6-ide-y-entorno-de-desarrollo)
7. [Modelo de datos](#7-modelo-de-datos)
8. [Flujo de la aplicación](#8-flujo-de-la-aplicación)
9. [Reglas de lógica de aplicación](#9-reglas-de-lógica-de-aplicación)
10. [Seguridad](#10-seguridad)
11. [Plan de pruebas — casos críticos (QA)](#11-plan-de-pruebas--casos-críticos-qa)
12. [Roadmap](#12-roadmap)

---

## 1. Problema y usuario objetivo

Gran parte de las personas tiene dificultades para incorporar hábitos saludables de forma sostenida. El problema no es la falta de intención, sino la falta de seguimiento, visibilidad del progreso y motivación continua.

| Segmento | Necesidad |
|---|---|
| Estudiantes (18–30 años) | Organizar rutinas de estudio, ejercicio y descanso durante períodos de alta carga académica. |
| Profesionales independientes | Mantener hábitos de productividad y salud sin estructura externa que los obligue. |
| Personas en proceso de cambio | Dejar de fumar, hacer ejercicio, meditar: necesitan registro y refuerzo positivo diario. |

---

## 2. Modelo de negocio

El producto se lanza como una aplicación web freemium, accesible desde el navegador, con un plan gratuito funcional y un plan premium que desbloquea funciones avanzadas de análisis y datos históricos.

| Elemento | Detalle |
|---|---|
| Modelo de ingresos | Freemium: plan gratuito (hasta 5 hábitos activos, estadísticas de últimos 7 días) + plan Premium (~USD 4/mes) con hábitos ilimitados, historial completo y exportación de datos. |
| Canal de distribución | Web app responsive. Sin app nativa en el MVP, pero optimizada para móvil (PWA como próximo paso). |
| Relación con el cliente | Self-service con onboarding guiado, notificaciones por email y resumen semanal de progreso. |
| Recursos clave | Plataforma web, base de datos de hábitos y tracking, lógica de rachas y estadísticas. |
| Estructura de costos | Hosting (ej. Railway o DigitalOcean), base de datos, dominio. Costo operativo bajo en etapa inicial. |

### 2.1 Gates de conversión del plan gratuito

El modelo freemium se sostiene sobre dos restricciones concretas e implementadas en el sistema:

**Restricción 1 — Cantidad de hábitos activos (límite: 5)**

Cuando un usuario del plan gratuito intenta crear un sexto hábito activo, el sistema responde con HTTP 422 y un mensaje de error con acción de upgrade. Esta validación ocurre en el servidor (`HabitController@store`), independientemente del estado del frontend. El límite aplica sobre hábitos con `estado = true`; los hábitos inactivos no cuentan para el límite.

Comportamiento exacto:
- El `HabitRequest` evalúa `auth()->user()->isPremium()` antes de procesar el formulario.
- Si el usuario no es premium y ya tiene 5 hábitos activos, se retorna una respuesta 422 con el JSON `{ "error": "plan_limit_reached", "message": "Alcanzaste el límite de hábitos de tu plan gratuito.", "action": "upgrade" }`.
- El frontend intercepta ese código y muestra un componente Blade de upgrade en lugar del mensaje genérico de error.

**Restricción 2 — Historial de estadísticas (límite: 7 días)**

Los usuarios gratuitos solo pueden ver estadísticas (porcentaje de cumplimiento, calendario, racha) filtradas a los últimos 7 días. Los usuarios premium acceden al historial completo desde la fecha de creación del hábito. Esta restricción se aplica en el query de `HabitTrackingController@statistics` con un condicional sobre `isPremium()`.

### 2.2 Simulación del plan Premium en el MVP académico

En ausencia de pasarela de pago real, el MVP simula el plan Premium mediante el campo `plan_expires_at` en la tabla `users`.

**Lógica de isPremium:**
```php
// En el modelo User
public function isPremium(): bool
{
    return $this->plan_expires_at !== null
        && $this->plan_expires_at->isFuture();
}
```

Este campo permite:
- Simular un trial de 14 días seteando `plan_expires_at = now()->addDays(14)` en el seeder.
- Demostrar la degradación al plan gratuito cuando el trial expira (el campo queda en el pasado sin eliminarlo).
- Testear ambos estados con factories sin infraestructura de pagos.

Para la presentación académica, el seed incluye al menos dos usuarios: uno con `plan_expires_at` en el futuro (premium activo) y uno con `plan_expires_at` nulo o en el pasado (gratuito), para demostrar el comportamiento diferenciado en estadísticas y creación de hábitos.

---

## 3. Propuesta de valor y análisis competitivo

**Propuesta de valor:** Una herramienta simple, sin gamificación forzada ni distracciones, centrada en la consistencia personal. A diferencia de alternativas más complejas, el usuario puede estar operativo en menos de 2 minutos, sin curva de aprendizaje.

> **Restricción de diseño derivada de la propuesta de valor:** el flujo de onboarding no puede superar 3 pasos desde el registro hasta el primer hábito creado y guardado. Esta restricción es verificable y debe incluirse en los tests de aceptación.

| Competidor | Fortaleza | Diferencial nuestro |
|---|---|---|
| Habitica | Gamificación y comunidad | Más simple, sin ruido visual. Enfocado en el hábito, no en el juego. |
| Streaks (iOS) | Excelente UX móvil nativa | Accesible desde cualquier navegador, sin costo de descarga. |
| Loop Habit Tracker | Gratuito, Android | Estadísticas más ricas y acceso web multiplataforma. |
| Fabulous | Rutinas y bienestar | Sin suscripción agresiva. Propuesta más directa y sin coaching forzado. |

---

## 4. Alcance del MVP

### Incluido en el MVP

- Registro e inicio de sesión de usuarios (con verificación por email)
- CRUD de hábitos con periodicidad (diaria, semanal con días específicos, mensual)
- Dashboard diario con los hábitos del día filtrados por periodicidad
- Estado vacío del dashboard: mensaje motivacional + acceso directo a estadísticas (ver sección 8.2)
- Tracking diario: marcar hábito como Completado, Fallado u Omitido
- Sistema de rachas (streak) por ocurrencias individuales completadas (ver sección 9.2)
- Estadísticas básicas: últimos 7 días para plan gratuito, historial completo para premium
- Cierre automático de día por cronjob con manejo de zona horaria por usuario
- Gestión de categorías con color e ícono
- Gate de conversión freemium: límite de 5 hábitos activos con respuesta HTTP 422 estructurada
- Simulación de plan Premium via `plan_expires_at` (trial de 14 días por seeder)
- Soft delete de hábitos con conservación silenciosa del historial de tracking

### Fuera del MVP (roadmap)

- App móvil nativa (diferida al siguiente sprint)
- Notificaciones push (se implementa post-MVP)
- Pasarela de pago real (Stripe o MercadoPago)
- Funcionalidades premium adicionales (exportación CSV/PDF)
- Integración con servicios externos (Google Fit, Apple Health)
- Sistema de metas con métricas cuantitativas

---

## 5. Arquitectura y justificación del stack

La arquitectura sigue el patrón MVC (Modelo–Vista–Controlador), que separa claramente la lógica de datos, la interfaz y el flujo de control. Esta decisión facilita el mantenimiento, el trabajo en equipo y la escalabilidad progresiva del proyecto.

### Patrón MVC — responsabilidades

| Capa | Responsabilidad | Tecnología |
|---|---|---|
| Modelo | Representación de los datos, relaciones entre entidades y lógica de persistencia. | Eloquent ORM (Laravel) |
| Vista | Interfaz que ve el usuario: formularios, dashboard, estadísticas. | Blade + Tailwind CSS |
| Controlador | Recibe peticiones HTTP, coordina Modelo y Vista, aplica reglas de lógica. | Controllers de Laravel (PHP) |

### Justificación de Laravel frente a otras opciones PHP

La cátedra requiere PHP como lenguaje de backend. La elección de Laravel dentro del ecosistema PHP responde a los siguientes criterios, evaluados contra las alternativas disponibles:

| Framework | Características | Razón de descarte |
|---|---|---|
| PHP puro / sin framework | Control total, sin dependencias. | Requiere escribir manualmente routing, ORM, validaciones y seguridad. Inviable para el tiempo del proyecto. |
| Symfony | Muy robusto, modular, ideal para sistemas complejos. | Curva de aprendizaje alta. Más adecuado para aplicaciones enterprise que para un MVP académico. |
| CodeIgniter 4 | Ligero, rápido de aprender. | Ecosistema más pequeño, menos herramientas built-in, comunidad menor. |
| **Laravel ✓ ELEGIDO** | MVC nativo, Eloquent ORM, Artisan CLI, Blade, Queues, Scheduler. | Es el estándar más adoptado del ecosistema PHP, con la mayor comunidad activa, documentación exhaustiva y el ecosistema de testing más completo (PHPUnit integrado, HTTP Tests, Factories, Mocking). |

**Nota específica sobre testing:** Laravel es la elección óptima para esta materia en particular porque su ecosistema de pruebas es el más maduro del ecosistema PHP. PHPUnit está integrado desde el scaffolding inicial, los HTTP Tests permiten simular requests completos sin browser, las Factories generan estados de base de datos complejos de forma declarativa, y Carbon::setTestNow() permite controlar el tiempo en los tests. Ninguna alternativa PHP ofrece este nivel de integración sin configuración adicional.

**Base de datos:** Se elige MySQL como motor principal por su compatibilidad probada con Laravel/Eloquent, disponibilidad en entornos de hosting compartido y facilidad de administración con herramientas como TablePlus. PostgreSQL queda como alternativa válida si el entorno de producción lo favorece.

---

## 6. IDE y entorno de desarrollo

| Herramienta | Rol | Justificación |
|---|---|---|
| Visual Studio Code | IDE principal | Gratuito, multiplataforma y con el mejor ecosistema de extensiones para PHP/Laravel: PHP Intelephense, Laravel Blade Snippets, Laravel Artisan, GitLens y ESLint. |
| Laragon (Windows) / Laravel Herd (Mac) | Entorno local | Servidor local con PHP, MySQL y Nginx preconfigurados. Evita la complejidad de WAMP/XAMPP. |
| Composer | Gestor de dependencias PHP | Herramienta estándar para instalar y actualizar Laravel y sus paquetes. |
| TablePlus / DBeaver | Cliente de base de datos | Interfaz gráfica para inspeccionar y depurar MySQL durante el desarrollo. |
| Git + GitHub | Control de versiones | Repositorio compartido por el equipo. Flujo de trabajo por ramas (main / develop / feature-*). |

**Extensiones clave de VS Code:** PHP Intelephense, Laravel Blade Snippets, Laravel Artisan, Tailwind CSS IntelliSense y GitLens.

---

## 7. Modelo de datos

Se mantiene la separación correcta entre la definición del hábito (tabla `habits`) y sus eventos de cumplimiento (tabla `habit_tracking`). El modelo incorpora todas las decisiones de negocio y QA definidas en este documento.

### `users` — Usuarios

| Campo | Tipo / Descripción |
|---|---|
| `id` | PK, autoincrement |
| `nombre` | string(100) |
| `email` | string, unique |
| `password` | string (bcrypt hash) |
| `email_verified_at` | timestamp, nullable — verificación de cuenta |
| `timezone` | string, default `'America/Argentina/Buenos_Aires'` — ver nota Q8 |
| `plan_expires_at` | timestamp, nullable — null = plan gratuito; fecha futura = premium activo |
| `fecha_registro` | timestamp, default now() |
| `deleted_at` | timestamp, nullable — soft delete |

> **Decisión Q2:** El campo `plan_expires_at` reemplaza la necesidad de un campo booleano `is_premium` estático. La lógica `isPremium()` en el modelo evalúa si este campo es no nulo y está en el futuro. Esto permite simular trials temporales sin infraestructura de pagos.

> **Decisión Q8 — Validación y fallback de timezone:** El campo `timezone` está sujeto a dos capas de protección. Primera capa: el `RegisterRequest` aplica la regla de validación nativa de Laravel `'timezone' => ['required', 'timezone']`, que valida contra la lista oficial de zonas horarias de PHP. Segunda capa: el modelo `User` define un Attribute Accessor que, si por cualquier razón el campo está vacío o es inválido en tiempo de ejecución, devuelve el valor de `config('app.timezone')` como fallback seguro. Esto previene que el Scheduler falle silenciosamente con valores nulos o corruptos.

```php
// En el modelo User — Accessor de timezone con fallback
protected function timezone(): Attribute
{
    return Attribute::make(
        get: fn ($value) => filled($value) ? $value : config('app.timezone'),
    );
}
```

### `categories` — Categorías

| Campo | Tipo / Descripción |
|---|---|
| `id` | PK, autoincrement |
| `nombre` | string(60) — ej: Salud, Estudio, Finanzas |
| `color_icono` | string — código hex o nombre de ícono |

### `habits` — Definición de hábitos

| Campo | Tipo / Descripción |
|---|---|
| `id` | PK, autoincrement |
| `usuario_id` | FK → users.id, cascade delete |
| `categoria_id` | FK → categories.id, nullable |
| `titulo` | string(120) |
| `descripcion` | text, nullable |
| `estado` | boolean, default true — activo/inactivo |
| `fecha_inicio` | date |
| `deleted_at` | timestamp, nullable — soft delete |

> **Decisión Q6:** Cuando un usuario elimina un hábito, se ejecuta un soft delete (se escribe `deleted_at`). Los registros asociados en `habit_tracking` se conservan en la base de datos para no perder integridad histórica. Sin embargo, esos registros quedan silenciados: no aparecen en el dashboard, no se incluyen en el cálculo de estadísticas y no son accesibles desde ninguna vista de la aplicación. El hábito eliminado tampoco es restaurable desde la UI del MVP.

### `habit_schedule` — Programación del hábito

| Campo | Tipo / Descripción |
|---|---|
| `id` | PK, autoincrement |
| `habito_id` | FK → habits.id, cascade delete |
| `tipo` | enum('diario','semanal','mensual') |
| `dias_semana` | JSON nullable — ej: `[1,3,5]` = lunes/miércoles/viernes |
| `dia_mes` | tinyint nullable — ej: 15 para hábitos mensuales |

> **Nota de diseño:** Esta tabla reemplaza al campo `periodicidad` como string/enum en `habits`. Resuelve el caso de hábitos como "lunes, miércoles y viernes" que no pueden representarse con un enum simple. El campo `dias_semana` almacena un array JSON con los números de día de la semana (1=lunes … 7=domingo).

### `habit_tracking` — Registro de cumplimiento

| Campo | Tipo / Descripción |
|---|---|
| `id` | PK, autoincrement |
| `habito_id` | FK → habits.id |
| `fecha_registro` | date |
| `estado_cumplimiento` | enum('completado','fallado','omitido') |
| `notas_opcionales` | text, nullable |
| `registrado_por_sistema` | boolean, default false — indica cierre automático del cronjob |

---

## 8. Flujo de la aplicación

### 8.1 Registro e inicio de sesión

El usuario se registra con nombre, email, contraseña y timezone (validado contra la lista oficial de PHP). El sistema envía un email de verificación. Una vez verificado, puede iniciar sesión. Se usa Laravel Breeze con protección CSRF en todos los formularios.

### 8.2 Dashboard principal

Al iniciar sesión, el sistema consulta los hábitos activos del usuario y, usando `habit_schedule`, calcula cuáles corresponden a la fecha actual según el día de la semana y la timezone del usuario. Solo esos hábitos se muestran en el dashboard.

**Estado vacío del dashboard (Decisión Q7):** Si el usuario no tiene hábitos para el día actual (ya sea porque es nuevo o porque ningún hábito programado corresponde a hoy), el dashboard muestra un mensaje motivacional contextual y un acceso directo a la sección de estadísticas. No se muestra una pantalla en blanco ni se redirige automáticamente. Este estado tiene su propio test de aceptación (ver sección 11).

### 8.3 Creación de hábitos

Formulario donde el usuario define: título, categoría, descripción opcional, tipo de periodicidad y días específicos (si aplica). Al guardar, el sistema valida primero el límite de plan antes de procesar el formulario. Si la validación pasa, se crean registros en `habits` y en `habit_schedule`.

### 8.4 Tracking diario

Desde el dashboard, el usuario marca cada hábito como Completado, Fallado, o simplemente lo ignora. Cada acción genera un registro en `habit_tracking` con la fecha actual y el estado elegido. No se pueden registrar fechas futuras (validado en servidor, independientemente del frontend).

### 8.5 Estadísticas

Vista dedicada donde el usuario puede ver: racha actual y racha máxima por hábito, porcentaje de cumplimiento, y calendario de hábitos con colores por estado. El rango de fechas disponible depende del plan:

- **Plan gratuito:** últimos 7 días corridos desde hoy.
- **Plan premium:** historial completo desde `habits.fecha_inicio`.

---

## 9. Reglas de lógica de aplicación

> **Nota:** estas reglas son de lógica de aplicación, no de modelo de negocio propiamente dicho. El modelo de negocio fue presentado en la sección 2.

### 9.1 Visibilidad por periodicidad

El controlador consulta `habit_schedule` para determinar si un hábito corresponde al día actual. Si un hábito tiene `dias_semana = [2, 4]` (martes y jueves), no aparece en el dashboard de los miércoles. El cálculo toma la fecha en la timezone del usuario (campo `timezone` en `users`, con fallback a `config('app.timezone')`).

### 9.2 Sistema de rachas (streak) — Decisiones Q4 y Q5

**Unidad de racha:** La racha se mide en **ocurrencias individuales completadas**, no en semanas o días calendario. Para un hábito con `dias_semana = [1,3,5]`, cada día programado es una ocurrencia independiente. Completar el lunes sube la racha en 1; completar el miércoles la sube en 1 más. La racha máxima posible por semana para ese hábito es 3.

**Qué rompe la racha:** Cualquier ocurrencia programada que finaliza con estado distinto de `completado` — ya sea `fallado` (acción explícita del usuario) o `omitido` (cierre automático del sistema, `registrado_por_sistema = true`) — rompe la racha y la reinicia en 0. No existe distinción de comportamiento entre ambos orígenes del estado `omitido`; ambos tienen el mismo efecto sobre el streak. Esta regla es deliberadamente estricta para mantener la propuesta de valor de consistencia real.

**Algoritmo de cálculo:**
```
racha_actual = 0
Para cada ocurrencia programada en orden cronológico inverso:
    Si estado_cumplimiento == 'completado':
        racha_actual += 1
    Sino (fallado u omitido):
        BREAK
```

### 9.3 Cierre automático de día (cronjob)

Un job programado en el Scheduler de Laravel se ejecuta a medianoche de cada timezone representada entre los usuarios activos. Para cada usuario, verifica si quedaron hábitos del día sin registrar y los cierra automáticamente como `omitido` con `registrado_por_sistema = true`. Este proceso usa la timezone del usuario (con fallback seguro, ver Q8) para determinar "su medianoche", evitando cierres prematuros o tardíos.

### 9.4 Restricción de fechas futuras

Tanto en el `HabitTrackingRequest` (validación server-side obligatoria) como en el frontend Blade (complementaria), se bloquea cualquier intento de registrar cumplimiento para una fecha posterior a hoy. La validación server-side es el único control confiable; la del frontend es UX, no seguridad.

### 9.5 Gate de conversión freemium

Antes de persistir un nuevo hábito, el `HabitController@store` evalúa:

```php
if (!auth()->user()->isPremium()
    && auth()->user()->habits()->where('estado', true)->count() >= 5) {
    return response()->json([
        'error'   => 'plan_limit_reached',
        'message' => 'Alcanzaste el límite de hábitos de tu plan gratuito.',
        'action'  => 'upgrade',
    ], 422);
}
```

El frontend intercepta el código `plan_limit_reached` y renderiza un componente Blade de upgrade en lugar del error genérico de formulario.

---

## 10. Seguridad

| Aspecto | Implementación |
|---|---|
| Contraseñas | Hashing con bcrypt mediante `Hash::make()` de Laravel. Nunca se almacenan en texto plano. |
| Autenticación | Sesiones con token de sesión HTTP-only (Laravel Breeze). Sin JWT en el MVP dado que no hay API pública. |
| CSRF | Token CSRF automático en todos los formularios Blade mediante `@csrf`. Validado por middleware en cada petición POST/PUT/DELETE. |
| XSS | Blade escapa automáticamente las variables con `{{ }}`. Se evita `{!! !!}` salvo casos controlados y documentados. |
| Verificación de email | Campo `email_verified_at` en `users`. Las rutas protegidas requieren el middleware `verified`. |
| Recuperación de contraseña | Flujo nativo de Laravel con token temporal enviado por email y expiración en 60 minutos. |
| Rate limiting | Middleware `throttle` en rutas de login y registro para prevenir ataques de fuerza bruta. |
| Autorización | Cada usuario solo puede ver y modificar sus propios hábitos. Validado mediante Policies de Laravel (no solo por ocultamiento en la vista). La respuesta ante acceso no autorizado es HTTP 403, no 404. |
| Validación de timezone | Regla nativa `'timezone'` de Laravel en el `RegisterRequest` + Attribute Accessor con fallback en el modelo (ver Q8). |

---

## 11. Plan de pruebas — Casos críticos (QA)

Esta sección forma parte del alcance del proyecto por ser una entrega de la materia de Testing. Los casos están clasificados por tipo de test y derivados directamente de las reglas de negocio y lógica de la aplicación.

### Herramientas utilizadas

| Herramienta | Uso |
|---|---|
| PHPUnit (integrado en Laravel) | Suite principal de tests |
| HTTP Tests de Laravel | Simular requests completos con sesión, sin browser |
| Model Factories | Generar estados de base de datos complejos de forma declarativa |
| `Carbon::setTestNow()` | Falsear la fecha/hora del sistema para tests del Scheduler |
| `Bus::fake()` / `Queue::fake()` | Interceptar jobs del Scheduler sin ejecutarlos |

---

### TC-01 — Gate de conversión freemium (Feature Test)
**Origen:** Decisión Q1 + Sección 9.5

**Dado que** un usuario del plan gratuito ya tiene exactamente 5 hábitos con `estado = true`.

**Cuando** envía un POST a `/habits` con datos válidos para un sexto hábito.

**Entonces:**
- El servidor responde HTTP 422.
- El body contiene `"error": "plan_limit_reached"`.
- La base de datos sigue teniendo exactamente 5 hábitos activos para ese usuario (el nuevo no se persiste).

**Variante A:** El mismo usuario tiene 5 activos + 3 inactivos (`estado = false`). El POST debe ser exitoso (HTTP 201) — los inactivos no cuentan para el límite.

**Variante B:** Un usuario con `plan_expires_at` en el futuro (premium activo) intenta crear el sexto hábito. El POST debe ser exitoso.

```php
// Estructura base del test
public function test_free_user_cannot_create_sixth_active_habit(): void
{
    $user = User::factory()->create(['plan_expires_at' => null]);
    Habit::factory()->count(5)->create(['usuario_id' => $user->id, 'estado' => true]);

    $this->actingAs($user)
         ->postJson('/habits', HabitFactory::validPayload())
         ->assertStatus(422)
         ->assertJsonFragment(['error' => 'plan_limit_reached']);

    $this->assertDatabaseCount('habits', 5);
}
```

---

### TC-02 — Cronjob de cierre con múltiples timezones (Integration Test)
**Origen:** Decisión Q8 + Sección 9.3

**Dado que** existen dos usuarios: uno con `timezone = 'America/Argentina/Buenos_Aires'` (UTC-3) y otro con `timezone = 'America/New_York'` (UTC-4), cada uno con un hábito activo sin registrar para hoy.

**Cuando** se ejecuta el Scheduler y `Carbon::setTestNow()` está seteado a las 00:00:00 en UTC-3.

**Entonces:**
- Solo el usuario de Buenos Aires recibe un registro automático de `omitido` con `registrado_por_sistema = true`.
- El usuario de New York NO tiene ningún registro nuevo (su medianoche aún no llegó).

**Variante de regresión:** Si el campo `timezone` de un usuario está vacío (dato corrupto), el cronjob usa `config('app.timezone')` como fallback y no falla silenciosamente.

---

### TC-03 — Cálculo de racha por ocurrencias individuales (Unit Test)
**Origen:** Decisiones Q4 y Q5 + Sección 9.2

**Dado que** un hábito con `dias_semana = [1,3,5]` tiene el siguiente historial en `habit_tracking`:

| Fecha | Estado | registrado_por_sistema |
|---|---|---|
| Lunes | completado | false |
| Miércoles | completado | false |
| Viernes | omitido | true |
| Lunes siguiente | completado | false |

**Cuando** se llama al servicio de cálculo de racha.

**Entonces:** La racha actual es 1 (solo el lunes siguiente; el viernes `omitido` por sistema reinició la racha a 0 aunque fue automático).

**Variante:** Verificar que `omitido` por el usuario (`registrado_por_sistema = false`) también reinicia la racha, sin excepción.

---

### TC-04 — Restricción de fechas futuras vía request directo (Feature Test)
**Origen:** Sección 9.4

**Dado que** un usuario autenticado tiene un hábito activo.

**Cuando** envía un POST directo a `/habit-tracking` con `fecha_registro = Carbon::tomorrow()->toDateString()` (bypass del frontend Blade).

**Entonces:**
- El servidor responde HTTP 422.
- No se crea ningún registro en `habit_tracking`.

> **Por qué importa:** esta validación debe existir en el servidor independientemente de cualquier control de UI. Un usuario podría manipular la fecha via DevTools o curl para inflar su racha artificialmente, invalidando la propuesta de valor del producto.

---

### TC-05 — Aislamiento de datos entre usuarios (Feature Test)
**Origen:** Sección 10 (Autorización via Policies)

**Dado que** el usuario A tiene un hábito con `id = X` y el usuario B está autenticado en una sesión separada.

**Cuando** el usuario B realiza GET `/habits/{X}` o POST `/habits/{X}/track`.

**Entonces:** El servidor responde HTTP 403 (Forbidden). No se devuelven datos del usuario A ni se modifican sus registros.

> **Nota técnica:** la respuesta correcta es 403, no 404. Devolver 404 oculta la existencia del recurso (security by obscurity), pero en el contexto de esta aplicación es aceptable como decisión de producto. La Policy de Laravel debe estar implementada; el ocultamiento en la vista Blade no es suficiente como control de acceso.

---

### TC-06 — Estadísticas filtradas por plan (Feature Test)
**Origen:** Decisión Q3 + Sección 2.1

**Dado que** un usuario gratuito tiene un hábito con registros en `habit_tracking` de los últimos 30 días.

**Cuando** accede a la vista de estadísticas de ese hábito.

**Entonces:** Solo se muestran datos de los últimos 7 días. Los registros anteriores existen en la base de datos pero no se incluyen en el query.

**Variante:** El mismo usuario activa un trial premium (`plan_expires_at = now()->addDays(14)`). Al recargar estadísticas, ve el historial completo de 30 días.

---

### TC-07 — Estado vacío del dashboard (Feature Test)
**Origen:** Decisión Q7 + Sección 8.2

**Dado que** un usuario autenticado no tiene ningún hábito programado para el día actual (por ejemplo, sus hábitos son solo los miércoles y hoy es martes).

**Cuando** accede al dashboard.

**Entonces:** La vista responde HTTP 200 y renderiza el componente de estado vacío con mensaje motivacional y el enlace a estadísticas. No se produce un error ni una redirección.

---

### TC-08 — Validación de timezone en registro (Feature Test)
**Origen:** Decisión Q8 + Sección 10

**Dado que** un actor intenta registrarse enviando un POST a `/register` con `timezone = 'Invalid/Zone'`.

**Cuando** el servidor procesa el request.

**Entonces:** Responde HTTP 422 con error de validación en el campo `timezone`. No se crea ningún usuario con ese valor.

**Variante de resiliencia:** Un usuario existe en la base de datos con `timezone = null` (dato corrupto pre-existente). Al ejecutar el Scheduler, el Attribute Accessor devuelve `config('app.timezone')` y el job se completa sin excepción.

---

## 12. Roadmap

| Sprint | Funcionalidades |
|---|---|
| MVP (actual) | CRUD de hábitos, tracking diario, rachas por ocurrencias, estadísticas con restricción por plan, autenticación completa, gate de conversión freemium, simulación de premium via `plan_expires_at`. |
| Sprint 2 | PWA (Progressive Web App) para experiencia móvil sin descarga. Notificaciones por email diarias con resumen. |
| Sprint 3 | Notificaciones push web. Sistema de metas cuantitativas asociadas a hábitos (ej: "correr 5 km"). |
| Sprint 4 | Integración con pasarela de pago real (Stripe / MercadoPago). Plan Premium: hábitos ilimitados, exportación de datos (PDF/CSV), estadísticas avanzadas sin restricción de fecha. |
| Futuro | API REST pública, integración con wearables, versión móvil nativa (React Native o Flutter). |

---

*Documento elaborado como especificación técnica, de negocio y de calidad para proyecto académico — 2025*
*Versión final — incorpora análisis crítico inicial, correcciones de arquitectura y decisiones de negocio resueltas (Q1–Q8)*
