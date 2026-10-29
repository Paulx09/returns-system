# Plan y Matriz de Casos de Prueba - SCD-TL (Tai Loy S.A.)

Especificación de Casos de Prueba (Test Cases) para el Sistema de Control y Seguimiento de Devoluciones (SCD-TL). Define la trazabilidad con Historias de Usuario (Jira) y el catálogo tabular de ejecución para pruebas manuales y destructivas.

---

## 1. Convenciones y Estados

### Tipos de Prueba
- **Funcional**: Flujo nominal del requerimiento (Happy Path).
- **Negativa**: Validaciones de negocio, límites e inputs inválidos.
- **Exploratoria / Destructiva**: Acciones imprevistas, estrés de UI, doble submit y formatos anómalos.
- **Seguridad / RBAC**: Control de acceso por roles (`admin` vs `operator`) y bypass de sesión.

### Nivel de Automatización
- **Automatizado**: Implementado en PHPUnit, Playwright o Postman/Newman.
- **Manual**: Validación humana (usabilidad, UI/UX y pruebas heurísticas).
- **Híbrido**: Automatizado en backend/E2E con verificación visual complementaria.

### Estados de Ejecución
- `[ ]` Pendiente
- `[X]` Aprobado (Passed)
- `[!]` Fallido (Failed)
- `[-]` Bloqueado (Blocked)

---

## 2. Matriz de Trazabilidad General

| ID Caso | Historia Jira | Requerimiento / Funcionalidad | Tipo | Nivel Automatizado | Archivo Automatizado Asociado | Estado Manual |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **TC-CLI-01** | HU-01 | Autenticación con pedido vigente (≤ 7 días) | Funcional | Automatizado | `tests/Feature/Returns/CustomerAuthenticationTest.php`<br>`e2e/customer-returns-flow.spec.ts` | `[ ]` |
| **TC-CLI-02** | HU-01 | Bloqueo por pedido fuera de plazo (> 7 días) | Negativa | Automatizado | `tests/Feature/Returns/CustomerAuthenticationTest.php`<br>`postman/returns-api.collection.json` | `[ ]` |
| **TC-CLI-03** | HU-01 | Redirección de pedido con ticket activo en curso | Negativa | Automatizado | `tests/Feature/Returns/CustomerAuthenticationTest.php` | `[ ]` |
| **TC-CLI-04** | HU-01 | Validación de formato de DNI (8 dígitos) y pedido inexistente | Negativa | Automatizado | `tests/Feature/Returns/CustomerAuthenticationTest.php` | `[ ]` |
| **TC-CLI-05** | HU-02 | Fallback de consulta ante caída de API externa (Caché local) | Resiliencia | Automatizado | `tests/Feature/Services/ExternalOrderServiceTest.php` | `[ ]` |
| **TC-CLI-06** | HU-03 | Selección de ítems, condición física y motivo de devolución | Funcional | Automatizado | `e2e/customer-returns-flow.spec.ts` | `[ ]` |
| **TC-CLI-07** | HU-04 | Carga de evidencias válidas (1 a 5 archivos JPG/PNG/PDF ≤ 5MB) | Funcional | Automatizado | `tests/Feature/Returns/EvidenceUploadTest.php`<br>`e2e/customer-returns-flow.spec.ts` | `[ ]` |
| **TC-CLI-08** | HU-04 | Rechazo de archivo que excede 5 MB o extensión prohibida | Negativa | Automatizado | `tests/Feature/Returns/EvidenceUploadTest.php` | `[ ]` |
| **TC-CLI-09** | HU-04 | Bloqueo por validación MIME (archivo ejecutable renombrado) | Seguridad | Automatizado | `tests/Feature/Returns/EvidenceUploadTest.php` | `[ ]` |
| **TC-CLI-10** | HU-05 | Generación de Tracking Code (`RET-XXXXXXXX`) y estado `received` | Funcional | Automatizado | `tests/Feature/Returns/ReturnTicketSubmissionTest.php`<br>`e2e/customer-returns-flow.spec.ts` | `[ ]` |
| **TC-CLI-11** | HU-05 | Prevención de doble envío por clic repetitivo (Doble Submit) | Destructiva | Manual | N/A (Inspección de UI / Network) | `[ ]` |
| **TC-TRK-01** | HU-06 | Consulta pública de estado mediante Tracking Code y DNI | Funcional | Automatizado | `tests/Feature/Returns/TicketTrackingAndLifecycleTest.php`<br>`postman/returns-api.collection.json` | `[X]` |
| **TC-TRK-02** | HU-06 | Consulta pública con DNI erróneo o Tracking Code inválido (404) | Negativa | Automatizado | `tests/Feature/Returns/TicketTrackingAndLifecycleTest.php` | `[X]` |
| **TC-NOT-01** | HU-07 | Emisión de correo ante transición de estado | Funcional | Automatizado | `tests/Feature/Returns/TicketTrackingAndLifecycleTest.php` | `[ ]` |
| **TC-ADM-01** | HU-08 | Autenticación interna con roles (`operator` vs `admin`) | Funcional | Automatizado | `tests/Feature/Admin/AdminTicketControllerTest.php`<br>`e2e/admin-workflow.spec.ts` | `[ ]` |
| **TC-ADM-02** | HU-08 | Bloqueo de acceso no autenticado a panel administrativo (401/403) | Seguridad | Automatizado | `tests/Feature/Middleware/AdminMiddlewareTest.php` | `[ ]` |
| **TC-ADM-03** | HU-09 | Filtrado por estado y paginación reactiva en bandeja | Funcional | Automatizado | `tests/Feature/Admin/AdminTicketControllerTest.php`<br>`e2e/admin-workflow.spec.ts` | `[ ]` |
| **TC-ADM-04** | HU-10 | Visualización y descarga segura de evidencias adjuntas | Funcional | Automatizado | `tests/Feature/Admin/AdminTicketControllerTest.php` | `[ ]` |
| **TC-ADM-05** | HU-11 | Transición de estado con justificación obligatoria en rechazo/info | Funcional | Automatizado | `tests/Feature/Admin/AdminTicketLifecycleTransitionTest.php` | `[X]` |
| **TC-ADM-06** | HU-12 | Intento de cierre de ticket (`closed`) por `operator` (Bloqueo 403) | Seguridad / RBAC | Automatizado | `tests/Feature/Admin/AdminTicketControllerTest.php`<br>`e2e/admin-workflow.spec.ts` | `[X]` |
| **TC-ADM-07** | HU-12 | Cierre definitivo de ticket (`closed`) exclusivo por rol `admin` | Funcional | Automatizado | `tests/Feature/Admin/AdminTicketControllerTest.php`<br>`e2e/admin-workflow.spec.ts` | `[X]` |
| **TC-EXP-01** | General | Resiliencia de formulario ante botón Atrás del navegador | Exploratoria | Manual | N/A (Flujo de navegación) | `[X]` |

---

## 3. Catálogo Tabular de Pruebas Manuales

### 3.1 Portal Cliente (EP-01 y EP-02)

| ID | HU | Escenario / Objetivo | Precondiciones | Pasos de Ejecución | Datos de Entrada | Resultado Esperado | Estado |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **TC-CLI-01** | HU-01 | Login cliente con pedido elegible | Servidor activo en `:8000`. BD migrada y seed aplicada. | 1. Ir a `/returns/start`.<br>2. Ingresar Pedido y DNI.<br>3. Clic en "Iniciar Solicitud". | Pedido: `ORD-123456`<br>DNI: `12345678` | Redirección exitosa a formulario de devolución. Muestra artículos asociados. | `[X]` |
| **TC-CLI-02** | HU-01 | Bloqueo por plazo vencido (> 7 días) | Pedido entregado hace > 7 días en base de datos. | 1. Ir a `/returns/start`.<br>2. Ingresar Pedido vencido.<br>3. Clic en "Iniciar Solicitud". | Pedido: `ORD-290146`<br>DNI: `40112710` | Permanece en la pantalla. Mensaje visible: "El plazo máximo de 7 días para devoluciones ha vencido". HTTP 422. | `[X]` |
| **TC-CLI-03** | HU-01 | Redirección de pedido con ticket en curso | Pedido ya cuenta con un ticket activo en estado `received`. | 1. Ir a `/returns/start`.<br>2. Ingresar datos del pedido.<br>3. Clic en "Iniciar Solicitud". | Pedido: `ORD-ACTIVE-01`<br>DNI: `12345678` | Redirige automáticamente a `/returns/tracking` mostrando el estado del trámite en curso. | `[X]` |
| **TC-CLI-04** | HU-01 | Validación de formato de DNI | Formulario en `/returns/start`. | 1. Ingresar DNI con letras o menos de 8 dígitos.<br>2. Clic en "Iniciar Solicitud". | Pedido: `ORD-123456`<br>DNI: `1234A` | Validación en cliente y servidor. Bloquea el envío con mensaje de error sobre formato de 8 dígitos numéricos. | `[X]` |
| **TC-CLI-06** | HU-03 | Selección de ítems y motivos | Sesión de cliente iniciada en formulario. | 1. Marcar casilla del producto.<br>2. Seleccionar condición y motivo.<br>3. Clic en "Continuar". | Condición: `damaged`<br>Motivo: `Defecto de Fábrica` | Permite avanzar al paso de carga de evidencias. Mantiene selecciones en estado del formulario. | `[X]` |
| **TC-CLI-07** | HU-04 | Carga de evidencias válidas | Ítem seleccionado previamente. Archivos JPG/PNG preparados. | 1. Adjuntar 2 imágenes de ~1.5 MB.<br>2. Ingresar observación.<br>3. Clic en "Enviar Solicitud". | Archivos: `evidencia1.jpg`, `evidencia2.png`<br>Texto: "El producto llegó roto" | Barra de progreso activa, petición exitosa y redirección a confirmación con código `RET-XXXXXXXX`. | `[X]` |
| **TC-CLI-08** | HU-04 | Rechazo de archivo > 5 MB y extensión inválida | Archivo de 8 MB y archivo `.exe` o `.zip` disponibles. | 1. Intentar adjuntar archivo de 8 MB.<br>2. Intentar adjuntar archivo `.zip`. | Archivo 8 MB, archivo `.zip` | Alerta inmediata en UI. No permite adjuntar el archivo ni habilitar el envío. | `[X]` |
| **TC-CLI-11** | HU-05 | Prevención de doble clic (Doble Submit) | Formulario completo. Network con throttling en Fast 3G. | 1. Presionar el botón "Enviar Solicitud" 3 veces rápidamente. | Formulario completo | El botón pasa a estado inhabilitado (disabled) al primer clic. Solo se envía 1 petición POST y se crea 1 único ticket. | `[X]` |

---

### 3.2 Seguimiento y Consulta Pública (EP-03)

| ID | HU | Escenario / Objetivo | Precondiciones | Pasos de Ejecución | Datos de Entrada | Resultado Esperado | Estado |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **TC-TRK-01** | HU-06 | Consulta pública de ticket existente | Ticket previamente generado en BD. Modo incógnito sin sesión. | 1. Ir a `/returns/tracking`.<br>2. Ingresar Tracking Code y DNI.<br>3. Clic en "Consultar". | Tracking: `RET-12345678`<br>DNI: `12345678` | Muestra línea de tiempo con fecha de recepción, estado actual (`Recibido`) e ítems en devolución. | `[X]` |
| **TC-TRK-02** | HU-06 | Consulta con datos no coincidentes | Pantalla de consulta abierta. | 1. Ingresar Tracking inexistente o DNI erróneo.<br>2. Clic en "Consultar". | Tracking: `RET-00000000`<br>DNI: `99999999` | Mensaje claro: "No se encontró ninguna solicitud con los datos ingresados". HTTP 404 manejado. | `[X]` |

---

### 3.3 Backoffice y Control de Acceso RBAC (EP-04)

| ID | HU | Escenario / Objetivo | Precondiciones | Pasos de Ejecución | Datos de Entrada | Resultado Esperado | Estado |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **TC-ADM-01** | HU-08 | Acceso de Operador a bandeja | Cuenta de operador en BD. | 1. Ir a `/login`.<br>2. Ingresar credenciales.<br>3. Clic en "Ingresar". | Email: `soporte@tailoy.com`<br>Pass: `password` | Accede a `/admin/tickets`. Visualiza bandeja reactiva con tickets asignados y filtros activos. | `[X]` |
| **TC-ADM-03** | HU-09 | Filtrado reactivo en bandeja | Tickets con múltiples estados en BD. | 1. Seleccionar filtro "En Revisión".<br>2. Cambiar a "Aprobado".<br>3. Navegar a página 2 si existe. | Filtros de estado | La grilla se actualiza sin recarga completa. Muestra solo los tickets coincidentes. | `[X]` |
| **TC-ADM-04** | HU-10 | Visualización y descarga de evidencias | Ticket con evidencias adjuntas abierto. | 1. Clic en miniatura de evidencia.<br>2. Clic en "Descargar archivo". | Evidencias asociadas | Abre visor modal o descarga directa con firma de URL segura sin exponer storage público. | `[!]` |
| **TC-ADM-05** | HU-11 | Justificación obligatoria en rechazo | Ticket en estado `received`. Sesión de Operador activa. | 1. Seleccionar estado `rejected`.<br>2. Dejar justificación vacía y guardar.<br>3. Ingresar texto y guardar. | Texto: "Producto presenta signos de uso indebido." | Paso 2: Bloquea guardado por validación de campo requerido.<br>Paso 3: Actualiza estado y guarda historial. | `[X]` |
| **TC-ADM-06** | HU-12 | Restricción de cierre (`closed`) por Operador | Sesión de `soporte@tailoy.com`. Ticket en detalle. | 1. Inspeccionar selector de estado.<br>2. Intentar seleccionar o forzar `closed`. | Estado: `closed` | La opción no aparece o está bloqueada. Si se envía payload manual por API, devuelve HTTP 403 Forbidden. | `[X]` |
| **TC-ADM-07** | HU-12 | Cierre definitivo exclusivo por Admin | Sesión de `admin@tailoy.com`. Ticket en estado apto para cierre (`approved` o `more_information_requested`). | 1. Seleccionar estado `closed`.<br>2. Ingresar motivo de cierre.<br>3. Confirmar acción. | Email: `admin@tailoy.com`<br>Pass: `password`<br>Motivo: "Cierre conforme" | Ticket pasa a `closed` (estado terminal). Queda bloqueado para transiciones futuras. | `[X]` |

---

### 3.4 Pruebas Destructivas y Heurísticas (Romper el Sistema)

| ID | Escenario / Vector de Prueba | Procedimiento | Resultado Esperado | Estado |
| :--- | :--- | :--- | :--- | :--- |
| **TC-EXP-01** | Navegación con botón Atrás del navegador | Tras completar y enviar una solicitud con éxito, presionar el botón "Atrás" del navegador dos veces consecutivas. | El sistema no debe reenviar la solicitud ni generar un ticket duplicado. Debe redirigir a `/returns/start` o mostrar la vista con el estado actual. | `[X]` |
| **TC-EXP-02** | Inyección de scripts (XSS) en comentarios | Ingresar `<script>alert('xss')</script>` o `<b>test</b>` en los campos de observación del cliente y justificación del backoffice. | El texto se sanitiza y se muestra como texto plano escapado, sin ejecutar scripts ni romper el layout. | `[X]` |
| **TC-EXP-03** | Bypass de extensión de archivo (MIME Sniffing) | Tomar un archivo ejecutable `.exe`, renombrarlo a `foto_evidencia.png` e intentar cargarlo en el formulario de devolución. | El validador del backend detecta la firma binaria real (MIME type) y rechaza la carga con error HTTP 422 sin almacenarlo en disco. | `[X]` |
| **TC-EXP-04** | Acceso a ticket ajeno por URL (IDOR) | Con sesión de cliente o sin autenticar, intentar acceder a `/returns/ticket/{otro_id}` o descargar evidencias mediante URLs manipuladas. | El sistema valida que el recurso pertenezca a la sesión activa o exige token de verificación. Responde HTTP 403 o 404. | `[X]` |

---

## 4. Plantilla de Defectos para Jira (Bug Report)

Cuando un caso de prueba resulte en estado `[!]`, reportar el issue en Jira usando este esquema:

```markdown
### Resumen del Defecto:
[Componente/Flujo]: [Descripción concisa del fallo observado]

- **Caso de Prueba Asociado**: TC-CLI-XX / TC-ADM-XX
- **Historia Jira**: HU-XX
- **Severidad**: Blocker / Critical / Major / Minor
- **Ambiente**: Local / Staging (PHP 8.3, Chrome 129, SQLite/PostgreSQL)

### Pasos para Reproducir:
1. Ir a [URL o ruta].
2. Ingresar [valores utilizados].
3. Ejecutar [acción concreta, ej. clic en botón].

### Resultado Obtenido:
[Qué ocurrió exactamente: mensaje de error, código HTTP o comportamiento de interfaz].

### Resultado Esperado:
[Qué debía ocurrir según el criterio de aceptación del caso de prueba].

### Evidencias:
- Captura de pantalla o registro del tab Network / Console.
- Extracto de log (`storage/logs/laravel.log`) si aplica.
```
