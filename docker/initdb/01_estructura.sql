-- Base de datos SIGSM - Nordlyn
-- Ejecutar como usuario con permisos de creación de bases de datos.
CREATE DATABASE IF NOT EXISTS sigsm_nordlyn CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sigsm_nordlyn;

CREATE TABLE IF NOT EXISTS roles (
 id INT AUTO_INCREMENT PRIMARY KEY,
 nombre VARCHAR(30) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS usuarios (
 id INT AUTO_INCREMENT PRIMARY KEY,
 usuario VARCHAR(30) NOT NULL UNIQUE,
 cedula VARCHAR(20) NULL UNIQUE,
 nombre VARCHAR(80) NOT NULL,
 apellido VARCHAR(80) NOT NULL,
 correo VARCHAR(150) NOT NULL UNIQUE,
 celular VARCHAR(30) NULL,
 password_hash VARCHAR(255) NOT NULL,
 rol_id INT NOT NULL,
 activo TINYINT(1) NOT NULL DEFAULT 1,
 creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY (rol_id) REFERENCES roles(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS categorias (
 id INT AUTO_INCREMENT PRIMARY KEY,
 nombre VARCHAR(90) NOT NULL UNIQUE,
 descripcion VARCHAR(255) NULL,
 grupo VARCHAR(90) NOT NULL DEFAULT 'Especialidades Clínicas',
 grupo_orden SMALLINT NOT NULL DEFAULT 1,
 orden SMALLINT NOT NULL DEFAULT 0
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS usuario_categoria (
 usuario_id INT NOT NULL,
 categoria_id INT NOT NULL,
 PRIMARY KEY(usuario_id,categoria_id),
 FOREIGN KEY(usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
 FOREIGN KEY(categoria_id) REFERENCES categorias(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS documentos (
 id INT AUTO_INCREMENT PRIMARY KEY,
 titulo VARCHAR(180) NOT NULL,
 descripcion TEXT NULL,
 archivo VARCHAR(255) NOT NULL,
 nombre_original VARCHAR(255) NOT NULL,
 autor_id INT NOT NULL,
 estado ENUM('publicado','oculto','baja') NOT NULL DEFAULT 'publicado',
 creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 FOREIGN KEY(autor_id) REFERENCES usuarios(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS documento_categoria (
 documento_id INT NOT NULL,
 categoria_id INT NOT NULL,
 PRIMARY KEY(documento_id,categoria_id),
 FOREIGN KEY(documento_id) REFERENCES documentos(id) ON DELETE CASCADE,
 FOREIGN KEY(categoria_id) REFERENCES categorias(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS versiones_documento (
 id INT AUTO_INCREMENT PRIMARY KEY,
 documento_id INT NOT NULL,
 archivo VARCHAR(255) NOT NULL,
 descripcion_cambio VARCHAR(255) NULL,
 usuario_id INT NOT NULL,
 creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(documento_id) REFERENCES documentos(id) ON DELETE CASCADE,
 FOREIGN KEY(usuario_id) REFERENCES usuarios(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS encuestas (
 id INT AUTO_INCREMENT PRIMARY KEY,
 titulo VARCHAR(180) NOT NULL,
 descripcion TEXT NULL,
 publico ENUM('publico','personal') NOT NULL DEFAULT 'publico',
 estado ENUM('borrador','publicada','cerrada') NOT NULL DEFAULT 'borrador',
 creador_id INT NOT NULL,
 creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 FOREIGN KEY(creador_id) REFERENCES usuarios(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS encuesta_categoria (
 encuesta_id INT NOT NULL,
 categoria_id INT NOT NULL,
 PRIMARY KEY(encuesta_id,categoria_id),
 FOREIGN KEY(encuesta_id) REFERENCES encuestas(id) ON DELETE CASCADE,
 FOREIGN KEY(categoria_id) REFERENCES categorias(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS preguntas (
 id INT AUTO_INCREMENT PRIMARY KEY,
 encuesta_id INT NOT NULL,
 enunciado TEXT NOT NULL,
 tipo ENUM('unica','multiple','select','valoracion','texto') NOT NULL,
 obligatoria TINYINT(1) NOT NULL DEFAULT 1,
 orden INT NOT NULL DEFAULT 1,
 FOREIGN KEY(encuesta_id) REFERENCES encuestas(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS opciones_pregunta (
 id INT AUTO_INCREMENT PRIMARY KEY,
 pregunta_id INT NOT NULL,
 texto VARCHAR(180) NOT NULL,
 orden INT NOT NULL DEFAULT 1,
 FOREIGN KEY(pregunta_id) REFERENCES preguntas(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS envios_encuesta (
 id BIGINT AUTO_INCREMENT PRIMARY KEY,
 encuesta_id INT NOT NULL,
 token_envio CHAR(64) NOT NULL UNIQUE,
 creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(encuesta_id) REFERENCES encuestas(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS respuestas (
 id BIGINT AUTO_INCREMENT PRIMARY KEY,
 envio_id BIGINT NOT NULL,
 pregunta_id INT NOT NULL,
 contenido TEXT NOT NULL,
 FOREIGN KEY(envio_id) REFERENCES envios_encuesta(id) ON DELETE CASCADE,
 FOREIGN KEY(pregunta_id) REFERENCES preguntas(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS soporte (
 id INT AUTO_INCREMENT PRIMARY KEY,
 tipo ENUM('consulta','olvido_clave') NOT NULL DEFAULT 'consulta',
 nombre VARCHAR(160) NULL,
 cedula VARCHAR(20) NULL,
 mensaje TEXT NOT NULL,
 leido TINYINT(1) NOT NULL DEFAULT 0,
 creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS auditoria (
 id BIGINT AUTO_INCREMENT PRIMARY KEY,
 usuario_id INT NULL,
 accion VARCHAR(120) NOT NULL,
 detalle TEXT NULL,
 creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS intentos_login (
 id INT AUTO_INCREMENT PRIMARY KEY,
 usuario VARCHAR(60) NOT NULL,
 ip VARCHAR(45) NOT NULL,
 correcto TINYINT(1) NOT NULL DEFAULT 0,
 creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_login (usuario, ip, creado_en)
) ENGINE=InnoDB;

INSERT IGNORE INTO roles(id,nombre) VALUES (1,'Administrador'),(2,'Medico'),(3,'Enfermero');
INSERT IGNORE INTO categorias(nombre,descripcion,grupo,grupo_orden,orden) VALUES
('Unidad de Cuidados Paliativos','Documentación correspondiente a Unidad de Cuidados Paliativos.','Especialidades Clínicas',1,1),
('Emergencia','Documentación correspondiente a Emergencia.','Especialidades Clínicas',1,2),
('Neonatología','Documentación correspondiente a Neonatología.','Especialidades Clínicas',1,3),
('Dpto. Clínico de Medicina','Documentación correspondiente a Dpto. Clínico de Medicina.','Especialidades Clínicas',1,4),
('Cardiologia','Documentación correspondiente a Cardiologia.','Especialidades Clínicas',1,5),
('Clínica Médica A','Documentación correspondiente a Clínica Médica A.','Especialidades Clínicas',1,6),
('Clínica Médica B','Documentación correspondiente a Clínica Médica B.','Especialidades Clínicas',1,7),
('Clínica Médica C','Documentación correspondiente a Clínica Médica C.','Especialidades Clínicas',1,8),
('Dermatología','Documentación correspondiente a Dermatología.','Especialidades Clínicas',1,9),
('Endocrinología','Documentación correspondiente a Endocrinología.','Especialidades Clínicas',1,10),
('Gastroenterología','Documentación correspondiente a Gastroenterología.','Especialidades Clínicas',1,11),
('Unidad de Ostomías','Documentación correspondiente a Unidad de Ostomías.','Especialidades Clínicas',1,12),
('Geriatría','Documentación correspondiente a Geriatría.','Especialidades Clínicas',1,13),
('Hematología','Documentación correspondiente a Hematología.','Especialidades Clínicas',1,14),
('Infectología','Documentación correspondiente a Infectología.','Especialidades Clínicas',1,15),
('Medicina Física, Rehabilitación y Medicina del Deporte','Documentación correspondiente a Medicina Física, Rehabilitación y Medicina del Deporte.','Especialidades Clínicas',1,16),
('Nefrología','Documentación correspondiente a Nefrología.','Especialidades Clínicas',1,17),
('Neurología','Documentación correspondiente a Neurología.','Especialidades Clínicas',1,18),
('Oncología','Documentación correspondiente a Oncología.','Especialidades Clínicas',1,19),
('Psiquiatría','Documentación correspondiente a Psiquiatría.','Especialidades Clínicas',1,20),
('U.E. Autoinmunes Sistémicas','Documentación correspondiente a U.E. Autoinmunes Sistémicas.','Especialidades Clínicas',1,21),
('Dpto. Clínico de Cirugía','Documentación correspondiente a Dpto. Clínico de Cirugía.','Especialidades Quirúrgicas',2,1),
('Anestesiología','Documentación correspondiente a Anestesiología.','Especialidades Quirúrgicas',2,2),
('Cirugía Plástica y Quemados','Documentación correspondiente a Cirugía Plástica y Quemados.','Especialidades Quirúrgicas',2,3),
('Cirugía Vascular Periférica','Documentación correspondiente a Cirugía Vascular Periférica.','Especialidades Quirúrgicas',2,4),
('Ginecotocología B','Documentación correspondiente a Ginecotocología B.','Especialidades Quirúrgicas',2,5),
('Neurocirugía','Documentación correspondiente a Neurocirugía.','Especialidades Quirúrgicas',2,6),
('Odontología','Documentación correspondiente a Odontología.','Especialidades Quirúrgicas',2,7),
('Oftalmología','Documentación correspondiente a Oftalmología.','Especialidades Quirúrgicas',2,8),
('Otorrinolaringología','Documentación correspondiente a Otorrinolaringología.','Especialidades Quirúrgicas',2,9),
('Quirúrgica A','Documentación correspondiente a Quirúrgica A.','Especialidades Quirúrgicas',2,10),
('Quirúrgica B','Documentación correspondiente a Quirúrgica B.','Especialidades Quirúrgicas',2,11),
('Quirúrgica F','Documentación correspondiente a Quirúrgica F.','Especialidades Quirúrgicas',2,12),
('Urología','Documentación correspondiente a Urología.','Especialidades Quirúrgicas',2,13),
('Traumatología y Ortopedia de Adultos','Documentación correspondiente a Traumatología y Ortopedia de Adultos.','Especialidades Quirúrgicas',2,14),
('Centro Cardiovascular','Documentación correspondiente a Centro Cardiovascular.','Centros y Unidades Especializadas',3,1),
('Hemodinamia','Documentación correspondiente a Hemodinamia.','Centros y Unidades Especializadas',3,2),
('Cirugía Cardíaca','Documentación correspondiente a Cirugía Cardíaca.','Centros y Unidades Especializadas',3,3),
('Unidad de Tabaquismo','Documentación correspondiente a Unidad de Tabaquismo.','Centros y Unidades Especializadas',3,4),
('Medicina Nuclear','Documentación correspondiente a Medicina Nuclear.','Centros y Unidades Especializadas',3,5),
('Imagenología','Documentación correspondiente a Imagenología.','Servicios Diagnósticos',4,1),
('Hemoterapia','Documentación correspondiente a Hemoterapia.','Servicios Diagnósticos',4,2),
('Radioterapia','Documentación correspondiente a Radioterapia.','Servicios Diagnósticos',4,3);
