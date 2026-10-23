import http from 'k6/http';
import { check, sleep } from 'k6';
import { Counter, Rate } from 'k6/metrics';

// Custom metrics for non-functional governance
export const successfulQueries = new Counter('successful_tracking_queries');
export const serverErrors = new Rate('server_errors');

export const options = {
  stages: [
    { duration: '20s', target: 20 }, // Ramp-up: 0 a 20 VUs en 20 segundos
    { duration: '1m', target: 50 },  // Carga sostenida: 50 VUs durante 1 minuto
    { duration: '15s', target: 0 },  // Ramp-down: enfriamiento a 0 VUs en 15 segundos
  ],
  thresholds: {
    // RNF-01: El 95% de las respuestas debe resolver en menos de 3.0 segundos
    http_req_duration: ['p(95)<3000'],
    // RNF-01: Menos del 1% de errores HTTP (5xx / caídas de red)
    http_req_failed: ['rate<0.01'],
    // Cero caídas o errores de servidor (5xx)
    server_errors: ['rate<0.01'],
  },
};

const BASE_URL = __ENV.BASE_URL || 'http://127.0.0.1:8000';

// Dataset dinámico y cíclico de consulta de tickets
const TRACKING_DATASET = [
  { tracking_code: 'RET-56667701', customer_dni: '09897517' }, // Ticket sembrado persistente en BD
  { tracking_code: 'RET-56667701', customer_dni: '09897517' },
  { tracking_code: 'RET-98791201', customer_dni: '08224073' }, // Ticket dinámico secundario
  { tracking_code: 'RET-56790301', customer_dni: '12198576' }, // Ticket dinámico secundario
  { tracking_code: 'RET-00000000', customer_dni: '99999999' }, // Ticket inexistente para validar 422 sin crash
];

function getVuXsrfToken() {
  const jar = http.cookieJar();
  const cookies = jar.cookiesForURL(BASE_URL);
  if (!cookies['XSRF-TOKEN'] || cookies['XSRF-TOKEN'].length === 0) {
    http.get(`${BASE_URL}/sanctum/csrf-cookie`, {
      tags: { name: 'GET /sanctum/csrf-cookie' },
    });
  }
  const updatedCookies = jar.cookiesForURL(BASE_URL);
  if (updatedCookies['XSRF-TOKEN'] && updatedCookies['XSRF-TOKEN'].length > 0) {
    return decodeURIComponent(updatedCookies['XSRF-TOKEN'][0]);
  }
  return '';
}

export default function () {
  const xsrfToken = getVuXsrfToken();

  // Rotación cíclica según VU e iteración actual
  const item = TRACKING_DATASET[(__VU + __ITER) % TRACKING_DATASET.length];

  // Emulación de IP de cliente distribuido por VU e iteración para simular usuarios públicos reales
  const clientIp = `192.168.${Math.floor((__VU * 100 + __ITER) / 250)}.${((__VU * 100 + __ITER) % 250) + 1}`;

  const payload = JSON.stringify({
    tracking_code: item.tracking_code,
    customer_dni: item.customer_dni,
  });

  const params = {
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'X-XSRF-TOKEN': xsrfToken,
      'X-Forwarded-For': clientIp,
    },
    // No seguir la redirección 302 a /returns/tracking para medir el tiempo puro de procesamiento del endpoint
    redirects: 0,
    // Considerar 200, 302 y 422 como códigos de respuesta de dominio esperados (no fallos de transporte HTTP)
    responseCallback: http.expectedStatuses(200, 302, 422),
    tags: { name: 'POST /returns/track-by-code' },
  };

  const res = http.post(`${BASE_URL}/returns/track-by-code`, payload, params);

  // Verificación de errores de infraestructura (5xx)
  const is5xx = res.status >= 500;
  serverErrors.add(is5xx);

  // Registro de consultas exitosas
  if (res.status === 200 || res.status === 302) {
    successfulQueries.add(1);
  }

  check(res, {
    'status is acceptable domain code (200, 302, or 422)': (r) =>
      [200, 302, 422].includes(r.status),
    'no 5xx server crash': () => !is5xx,
  });

  // Pacing constante
  sleep(0.3);
}
