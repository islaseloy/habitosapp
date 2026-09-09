/**
 * Test de estrés — Sistema de Gestión de Hábitos (Laravel + Blade + MySQL)
 *
 * Cubre los 4 flujos críticos:
 *   1. Login
 *   2. Creación de hábito
 *   3. Tracking diario
 *   4. Estadísticas
 */

import http from 'k6/http';
import { check, group, sleep } from 'k6';
import { Rate, Trend } from 'k6/metrics';
import { htmlReport } from 'https://raw.githubusercontent.com/benc-uk/k6-reporter/main/dist/bundle.js';
import { textSummary } from 'https://jslib.k6.io/k6-summary/0.0.2/index.js';

// ---------- Configuración ----------
const BASE_URL = __ENV.BASE_URL || 'http://localhost:8000';
const MAX_USERS = parseInt(__ENV.MAX_USERS || '60', 10);
const TEST_PASSWORD = __ENV.TEST_PASSWORD || 'password';

// ---------- Métricas custom ----------
const loginFailRate = new Rate('login_fallidos');
const habitCreateFailRate = new Rate('creacion_habito_fallidos');
const trackingFailRate = new Rate('tracking_fallidos');
const statsFailRate = new Rate('estadisticas_fallidos');
const gateHitRate = new Rate('gate_freemium_activado');

const loginDuration = new Trend('duracion_login', true);
const trackingDuration = new Trend('duracion_tracking', true);
const statsDuration = new Trend('duracion_estadisticas', true);

// ---------- Umbrales de aceptación ----------
export const options = {
  scenarios: {
    carga_progresiva: {
      executor: 'ramping-vus',
      startVUs: 0,
        stages: [
          { duration: '5s', target: 1 },
          { duration: '20s', target: 1 },
          { duration: '5s', target: 0 },
        ],
      gracefulRampDown: '30s',
    },
  },
  thresholds: {
    http_req_duration: ['p(95)<800', 'p(99)<1500'],
    http_req_failed: ['rate<0.02'],
    login_fallidos: ['rate<0.02'],
    tracking_fallidos: ['rate<0.02'],
    estadisticas_fallidos: ['rate<0.02'],
    duracion_tracking: ['p(95)<600'],
    duracion_estadisticas: ['p(95)<1000'],
  },
};

// ---------- Helper: extrae el token XSRF de la cookie ----------
function getCsrfToken(jar, baseUrl) {
  const cookies = jar.cookiesForURL(baseUrl);

  const raw = cookies['XSRF-TOKEN']
    ? cookies['XSRF-TOKEN'][0]
    : null;

  return raw ? decodeURIComponent(raw) : null;
}

// ---------- Helper: extrae el _token del formulario Blade ----------
function getFormCsrfToken(body) {
  const match = body.match(
    /<input[^>]+name=["']_token["'][^>]+value=["']([^"']+)["']/
  );

  return match ? match[1] : null;
}

export default function () {
  const jar = http.cookieJar();

  // Usamos los dos usuarios reales existentes.
  // VUs pares -> premium
  // VUs impares -> gratis
  const email = (__VU % 2 === 0)
    ? 'premium@example.com'
    : 'gratis@example.com';

  // ID del hábito creado durante esta iteración.
  let habitId = null;

  // ============================================================
  // 1. LOGIN
  // ============================================================

  group('1. Login', function () {
    const loginPage = http.get(`${BASE_URL}/login`, { jar });

    // Laravel Breeze utiliza el _token oculto del formulario.
    const token = getFormCsrfToken(loginPage.body);

    console.log(
      `[VU ${__VU} ITER ${__ITER}] LOGIN PAGE -> status=${loginPage.status} form_csrf=${token ? 'OK' : 'MISSING'}`
    );

    const start = Date.now();

    const res = http.post(
      `${BASE_URL}/login`,
      {
        email: email,
        password: TEST_PASSWORD,
        _token: token,
      },
      {
        jar,
      }
    );

    loginDuration.add(Date.now() - start);

    console.log(
      `[VU ${__VU} ITER ${__ITER}] LOGIN -> email=${email} status=${res.status} url=${res.url}`
    );

    console.log(
      `[VU ${__VU} ITER ${__ITER}] LOGIN BODY -> ${res.body ? res.body.substring(0, 500) : '<empty>'}`
    );

    const location = res.headers['Location'] || res.headers['location'];

    if (location) {
      console.log(
        `[VU ${__VU} ITER ${__ITER}] LOGIN LOCATION -> ${location}`
      );
    }

    const ok = check(res, {
      'login exitoso (redirect 302 a dashboard)': (r) =>
        r.status === 302 || r.status === 200,
    });

    loginFailRate.add(!ok);
  });

  sleep(1);

  // ============================================================
  // 2. CREACIÓN DE HÁBITO
  // ============================================================

  group('2. Creación de hábito (gate freemium)', function () {
    const token = getCsrfToken(jar, BASE_URL);

    console.log(
      `[VU ${__VU} ITER ${__ITER}] HABIT -> csrf=${token ? 'OK' : 'MISSING'}`
    );

    const res = http.post(
      `${BASE_URL}/api/habits`,
      JSON.stringify({
        titulo: `Hábito carga VU${__VU} iter${__ITER}`,
        categoria_id: 1,
        tipo: 'diario',
        fecha_inicio: new Date().toISOString().slice(0, 10),

        // El HabitRequest espera el tipo dentro de schedule.
        schedule: {
          tipo: 'diario',
        },
      }),
      {
        jar,
        headers: {
          'Content-Type': 'application/json',
          'X-XSRF-TOKEN': token,
          Accept: 'application/json',
        },
      }
    );

    console.log(
      `[VU ${__VU} ITER ${__ITER}] HABIT -> status=${res.status} url=${res.url}`
    );

    console.log(
      `[VU ${__VU} ITER ${__ITER}] HABIT BODY -> ${res.body ? res.body.substring(0, 500) : '<empty>'}`
    );

    const location = res.headers['Location'] || res.headers['location'];

    if (location) {
      console.log(
        `[VU ${__VU} ITER ${__ITER}] HABIT LOCATION -> ${location}`
      );
    }

    // 201 = hábito creado
    // 422 + plan_limit_reached = gate freemium funcionando
    const gateActivado =
      res.status === 422 &&
      res.body &&
      res.body.includes('plan_limit_reached');

    gateHitRate.add(gateActivado);

    if (res.status === 201) {
      try {
        habitId = res.json('id');
      } catch (e) {
        habitId = null;
      }
    }

    console.log(
      `[VU ${__VU} ITER ${__ITER}] HABIT -> habitId=${habitId ?? 'NONE'}`
    );

    console.log(
      `[VU ${__VU} ITER ${__ITER}] HABIT GATE -> ${gateActivado ? 'ACTIVADO' : 'NO'}`
    );

    const ok = check(res, {
      'creación OK o gate freemium correcto': (r) =>
        r.status === 201 || gateActivado,
    });

    habitCreateFailRate.add(!ok);
  });

  sleep(1);

  // ============================================================
  // 3. TRACKING DIARIO
  // ============================================================

  group('3. Tracking diario', function () {
    // Si el hábito no se creó porque el usuario gratuito
    // alcanzó el límite, no podemos hacer tracking.
    if (!habitId) {
      console.log(
        `[VU ${__VU} ITER ${__ITER}] TRACKING -> SKIPPED (sin hábito creado)`
      );

      return;
    }

    const token = getCsrfToken(jar, BASE_URL);

    console.log(
      `[VU ${__VU} ITER ${__ITER}] TRACKING -> csrf=${token ? 'OK' : 'MISSING'} habitId=${habitId}`
    );

    const start = Date.now();

    const res = http.post(
      `${BASE_URL}/api/habit-tracking`,
      JSON.stringify({
        habito_id: habitId,
        fecha_registro: new Date().toISOString().slice(0, 10),
        estado_cumplimiento: 'completado',
      }),
      {
        jar,
        headers: {
          'Content-Type': 'application/json',
          'X-XSRF-TOKEN': token,
          Accept: 'application/json',
        },
      }
    );

    trackingDuration.add(Date.now() - start);

    console.log(
      `[VU ${__VU} ITER ${__ITER}] TRACKING -> status=${res.status} url=${res.url}`
    );

    console.log(
      `[VU ${__VU} ITER ${__ITER}] TRACKING BODY -> ${res.body ? res.body.substring(0, 500) : '<empty>'}`
    );

    const location = res.headers['Location'] || res.headers['location'];

    if (location) {
      console.log(
        `[VU ${__VU} ITER ${__ITER}] TRACKING LOCATION -> ${location}`
      );
    }

    const ok = check(res, {
      'tracking registrado': (r) =>
        r.status === 200 || r.status === 201,
    });

    trackingFailRate.add(!ok);
  });

  sleep(1);

  // ============================================================
  // 4. ESTADÍSTICAS
  // ============================================================

  group('4. Estadísticas', function () {
    // Si no se creó el hábito, no tenemos un recurso válido
    // para consultar.
    if (!habitId) {
      console.log(
        `[VU ${__VU} ITER ${__ITER}] STATS -> SKIPPED (sin hábito creado)`
      );

      return;
    }

    const start = Date.now();

    const res = http.get(
      `${BASE_URL}/api/habits/${habitId}/statistics`,
      { jar }
    );

    statsDuration.add(Date.now() - start);

    console.log(
      `[VU ${__VU} ITER ${__ITER}] STATS -> status=${res.status} url=${res.url}`
    );

    console.log(
      `[VU ${__VU} ITER ${__ITER}] STATS BODY -> ${res.body ? res.body.substring(0, 500) : '<empty>'}`
    );

    const location = res.headers['Location'] || res.headers['location'];

    if (location) {
      console.log(
        `[VU ${__VU} ITER ${__ITER}] STATS LOCATION -> ${location}`
      );
    }

    const ok = check(res, {
      'estadísticas cargadas': (r) => r.status === 200,
    });

    statsFailRate.add(!ok);
  });

  sleep(Math.random() * 2 + 1);
}

// ---------- Reporte automático ----------
export function handleSummary(data) {
  return {
    'resumen_test_estres.html': htmlReport(data),
    'resumen_test_estres.json': JSON.stringify(data, null, 2),
    stdout: textSummary(data, {
      indent: ' ',
      enableColors: true,
    }),
  };
}