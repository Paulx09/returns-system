# Product Backlog e Historias de Usuario (US) - Metodología Scrum

El desarrollo del módulo SCD-TL se descompone en 4 Épicas principales y 12 Historias de Usuario (HU), acumulando un total de 70 Story Points (SP) validados mediante la técnica de Planning Poker.

---

## 1. Desglose de Épicas

- **EP-01**: Acceso, Autenticación y Validación de Pedidos.
- **EP-02**: Registro de Solicitud de Devolución y Carga de Evidencias.
- **EP-03**: Trazabilidad, Notificaciones y Consulta Pública.
- **EP-04**: Backoffice Administrativo, Gestión de Tickets y Gobernanza RBAC.

---

## 2. Matriz Consolidada del Product Backlog

| ID | Épica | Historia de Usuario | Rol | Prioridad (MoSCoW) | SP | Sprint |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **HU-01** | EP-01 | Autenticación de cliente por Pedido y DNI con validación de política de 7 días. | Cliente | Must Have | 5 | Sprint 1 |
| **HU-02** | EP-01 | Resiliencia de consulta ante fallos de API externa mediante caché de órdenes. | Sistema | Should Have | 5 | Sprint 1 |
| **HU-03** | EP-02 | Visualización interactiva de artículos del pedido y selección de motivos. | Cliente | Must Have | 8 | Sprint 2 |
| **HU-04** | EP-02 | Carga de evidencias digitales (1-5 archivos) con límite de 5 MB por archivo. | Cliente | Must Have | 8 | Sprint 2 |
| **HU-05** | EP-02 | Confirmación transaccional y generación de Tracking Code (`RET-XXXXXXXX`). | Cliente | Must Have | 5 | Sprint 2 |
| **HU-06** | EP-03 | Consulta pública de estado e historial mediante Tracking Code y DNI. | Cliente | Must Have | 5 | Sprint 3 |
| **HU-07** | EP-03 | Emisión de notificaciones por correo electrónico ante transiciones de estado. | Sistema | Should Have | 5 | Sprint 3 |
| **HU-08** | EP-04 | Inicio de sesión seguro y gestión de roles (`admin` vs `operator`). | Personal Interno | Must Have | 5 | Sprint 1 |
| **HU-09** | EP-04 | Bandeja de tickets con filtros (estado, fechas) y paginación reactiva. | Personal Interno | Must Have | 8 | Sprint 3 |
| **HU-10** | EP-04 | Detalle integral del ticket con descarga/visualización segura de evidencias. | Personal Interno | Must Have | 5 | Sprint 4 |
| **HU-11** | EP-04 | Workflow de cambio de estado con justificación obligatoria en rechazo o solicitud de info. | Operador / Admin | Must Have | 8 | Sprint 4 |
| **HU-12** | EP-04 | Restricción estricta de cierre definitivo de ticket (`closed`) reservada a rol Admin. | Administrador | Must Have | 3 | Sprint 4 |

---

## 3. Especificación Detallada de Criterios de Aceptación (Gherkin BDD)

### HU-01: Autenticación de Cliente y Control de Política de 7 Días

- **Descripción**: Como cliente que compró online, quiero ingresar mi N.° de Pedido y DNI para verificar si la compra es válida y se encuentra dentro de la ventana de garantía.
- **Criterios de Aceptación**:

```gherkin
Scenario: Ingreso válido dentro del plazo de garantía
  Given que el cliente ingresa un número de pedido y DNI válidos
  And la fecha de entrega de la compra tiene 7 días o menos de antigüedad
  When presiona el botón "Iniciar Solicitud"
  Then el sistema inicializa la sesión regenerando el ID de sesión
  And redirige al cliente a la vista del formulario de devolución (/returns/dashboard)

Scenario: Intento de inicio con compra fuera de plazo (> 7 días)
  Given que el cliente ingresa un número de pedido con más de 7 días de entregado
  When presiona el botón "Iniciar Solicitud"
  Then el sistema bloquea el acceso con código HTTP 422
  And muestra el mensaje: "El plazo máximo de 7 días para devoluciones ha vencido para este pedido."

Scenario: Pedido con ticket activo en curso
  Given que el pedido ingresado ya tiene un ticket asociado en estado no terminal
  When el cliente intenta iniciar sesión
  Then el sistema lo redirige directamente a la pantalla de seguimiento (/returns/tracking)
```

---

### HU-03 & HU-04: Selección de Ítems y Carga de Evidencias

- **Descripción**: Como cliente, quiero seleccionar qué productos devolver, su condición física y adjuntar fotos para fundamentar el reclamo.
- **Criterios de Aceptación**:

```gherkin
Scenario: Selección de ítems con cantidad válida
  Given que el cliente visualiza el desglose de productos de su pedido
  When selecciona un artículo indicando condición ("sealed", "opened", "damaged") y motivo
  And la cantidad seleccionada es menor o igual a la adquirida
  Then el formulario permite avanzar al paso de carga de evidencias

Scenario: Validación de cuota de archivos de evidencia (1 a 5)
  Given que el cliente está en el paso de adjuntar archivos
  When intenta enviar el formulario sin adjuntar archivos o adjuntando más de 5 archivos
  Then el sistema bloquea el envío con un error 422 indicando cuota no permitida

Scenario: Restricción de tamaño y formato
  Given que el cliente selecciona un archivo mayor a 5120 KB o con extensión no autorizada
  When el archivo es evaluado por el sistema
  Then el sistema rechaza el archivo y exige formatos .jpg, .png o .pdf menores a 5 MB
```

---

### HU-05: Atomicidad Transaccional y Creación de Ticket

- **Descripción**: Como cliente, quiero confirmar el registro para obtener mi comprobante formal de reclamo.
- **Criterios de Aceptación**:

```gherkin
Scenario: Registro exitoso y generación de Tracking Code
  Given un formulario válido con ítems y evidencias
  When el backend procesa la petición en DB::transaction
  Then crea el ticket en estado "received"
  And genera un código con formato "RET-XXXXXXXX"
  And persiste los archivos en storage privado retornando HTTP 201 Created

Scenario: Compensación y rollback ante fallo transaccional
  Given una falla simulada en la inserción de ítems o base de datos
  When se dispara una excepción en la transacción
  Then el sistema ejecuta DB::rollBack()
  And purga cualquier archivo temporal guardado en disco para evitar huérfanos
```

---

### HU-11: Workflow de Transición de Estados

- **Descripción**: Como operador de soporte, quiero cambiar el estado de un ticket para reflejar su avance operativo.
- **Criterios de Aceptación**:

```gherkin
Scenario: Transición a rechazo sin justificación obligatoria
  Given que el operador intenta transicionar el ticket a "rejected"
  When omite ingresar el campo "comment"
  Then el sistema rechaza la petición con HTTP 422
  And muestra el error: "El campo comment es obligatorio cuando new_status es rejected."

Scenario: Transición válida con registro histórico
  Given una transición permitida por la FSM con comentario válido
  When el operador guarda los cambios
  Then se actualiza el estado del ticket
  And se inserta un registro inmutable en "ticket_status_history"
  And se dispara el evento "TicketStatusUpdated" despachando el correo al cliente
```

---

### HU-12: Gobernanza RBAC y Cierre Exclusivo por Administrador

- **Descripción**: Como Administrador, quiero reservar en exclusiva la potestad de cerrar tickets para evitar cierres arbitrarios sin auditoría.
- **Criterios de Aceptación**:

```gherkin
Scenario: Operador de soporte intenta cerrar un ticket
  Given un usuario autenticado con rol "operator" o "support"
  When envía una solicitud PATCH/POST para transicionar un ticket a "closed"
  Then el backend aborta la petición con HTTP 403 Forbidden
  And responde con el mensaje: "Solo administradores pueden cerrar tickets."

Scenario: Administrador ejecuta el cierre definitivo
  Given un usuario autenticado con rol "admin"
  When envía la solicitud para transicionar a "closed"
  Then el ticket se actualiza al estado terminal "closed" exitosamente
```
