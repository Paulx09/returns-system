import http from 'k6/http';
import { check, sleep } from 'k6';
import { Counter, Rate } from 'k6/metrics';

// Métricas de gobernanza de estrés y concurrencia
export const serverErrors = new Rate('server_errors');
export const rateLimitedRequests = new Counter('rate_limited_requests');
export const successfulValidations = new Counter('successful_validations');
export const validationErrors = new Counter('validation_errors');

export const options = {
  stages: [
    { duration: '10s', target: 30 }, // Spike rápido hacia 30 VUs concurrentes
    { duration: '30s', target: 30 }, // Carga sostenida en pico de estrés
    { duration: '10s', target: 0 },  // Descompresión / enfriamiento a 0 VUs
  ],
  thresholds: {
    // RNF-01: El 95% de las respuestas debe resolver en menos de 3.0 segundos incluso bajo estrés
    http_req_duration: ['p(95)<3000'],
    // Cero caídas o errores de contención en PostgreSQL (5xx < 1%)
    server_errors: ['rate<0.01'],
  },
};

const BASE_URL = __ENV.BASE_URL || 'http://127.0.0.1:8000';

// Pool de órdenes sembradas en external_orders_cache
const ORDER_DATASET = [
  { order_number: 'ORD-566677', customer_dni: '09897517' }, // Orden válida en caché
  { order_number: 'ORD-987912', customer_dni: '08224073' }, // Orden válida en caché
  { order_number: 'ORD-567903', customer_dni: '12198576' }, // Orden válida en caché
  { order_number: 'ORD-NONEXISTENT', customer_dni: '00000000' }, // Orden inexistente para estrés de fallback
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
  const item = ORDER_DATASET[(__VU + __ITER) % ORDER_DATASET.length];

  // Agrupamos en 3 clientes simulados para evaluar la activación de throttle:5,1
  // permitiendo verificar que el rate limiter actúa adecuadamente ante ráfagas concurrentes
  const clientSubnet = (__VU % 3) + 1;
  const clientIp = `192.168.10.${clientSubnet}`;

  const payload = JSON.stringify({
    order_number: item.order_number,
    customer_dni: item.customer_dni,
  });

  const params = {
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'X-XSRF-TOKEN': xsrfToken,
      'X-Forwarded-For': clientIp,
    },
    redirects: 0,
    responseCallback: http.expectedStatuses(200, 302, 422, 429),
    tags: { name: 'POST /returns/login' },
  };

  const res = http.post(`${BASE_URL}/returns/login`, payload, params);

  // Detección de fallos de infraestructura / base de datos
  const is5xx = res.status >= 500;
  serverErrors.add(is5xx);

  // Clasificación de respuestas de dominio
  if (res.status === 429) {
    rateLimitedRequests.add(1);
  } else if (res.status === 200 || res.status === 302) {
    successfulValidations.add(1);
  } else if (res.status === 422) {
    validationErrors.add(1);
  }

  // Aserciones de control de calidad
  check(res, {
    'defensive response (200, 302, 422, or 429 rate limited)': (r) =>
      [200, 302, 422, 429].includes(r.status),
    'no 5xx database contention or crash': () => !is5xx,
  });

  // Pacing breve
  sleep(0.3);
}
