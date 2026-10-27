-- =====================================================
-- Carpintería Carpin – schema.sql (MariaDB 10.11 de XAMPP 8.3.36, compatible con MySQL 8)
-- =====================================================
DROP DATABASE IF EXISTS carpin_db;
CREATE DATABASE carpin_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE carpin_db;
 
CREATE TABLE rol (
  id_rol TINYINT UNSIGNED NOT NULL,
  nombre VARCHAR(30) NOT NULL UNIQUE,
  PRIMARY KEY (id_rol)
) ENGINE=InnoDB;
 
CREATE TABLE usuario (
  id_usuario INT UNSIGNED NOT NULL AUTO_INCREMENT,
  username VARCHAR(30) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  id_rol TINYINT UNSIGNED NOT NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  intentos_fallidos TINYINT UNSIGNED NOT NULL DEFAULT 0,
  bloqueado_hasta DATETIME NULL,
  ultimo_acceso DATETIME NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_usuario),
  CONSTRAINT fk_usuario_id_rol FOREIGN KEY (id_rol) REFERENCES rol(id_rol)
) ENGINE=InnoDB;
 
CREATE TABLE trabajador (
  id_trabajador INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_usuario INT UNSIGNED NOT NULL UNIQUE,
  nombre_completo VARCHAR(120) NOT NULL,
  telefono VARCHAR(15) NULL,
  forma_pago ENUM('HORA','DIA','PROYECTO') NULL,
  tarifa_hora DECIMAL(10,2) NULL,
  salario_dia DECIMAL(10,2) NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (id_trabajador),
  CONSTRAINT fk_trabajador_id_usuario FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario)
) ENGINE=InnoDB;
 
CREATE TABLE cliente (
  id_cliente INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_usuario INT UNSIGNED NOT NULL UNIQUE,
  nombre_completo VARCHAR(120) NOT NULL,
  telefono VARCHAR(15) NOT NULL,
  correo VARCHAR(120) NULL,
  direccion VARCHAR(255) NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_cliente),
  CONSTRAINT fk_cliente_id_usuario FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario)
) ENGINE=InnoDB;
 
CREATE TABLE configuracion (
  id_config TINYINT UNSIGNED NOT NULL,
  nombre_negocio VARCHAR(100) NOT NULL,
  descripcion TEXT NULL,
  direccion VARCHAR(255) NULL,
  horario VARCHAR(150) NULL,
  telefono VARCHAR(15) NULL,
  whatsapp VARCHAR(15) NULL,
  mensaje_whatsapp VARCHAR(255) NULL,
  mapa_url TEXT NULL,
  logo_ruta VARCHAR(255) NULL,
  pct_mano_obra DECIMAL(5,2) NOT NULL DEFAULT 40.00,
  pct_ganancia DECIMAL(5,2) NOT NULL DEFAULT 30.00,
  actualizado_en DATETIME NULL,
  PRIMARY KEY (id_config)
) ENGINE=InnoDB;
 
CREATE TABLE porcentaje_rol (
  id_rol TINYINT UNSIGNED NOT NULL,
  porcentaje DECIMAL(5,2) NOT NULL,
  PRIMARY KEY (id_rol),
  CONSTRAINT fk_porcentaje_rol_id_rol FOREIGN KEY (id_rol) REFERENCES rol(id_rol)
) ENGINE=InnoDB;
 
CREATE TABLE categoria_catalogo (
  id_categoria INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(60) NOT NULL UNIQUE,
  PRIMARY KEY (id_categoria)
) ENGINE=InnoDB;
 
CREATE TABLE producto_catalogo (
  id_producto INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_categoria INT UNSIGNED NOT NULL,
  nombre VARCHAR(100) NOT NULL,
  descripcion TEXT NULL,
  precio_desde DECIMAL(10,2) NULL,
  imagen_ruta VARCHAR(255) NULL,
  destacado TINYINT(1) NOT NULL DEFAULT 0,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_producto),
  CONSTRAINT fk_producto_catalogo_id_categoria FOREIGN KEY (id_categoria) REFERENCES categoria_catalogo(id_categoria)
) ENGINE=InnoDB;
 
CREATE TABLE solicitud_cotizacion (
  id_solicitud INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(120) NOT NULL,
  telefono VARCHAR(15) NOT NULL,
  correo VARCHAR(120) NULL,
  id_producto INT UNSIGNED NULL,
  medidas VARCHAR(100) NULL,
  descripcion TEXT NOT NULL,
  estado ENUM('NUEVA','CONTACTADO','CONVERTIDA','DESCARTADA') NOT NULL DEFAULT 'NUEVA',
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_solicitud),
  CONSTRAINT fk_solicitud_cotizacion_id_producto FOREIGN KEY (id_producto) REFERENCES producto_catalogo(id_producto)
) ENGINE=InnoDB;
 
CREATE TABLE material (
  id_material INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(100) NOT NULL,
  categoria ENUM('MADERA','PINTURA','SOLVENTE','BARNIZ','LIJA','HERRAJE','PEGAMENTO','OTRO') NOT NULL,
  unidad ENUM('PIEZA','LITRO','METRO','KG','HOJA','CAJA') NOT NULL,
  existencia DECIMAL(10,2) NOT NULL DEFAULT 0,
  minimo DECIMAL(10,2) NOT NULL DEFAULT 3,
  costo_unitario DECIMAL(10,2) NOT NULL DEFAULT 0,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (id_material)
) ENGINE=InnoDB;
 
CREATE TABLE presupuesto (
  id_presupuesto INT UNSIGNED NOT NULL AUTO_INCREMENT,
  folio VARCHAR(15) NOT NULL UNIQUE,
  id_solicitud INT UNSIGNED NULL,
  id_cliente INT UNSIGNED NULL,
  nombre_cliente VARCHAR(120) NULL,
  descripcion TEXT NOT NULL,
  costo_materiales DECIMAL(10,2) NOT NULL,
  pct_mano_obra DECIMAL(5,2) NOT NULL,
  mano_obra DECIMAL(10,2) NOT NULL,
  pct_ganancia DECIMAL(5,2) NOT NULL,
  ganancia DECIMAL(10,2) NOT NULL,
  total DECIMAL(10,2) NOT NULL,
  id_usuario INT UNSIGNED NOT NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_presupuesto),
  CONSTRAINT fk_presupuesto_id_solicitud FOREIGN KEY (id_solicitud) REFERENCES solicitud_cotizacion(id_solicitud),
  CONSTRAINT fk_presupuesto_id_cliente FOREIGN KEY (id_cliente) REFERENCES cliente(id_cliente),
  CONSTRAINT fk_presupuesto_id_usuario FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario)
) ENGINE=InnoDB;
 
CREATE TABLE presupuesto_detalle (
  id_detalle INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_presupuesto INT UNSIGNED NOT NULL,
  id_material INT UNSIGNED NOT NULL,
  cantidad DECIMAL(10,2) NOT NULL,
  costo_unitario DECIMAL(10,2) NOT NULL,
  subtotal DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (id_detalle),
  CONSTRAINT fk_presupuesto_detalle_id_presupuesto FOREIGN KEY (id_presupuesto) REFERENCES presupuesto(id_presupuesto),
  CONSTRAINT fk_presupuesto_detalle_id_material FOREIGN KEY (id_material) REFERENCES material(id_material)
) ENGINE=InnoDB;
 
CREATE TABLE fase (
  id_fase TINYINT UNSIGNED NOT NULL,
  nombre VARCHAR(40) NOT NULL UNIQUE,
  orden TINYINT UNSIGNED NOT NULL UNIQUE,
  id_rol_responsable TINYINT UNSIGNED NOT NULL,
  porcentaje DECIMAL(5,2) NOT NULL,
  PRIMARY KEY (id_fase),
  CONSTRAINT fk_fase_id_rol_responsable FOREIGN KEY (id_rol_responsable) REFERENCES rol(id_rol)
) ENGINE=InnoDB;
 
CREATE TABLE proyecto (
  id_proyecto INT UNSIGNED NOT NULL AUTO_INCREMENT,
  folio VARCHAR(15) NOT NULL UNIQUE,
  id_cliente INT UNSIGNED NOT NULL,
  id_solicitud INT UNSIGNED NULL,
  id_presupuesto INT UNSIGNED NULL,
  tipo_mueble VARCHAR(60) NOT NULL,
  descripcion TEXT NULL,
  alto_cm DECIMAL(6,1) NULL,
  ancho_cm DECIMAL(6,1) NULL,
  fondo_cm DECIMAL(6,1) NULL,
  madera VARCHAR(60) NOT NULL,
  acabado VARCHAR(60) NULL,
  color VARCHAR(60) NULL,
  granulado_lija VARCHAR(40) NULL,
  ficha_color TEXT NULL,
  fecha_registro DATE NOT NULL,
  fecha_entrega DATE NOT NULL,
  fecha_limite_pago DATE NOT NULL,
  precio DECIMAL(10,2) NOT NULL,
  mano_obra DECIMAL(10,2) NULL,
  id_fase TINYINT UNSIGNED NOT NULL DEFAULT 1,
  porcentaje_avance DECIMAL(5,2) NOT NULL DEFAULT 11.11,
  status_pago ENUM('PENDIENTE','CON_ANTICIPO','PAGADO') NOT NULL DEFAULT 'PENDIENTE',
  entregado_en DATETIME NULL,
  id_usuario_registro INT UNSIGNED NOT NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_proyecto),
  CONSTRAINT fk_proyecto_id_cliente FOREIGN KEY (id_cliente) REFERENCES cliente(id_cliente),
  CONSTRAINT fk_proyecto_id_solicitud FOREIGN KEY (id_solicitud) REFERENCES solicitud_cotizacion(id_solicitud),
  CONSTRAINT fk_proyecto_id_presupuesto FOREIGN KEY (id_presupuesto) REFERENCES presupuesto(id_presupuesto),
  CONSTRAINT fk_proyecto_id_fase FOREIGN KEY (id_fase) REFERENCES fase(id_fase),
  CONSTRAINT fk_proyecto_id_usuario_registro FOREIGN KEY (id_usuario_registro) REFERENCES usuario(id_usuario)
) ENGINE=InnoDB;
 
CREATE TABLE actividad (
  id_actividad INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_proyecto INT UNSIGNED NOT NULL,
  id_trabajador INT UNSIGNED NOT NULL,
  tipo ENUM('ARMADO','LIJADO','PINTURA') NOT NULL,
  fecha_compromiso DATE NULL,
  estado ENUM('PENDIENTE','TERMINADA') NOT NULL DEFAULT 'PENDIENTE',
  asignada_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  terminada_en DATETIME NULL,
  PRIMARY KEY (id_actividad),
  UNIQUE KEY uq_actividad (id_proyecto, id_trabajador, tipo),
  CONSTRAINT fk_actividad_id_proyecto FOREIGN KEY (id_proyecto) REFERENCES proyecto(id_proyecto),
  CONSTRAINT fk_actividad_id_trabajador FOREIGN KEY (id_trabajador) REFERENCES trabajador(id_trabajador)
) ENGINE=InnoDB;
 
CREATE TABLE historial_fase (
  id_historial INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_proyecto INT UNSIGNED NOT NULL,
  id_fase_anterior TINYINT UNSIGNED NULL,
  id_fase_nueva TINYINT UNSIGNED NOT NULL,
  id_usuario INT UNSIGNED NOT NULL,
  motivo VARCHAR(255) NULL,
  fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_historial),
  CONSTRAINT fk_historial_fase_id_proyecto FOREIGN KEY (id_proyecto) REFERENCES proyecto(id_proyecto),
  CONSTRAINT fk_historial_fase_id_fase_anterior FOREIGN KEY (id_fase_anterior) REFERENCES fase(id_fase),
  CONSTRAINT fk_historial_fase_id_fase_nueva FOREIGN KEY (id_fase_nueva) REFERENCES fase(id_fase),
  CONSTRAINT fk_historial_fase_id_usuario FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario)
) ENGINE=InnoDB;
 
CREATE TABLE capa_pintura (
  id_capa INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_proyecto INT UNSIGNED NOT NULL,
  numero_capa TINYINT UNSIGNED NOT NULL,
  producto VARCHAR(80) NOT NULL,
  observaciones VARCHAR(255) NULL,
  id_trabajador INT UNSIGNED NOT NULL,
  fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_capa),
  CONSTRAINT fk_capa_pintura_id_proyecto FOREIGN KEY (id_proyecto) REFERENCES proyecto(id_proyecto),
  CONSTRAINT fk_capa_pintura_id_trabajador FOREIGN KEY (id_trabajador) REFERENCES trabajador(id_trabajador)
) ENGINE=InnoDB;
 
CREATE TABLE secado (
  id_secado INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_proyecto INT UNSIGNED NOT NULL,
  inicio DATETIME NOT NULL,
  horas DECIMAL(5,2) NOT NULL,
  fin_estimado DATETIME NOT NULL,
  notificado TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (id_secado),
  CONSTRAINT fk_secado_id_proyecto FOREIGN KEY (id_proyecto) REFERENCES proyecto(id_proyecto)
) ENGINE=InnoDB;
 
CREATE TABLE reparacion (
  id_reparacion INT UNSIGNED NOT NULL AUTO_INCREMENT,
  folio VARCHAR(15) NOT NULL UNIQUE,
  id_cliente INT UNSIGNED NOT NULL,
  descripcion_mueble VARCHAR(150) NOT NULL,
  tamano ENUM('CHICO','MEDIANO','GRANDE') NOT NULL,
  tipo_reparacion VARCHAR(80) NOT NULL,
  descripcion_danio TEXT NULL,
  costo DECIMAL(10,2) NOT NULL,
  estado ENUM('RECIBIDO','EN_REPARACION','LISTA','ENTREGADA') NOT NULL DEFAULT 'RECIBIDO',
  porcentaje_avance DECIMAL(5,2) NOT NULL DEFAULT 25.00,
  fecha_recepcion DATE NOT NULL,
  fecha_estimada DATE NOT NULL,
  fecha_limite_pago DATE NOT NULL,
  status_pago ENUM('PENDIENTE','CON_ANTICIPO','PAGADO') NOT NULL DEFAULT 'PENDIENTE',
  entregada_en DATETIME NULL,
  id_usuario_registro INT UNSIGNED NOT NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_reparacion),
  CONSTRAINT fk_reparacion_id_cliente FOREIGN KEY (id_cliente) REFERENCES cliente(id_cliente),
  CONSTRAINT fk_reparacion_id_usuario_registro FOREIGN KEY (id_usuario_registro) REFERENCES usuario(id_usuario)
) ENGINE=InnoDB;
 
CREATE TABLE imagen_reparacion (
  id_imagen INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_reparacion INT UNSIGNED NOT NULL,
  ruta VARCHAR(255) NOT NULL,
  tipo ENUM('ANTES','DESPUES') NOT NULL DEFAULT 'ANTES',
  subida_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_imagen),
  CONSTRAINT fk_imagen_reparacion_id_reparacion FOREIGN KEY (id_reparacion) REFERENCES reparacion(id_reparacion)
) ENGINE=InnoDB;
 
CREATE TABLE movimiento_inventario (
  id_movimiento INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_material INT UNSIGNED NOT NULL,
  tipo ENUM('ENTRADA','SALIDA') NOT NULL,
  cantidad DECIMAL(10,2) NOT NULL,
  costo_unitario DECIMAL(10,2) NOT NULL,
  id_proyecto INT UNSIGNED NULL,
  id_reparacion INT UNSIGNED NULL,
  id_usuario INT UNSIGNED NOT NULL,
  nota VARCHAR(255) NULL,
  fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_movimiento),
  CONSTRAINT fk_movimiento_inventario_id_material FOREIGN KEY (id_material) REFERENCES material(id_material),
  CONSTRAINT fk_movimiento_inventario_id_proyecto FOREIGN KEY (id_proyecto) REFERENCES proyecto(id_proyecto),
  CONSTRAINT fk_movimiento_inventario_id_reparacion FOREIGN KEY (id_reparacion) REFERENCES reparacion(id_reparacion),
  CONSTRAINT fk_movimiento_inventario_id_usuario FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario)
) ENGINE=InnoDB;
 
CREATE TABLE reporte_danio (
  id_reporte INT UNSIGNED NOT NULL AUTO_INCREMENT,
  elemento VARCHAR(100) NOT NULL,
  tipo ENUM('MATERIAL','HERRAMIENTA') NOT NULL,
  descripcion TEXT NOT NULL,
  accion ENUM('REPARAR','REEMPLAZAR') NOT NULL,
  foto_ruta VARCHAR(255) NULL,
  estado ENUM('REPORTADO','EN_ATENCION','RESUELTO') NOT NULL DEFAULT 'REPORTADO',
  id_usuario_reporta INT UNSIGNED NOT NULL,
  fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  resuelto_en DATETIME NULL,
  PRIMARY KEY (id_reporte),
  CONSTRAINT fk_reporte_danio_id_usuario_reporta FOREIGN KEY (id_usuario_reporta) REFERENCES usuario(id_usuario)
) ENGINE=InnoDB;
 
CREATE TABLE asistencia (
  id_asistencia INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_trabajador INT UNSIGNED NOT NULL,
  fecha DATE NOT NULL,
  hora_entrada TIME NOT NULL,
  hora_salida TIME NOT NULL,
  horas DECIMAL(5,2) NOT NULL,
  id_usuario_captura INT UNSIGNED NOT NULL,
  PRIMARY KEY (id_asistencia),
  UNIQUE KEY uq_asistencia (id_trabajador, fecha),
  CONSTRAINT fk_asistencia_id_trabajador FOREIGN KEY (id_trabajador) REFERENCES trabajador(id_trabajador),
  CONSTRAINT fk_asistencia_id_usuario_captura FOREIGN KEY (id_usuario_captura) REFERENCES usuario(id_usuario)
) ENGINE=InnoDB;
 
CREATE TABLE nomina (
  id_nomina INT UNSIGNED NOT NULL AUTO_INCREMENT,
  periodo_inicio DATE NOT NULL,
  periodo_fin DATE NOT NULL,
  total DECIMAL(12,2) NOT NULL,
  estado ENUM('CALCULADA','PAGADA') NOT NULL DEFAULT 'CALCULADA',
  metodo ENUM('EFECTIVO','TARJETA','TRANSFERENCIA') NULL,
  pagada_en DATETIME NULL,
  id_usuario INT UNSIGNED NOT NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_nomina),
  CONSTRAINT fk_nomina_id_usuario FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario)
) ENGINE=InnoDB;
 
CREATE TABLE nomina_detalle (
  id_detalle INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_nomina INT UNSIGNED NOT NULL,
  id_trabajador INT UNSIGNED NOT NULL,
  forma_pago ENUM('HORA','DIA','PROYECTO') NOT NULL,
  horas DECIMAL(7,2) NOT NULL DEFAULT 0,
  dias SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  monto_tiempo DECIMAL(10,2) NOT NULL DEFAULT 0,
  monto_proyectos DECIMAL(10,2) NOT NULL DEFAULT 0,
  total DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (id_detalle),
  CONSTRAINT fk_nomina_detalle_id_nomina FOREIGN KEY (id_nomina) REFERENCES nomina(id_nomina),
  CONSTRAINT fk_nomina_detalle_id_trabajador FOREIGN KEY (id_trabajador) REFERENCES trabajador(id_trabajador)
) ENGINE=InnoDB;
 
CREATE TABLE pago_proyecto_trabajador (
  id_pago_pt INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_proyecto INT UNSIGNED NOT NULL,
  id_trabajador INT UNSIGNED NOT NULL,
  id_rol TINYINT UNSIGNED NOT NULL,
  porcentaje_rol DECIMAL(5,2) NOT NULL,
  monto DECIMAL(10,2) NOT NULL,
  id_nomina INT UNSIGNED NULL,
  generado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_pago_pt),
  CONSTRAINT fk_pago_proyecto_trabajador_id_proyecto FOREIGN KEY (id_proyecto) REFERENCES proyecto(id_proyecto),
  CONSTRAINT fk_pago_proyecto_trabajador_id_trabajador FOREIGN KEY (id_trabajador) REFERENCES trabajador(id_trabajador),
  CONSTRAINT fk_pago_proyecto_trabajador_id_rol FOREIGN KEY (id_rol) REFERENCES rol(id_rol),
  CONSTRAINT fk_pago_proyecto_trabajador_id_nomina FOREIGN KEY (id_nomina) REFERENCES nomina(id_nomina)
) ENGINE=InnoDB;
 
CREATE TABLE pago_cliente (
  id_pago INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_proyecto INT UNSIGNED NULL,
  id_reparacion INT UNSIGNED NULL,
  monto DECIMAL(10,2) NOT NULL,
  metodo ENUM('EFECTIVO','TARJETA','TRANSFERENCIA') NOT NULL,
  fecha DATE NOT NULL,
  referencia VARCHAR(60) NULL,
  id_usuario INT UNSIGNED NOT NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_pago),
  CONSTRAINT chk_pago_destino CHECK ((id_proyecto IS NULL) <> (id_reparacion IS NULL)),
  CONSTRAINT fk_pago_cliente_id_proyecto FOREIGN KEY (id_proyecto) REFERENCES proyecto(id_proyecto),
  CONSTRAINT fk_pago_cliente_id_reparacion FOREIGN KEY (id_reparacion) REFERENCES reparacion(id_reparacion),
  CONSTRAINT fk_pago_cliente_id_usuario FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario)
) ENGINE=InnoDB;
 
CREATE TABLE comprobante_cliente (
  id_comprobante INT UNSIGNED NOT NULL AUTO_INCREMENT,
  folio VARCHAR(15) NOT NULL UNIQUE,
  id_proyecto INT UNSIGNED NULL,
  id_reparacion INT UNSIGNED NULL,
  ruta_pdf VARCHAR(255) NOT NULL,
  id_usuario INT UNSIGNED NOT NULL,
  generada_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_comprobante),
  CONSTRAINT fk_comprobante_cliente_id_proyecto FOREIGN KEY (id_proyecto) REFERENCES proyecto(id_proyecto),
  CONSTRAINT fk_comprobante_cliente_id_reparacion FOREIGN KEY (id_reparacion) REFERENCES reparacion(id_reparacion),
  CONSTRAINT fk_comprobante_cliente_id_usuario FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario)
) ENGINE=InnoDB;
 
CREATE TABLE proveedor (
  id_proveedor INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(120) NOT NULL,
  contacto VARCHAR(120) NULL,
  telefono VARCHAR(15) NULL,
  correo VARCHAR(120) NULL,
  productos TEXT NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (id_proveedor)
) ENGINE=InnoDB;
 
CREATE TABLE comprobante_proveedor (
  id_comprobante_prov INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_proveedor INT UNSIGNED NOT NULL,
  folio VARCHAR(40) NOT NULL,
  fecha DATE NOT NULL,
  fecha_vencimiento DATE NOT NULL,
  monto DECIMAL(12,2) NOT NULL,
  concepto VARCHAR(255) NULL,
  pagado TINYINT(1) NOT NULL DEFAULT 0,
  pagado_en DATE NULL,
  archivo_ruta VARCHAR(255) NOT NULL,
  id_usuario INT UNSIGNED NOT NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_comprobante_prov),
  UNIQUE KEY uq_comprobante_prov (id_proveedor, folio),
  CONSTRAINT fk_comprobante_proveedor_id_proveedor FOREIGN KEY (id_proveedor) REFERENCES proveedor(id_proveedor),
  CONSTRAINT fk_comprobante_proveedor_id_usuario FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario)
) ENGINE=InnoDB;
 
CREATE TABLE notificacion (
  id_notificacion INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_usuario INT UNSIGNED NOT NULL,
  tipo VARCHAR(40) NOT NULL,
  mensaje VARCHAR(255) NOT NULL,
  enlace VARCHAR(255) NULL,
  clave_unica VARCHAR(100) NULL,
  leida TINYINT(1) NOT NULL DEFAULT 0,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_notificacion),
  UNIQUE KEY uq_notif (id_usuario, clave_unica),
  CONSTRAINT fk_notificacion_id_usuario FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario)
) ENGINE=InnoDB;
