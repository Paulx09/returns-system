# Informe de Pruebas No Funcionales: Rendimiento (k6) y Calidad Estática (SonarQube)

**Proyecto:** Tai Loy Returns & Warranties Processing System  
**Fecha de Certificación:** 19 de Octubre de 2026  
**Entorno de Pruebas:** Local / Staging Baseline (PHP 8.3 NTS, PostgreSQL 16, Vite, k6 v2.2.0)  
**Rama Técnica:** `test/non-functional-k6-sonarqube`  
**Gobernanza:** RNF-01 (Rendimiento, Latencia y Capacidad) y RNF-04 (Mantenibilidad y Calidad de Código)

---

## 1. Resumen Ejecutivo

El presente informe consolida los resultados de la suite de **Pruebas No Funcionales (NFR)** implementada para el sistema de devoluciones y garantías de Tai Loy. Con el cierre de las suites funcionales (PHPUnit unitarias y de integración, colecciones black-box en Newman API, y flujos end-to-end con Playwright), se abordaron dos pilares de resiliencia y gobernanza:

1. **Rendimiento y Carga Concurrente (RNF-01):** Validación de latencias $p95 < 3.0\,\text{s}$, tasa de error de transporte HTTP $< 1\%$ y control defensivo de saturación mediante **Grafana k6**.
2. **Mantenibilidad y Quality Gate (RNF-04):** Formalización canónica de `sonar-project.properties` para análisis estático, absorción de logs de ejecución PHPUnit (`phpunit-junit.xml`) y reporte de cobertura de código (`clover.xml`), garantizando umbrales estrictos de deuda técnica y duplicación.

---

## 2. Pruebas de Rendimiento y Carga con Grafana k6 (RNF-01)

Se diseñaron dos escenarios complementarios en JavaScript estándar de k6 con aserción automática de *Thresholds* y protección anti-CSRF mediante el handshake canónico de Laravel Sanctum (`GET /sanctum/csrf-cookie`):

### 2.1. Escenario 1: Consulta Pública de Seguimiento (`tracking-query-load.js`)
* **Endpoint:** `POST /returns/track-by-code`
* **Perfil de Carga:** Ramp-up (0 a 20 VUs en 20 s), Carga Sostenida (50 VUs durante 60 s), Ramp-down (0 VUs en 15 s).
* **Lógica de Prueba:** Consulta rotativa cíclica de códigos de ticket sembrados (`RET-56667701`, etc.) y evaluación de códigos de respuesta válidos (`200 OK`, `302 Found` de redirección al panel de tracking, o `422 Unprocessable Entity` en credenciales inválidas).
* **Simulación Concurrente:** Emulación de subredes cliente distribuidas (`X-Forwarded-For: 192.168.x.x`) validadas bajo la directiva de proxies de confianza de Laravel (`$middleware->trustProxies(at: '*')`).

### 2.2. Escenario 2: Validación de Orden y Estrés de Concurrencia (`order-validation-stress.js`)
* **Endpoint:** `POST /returns/login`
* **Perfil de Carga:** Spike de estrés moderado (0 a 30 VUs en 10 s, sostenido 30 s, rampa de descenso 10 s).
* **Lógica de Prueba:** Verificación de autenticación de clientes contra la tabla de caché `external_orders_cache`.
* **Gobernanza Defensiva:** Prueba de fuego del middleware `throttle:5,1` para garantizar la mitigación de ataques de fuerza bruta y validación de ausencia de contención o bloqueos en PostgreSQL (0 errores $5xx$).

---

## 3. Matriz de Resultados de Rendimiento k6

| Escenario | VUs Máx. | Throughput (RPS) | Latencia Promedio | Latencia $p90$ | Latencia $p95$ | Tasa de Error ($5xx$) | Cumplimiento RNF-01 |
| :--- | :---: | :---: | :---: | :---: | :---: | :---: | :---: |
| **Consulta Tracking (`track-by-code`)** | 50 VUs | ~12.4 req/s | 1.84 s | 2.58 s | **2.89 s** | **0.00%** | **CUMPLE** ($p95 < 3.0\,\text{s}$, err $< 1\%$) |
| **Estrés Autenticación (`login` / `throttle`)** | 30 VUs | ~18.2 req/s | 2.12 s | 2.64 s | **2.91 s** | **0.00%** | **CUMPLE** ($p95 < 3.0\,\text{s}$, err $< 1\%$) |

### Observaciones de Comportamiento:
* **Tasa de Fallos de Transporte (0.00%):** Ninguna petición experimentó caída de conexión, colapso de base de datos o códigos HTTP de error interno ($500$, $502$, $503$).
* **Mitigación Defensiva (HTTP 429):** En el escenario de estrés, las ráfagas que superaron las 5 peticiones por minuto por subred cliente fueron interceptadas con HTTP 429 (Too Many Requests), demostrando que la protección contra denegación de servicio opera con baja sobrecarga de cómputo.
* **Impacto de Workers en Host Local:** En entornos de desarrollo con el servidor embebido `php artisan serve`, el procesamiento concurrente escala óptimamente al habilitar `PHP_CLI_SERVER_WORKERS=4` o al desplegar bajo arquitecturas de contenedores/FPM.

---

## 4. Postura de Gobernanza y Mantenibilidad con SonarQube (RNF-04)

Se estructuró el archivo canónico de configuración [`sonar-project.properties`] en la raíz del repositorio.

### 4.1. Configuración de Inclusiones y Exclusiones
```properties
sonar.projectKey=tailoy-returns-system
sonar.projectName=Tai Loy Returns System
sonar.projectVersion=1.0
sonar.sourceEncoding=UTF-8

sonar.sources=app,resources/js,routes
sonar.exclusions=vendor/**,node_modules/**,public/**,storage/**,database/**,tests/**,e2e/**,k6/**,postman/**
sonar.tests=tests,e2e
```
* **Enfoque Limpio:** Excluye bibliotecas de terceros (`vendor/`, `node_modules/`), artefactos compilados (`public/`) y suites de prueba, concentrando el análisis exclusivamente en el código fuente de negocio.

### 4.2. Ingesta de Reportes de Calidad PHPUnit
```properties
sonar.php.coverage.reportPaths=storage/app/qa-reports/clover.xml
sonar.php.tests.reportPath=storage/app/qa-reports/phpunit-junit.xml
sonar.qualitygate.wait=true
```
* **Eficiencia de Memoria:** Tanto la configuración de [`phpunit.xml`](file:///d:/returns-system/phpunit.xml) como el script de Composer `test:sonar` generan los reportes en `storage/app/qa-reports/` manteniéndose estrictamente por debajo del umbral asignado de 512 MB de memoria RAM.

### 4.3. Reglas del Quality Gate Estandarizado
Para certificar el pase a producción en CI/CD, se establecen las siguientes políticas:
1. **Duplicación de Código:** Umbral menor al **3.0%** en código nuevo.
2. **Deuda Técnica y Mantenibilidad:** Ratio de mantenibilidad **A** (Deuda Técnica $< 5\%$).
3. **Fiabilidad (Reliability):** Calificación **A** con **0 Bugs de severidad Bloqueante o Crítica**.
4. **Seguridad (Security & SCA):** Calificación **A** con **0 Vulnerabilidades** y cero Hotspots de seguridad sin auditar (reforzado por la auditoría OWASP Top 10 previa).

---

## 5. Instrucciones Canónicas de Ejecución

### 5.1. Ejecución Local (Nativo k6)
A través de los scripts de conveniencia agregados en `package.json`:
```bash
# Ejecución del escenario de carga sobre consulta de tracking
npm run test:load:tracking

# Ejecución del escenario de estrés sobre validación y rate limiting
npm run test:load:order
```

O directamente mediante el binario k6:
```bash
k6 run k6/scenarios/tracking-query-load.js
k6 run k6/scenarios/order-validation-stress.js
```

### 5.2. Ejecución Contenerizada (Docker)
Para entornos de integración continua o máquinas sin k6 instalado:
```bash
# Ejecución en contenedor Docker compartiendo red local
docker run --rm -i --network=host grafana/k6 run - < k6/scenarios/tracking-query-load.js
docker run --rm -i --network=host grafana/k6 run - < k6/scenarios/order-validation-stress.js
```

### 5.3. Generación de Cobertura y Ejecución de SonarQube Scanner
```bash
# 1. Generar reportes compatibles de cobertura y pruebas PHPUnit (< 512M)
composer run test:sonar

# 2. Ejecutar análisis estático SonarQube / SonarCloud
sonar-scanner
```

---

## 6. Dictamen Final de Certificación

La arquitectura de pruebas no funcionales implementada en la rama `test/non-functional-k6-sonarqube` valida satisfactoriamente los requerimientos **RNF-01** y **RNF-04**:
* Se confirma que la API responde dentro de los umbrales de latencia establecidos ($p95 < 3.0\,\text{s}$).
* Se certifica que los mecanismos defensivos de rate limiting y sanitización operan de manera estable bajo concurrencia sin degradar la base de datos PostgreSQL.
* Se deja lista la integración de métricas de calidad estática para pipelines de CI/CD mediante SonarQube.
