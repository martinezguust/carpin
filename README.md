# Carpintería Carpin – Sistema Web de Gestión

Sistema web para gestionar una carpintería familiar (muebles a pedido y reparaciones). Proyecto de la experiencia educativa **Gestión de Procesos de Desarrollo de Software**, Facultad de Estadística e Informática, Universidad Veracruzana. **Equipo 6** · Scrum en 4 Sprints.

## Estado del proyecto

| Fase | Contenido | Estado |
|---|---|---|
| Fase 0 – Setup | Estructura, Composer + Dompdf, configuración, core, base de datos y datos de prueba | ✅ Terminada y aprobada |
| Fase 1 – Sprint 1 | Acceso por roles, usuarios, proyectos, actividades, fases, portal del cliente y notificaciones | ✅ Terminada y aprobada |
| Fase 2 – Sprint 2 | Producción, página pública, cotizaciones, reparaciones y formas de pago | ✅ Terminada y aprobada |
| Fase 3 – Sprint 3 | Inventario, reportes de daño, asistencia, pagos de clientes y saldos vencidos | Pendiente |
| Fase 4 – Sprint 4 | Nómina, proveedores, presupuestos, costos y PDF | Pendiente |

## Tecnologías

* **Backend:** PHP 8.3 puro con arquitectura MVC sencilla, sin frameworks.
* **Acceso a datos:** PDO con sentencias preparadas.
* **Base de datos:** MariaDB 10.11 (XAMPP 8.3.36), esquema `carpin_db`, InnoDB, utf8mb4.
* **Frontend:** HTML5, CSS3 y JavaScript nativo.
* **Estilos:** Bootstrap 5.3.3 y Bootstrap Icons 1.11.3 (locales en `public/assets/vendor`).
* **Gráficas:** Chart.js 4.4.9 (local).
* **Fuentes:** Merriweather (títulos) e Inter (texto), locales en `public/assets/vendor/fuentes`.
* **PDF:** Dompdf 3.x (única dependencia de Composer).
