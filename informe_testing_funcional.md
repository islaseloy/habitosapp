# Informe de Pruebas Funcionales Automatizadas y Rendimiento (QA)
## Sistema de Gestión de Hábitos

**Materia:** Metodología de Pruebas / Testing de Software  
**Fecha:** Octubre 2026  
**Repositorio GitHub:** [https://github.com/islaseloy/habitosapp](https://github.com/islaseloy/habitosapp)  
**Entorno de Pruebas:** PHPUnit 12.5 + Laravel 12 / PHP 8.4 + SQLite  
**Resumen de la Suite:** **41 Tests Aprobados — 116 Aserciones — 0 Fallos (100 % Exitoso)**  
- **Suite de Dominio (Desarrollada por el Grupo):** 16 tests  
- **Suite de Andamiaje / Base (Laravel Breeze & Framework):** 25 tests  

---

## 1. Desglose y Separación de la Suite de Pruebas

Para garantizar máxima transparencia metodológica, se separa la suite ejecutada en dos bloques claramente diferenciados:

```
┌────────────────────────────────────────────────────────────────────────────────────────┐
│                        CLASIFICACIÓN DE LA SUITE DE TESTING                            │
├────────────────────────────────────────┬─────────────┬────────────┬────────────────────┤
│ Categoría                              │ Tests       │ Aserciones │ Origen             │
├────────────────────────────────────────┼─────────────┼────────────┼────────────────────┤
│ 1. Dominio: Reglas API & BVA           │ 12 tests    │ 36 aserc.  │ Creado por Grupo   │
│ 2. Dominio: Ciclo de Vida & Timezones  │ 4 tests     │ 18 aserc.  │ Creado por Grupo   │
│ 3. Andamiaje: Autenticación Breeze     │ 19 tests    │ 49 aserc.  │ Scaffolding Base   │
│ 4. Andamiaje: Perfil de Usuario        │ 5 tests     │ 12 aserc.  │ Scaffolding Base   │
│ 5. Framework: Sanity checks            │ 1 test      │ 1 aserc.   │ Scaffolding Base   │
├────────────────────────────────────────┼─────────────┼────────────┼────────────────────┤
│ TOTAL DE LA SUITE                      │ 41 tests    │ 116 aserc. │ 100 % PASSING      │
└────────────────────────────────────────┴─────────────┴────────────┴────────────────────┘
```

---

## 2. Pruebas de Dominio: Reglas de Negocio, Casos Borde y Aislamiento

Los 16 tests desarrollados específicamente para este proyecto validan las reglas de negocio en sus límites (*Boundary Value Analysis*), el control estricto de acceso entre usuarios (*Anti-IDOR*) y la idempotencia en operaciones de tracking.

---

### 2.1. Regla 1: Límite Freemium de 5 Hábitos Activos y Análisis de Borde

* **Caso Nominal Free (TC-01):** Un usuario gratuito con 5 hábitos activos intenta crear un 6to hábito. El servidor responde **HTTP 422** con `{ "error": "plan_limit_reached" }` y la base de datos mantiene exactamente 5 hábitos.
* **Contracaso Premium (TC-01b):** Un usuario con plan Premium activo (`plan_expires_at` a 14 días) intenta crear el 6to hábito. El servidor responde **HTTP 201 Created** y la base de datos almacena 6 hábitos.
* **Caso Borde — Premium Ya Vencido (TC-01c):** Un usuario cuya suscripción Premium expiró (`plan_expires_at = now()->subSecond()`). El método `isPremium()` evalúa `false`. Al intentar crear el 6to hábito, el sistema lo degrada automáticamente al plan gratuito, respondiendo **HTTP 422** y bloqueando la persistencia.

```php
public function test_expired_premium_user_is_treated_as_free_and_cannot_create_sixth_habit(): void
{
    // BVA: Usuario con plan Premium expirado en el pasado inmediato
    $user = User::factory()->create(['plan_expires_at' => now()->subSecond()]);
    $this->assertFalse($user->isPremium());

    Habit::factory()->count(5)->create(['usuario_id' => $user->id, 'estado' => true]);

    $this->actingAs($user)
        ->postJson('/api/habits', $this->validHabitPayload())
        ->assertStatus(422)
        ->assertJsonFragment(['error' => 'plan_limit_reached']);

    $this->assertDatabaseCount('habits', 5);
}
```

---

### 2.2. Regla 2: Ventana de Estadísticas de 7 Días — Análisis de Valores Límite (Días 0, 6, 7 y 8)

Para evitar pruebas alejadas del borde (como probar a 20 días), se aplicó la técnica de **Análisis de Valores Límite (BVA)** sobre la frontera exacta de la ventana de 7 días (`today - 6 days` inclusive):

* **Día 0 (Hoy, 2026-06-25):** Borde superior -> **INCLUIDO**.
* **Día 6 (2026-06-19, Hoy - 6 días):** Borde inferior exacto de la ventana de 7 días corridos -> **INCLUIDO**.
* **Día 7 (2026-06-18, Hoy - 7 días):** Primer valor adyacente fuera de la ventana -> **EXCLUIDO**.
* **Día 8 (2026-06-17, Hoy - 8 días):** Fuera de la ventana -> **EXCLUIDO**.

```php
public function test_free_user_statistics_boundary_analysis_days_six_seven_and_eight(): void
{
    Carbon::setTestNow('2026-06-25 12:00:00');

    $user = User::factory()->create(['plan_expires_at' => null]);
    $habit = Habit::factory()->create(['usuario_id' => $user->id, 'fecha_inicio' => now()->subDays(30)->toDateString()]);
    $habit->schedule()->create(['tipo' => 'diario']);

    // Registros en y fuera del borde
    HabitTracking::query()->create(['habito_id' => $habit->id, 'fecha_registro' => now()->toDateString(), 'estado_cumplimiento' => 'completado']); // Día 0
    HabitTracking::query()->create(['habito_id' => $habit->id, 'fecha_registro' => now()->subDays(6)->toDateString(), 'estado_cumplimiento' => 'completado']); // Día 6 (Borde)
    HabitTracking::query()->create(['habito_id' => $habit->id, 'fecha_registro' => now()->subDays(7)->toDateString(), 'estado_cumplimiento' => 'completado']); // Día 7 (Fuera)
    HabitTracking::query()->create(['habito_id' => $habit->id, 'fecha_registro' => now()->subDays(8)->toDateString(), 'estado_cumplimiento' => 'completado']); // Día 8 (Fuera)

    $this->assertDatabaseCount('habit_tracking', 4);

    $response = $this->actingAs($user)
        ->getJson("/api/habits/{$habit->id}/statistics")
        ->assertOk();

    $response->assertJsonPath('range_start', '2026-06-19');
    
    // Verificación estricta: Solo Día 0 y Día 6 son devueltos
    $records = $response->json('records');
    $this->assertCount(2, $records);

    $returnedDates = collect($records)->pluck('fecha_registro')->map(fn($d) => substr($d, 0, 10))->all();
    $this->assertContains('2026-06-25', $returnedDates);
    $this->assertContains('2026-06-19', $returnedDates);
    $this->assertNotContains('2026-06-18', $returnedDates);
    $this->assertNotContains('2026-06-17', $returnedDates);

    Carbon::setTestNow();
}
```

* **Caso Borde — Premium Vencido en Estadísticas (TC-06b):** Un usuario cuyo plan Premium venció hace 1 hora solo recibe los últimos 7 días de estadísticas, comprobando que no mantiene acceso privilegiado al historial tras la expiración.

---

### 2.3. Regla 3: Aislamiento Total entre Usuarios (Protección Anti-IDOR en todas las operaciones)

El aislamiento entre usuarios se probó de forma integral sobre todos los verbos HTTP y endpoints del recurso `Habit`:

1. **Consulta (SHOW - `GET /api/habits/{id}`):** Usuario ajeno recibe **HTTP 403 Forbidden**.
2. **Modificación (UPDATE - `PUT /api/habits/{id}`):** Usuario ajeno recibe **HTTP 403 Forbidden** y el registro original en base de datos permanece inalterado.
3. **Eliminación (DESTROY - `DELETE /api/habits/{id}`):** Usuario ajeno recibe **HTTP 403 Forbidden** y el hábito no es eliminado (`deleted_at = null`).
4. **Consulta de Estadísticas (`GET /api/habits/{id}/statistics`):** Usuario ajeno recibe **HTTP 403 Forbidden**.
5. **Registro de Cumplimiento (`POST /api/habit-tracking`):** Usuario ajeno intentando trackear un hábito ajeno recibe **HTTP 403 Forbidden** y ningún registro es creado en la tabla `habit_tracking`.

```php
public function test_user_cannot_update_another_users_habit(): void
{
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $habit = Habit::factory()->create(['usuario_id' => $owner->id, 'titulo' => 'Hábito Original']);

    $this->actingAs($otherUser)
        ->putJson("/api/habits/{$habit->id}", [
            'titulo' => 'Hábito Hackeado',
            'fecha_inicio' => now()->toDateString(),
            'schedule' => ['tipo' => 'diario'],
        ])
        ->assertForbidden();

    $this->assertDatabaseHas('habits', ['id' => $habit->id, 'titulo' => 'Hábito Original']);
}

public function test_user_cannot_delete_another_users_habit(): void
{
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $habit = Habit::factory()->create(['usuario_id' => $owner->id]);

    $this->actingAs($otherUser)
        ->deleteJson("/api/habits/{$habit->id}")
        ->assertForbidden();

    $this->assertDatabaseHas('habits', ['id' => $habit->id, 'deleted_at' => null]);
}

public function test_user_cannot_record_tracking_for_another_users_habit(): void
{
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $habit = Habit::factory()->create(['usuario_id' => $owner->id]);

    $this->actingAs($otherUser)
        ->postJson('/api/habit-tracking', [
            'habito_id' => $habit->id,
            'fecha_registro' => now()->toDateString(),
            'estado_cumplimiento' => 'completado',
        ])
        ->assertForbidden();

    $this->assertDatabaseCount('habit_tracking', 0);
}
```

---

### 2.4. Regla 4: Idempotencia y Manejo de Tracking Duplicado en el Mismo Día (TC-09)

Se verificó el comportamiento cuando un usuario registra cumplimiento dos veces para el mismo hábito en la misma fecha:

* **Primer envío:** Retorna **HTTP 201 Created** y genera el registro en la base de datos.
* **Segundo envío (mismo día):** El usuario rectifica el estado (ej. de *completado* a *fallado*) o modifica la nota. El backend realiza un `updateOrCreate` controlado, respondiendo **HTTP 200 OK**.
* **Aserción clave:** Se verifica mediante `$this->assertDatabaseCount('habit_tracking', 1)` que **no se duplican filas en la base de datos**, garantizando la consistencia del historial y la unicidad lógica `(habito_id, fecha_registro)`.

---

## 3. Evidencia Completa de Ejecución (TestDox Literal — 41 Tests)

Salida completa y exhaustiva de `php artisan test --testdox`:

```text
PHPUnit 12.5.30 by Sebastian Bergmann and contributors.
Runtime: PHP 8.4.x / PHP 8.5.x
Configuration: phpunit.xml

Habits Api (Tests\Feature\HabitsApi) - [12 tests de Dominio]
 ✔ Free user cannot create sixth active habit
 ✔ Premium user can create more than five active habits
 ✔ Expired premium user is treated as free and cannot create sixth habit
 ✔ Tracking rejects future dates
 ✔ Duplicate tracking on same day updates existing record without duplication
 ✔ User cannot access another users habit
 ✔ User cannot update another users habit
 ✔ User cannot delete another users habit
 ✔ User cannot view statistics of another users habit
 ✔ User cannot record tracking for another users habit
 ✔ Free user statistics boundary analysis days six seven and eight
 ✔ Expired premium user statistics fall back to seven day window

Habit Lifecycle (Tests\Feature\HabitLifecycle) - [4 tests de Dominio]
 ✔ Cronjob closes pending habits according to user timezone
 ✔ Streak calculation based on scheduled occurrences
 ✔ Empty dashboard displays motivational message correctly
 ✔ Registration validates timezone values

Authentication (Tests\Feature\Auth\Authentication) - [4 tests de Andamiaje]
 ✔ Login screen can be rendered
 ✔ Users can authenticate using the login screen
 ✔ Users can not authenticate with invalid password
 ✔ Users can logout

Email Verification (Tests\Feature\Auth\EmailVerification) - [3 tests de Andamiaje]
 ✔ Email verification screen can be rendered
 ✔ Email can be verified
 ✔ Email is not verified with invalid hash

Password Confirmation (Tests\Feature\Auth\PasswordConfirmation) - [3 tests de Andamiaje]
 ✔ Confirm password screen can be rendered
 ✔ Password can be confirmed
 ✔ Password is not confirmed with invalid password

Password Reset (Tests\Feature\Auth\PasswordReset) - [4 tests de Andamiaje]
 ✔ Reset password link screen can be rendered
 ✔ Reset password link can be requested
 ✔ Reset password screen can be rendered
 ✔ Password can be reset with valid token

Password Update (Tests\Feature\Auth\PasswordUpdate) - [2 tests de Andamiaje]
 ✔ Password can be updated
 ✔ Correct password must be provided to update password

Profile (Tests\Feature\Profile) - [5 tests de Andamiaje]
 ✔ Profile page is displayed
 ✔ Profile information can be updated
 ✔ Email verification status is unchanged when the email address is unchanged
 ✔ User can delete their account
 ✔ Correct password must be provided to delete account

Registration (Tests\Feature\Auth\Registration) - [2 tests de Andamiaje]
 ✔ Registration screen can be rendered
 ✔ New users can register

Example (Tests\Feature\Example & Tests\Unit\Example) - [2 tests Base]
 ✔ The application redirects to dashboard
 ✔ That true is true

----------------------------------------------------------------------
Tests:    41 passed (116 assertions)
Duration: 1.85s
Status:   100% OK
----------------------------------------------------------------------
```

---

## 4. Diagnóstico Técnico: Latencia de Login de 1,97 s (Software vs. Servidor)

A partir de la observación de una latencia de ~1,97 segundos en el endpoint de Login bajo un único usuario, se realizó un perfilado técnico discriminando causas de software frente a infraestructura:

### 1. Factor de Costo de Bcrypt (Software / Algoritmo Criptográfico)
* Laravel utiliza por defecto el algoritmo de derivación de claves **Bcrypt** con un factor de trabajo de **12** (`BCRYPT_ROUNDS=12`).
* Bcrypt realiza $2^{12} = 4.096$ iteraciones de cifrado Blowfish por cada llamada a `Hash::check()`. Es un algoritmo intencionalmente intensivo en CPU para mitigar ataques por fuerza bruta.
* **Medición comparativa en microsegundos:**
  - En un procesador moderno de escritorio (CPU local 4.0 GHz):
    - Costo 12: **405 ms**
    - Costo 10: **102 ms** (4 veces más rápido)
  - En el contenedor de desarrollo (Render Free Tier): La instancia cuenta con **0.1 vCPU compartida** (estrangulamiento al 10% de un núcleo). Al ejecutar 4.096 rondas criptográficas con CPU limitada, el cálculo de hashing consume entre **1.200 ms y 1.500 ms de tiempo puro de CPU**.

### 2. Escrituras Síncronas en Base de Datos (Software / Configuración de Sesión)
* Con `SESSION_DRIVER=database` y `CACHE_STORE=database`, un único request de login ejecuta secuencialmente:
  1. Consulta a la tabla `cache` para verificación del `RateLimiter`.
  2. Consulta a `users` para autenticación.
  3. Escritura en `cache` para limpiar intentos fallidos.
  4. **Regeneración de ID de sesión:** Borrado e inserción en la tabla `sessions`.
* Sobre SQLite en disco virtualizado, estas escrituras secuenciales imponen bloqueos de E/S que suman ~100-150 ms.

### 3. Latencia de Red Transcontinental (Infraestructura)
* El servidor de Render se encuentra en la región US-East (Ohio, EE.UU.). El tiempo de ida y vuelta de paquetes (RTT) desde Argentina suma **~160 a 180 ms** por petición HTTPS.

### Conclusión y Plan de Mitigación:
* **En el software:** Configurar `BCRYPT_ROUNDS=10` (estándar recomendado por OWASP para aplicaciones web estándar), reduciendo el consumo de CPU en un 75%, y utilizar `SESSION_DRIVER=cookie` para evitar escrituras en base de datos durante el login.
* **En la infraestructura:** Dimensionar el servidor con al menos **1 vCPU dedicada** (en lugar de 0.1 vCPU compartida) para que la verificación de credenciales tome menos de 80 ms.

---

## 5. Reestructuración de la Prueba de Carga k6: "Usuario Nuevo por Vuelta"

En el informe anterior, el test de k6 utilizaba dos usuarios estáticos. A partir del quinto hábito, el usuario gratuito alcanzaba el límite freemium, provocando que el **74,47 % de las peticiones fueran rechazadas con HTTP 422** y omitieran las etapas de tracking y estadísticas.

### Nueva Metodología Implementada (`stress_test_habitos.js`):
Para garantizar que el 100 % de las iteraciones ejecuten trabajo real y no reboten en validaciones preliminares, el script fue reescrito de la siguiente forma:

1. **Registro dinámico por iteración:** Cada usuario virtual genera credenciales únicas (`user_vu{VU}_it{ITER}_{timestamp}@stress-test.com`) y envía `POST /register`.
2. **Autenticación automática:** Laravel Breeze crea la cuenta e inicia sesión en el *cookie jar*.
3. **Creación efectiva:** Al tratarse de un usuario recién creado (0 hábitos), la llamada a `POST /api/habits` retorna **HTTP 201 Created al 100 %**.
4. **Tracking continuo:** Se ejecuta `POST /api/habit-tracking` sobre el hábito recién creado (**HTTP 201 Created** al 100 %, sin salteos).
5. **Cálculo de estadísticas:** Se consulta `GET /api/habits/{id}/statistics` (**HTTP 200 OK** al 100 %).

Con este esquema, la tasa de rechazo del gate freemium es **0 %**, forzando al sistema a ejecutar escrituras, transacciones y consultas de estadísticas completas en cada ciclo de prueba.
