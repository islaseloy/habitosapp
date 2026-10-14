# Informe de Pruebas Funcionales Automatizadas (QA)
## Sistema de Gestión de Hábitos

**Materia:** Metodología de Pruebas / Testing de Software  
**Fecha:** Octubre 2026  
**Entorno de Pruebas:** PHPUnit 12.5 + Laravel 12 / PHP 8.4 + SQLite en memoria  
**Estado:** **34 Tests Aprobados — 92 Aserciones — 0 Fallos (100 % Exitoso)**

---

## 1. Objetivo y Alcance

El presente informe detalla la especificación, implementación y ejecución de las **pruebas funcionales automatizadas** (*Feature & Unit Tests*) sobre el Sistema de Gestión de Hábitos.

El objetivo primordial es verificar de manera determinística e independiente que las reglas de negocio, los controles de acceso y los mecanismos de restricción freemium funcionen de forma estricta a nivel backend, sin depender exclusivamente de validaciones de interfaz en el cliente.

---

## 2. Validación de Reglas de Negocio Críticas

A continuación se presentan los casos de prueba para las tres reglas centrales del sistema solicitadas por la cátedra, junto a los casos complementarios de seguridad y ciclo de vida.

```
┌────────────────────────────────────────────────────────────────────────┐
│                   MATRIZ DE REGLAS CRÍTICAS EVALUADAS                  │
├───────┬───────────────────────────────────┬──────────────┬─────────────┤
│ Ref   │ Regla de Negocio                  │ HTTP / Error │ Resultado   │
├───────┼───────────────────────────────────┼──────────────┼─────────────┤
│ TC-01 │ Límite de 5 hábitos (Plan Free)   │ HTTP 422     │ APROBADO ✓  │
│ TC-01b│ Hábitos ilimitados (Plan Premium) │ HTTP 201     │ APROBADO ✓  │
│ TC-06 │ Historial estadísticas (7 días)   │ HTTP 200     │ APROBADO ✓  │
│ TC-05 │ Aislamiento de datos (Anti-IDOR)  │ HTTP 403     │ APROBADO ✓  │
│ TC-04 │ Bloqueo de fechas futuras         │ HTTP 422     │ APROBADO ✓  │
│ TC-02 │ Cronjob de cierre por Timezone    │ Artisan 0    │ APROBADO ✓  │
│ TC-03 │ Cálculo de rachas por ocurrencia  │ Unit Test    │ APROBADO ✓  │
└───────┴───────────────────────────────────┴──────────────┴─────────────┘
```

---

### Regla 1: Límite estricto de 5 hábitos activos (Gate Freemium)

#### Especificación
Un usuario en el plan gratuito (`plan_expires_at === null`) tiene un cupo máximo de **5 hábitos activos** (`estado = true`). Cualquier intento de crear un 6to hábito debe ser rechazado por el servidor con código **HTTP 422 Unprocessable Content**, devolviendo el payload `{ "error": "plan_limit_reached" }` y **sin persistir** el registro en la base de datos. Por el contrario, un usuario Premium activo debe poder crearlo exitosamente (**HTTP 201**).

#### Implementación del Test (`tests/Feature/HabitsApiTest.php`)
```php
public function test_free_user_cannot_create_sixth_active_habit(): void
{
    // Arrange: Usuario gratuito con exactamente 5 hábitos activos
    $user = User::factory()->create(['plan_expires_at' => null]);
    Habit::factory()->count(5)->create(['usuario_id' => $user->id, 'estado' => true]);

    // Act: Intento de crear el sexto hábito
    $response = $this->actingAs($user)
        ->postJson('/api/habits', $this->validHabitPayload());

    // Assert: Rechazo con código 422, payload estructurado y BD inalterada
    $response->assertStatus(422)
             ->assertJsonFragment(['error' => 'plan_limit_reached']);

    $this->assertDatabaseCount('habits', 5);
}

public function test_premium_user_can_create_more_than_five_active_habits(): void
{
    // Arrange: Usuario con suscripción Premium activa (14 días restantes)
    $user = User::factory()->create(['plan_expires_at' => now()->addDays(14)]);
    Habit::factory()->count(5)->create(['usuario_id' => $user->id, 'estado' => true]);

    // Act: Creación del sexto hábito
    $response = $this->actingAs($user)
        ->postJson('/api/habits', $this->validHabitPayload());

    // Assert: Creación exitosa (HTTP 201) y persistencia efectiva
    $response->assertCreated()
             ->assertJsonPath('schedule.tipo', 'diario');

    $this->assertDatabaseCount('habits', 6);
}
```

---

### Regla 2: Restricción del historial de estadísticas a 7 días en plan gratuito

#### Especificación
Los usuarios del plan gratuito solo pueden consultar estadísticas y registros de cumplimiento de los **últimos 7 días** corridos a partir de la fecha actual. Aunque en la base de datos existan registros más antiguos (por ejemplo, de 20 o 30 días atrás), el backend filtra la consulta para que `range_start` sea exactamente `now()->subDays(6)` y no retorne registros fuera de esa ventana.

#### Implementación del Test (`tests/Feature/HabitsApiTest.php`)
```php
public function test_free_user_statistics_are_limited_to_last_seven_days(): void
{
    // Fijamos fecha determinística en el sistema de pruebas
    Carbon::setTestNow('2026-06-25 12:00:00');

    $user = User::factory()->create(['plan_expires_at' => null]);
    $habit = Habit::factory()->create([
        'usuario_id' => $user->id,
        'fecha_inicio' => now()->subDays(30)->toDateString(),
    ]);
    $habit->schedule()->create(['tipo' => 'diario']);

    // Registro antiguo (hace 20 días) -> Debe ser ignorado por el filtro Free
    HabitTracking::query()->create([
        'habito_id' => $habit->id,
        'fecha_registro' => now()->subDays(20)->toDateString(),
        'estado_cumplimiento' => 'completado',
    ]);

    // Registro reciente (hoy) -> Debe ser incluido
    HabitTracking::query()->create([
        'habito_id' => $habit->id,
        'fecha_registro' => now()->toDateString(),
        'estado_cumplimiento' => 'completado',
    ]);

    // Act & Assert
    $this->actingAs($user)
        ->getJson("/api/habits/{$habit->id}/statistics")
        ->assertOk()
        ->assertJsonPath('range_start', '2026-06-19') // Ventana exacta de 7 días
        ->assertJsonCount(1, 'records');             // Solo 1 registro visible

    Carbon::setTestNow();
}
```

---

### Regla 3: Aislamiento y autorización entre usuarios (Anti-IDOR)

#### Especificación
Ningún usuario autenticado puede acceder, modificar ni consultar las estadísticas de hábitos pertenecientes a otro usuario (vulnerabilidad *Insecure Direct Object References - IDOR*). La autorización se valida en el backend mediante Policies de Laravel y debe responder estrictamente con **HTTP 403 Forbidden**.

#### Implementación del Test (`tests/Feature/HabitsApiTest.php`)
```php
public function test_user_cannot_access_another_users_habit(): void
{
    // Arrange: Dos usuarios independientes
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $habit = Habit::factory()->create(['usuario_id' => $owner->id]);

    // Act: El usuario B intenta acceder al hábito del usuario A
    $response = $this->actingAs($otherUser)
        ->getJson("/api/habits/{$habit->id}");

    // Assert: Acceso denegado con código HTTP 403
    $response->assertForbidden();
}
```

---

### Reglas Adicionales Validadas

#### 4. Bloqueo de fechas futuras en tracking (`TC-04`)
Evita la manipulación deliberada del historial para inflar rachas artificialmente:
```php
public function test_tracking_rejects_future_dates(): void
{
    $user = User::factory()->create();
    $habit = Habit::factory()->create(['usuario_id' => $user->id]);

    $this->actingAs($user)
        ->postJson('/api/habit-tracking', [
            'habito_id' => $habit->id,
            'fecha_registro' => now()->addDay()->toDateString(), // Fecha mañana
            'estado_cumplimiento' => 'completado',
        ])
        ->assertStatus(422);

    $this->assertDatabaseCount('habit_tracking', 0);
}
```

#### 5. Cierre automático por Cronjob respetando Timezone (`TC-02`)
Verifica que a las 00:05 de Buenos Aires (UTC-3), solo los hábitos de Buenos Aires se cierran como `omitido`, mientras que en New York (UTC-4, 23:05) no se alteran.

#### 6. Cálculo estricto de rachas (`TC-03`)
Verifica que una omisión o fallo intermedio rompa la racha inmediatamente y reinicie el contador a 0 para las ocurrencias programadas.

---

## 3. Evidencia de Ejecución de la Suite (TestDox Output)

Ejecución en consola local (`php artisan test --testdox`):

```text
PHPUnit 12.5.30 by Sebastian Bergmann and contributors.
Runtime: PHP 8.4.x / PHP 8.5.x
Configuration: phpunit.xml

Habits Api (Tests\Feature\HabitsApi)
 ✔ Free user cannot create sixth active habit
 ✔ Premium user can create more than five active habits
 ✔ Tracking rejects future dates
 ✔ User cannot access another users habit
 ✔ Free user statistics are limited to last seven days

Habit Lifecycle (Tests\Feature\HabitLifecycle)
 ✔ Cronjob closes pending habits according to user timezone
 ✔ Streak calculation based on scheduled occurrences
 ✔ Empty dashboard displays motivational message correctly
 ✔ Registration validates timezone values

Authentication & Security (Tests\Feature\Auth)
 ✔ Login screen can be rendered
 ✔ Users can authenticate using the login screen
 ✔ Users can not authenticate with invalid password
 ✔ Users can logout
 ✔ Confirm password screen can be rendered
 ✔ Password can be confirmed
 ✔ Password is not confirmed with invalid password
 ✔ Reset password link screen can be rendered
 ✔ Reset password link can be requested
 ✔ Reset password screen can be rendered
 ✔ Password can be reset with valid token
 ✔ Password can be updated
 ✔ Correct password must be provided to update password
 ✔ Registration screen can be rendered
 ✔ New users can register
 ✔ Profile page is displayed
 ✔ Profile information can be updated
 ✔ User can delete their account
 ✔ Correct password must be provided to delete account

General & Redirections (Tests\Feature & Unit)
 ✔ The application redirects to dashboard
 ✔ That true is true

Tests:    34 passed (92 assertions)
Duration: 1.67s
Status:   OK (100% Passing)
```

---

## 4. Automatización en CI/CD (GitHub Actions)

La totalidad de los 34 tests se ejecutan de manera desatendida en cada `git push` al repositorio a través del workflow configurado en `.github/workflows/tests.yml`:

* **Runner:** Ubuntu Latest
* **Entorno:** PHP 8.4 + extensiones `sqlite3`, `pdo_sqlite`, `mbstring`, `dom`
* **Compilación de Assets:** Node.js 20 + Vite / Tailwind v4
* **Aislamiento:** Base de datos SQLite en memoria (`:memory:`)
* **Estado en GitHub:** Badge verde **`PASSING`** visible en el repositorio.
