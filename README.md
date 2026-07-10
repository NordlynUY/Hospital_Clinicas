# Hospital de Clínicas - Sistema de Gestión Documental y Encuestas
### Proyecto desarrollado por Nordlyn

## Descripción

Este proyecto consiste en el desarrollo de un sistema web para el Hospital de Clínicas compuesto por dos módulos independientes.

El primer módulo corresponde a un portal web de documentación, donde los usuarios autenticados podrán consultar documentación institucional publicada por el hospital y los funcionarios autorizados administrarán documentos, categorías y encuestas.

El segundo módulo corresponde a un sistema de encuestas de satisfacción ubicado en la intranet del hospital. Los pacientes accederán mediante un código único impreso en una pulsera entregada al confirmar su asistencia a la consulta, pudiendo responder una única vez.

---

## Tecnologías utilizadas

- Debian
- Apache 2
- PHP 8.2
- MariaDB
- HTML5
- CSS3
- JavaScript
- Bootstrap 5
- Git
- GitHub
- Visual Studio Code

---

## Requisitos

Antes de ejecutar el proyecto es necesario disponer de:

- Debian
- Apache 2
- PHP 8.2 o superior
- MariaDB
- Git
- Visual Studio Code (opcional para desarrollo)

Además, es necesario configurar Git con GitHub mediante SSH y clonar el repositorio del proyecto.

---

## Instalación

Para obtener una guía completa de instalación y configuración del entorno de desarrollo, consultar el documento **Configuracion_Entorno_Desarrollo**, ubicado en:

```
Hospital_Clinicas/docs/
```

En dicho manual se explica paso a paso cómo:

- instalar Debian;
- instalar y configurar Apache 2;
- instalar PHP 8.2;
- instalar MariaDB;
- instalar Visual Studio Code y las extensiones recomendadas;
- instalar y configurar Git;
- vincular Git con GitHub mediante SSH;
- clonar el repositorio del proyecto;
- verificar que todo el entorno funcione correctamente.

A continuación se presenta únicamente un resumen del proceso.

### 1. Clonar el repositorio

Una vez configurado Git y el acceso mediante SSH, abrir una terminal y ejecutar:

```bash
git clone git@github.com:usuario/Hospital_Clinicas.git
```

Ingresar a la carpeta del proyecto:

```bash
cd Hospital_Clinicas
```

### 2. Configurar la base de datos

Crear una base de datos en MariaDB:

```sql
CREATE DATABASE hospital_clinicas;
```

Luego importar el archivo hospital.sql ubicado en:

```
Hospital_Clinicas/database/
```

### 3. Copiar el proyecto al servidor web

Copiar la carpeta del proyecto al directorio utilizado por Apache:

```
/var/www/html/
```

### 4. Verificar los servicios

Comprobar que Apache y MariaDB se encuentren en ejecución:

```bash
sudo systemctl start apache2
sudo systemctl start mariadb
```

### 5. Ejecutar la aplicación

Abrir un navegador e ingresar a:

```
http://localhost/
```

Si la instalación fue realizada correctamente, se visualizará la página principal del sistema.

### 6. Comenzar el desarrollo

Abrir la carpeta del proyecto con Visual Studio Code:

```bash
code .
```

Antes de comenzar una nueva tarea se recomienda sincronizar el repositorio con GitHub para obtener la versión más reciente del proyecto.

Al finalizar cada tarea, realizar un commit con un mensaje descriptivo y subir los cambios al repositorio para mantener actualizado el trabajo del equipo.

---

## Estructura del proyecto

```
Hospital_Clinicas/
│
├── README.md
├── .gitignore
│
├── docs/
│
├── src/
│
└── database/
```

---

## Convención de commits

Nuestro equipo utiliza la siguiente nomenclatura:

| Prefijo  | Uso                       |
|----------|---------------------------|
| feat     | Nueva funcionalidad       |
| fix      | Corrección de errores     |
| docs     | Documentación             |
| style    | Cambios de formato        |
| refactor | Reorganización del código |
| test     | Pruebas                   |

Ejemplos:

```
feat: agregar gestión de documentos

fix: corregir validación del login

docs: actualizar README
```

---

## Equipo de desarrollo

**Nordlyn**

Integrantes:

- Diana Lahoda
- Andres Cherro
- Joaquin Texeira
- Katerin Arevalo

---

## Estado del proyecto

El proyecto se encuentra en desarrollo.

Actualmente corresponde a la Primera Entrega del Proyecto Integrador.

---

## Licencia

Proyecto académico desarrollado para la UTU.

Su utilización tiene fines exclusivamente educativos.