# Carpintería Carpin – Sistema Web de Gestión

Sistema web para gestionar una carpintería familiar (muebles a pedido y reparaciones).
Proyecto de la experiencia educativa **Gestión de Procesos de Desarrollo de Software**,
Facultad de Estadística e Informática, Universidad Veracruzana. **Equipo 6** · Scrum en 4 Sprints.

La especificación completa está en [`docs/SRS.docx`](docs/SRS.docx) y es la única fuente de verdad.

---

## Estado del proyecto

| Fase | Contenido | Estado |
|------|-----------|--------|
| Fase 0 – Setup | Estructura, Composer + Dompdf, configuración, core, base de datos y datos de prueba | ✅ Terminada (pendiente de revisión del equipo) |
| Fase 1 – Sprint 1 | Acceso por roles, usuarios, proyectos, actividades, fases, portal del cliente y notificaciones | Pendiente |
| Fase 2 – Sprint 2 | Producción, página pública, cotizaciones, reparaciones y formas de pago | Pendiente |
| Fase 3 – Sprint 3 | Inventario, reportes de daño, asistencia, pagos de clientes y saldos vencidos | Pendiente |
| Fase 4 – Sprint 4 | Nómina, proveedores, presupuestos, costos y PDF | Pendiente |

---

## Tecnologías (SRS sección IX)

- **Backend:** PHP 8.3 puro con arquitectura MVC sencilla, sin frameworks.
- **Acceso a datos:** PDO con sentencias preparadas.
- **Base de datos:** MariaDB 10.11 (XAMPP 8.3.36), esquema `carpin_db`, InnoDB, utf8mb4.
- **Frontend:** HTML5, CSS3 y JavaScript nativo.
- **Estilos:** Bootstrap 5.3.3 y Bootstrap Icons 1.11.3 (locales en `public/assets/vendor`).
- **Gráficas:** Chart.js 4.4.9 (local).
- **Fuentes:** Merriweather (títulos) e Inter (texto), locales en `public/assets/vendor/fuentes`.
- **PDF:** Dompdf 3.x (única dependencia de Composer).

---

## Requisitos

- XAMPP 8.3.36 instalado en `C:\xampp` con Apache y MySQL (MariaDB) encendidos.
- PHP de XAMPP en el PATH (`php -v`) y Composer 2 (`composer -V`).
- En `C:\xampp\php\php.ini` deben estar activas (sin `;` al inicio) estas líneas:

  ```ini
  extension=gd
  extension=zip
  ```

  Después de cambiar `php.ini` hay que **reiniciar Apache** desde el XAMPP Control Panel.
  `gd` la usa Dompdf para las imágenes de los PDF y `zip` la usa Composer para instalar paquetes.

---

## Instalación

Todos los comandos se ejecutan desde la carpeta del proyecto (`C:\xampp\htdocs\carpin`).

1. **Instalar Dompdf con Composer**

   ```bash
   composer install
   ```

2. **Crear la configuración local** copiando la plantilla (ya trae los datos de XAMPP:
   host `127.0.0.1`, puerto `3306`, usuario `root` sin contraseña):

   ```bash
   copy config\config.example.php config\config.php
   ```

3. **Crear la base de datos** `carpin_db` con sus 31 tablas y los datos iniciales.
   ⚠️ `schema.sql` borra la base `carpin_db` si ya existe.

   ```bash
   C:\xampp\mysql\bin\mysql -u root --default-character-set=utf8mb4 < database\schema.sql
   C:\xampp\mysql\bin\mysql -u root --default-character-set=utf8mb4 < database\seed.sql
   ```

   (También se pueden importar los dos archivos, en ese orden, desde
   http://localhost/phpmyadmin → pestaña **Importar**.)

4. **Insertar los usuarios y datos de prueba** (contraseñas cifradas con bcrypt):

   ```bash
   php database/seed.php
   ```

5. **Verificar la instalación:**

   ```bash
   php database/verificar.php
   ```

   Debe terminar con `RESULTADO: todo correcto. La Fase 0 está lista.`

6. **Abrir el sistema** en http://localhost/carpin/public
   (http://localhost/carpin/ redirige ahí automáticamente).

---

## Usuarios de prueba (SRS 14.5)

Todos usan la contraseña **`Carpin2026`**.

| Usuario | Rol | Nombre | Forma de pago |
|---------|-----|--------|---------------|
| `admin` | Administrador | Administrador General | — (fuera de nómina) |
| `carpintero` | Carpintero | Carlos Hernández | Por día, $500.00 |
| `lijador1` | Lijador | Juan Pérez | Por proyecto |
| `pintor1` | Pintor | Ana Ruiz | Por hora, $60.00 |
| `contador` | Contador | Laura Gómez | Por día, $450.00 |
| `cliente1` | Cliente | María López | — |

Datos de ejemplo incluidos:

- 6 productos de catálogo (sin imagen) y 8 materiales.
- **PRY-2026-0001** – Ropero de cedro, fase "Listo para pintura" (66.67 %), $12,500.00, anticipo $5,000.00.
- **PRY-2026-0002** – Mesa de comedor de pino, fase "En armado" (22.22 %), $4,500.00, anticipo $2,000.00.
- Una notificación sin leer para `pintor1`.

> Estas cuentas son solo para desarrollo y pruebas; deben cambiarse o eliminarse
> antes de usar el sistema con datos reales.

---

## Estructura del proyecto (SRS 14.2)

```
carpin/
├── .htaccess          ← redirige /carpin/ a public/ y bloquea todo lo demás
├── public/            ← única carpeta expuesta por Apache
│   ├── index.php      ← front controller
│   ├── .htaccess      ← reescritura a index.php
│   ├── assets/        ← css/carpin.css, js/, img/, vendor/ (Bootstrap, íconos, Chart.js, fuentes)
│   └── uploads/       ← archivos subidos (nunca se ejecuta PHP aquí)
│       ├── comprobantes/   ← bloqueada: se entrega por controlador
│       └── archivos_prov/  ← bloqueada: se entrega por controlador
├── app/
│   ├── core/          ← Router, Controller, Model, Database, Auth, Csrf, View, Upload, Flash
│   ├── controllers/
│   ├── models/
│   ├── services/
│   └── views/         ← layouts/, publico/, admin/, …, pdf/, errores/ (403, 404, 500)
├── config/            ← config.example.php, config.php (no se sube a Git), rutas.php
├── database/          ← schema.sql, seed.sql, seed.php, verificar.php, migraciones/
├── docs/              ← SRS.docx y diagramas/*.puml
├── vendor/            ← Composer (Dompdf)
└── composer.json
```

---

## Reglas de seguridad aplicadas desde la Fase 0

- Conexión PDO con sentencias preparadas reales y zona horaria `-06:00`.
- Zona horaria de PHP `America/Mexico_City`.
- Sesión con cookies HttpOnly y SameSite=Lax, cierre por 60 minutos de inactividad.
- Token CSRF validado en **todo** POST antes de llegar al controlador.
- Validación de rol en el servidor con `Auth::requireRol([...])` (responde 403).
- Función `e()` para escapar toda salida HTML.
- Subidas con `Upload::guardar()`: tipo MIME real, extensión, máximo 5 MB y nombre aleatorio.
- `public/uploads` no ejecuta PHP; `comprobantes/` y `archivos_prov/` no se sirven por enlace directo.

---

## Cambios a la base de datos

El esquema es exactamente el de la sección XI del SRS. Cualquier cambio aprobado se documenta
como `ALTER TABLE` en `database/migraciones/` y en esta sección.

- Sin cambios hasta el momento.

---

## Control de versiones

- Rama `main` y una rama por Sprint: `sprint-1`, `sprint-2`, `sprint-3`, `sprint-4`.
- Al cerrar cada Sprint se hace commit con el mensaje `Sprint N: <Sprint Goal>` y merge a `main`.
