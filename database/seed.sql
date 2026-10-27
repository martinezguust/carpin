-- =====================================================
-- seed.sql – datos iniciales y de prueba
-- Contraseñas de prueba: Carpin2026 (generar el hash con
-- password_hash('Carpin2026', PASSWORD_BCRYPT) en seed.php)
-- =====================================================
USE carpin_db;
INSERT INTO rol VALUES (1,'ADMINISTRADOR'),(2,'CARPINTERO'),(3,'LIJADOR'),
  (4,'PINTOR'),(5,'CONTADOR'),(6,'CLIENTE');
 
INSERT INTO fase (id_fase,nombre,orden,id_rol_responsable,porcentaje) VALUES
 (1,'Cotizado',1,2,11.11),(2,'En armado',2,2,22.22),(3,'Listo sin armar',3,2,33.33),
 (4,'Ensamblado',4,2,44.44),(5,'Lijado',5,3,55.56),(6,'Listo para pintura',6,3,66.67),
 (7,'Pintado',7,4,77.78),(8,'Secado',8,4,88.89),(9,'Listo para entrega',9,4,100.00),
 (10,'Entregado',10,2,100.00);
 
INSERT INTO porcentaje_rol VALUES (3,45.00),(4,55.00);
 
INSERT INTO configuracion (id_config,nombre_negocio,descripcion,horario,mensaje_whatsapp)
VALUES (1,'Carpintería Carpin','Carpintería familiar: muebles a la medida y reparaciones.',
 'Lunes a viernes 9:00–18:00, sábado 9:00–14:00',
 'Hola, me interesa cotizar un mueble.');
 
INSERT INTO categoria_catalogo (nombre) VALUES ('Mesas'),('Sillas'),
 ('Cocinas integrales'),('Estantes'),('Roperos'),('Otros');
 
-- Usuarios de prueba (el hash se inserta desde database/seed.php):
-- admin (ADMINISTRADOR), carpintero (CARPINTERO), lijador1 (LIJADOR),
-- pintor1 (PINTOR), contador (CONTADOR), cliente1 (CLIENTE)
-- Materiales de ejemplo:
INSERT INTO material (nombre,categoria,unidad,existencia,minimo,costo_unitario) VALUES
 ('Tabla de pino 1" x 12" x 8 ft','MADERA','PIEZA',20,3,380.00),
 ('Hoja de MDF 15 mm','MADERA','HOJA',8,3,690.00),
 ('Barniz poliuretano transparente','BARNIZ','LITRO',6,3,210.00),
 ('Thinner estándar','SOLVENTE','LITRO',10,4,65.00),
 ('Pintura esmalte nogal','PINTURA','LITRO',4,3,240.00),
 ('Lija de agua grano 120','LIJA','HOJA',50,10,12.00),
 ('Bisagra cierre lento','HERRAJE','PIEZA',30,6,45.00),
 ('Pegamento blanco para madera','PEGAMENTO','LITRO',5,2,95.00);
