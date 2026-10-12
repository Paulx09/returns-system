# Requerimientos No Funcionales (RNF) - Estándar ISO/IEC 25010

Los Requerimientos No Funcionales establecen las restricciones arquitectónicas, atributos de calidad, rendimiento y lineamientos de seguridad que rigen la operación técnica del sistema SCD-TL.

---

## 1. Catálogo de Atributos de Calidad

| Código | Categoría (ISO 25010) | Requisito Técnico | Métrica y Criterio de Aceptación |
| :--- | :--- | :--- | :--- |
| **RNF-01** | Rendimiento y Eficiencia de Desempeño | Tiempo de Respuesta en Consultas | El tiempo de respuesta del servidor en la consulta y validación de pedidos contra la API/caché debe ser **menor a 3.0 segundos** bajo condiciones normales de red. |
| **RNF-02** | Seguridad | Blindaje contra OWASP Top 10 | Mitigación estricta de vulnerabilidades críticas: prevención de SQL Injection mediante consultas preparadas con Eloquent ORM, protección contra CSRF vía tokens de sesión/cookies, y sanitización XSS en comentarios. Cero vulnerabilidades críticas o altas. |
| **RNF-03** | Seguridad & Almacenamiento | Control Riguroso de Archivos y Streaming | Restricción estricta de tipos MIME (`image/jpeg`, `image/png`, `application/pdf`) y almacenamiento privado bajo disco local seguro (`storage/app/evidences/`). Acceso denegado a descargas directas sin sesión autorizada. |
| **RNF-04** | Seguridad & Confidencialidad | Cumplimiento Ley N.° 29733 (Protección de Datos Personales) | Resguardo confidencial de DNI, teléfono y correo electrónico. Contraseñas del personal administrativo cifradas unidireccionalmente mediante Bcrypt. Sesiones efímeras de cliente destruidas tras 2 horas de inactividad. |
| **RNF-05** | Usabilidad & Accesibilidad | Diseño Adaptativo y WCAG 2.1 AA | Interfaz maquetada con Tailwind CSS 100% responsive (móvil para clientes, desktop para backoffice). Cumplimiento de pautas WCAG 2.1 Nivel AA evaluado con `vitest-axe` (contraste cromático, navegación por teclado, roles ARIA). |
| **RNF-06** | Fiabilidad & Disponibilidad | Resiliencia ante Fallos de API Externa | Mecanismo de tolerancia a fallos: si la API central de Tai Loy se encuentra fuera de servicio, el sistema debe consultar transparentemente la réplica en `external_orders_cache` sin interrumpir la operación del cliente. SLA de disponibilidad ≥ 99.9%. |
| **RNF-07** | Mantenibilidad | Estándares de Codificación y Análisis Estático | Cumplimiento estricto del estándar PSR-12 en PHP/Laravel verificado con herramientas de linting (Laravel Pint / PHPStan Nivel 8). Arquitectura limpia desacoplada en Controladores, Modelos, Form Requests y Servicios. |
| **RNF-08** | Integridad de Datos | Atomicidad Transaccional (ACID) y Compensación | El registro de tickets, ítems devueltos y evidencias debe ejecutarse dentro de transacciones de base de datos (`DB::beginTransaction` / `DB::rollBack`). Compensación explícita en disco para eliminar archivos huérfanos ante excepciones. |

---

## 2. Quality Gates y Umbrales de Aceptación (Testing)

- **Cobertura Backend**: $\ge 80\%$ de líneas y ramas evaluadas con PHPUnit / Xdebug / PCOV.
- **Cobertura Frontend**: $\ge 75\%$ evaluada con Vitest y `@testing-library/react`.
- **Accesibilidad (a11y)**: 0 violaciones críticas WCAG 2.1 AA.
- **Contratos de API**: 100% de conformidad contra `docs/openapi.yaml` verificado mediante suites automatizadas con Newman.
