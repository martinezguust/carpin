# Carpintería Carpin – Sistema Web de Gestión

Sistema web para gestionar una carpintería familiar (muebles a pedido y reparaciones).
Proyecto de la experiencia educativa **Gestión de Procesos de Desarrollo de Software**,
Facultad de Estadística e Informática, Universidad Veracruzana. **Equipo 6** · Scrum en 4 Sprints.

La especificación completa está en [`docs/SRS.docx`](docs/SRS.docx) y es la única fuente de verdad.

---

## Estado del proyecto

| Fase | Contenido | Estado |
|------|-----------|--------|
| Fase 0 – Setup | Estructura, Composer + Dompdf, configuración, core, base de datos y datos de prueba | ✅ Terminada y aprobada |
| Fase 1 – Sprint 1 | Acceso por roles, usuarios, proyectos, actividades, fases, portal del cliente y notificaciones | ✅ Terminada y aprobada |
| Fase 2 – Sprint 2 | Producción, página pública, cotizaciones, reparaciones y formas de pago | ✅ Terminada y aprobada |
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

   Debe terminar con `RESULTADO: todo correcto. La instalación está lista.`

   Para regresar la base a sus datos de prueba originales se repiten los pasos 3 y 4.

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

## Pantallas disponibles (hasta la Fase 2)

**Página pública** (sin sesión):

- **Inicio:** portada, productos destacados, quiénes somos, servicios y "Visítanos" (mapa y,
  debajo, dirección, horario y teléfono).
- **Catálogo** con filtro por categoría.
- **Nosotros (`/nosotros`):** quiénes somos, misión, visión, valores y servicios. Misión,
  visión y valores son texto fijo en la vista (no se guardan en la base de datos).
- **Contacto (`/contacto`):** dirección, horario, teléfono, mapa, botón de WhatsApp y
  "Solicitar cotización".
- **Solicitar cotización** e **Iniciar sesión**.

Botón flotante de WhatsApp cuando el Administrador captura el número. Lo que el
Administrador no captura no se muestra. Con sesión, el botón "Iniciar sesión" cambia a
"Mi panel".

Al iniciar sesión cada rol llega a su pantalla de inicio. Todos tienen la campana de
notificaciones y el menú **Mi cuenta** (Cambiar contraseña / Cerrar sesión). Los módulos de
fases posteriores todavía no aparecen en los menús.

| Rol | Pantalla de inicio | Menú |
|-----|--------------------|------|
| Administrador | `/admin` | Panel · Usuarios · Catálogo (alta, edición con imagen, ocultar/mostrar, eliminar) · Datos de la carpintería (datos, WhatsApp, mapa, cambiar/quitar logo) |
| Carpintero | `/tablero` | Panel (tablero con las 9 fases) · Proyectos · Reparaciones (registro con fotos, estados, fotos de "después") · Cotizaciones (bandeja con número de nuevas, convertir en proyecto) · Precios y porcentajes |
| Lijador | `/mis-pendientes` | Mis pendientes (granulado de lija y botón "Marcar como terminada") |
| Pintor | `/mis-pendientes` | Mis pendientes (ficha de color, capas, secado con cuenta regresiva y botón "Marcar como terminada") |
| Contador | `/panel` | Panel · Trabajadores y formas de pago · Porcentajes |
| Cliente | `/mis-pedidos` | Mis pedidos (proyectos y reparaciones, gráfica de dona, etapas, fotos y resumen de pago) |

### Aclaraciones aplicadas en la Fase 2 (aprobadas por el Product Owner)

- Avance por trabajador (RN-02): Lijador 4→5→6, Pintor 6→7→8→9. Para pasar de "Pintado" a
  "Secado" hay que programar el secado; para pasar a "Listo para entrega" antes de que termine
  el secado se pide confirmar. Cuenta el secado más reciente desde la última entrada a "Pintado".
- Capas y secado solo en la fase "Pintado". La capa lleva número automático y el producto se
  prellena con el de la ficha. El aviso "terminó el secado" se genera al iniciar sesión o al
  consultar la campana, solo si el proyecto sigue en "Pintado" o "Secado".
- Portada con textos fijos y degradado de la paleta; lo que el Administrador no captura no se
  muestra. "Nosotros" y "Contacto" son páginas separadas (cambio del 27/10/2026 que reemplaza
  la página única con anclas de la aclaración 5.4). El mapa solo acepta direcciones `https://www.google.com/maps/embed…`; WhatsApp de 12 a
  15 dígitos; teléfono de 10.
- Un producto solo se elimina (con su imagen) si ninguna cotización lo menciona; si no, se oculta.
- Cotizaciones: Nueva → Contactado → Convertida / Descartada. "Convertir en proyecto" precarga
  nombre, teléfono, correo, producto (como tipo de mueble) y descripción con medidas.
- Reparaciones: estados solo hacia adelante; al entregar con saldo se pide confirmar y se avisa
  al Contador. De 1 a 6 fotos de "antes" al registrar (las inválidas se rechazan); las de
  "después" se suben luego sin pasar de 6 en total.
- Tarifas y porcentajes: los configuran el Contador y el Carpintero (RN-08), pero **el
  Carpintero no puede editar su propia tarifa**. Porcentajes de 0 a 100; los de rol suman 100 %.
- El inicio de un secado no puede ser anterior a la entrada a "Pintado"; si se reprograma, el
  secado anterior ya no genera su aviso. Los textos de "Servicios" son fijos.

### Aclaraciones aplicadas en la Fase 1 (aprobadas por el Product Owner)

- Bloqueo de 10 minutos al quinto intento fallido. "Cuenta desactivada" solo se informa
  cuando la contraseña es correcta.
- El Administrador puede reactivar cuentas, cambiar nombres de usuario y editar clientes;
  no puede desactivarse ni quitarse el rol a sí mismo. Teléfono de 10 dígitos obligatorio.
- "Por proyecto" solo para Lijador y Pintor; el Administrador nunca tiene forma de pago.
- Avance normal del Carpintero: 1→2→3→4 y 9→10. Cualquier otro cambio es una corrección
  con motivo obligatorio. Al entregar con saldo pendiente se pide confirmación (RN-04).
- Las actividades se terminan al llegar a su fase (Armado 4, Lijado 6, Pintura 9) y vuelven a
  pendientes si una corrección regresa el proyecto antes de esa fase.
- Notificaciones: tabla 5.2 del SRS más, en correcciones a otras fases, los trabajadores
  asignados que deben actuar ahí. Se avisa a **todos** los usuarios activos del rol.
  La campana muestra las 10 más recientes y se actualiza cada 60 s sin contar como actividad
  de la sesión.
- La contraseña del cliente nuevo se muestra una sola vez en la hoja de credenciales.
- No se notifica a quien hizo la acción (por ejemplo, el Carpintero que corrige una fase).
- La fecha compromiso de una actividad no puede ser anterior a hoy.
- Si se asigna una actividad cuando el proyecto ya pasó su fase de término, nace terminada.
- Si el Administrador desactiva una cuenta con sesión abierta, esa sesión se cierra en la
  siguiente pantalla que abra.

---

## Estructura del proyecto (SRS 14.2)

```
carpin/
├── .htaccess          ← redirige /carpin/ a public/ y bloquea todo lo demás
├── public/            ← única carpeta expuesta por Apache
│   ├── index.php      ← front controller
│   ├── .htaccess      ← reescritura a index.php
│   ├── assets/        ← css/carpin.css, js/app.js, js/avance.js, img/,
│   │                     vendor/ (Bootstrap, íconos, Chart.js, fuentes)
│   └── uploads/       ← archivos subidos (nunca se ejecuta PHP aquí)
│       ├── comprobantes/   ← bloqueada: se entrega por controlador
│       └── archivos_prov/  ← bloqueada: se entrega por controlador
├── app/
│   ├── core/          ← Router, Controller, Model, Database, Auth, Csrf, View, Upload, Flash
│   ├── controllers/   ← uno por módulo (Auth, Usuario, Proyecto, Actividad, Panel, …)
│   ├── models/        ← un modelo por tabla principal
│   ├── services/      ← reglas de negocio (FaseService, PagoService, FolioService, …)
│   └── views/         ← layouts/, publico/, admin/, carpintero/, lijador/, pintor/,
│                         contador/, cliente/, comun/ (pantallas de todos los roles),
│                         pdf/, errores/ (403, 404, 500)
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
