/**
 * Test de estrés — Sistema de Gestión de Hábitos (Laravel + Blade + SQLite/MySQL)
 *
 * Flujo realista con un usuario nuevo por vuelta (vuelta = iteración de VU):
 *   1. Registro e inicio de sesión de usuario único (POST /register)
 *   2. Creación de hábito (POST /api/habits)
 *   3. Tracking diario del hábito creado (POST /api/habit-tracking)
 *   4. Consulta de estadísticas del hábito (GET /api/habits/{id}/statistics)
 */

import http from 'k6/http';
import { check, group, sleep } from 'k6';
import { Rate, Trend } from 'k6/metrics';
import { htmlReport } from 'https://raw.githubusercontent.com/benc-uk/k6-reporter/main/dist/bundle.js';
import { textSummary } from 'https://jslib.k6.io/k6-summary/0.0.2/index.js';

// ---------- Configuración ----------
const BASE_URL = __ENV.BASE_URL || 'http://localhost:8000';
const TEST_PASSWORD = __ENV.TEST_PASSWORD || 'password123';

// ---------- Métricas custom ----------
const registerFailRate = new Rate('registro_fallidos');
const habitCreateFailRate = new Rate('creacion_habito_fallidos');
const trackingFailRate = new Rate('tracking_fallidos');
const statsFailRate = new Rate('estadisticas_fallidos');

const registerDuration = new Trend('duracion_registro', true);
const habitCreateDuration = new Trend('duracion_creacion_habito', true);
const trackingDuration = new Trend('duracion_tracking', true);
const statsDuration = new Trend('duracion_estadisticas', true);

// ---------- Umbrales de aceptación ----------
export const options = {
  scenarios: {
    carga_progresiva: {
      executor: 'ramping-vus',
      startVUs: 0,
      stages: [
        { duration: '30s', target: 5 },
        { duration: '1m', target: 15 },
        { duration: '30s', target: 0 },
      ],
      gracefulRampDown: '15s',
    },
  },
  thresholds: {
    http_req_duration: ['p(95)<2000', 'p(99)<3000'],
    http_req_failed: ['rate<0.05'],
    registro_fallidos: ['rate<0.02'],
    creacion_habito_fallidos: ['rate<0.02'],
    tracking_fallidos: ['rate<0.02'],
    estadisticas_fallidos: ['rate<0.02'],
    duracion_tracking: ['p(95)<800'],
    duracion_estadisticas: ['p(95)<800'],
  },
};

// ---------- Helper: extrae el token XSRF de la cookie ----------
function getCsrfToken(jar, baseUrl) {
  const cookies = jar.cookiesForURL(baseUrl);
  const raw = cookies['XSRF-TOKEN'] ? cookies['XSRF-TOKEN'][0] : null;
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

  // Generamos un usuario único por cada iteración y usuario virtual
  const timestamp = Date.now();
  const randomSuffix = Math.floor(Math.random() * 10000);
  const uniqueId = `vu${__VU}_it${__ITER}_${timestamp}_${randomSuffix}`;
  const userName = `Usuario Test ${uniqueId}`;
  const userEmail = `user_${uniqueId}@stress-test.com`;

  let habitId = null;

  // ============================================================
  // 1. REGISTRO E INICIO DE SESIÓN (Usuario nuevo por vuelta)
  // ============================================================
  group('1. Registro y Autenticación', function () {
    const registerPage = http.get(`${BASE_URL}/register`, { jar });
    const token = getFormCsrfToken(registerPage.body);

    const start = Date.now();
    const res = http.post(
      `${BASE_URL}/register`,
      {
        nombre: userName,
        email: userEmail,
        password: TEST_PASSWORD,
        password_confirmation: TEST_PASSWORD,
        timezone: 'America/Argentina/Buenos_Aires',
        _token: token,
      },
      { jar }
    );
    registerDuration.add(Date.now() - start);

    const ok = check(res, {
      'registro exitoso (redirect 302 a dashboard)': (r) =>
        r.status === 302 || r.status === 200,
    });

    registerFailRate.add(!ok);
  });

  sleep(0.5);

  // ============================================================
  // 2. CREACIÓN DE HÁBITO (1er hábito del usuario fresco -> 100% de trabajo real)
  // ============================================================
  group('2. Creación de hábito', function () {
    const token = getCsrfToken(jar, BASE_URL);
    const start = Date.now();

    const res = http.post(
      `${BASE_URL}/api/habits`,
      JSON.stringify({
        titulo: `Hábito Productivo ${uniqueId}`,
        categoria_id: 1,
        fecha_inicio: new Date().toISOString().slice(0, 10),
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
    habitCreateDuration.add(Date.now() - start);

    const ok = check(res, {
      'hábito creado exitosamente (HTTP 201)': (r) => r.status === 201,
    });

    if (res.status === 201) {
      try {
        habitId = res.json('id');
      } catch (e) {
        habitId = null;
      }
    }

    habitCreateFailRate.add(!ok);
  });

  sleep(0.5);

  // ============================================================
  // 3. TRACKING DIARIO (Marca cumplimiento sobre el hábito creado)
  // ============================================================
  group('3. Tracking diario', function () {
    if (!habitId) {
      trackingFailRate.add(1);
      return;
    }

    const token = getCsrfToken(jar, BASE_URL);
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

    const ok = check(res, {
      'tracking registrado (HTTP 200/201)': (r) =>
        r.status === 200 || r.status === 201,
    });

    trackingFailRate.add(!ok);
  });

  sleep(0.5);

  // ============================================================
  // 4. CONSULTA DE ESTADÍSTICAS
  // ============================================================
  group('4. Estadísticas', function () {
    if (!habitId) {
      statsFailRate.add(1);
      return;
    }

    const start = Date.now();
    const res = http.get(`${BASE_URL}/api/habits/${habitId}/statistics`, { jar });
    statsDuration.add(Date.now() - start);

    const ok = check(res, {
      'estadísticas cargadas (HTTP 200)': (r) => r.status === 200,
    });

    statsFailRate.add(!ok);
  });

  sleep(Math.random() * 1.5 + 0.5);
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