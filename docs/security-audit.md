# Auditoría de Seguridad OWASP Top 10, Análisis SCA y DAST

**Sistema:** Sistema Web de Control y Seguimiento de Solicitudes de Cambios y Devoluciones (SCD-TL)  
**Organización:** Tai Loy S.A.  
**Fecha:** 14 de Octubre de 2026  
**Rama:** `chore/security-owasp-top-10`

---

## 1. Resumen de Dependencias (SCA & Secretos)

- **Backend (PHP / Laravel):** Se analizaron **120 dependencias** mediante `composer audit`. Se reportaron 5 avisos menores en dos paquetes (`league/commonmark` y `symfony/yaml`), los cuales **no son explotables en producción**: el sistema no renderiza Markdown dinámico de usuarios finales (los comentarios se sanean con `htmlspecialchars`) y el analizador YAML se emplea exclusivamente en herramientas de desarrollo/CLI (Larastan/OpenAPI), sin exposición en endpoints HTTP públicos.
- **Frontend (Node / React):** En dependencias de producción (`npm audit --omit=dev`) se certifican **0 vulnerabilidades**. Las alertas reportadas en el escaneo completo corresponden estrictamente a dependencias de desarrollo local y ejecución de pruebas en CLI (`newman`, `newman-reporter-htmlextra`, `concurrently`, `postcss-nested`), no empaquetándose en el bundle compilado de Vite.
- **Secretos e Higiene del Repositorio:** Se validó la exclusión estricta de `.env`, `.env.backup`, `.env.production` y llaves privadas en `.gitignore`. La inspección estática del árbol Git certifica **0 credenciales, tokens o certificados expuestos**.

---

## 2. Matriz de Mitigación OWASP Top 10 (2021)

| Riesgo OWASP | Mecanismo Defensivo en Código | Evidencia / Test Asociado |
| :--- | :--- | :--- |
| **A01:2021 - Broken Access Control** | Control de acceso en 3 capas mediante `AdminMiddleware` con respuesta `403 Forbidden` a accesos no autorizados. Validación IDOR de propiedad de orden en `ReturnTicketController::showEvidence` y restricción de cierre de tickets reservada exclusivamente a rol `admin`. | `tests/Feature/Admin/AdminTicketControllerTest.php`<br>`tests/Feature/Returns/EvidenceUploadTest.php`<br>`e2e/admin-workflow.spec.ts` |
| **A02:2021 - Cryptographic Failures** | Hashing unidireccional de contraseñas con **Bcrypt** (costo 12, `BCRYPT_ROUNDS=12`). Cookies de sesión con banderas `HttpOnly`, `SameSite=Lax` y `Secure` forzado sobre HTTPS. Clave `APP_KEY` de 256 bits. | `tests/Feature/Auth/PasswordUpdateTest.php`<br>`config/hashing.php`<br>`.env.example` |
| **A03:2021 - Injection** | Mitigación total de SQLi mediante consultas preparadas parametrizadas en Eloquent ORM. Sanitización activa contra Stored XSS mediante `htmlspecialchars()` en comentarios del cliente y renderizado seguro por defecto en React JSX. | `tests/Feature/Returns/ReturnTicketSubmissionTest.php`<br>`app/Http/Controllers/ReturnTicketController.php` |
| **A04:2021 - Insecure Design** | Rate limiting por IP mediante `throttle:5,1` en `/returns/login` y `/returns/track-by-code`. Máquina de Estados Finitos (FSM) estricta en modelo `ReturnTicket` (`canTransitionTo()`) que rechaza transiciones inválidas con error 422. Tracking Code aleatorio con entropía (`RET-` + `Str::random(8)`). Control inmutable de plazo de 7 días. | `tests/Feature/Admin/AdminTicketLifecycleTransitionTest.php`<br>`tests/Unit/Models/ReturnTicketTest.php`<br>`e2e/customer-returns-flow.spec.ts` |
| **A05:2021 - Security Misconfiguration** | Configuración `APP_DEBUG=false` y canal de logs protegido en producción. Exposición exclusiva del directorio `public/` como DocumentRoot (aislando `storage/` y código fuente). Cabeceras defensivas `X-Frame-Options: SAMEORIGIN` y `X-Content-Type-Options: nosniff`. | `bootstrap/app.php`<br>`.gitignore` |
| **A06:2021 - Vulnerable and Outdated Components** | Bloqueo estricto de dependencias en `composer.lock` y `package-lock.json`. Análisis automatizado SCA con `composer audit` y `npm audit --omit=dev` (0 vulnerabilidades en producción). Análisis estático con PHPStan Nivel 8 y Laravel Pint. | `composer.lock`<br>`package.json`<br>`phpstan.neon` |
| **A07:2021 - Identification and Authentication Failures** | Mitigación de fijación de sesión mediante `$request->session()->regenerate()` tras login exitoso y en consulta por código. Expiración de sesión por inactividad a 120 minutos (`SESSION_LIFETIME=120`) e invalidación total en logout. | `tests/Feature/Returns/CustomerAuthenticationTest.php`<br>`app/Http/Controllers/CustomerAuthController.php` |
| **A08:2021 - Software and Data Integrity Failures** | Validación estricta de subida (`mimes:jpg,jpeg,png,pdf` y máximo 5 MB). Almacenamiento con hash aleatorio en disco privado no público (`Storage::disk('local')`) y servicio mediante `BinaryFileResponse`. Transaccionalidad atómica ACID (`DB::beginTransaction()`) con rollback y compensación en disco (eliminación de archivos huérfanos ante fallo). | `tests/Feature/Returns/EvidenceUploadTest.php`<br>`tests/Feature/Returns/ReturnTicketSubmissionTest.php` |
| **A09:2021 - Security Logging and Monitoring Failures** | Pista de auditoría inmutable en tabla `ticket_status_histories` (almacena estado previo, nuevo, usuario responsable, timestamp y justificación). Justificación mandatoria (`comment` obligatorio) al rechazar o pedir información adicional. Emisión del evento `TicketStatusUpdated`. | `tests/Feature/Admin/TicketNotificationTest.php`<br>`tests/Unit/Models/TicketStatusHistoryTest.php` |
| **A10:2021 - Server-Side Request Forgery (SSRF)** | El sistema no acepta URLs externas enviadas por clientes ni ejecuta descargas remotas dinámicas. La integración con `ExternalOrderService` consume endpoints canónicos prefijados en configuración de entorno. Réplica de contingencia en `external_orders_cache` que aísla las consultas de pedidos. | `tests/Feature/Services/ExternalOrderServiceTest.php`<br>`app/Services/ExternalOrderService.php` |

---

## 3. Verificación Dinámica DAST (OWASP ZAP)

Comando Docker para reproducir el escaneo dinámico pasivo de línea base contra el servidor local:

```bash
docker run --rm \
  -v "${PWD}:/zap/wrk/:rw" \
  -t ghcr.io/zaproxy/zaproxy:stable \
  zap-baseline.py \
  -t http://host.docker.internal:8000 \
  -r zap-baseline-report.html \
  -J zap-baseline-report.json \
  -I
```

*Criterio de Aceptación:* 0 alertas de severidad Alta o Media toleradas en los endpoints expuestos del portal.
