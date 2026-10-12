# Requerimientos Funcionales (RF) - Módulo SCD-TL (Tai Loy S.A.)

El presente documento formaliza los Requerimientos Funcionales del Sistema Web de Control y Seguimiento de Solicitudes de Cambios y Devoluciones (SCD-TL), alineados con los objetivos de negocio y la arquitectura implementada en Laravel 11/12, React e Inertia.js.

---

## 1. Mapeo de Actores del Sistema
- **Cliente**: Usuario final (comprador de e-commerce o tienda física) que gestiona reclamos de manera autónoma 24/7 mediante DNI y Número de Pedido.
- **Operador / Evaluador (`operator` / `support`)**: Personal de Servicio al Cliente encargado de evaluar evidencias, solicitar información adicional y dictaminar aprobaciones o rechazos.
- **Administrador (`admin`)**: Superusuario con facultades de auditoría, gobernanza y autoridad exclusiva para el cierre definitivo de expedientes.
- **Sistema**: Procesos automáticos en segundo plano (motor de eventos, notificaciones por correo y validaciones de transaccionalidad).

---

## 2. Catálogo Canónico de Requerimientos Funcionales

| Código | Módulo | Nombre | Descripción Detallada | Prioridad | Módulo / Controlador |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **RF-01** | Autenticación Cliente | Validación de Pedido y DNI | Autenticar al cliente mediante N.° de Pedido y DNI, consultando `ExternalOrderService` o la réplica local `external_orders_cache`. Inicializa sesión protegida contra fixation. | Alta | `CustomerAuthController.php` |
| **RF-02** | Validación Negocio | Control de Plazo de Devolución | Validar que la fecha de compra no exceda los 7 días calendario. Si el plazo vence, se bloquea el inicio de la solicitud con una alerta informativa (HTTP 422). | Alta | `CustomerAuthController.php` / `ExternalOrderService.php` |
| **RF-03** | Portal Cliente | Selección de Ítems y Motivos | Listar los artículos del pedido para que el cliente seleccione las unidades a devolver, la condición física (`sealed`, `opened`, `damaged`) y el motivo tipificado (`ReturnReason`). | Alta | `ReturnTicketController.php` |
| **RF-04** | Portal Cliente | Carga y Validación de Evidencias | Permitir adjuntar de 1 a 5 evidencias digitales (formatos `.jpg`, `.jpeg`, `.png`, `.pdf`) con límite estricto de 5 MB por archivo, aplicando compresión en cliente y almacenamiento privado. | Alta | `ReturnTicketController.php` / `Evidence.php` |
| **RF-05** | Gestión Tickets | Generación de Código de Seguimiento | Generar atómicamente un identificador único alfanumérico (`tracking_code`, formato `RET-XXXXXXXX`) y asignar el estado inicial `received` dentro de una transacción DB. | Alta | `ReturnTicketController.php` |
| **RF-06** | Seguimiento Cliente | Consulta Pública de Estado | Permitir consultar el avance del trámite ingresando `tracking_code` y DNI, visualizando la línea de tiempo histórica y los dictámenes oficiales sin requerir autenticación administrativa. | Media | `TicketTrackingAndLifecycleTest.php` / `Tracking.jsx` |
| **RF-07** | Panel Backoffice | Autenticación y RBAC Interno | Proveer autenticación segura para el personal interno distinguiendo roles y permisos entre `operator` (evaluador) y `admin` (superusuario). | Alta | `AdminMiddleware.php` / `AdminTicketController.php` |
| **RF-08** | Bandeja Operativa | Listado y Filtrado Multicriterio | Exponer bandeja reactiva de solicitudes con filtros por estado (`received`, `under_review`, `more_information_requested`, `approved`, `rejected`, `closed`) y rango de fechas con paginación de 15 ítems. | Media | `AdminTicketController.php` (`index`) |
| **RF-09** | Evaluación | Workflow de Transición de Estados | Permitir transiciones de estado según la FSM. Exigir obligatoriamente el campo `comment` (justificación técnica) cuando el nuevo estado sea `rejected` o `more_information_requested`. | Alta | `AdminTicketController.php` (`updateStatus`) |
| **RF-10** | Gobernanza RBAC | Control Restringido de Cierre | Restringir el estado `closed` exclusivamente al rol `admin`. Si un usuario con rol `operator` intenta cerrarlo, abortar inmediatamente con HTTP 403 Forbidden. | Alta | `AdminTicketController.php` |
| **RF-11** | Notificaciones | Despacho de Correo Automatizado | Emitir notificaciones automáticas por correo electrónico al cliente ante cada transición de estado mediante el evento asíncrono `TicketStatusUpdated` y el listener `SendTicketStatusNotification`. | Media | `TicketStatusUpdated.php` / `SendTicketStatusNotification.php` |
| **RF-12** | Auditoría | Trazabilidad Histórica Inmutable | Registrar cada transición de estado en la tabla `ticket_status_history` guardando estado previo, estado nuevo, timestamp, ID del usuario responsable y observación técnica. | Alta | `TicketStatusHistory.php` |

---
