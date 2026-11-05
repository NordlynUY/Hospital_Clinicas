# SIGSM – Nordlyn

Aplicación académica para el Hospital de Clínicas. Está desarrollada con PHP, MariaDB, Bootstrap y JavaScript, y se ejecuta directamente con Apache en Debian (sin XAMPP).

## 1. Requisitos

- Debian 12 o superior.
- Apache 2 con `mod_rewrite`.
- PHP 8.2 o superior.
- MariaDB 10.6 o superior.
- Extensiones PHP: `php-mysql`, `php-mbstring`, `php-xml`, `php-curl`, `php-fileinfo`.
- Conexión a Internet para cargar Bootstrap y validar reCAPTCHA de prueba.

## 2. Instalación en Debian

### 2.1. Instalar Apache, PHP y MariaDB

```bash
sudo apt update
sudo apt install apache2 mariadb-server php libapache2-mod-php php-mysql php-mbstring php-xml php-curl php-fileinfo
sudo a2enmod rewrite
sudo systemctl enable --now apache2 mariadb
```

### 2.2. Copiar el proyecto

Desde la carpeta donde se encuentra el proyecto:

```bash
sudo cp -r SIGSM_Nordlyn /var/www/html/sigsm
```

La estructura debe quedar así:

```text
/var/www/html/sigsm/
├── app/
├── config/
├── database/
├── docs/
├── public/
└── tests/
```

Apache publicará únicamente la carpeta `public`. Los archivos de configuración, PHP interno, SQL y almacenamiento de documentos quedan fuera del acceso directo del navegador.

### 2.3. Configurar Apache para abrir el sitio en localhost

Crear el sitio:

```bash
sudo nano /etc/apache2/sites-available/sigsm.conf
```

Pegar esta configuración:

```apache
<VirtualHost *:80>
    ServerName localhost
    DocumentRoot /var/www/html/sigsm/public

    <Directory /var/www/html/sigsm/public>
        Options FollowSymLinks
        AllowOverride All
        Require all granted
        DirectoryIndex index.php
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/sigsm_error.log
    CustomLog ${APACHE_LOG_DIR}/sigsm_access.log combined
</VirtualHost>
```

Activar el sitio y recargar Apache:

```bash
sudo a2dissite 000-default.conf
sudo a2ensite sigsm.conf
sudo systemctl reload apache2
```

**Dirección para abrir el proyecto:**

```text
http://localhost/
```

No se debe agregar `/public` ni `/sigsm/public` a la dirección. Apache ya utiliza `public` como raíz del sitio. La página principal se carga desde `index.php`; las direcciones `.html` originales también se conservan como rutas compatibles y se conectan con las páginas PHP.

### 2.4. Crear la base de datos y los datos de prueba

Ejecutar los scripts como administrador de MariaDB:

```bash
sudo mariadb < /var/www/html/sigsm/database/01_estructura.sql
sudo mariadb < /var/www/html/sigsm/database/02_datos_prueba.sql
```

Crear el usuario de conexión:

```bash
sudo mariadb
```

Dentro de MariaDB:

```sql
CREATE USER 'sigsm_user'@'localhost' IDENTIFIED BY 'CambiarEstaClave_DB_2026';
GRANT ALL PRIVILEGES ON sigsm_nordlyn.* TO 'sigsm_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 2.5. Revisar la conexión y los permisos

El archivo `config/config.php` ya contiene los valores de laboratorio de esta entrega. Si se cambia la contraseña de MariaDB, actualizar también ese archivo.

Dar permisos de escritura al almacenamiento de documentos:

```bash
sudo chown -R www-data:www-data /var/www/html/sigsm/docs
sudo chmod -R 750 /var/www/html/sigsm/docs
sudo systemctl restart apache2
```

Abrir el navegador en `http://localhost/`.

## 3. Páginas del sistema

Las páginas mantienen la presentación visual de los HTML originales enviados para el proyecto. Se adaptaron sus enlaces y formularios para utilizar PHP y MariaDB, conservando sus colores, tarjetas, secciones, estilos y estructura visual general.

| Página | Dirección compatible | Función |
|---|---|---|
| Inicio | `http://localhost/` o `/index.html` | Portal principal y QR general dentro del hero |
| Documentación | `/portal_usuario.html` | Consulta pública de documentos |
| Encuestas | `/encuestas_buscar.html` | Búsqueda y acceso a encuestas publicadas |
| Responder encuesta | `/encuesta.html?id=ID` | Formulario dinámico de una encuesta |
| Iniciar sesión | `/login.html` | Acceso de personal y administración |
| Portal del personal | `/portal_funcionario.html` | Documentos, encuestas internas y sesión |
| Portal administrador | `/portal_admin.html` | Gestión del sistema |

El QR general dirige a `http://localhost/index.html`. Para utilizarlo desde otro equipo o desde una publicación real, se debe generar nuevamente con la dirección de red o dominio correspondiente.

## 4. Cuentas de prueba

| Rol | Usuario / cédula | Contraseña |
|---|---|---|
| Administrador | `123Admin` | `123Admin` |
| Personal | `42761358` | `Personal123` |

Son cuentas ficticias para la demostración académica. Cambiar las contraseñas antes de publicar el sistema.

## 5. reCAPTCHA

El inicio de sesión utiliza reCAPTCHA v2 con claves públicas de prueba de Google. Sirven para comprobar el flujo durante el desarrollo, pero no protegen un sitio real. Para producción se deben registrar claves propias y reemplazar `recaptcha_site_key` y `recaptcha_secret_key` en `config/config.php`.

La verificación se realiza desde PHP contra Google, por lo que se necesita conexión a Internet.

## 6. Funcionalidades incluidas

- Acceso público a la página principal, documentación publicada y encuestas públicas sin registro.
- Acceso autenticado para personal y administradores, con permisos comprobados en PHP.
- Sesiones PHP, cookies técnicas, protección CSRF y escape de contenido HTML.
- reCAPTCHA en el login y bloqueo tras tres intentos fallidos durante 15 minutos.
- Carga de documentos PDF con límite de 2 MB, validación MIME, nombre único, autor y categorías.
- Búsqueda por título, filtro por servicio, edición de datos, reemplazo de PDF e historial básico de versiones.
- Publicación y ocultamiento de documentos; eliminación definitiva desde el panel administrador.
- Encuestas públicas o dirigidas al personal, con preguntas de opción única, varias opciones, lista desplegable, valoración de 1 a 5 y texto breve.
- Respuestas anónimas, validación de campos, estadísticas y exportación CSV.
- Gestión de usuarios, roles y servicios; soporte y solicitudes de recuperación de contraseña.
- Registro de auditoría de acciones administrativas.
- Indicadores calculados a partir de los datos reales de MariaDB. No se agregan documentos ni respuestas ficticias.

## 7. Datos iniciales y prueba de documentos

Se crean siete categorías y dos cuentas ficticias. También se agrega una encuesta de satisfacción con una pregunta de valoración del 1 al 5. No se insertan documentos ni respuestas inventadas.

Para probar la carga de archivos se incluye `tests/ejemplo_documento_para_cargar.pdf`. El documento aparecerá en los listados una vez que se cargue desde el portal correspondiente.

## 8. Copias de seguridad

Respaldar la base:

```bash
mariadb-dump -u sigsm_user -p sigsm_nordlyn > respaldo_sigsm.sql
```

Respaldar los PDF:

```bash
sudo tar -czf respaldo_documentos.tar.gz -C /var/www/html/sigsm docs
```

Para recuperar el sistema, importar el archivo SQL y restaurar la carpeta `docs` manteniendo sus permisos.

## 9. Consideraciones para una publicación real

- Habilitar HTTPS y configurar el atributo `Secure` de las cookies mediante HTTPS.
- Sustituir las claves de reCAPTCHA de prueba por claves propias.
- Cambiar las contraseñas de demostración y la contraseña de MariaDB.
- Cambiar la URL local incluida en el QR por la dirección definitiva.
- Revisar permisos, copias de seguridad y registros del servidor.

## 10. Alcance de las pruebas

Se revisó la sintaxis de los archivos PHP y la estructura de las páginas. La integración completa con Apache y MariaDB debe verificarse en el equipo Debian donde se instalará el proyecto. La comprobación desde una segunda computadora queda pendiente porque se dispone de un solo equipo para el desarrollo.


## Actualización de interfaz y gestión (noviembre de 2026)

- Los portales de funcionario y administrador utilizan pestañas mediante el parámetro `tab`. El JavaScript guarda y restaura la posición de desplazamiento para que cambiar de sección o enviar un formulario no lleve al principio de la página.
- Los avisos de éxito y validación aparecen en la esquina inferior izquierda y desaparecen automáticamente.
- Los archivos PDF admitidos tienen un límite de **2 MB**. La validación se realiza en el servidor, además del selector de archivos del navegador.
- Los funcionarios autenticados pueden crear y editar documentos. La eliminación definitiva de documentos queda reservada al administrador y también elimina sus versiones y archivos PDF asociados.
- Las encuestas públicas y privadas se muestran por separado. Al eliminar una encuesta, también se eliminan sus envíos y respuestas.
- El botón de cierre de sesión está disponible en la pestaña Mi sesión del administrador y en la navegación del funcionario.
- La columna `categorias.grupo` organiza las categorías en cuatro grupos principales. Las etiquetas de subcategoría incluidas son las que constaban en el esquema entregado; no se agregaron nombres de especialidades que no estuvieran documentados.

### Actualizar una base de datos existente

Si la base de datos ya estaba creada antes de esta versión, ejecutar una sola vez `database/04_catalogo_especialidades.sql` desde phpMyAdmin (pestaña SQL). En una instalación nueva, ejecutar `01_estructura.sql` y luego `02_datos_prueba.sql`.


## Catálogo de especialidades (actualización noviembre 2026)

El sistema utiliza cuatro grupos visuales y 43 especialidades individuales. Los nombres de los grupos no son categorías de base de datos y no admiten documentos. Los documentos, usuarios y encuestas se relacionan únicamente con una especialidad individual.

Para una base ya existente, ejecutar `database/04_catalogo_especialidades.sql` desde phpMyAdmin > SQL. Este proceso conserva documentos y encuestas, pero retira las asignaciones a categorías antiguas que ya no existen. Después se pueden volver a asignar a una especialidad válida desde Gestión documental.
