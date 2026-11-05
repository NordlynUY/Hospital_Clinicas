-- Cuentas de prueba. Contraseñas: 123Admin / Personal123
USE sigsm_nordlyn;
INSERT IGNORE INTO usuarios(usuario,cedula,nombre,apellido,correo,password_hash,rol_id,activo)
VALUES
('123Admin',NULL,'Administrador','SIGSM','admin@nordlyn.local','$2y$12$Zy3Dfcy8pA2jV/r//Ck2Mu3AIiTSN6NXrKLaiKr64puExZhSOwLQG',1,1),
('42761358','42761358','Martín','Rodríguez','martin@nordlyn.local','$2y$12$fzjIbC79vZU1hzKwmIsi7.4wuU7vEVQ6W4xDlyLY6CEZ2UCxiGyqi',2,1);
-- Encuesta inicial mínima de satisfacción (sin respuestas ficticias).
INSERT INTO encuestas(titulo,descripcion,publico,estado,creador_id)
SELECT 'Encuesta de satisfacción','Encuesta breve para conocer la valoración de la atención recibida. Las respuestas son anónimas.','publico','publicada',u.id
FROM usuarios u WHERE u.usuario='123Admin'
AND NOT EXISTS (SELECT 1 FROM encuestas WHERE titulo='Encuesta de satisfacción');
INSERT INTO preguntas(encuesta_id,enunciado,tipo,obligatoria,orden)
SELECT e.id,'¿Cómo calificaría la atención recibida?','valoracion',1,1
FROM encuestas e WHERE e.titulo='Encuesta de satisfacción'
AND NOT EXISTS (SELECT 1 FROM preguntas p WHERE p.encuesta_id=e.id);
