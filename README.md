# Sistema de Gestión de Incidencias de Laboratorios

**UISIL · Facultad de Ingeniería de Sistemas**

Aplicación web desarrollada con PHP y MySQL para registrar, consultar y administrar incidencias tecnológicas en laboratorios universitarios. El sistema permite centralizar los reportes de fallas en computadoras, periféricos, proyectores, conexiones de red y otros equipos tecnológicos.

## 1. Objetivo del proyecto

Desarrollar una aplicación web que permita registrar y dar seguimiento a las incidencias tecnológicas de una universidad, sustituyendo el reporte verbal por un sistema centralizado que facilite su administración y consulta.

## 2. Tecnologías utilizadas

- **PHP:** procesamiento de formularios y operaciones del sistema.
- **MySQL/MariaDB:** almacenamiento de incidencias y usuarios administrativos.
- **MySQLi:** conexión con la base de datos mediante consultas preparadas.
- **HTML5:** estructura de las páginas.
- **CSS3:** estilos personalizados y paleta de colores verde agua.
- **Bootstrap 5.3.8:** diseño adaptable, navegación y presentación de tablas.
- **SweetAlert2:** ventanas de confirmación para eliminar incidencias y usuarios.
- **Apache:** servidor web local proporcionado por XAMPP.
- **Git y GitHub:** control de versiones y publicación del código fuente.

Bootstrap y SweetAlert2 se cargan desde sus respectivos CDN. Se requiere conexión a Internet para disponer de estos recursos cuando se ejecuta la aplicación.

## 3. Funcionalidades del sistema

### 3.1. Registro público de incidencias

Desde `index.php`, cualquier estudiante o profesor puede registrar una incidencia sin iniciar sesión.

El formulario solicita:

- Nombre de quien reporta.
- Laboratorio afectado.
- Equipo afectado.
- Descripción del problema.
- Prioridad de la incidencia.

El sistema valida los campos obligatorios y registra automáticamente la fecha de creación. Toda incidencia nueva queda inicialmente en estado **Pendiente**.

### 3.2. Panel administrativo de incidencias

Desde `admin.php`, los administradores autenticados pueden consultar y gestionar las incidencias registradas.

El panel incluye:

- Listado de las incidencias almacenadas en MySQL.
- Búsqueda por laboratorio.
- Filtros por estado y prioridad.
- Edición de los datos de una incidencia.
- Cambio del estado entre Pendiente, En proceso y Resuelta.
- Eliminación con confirmación mediante SweetAlert2.
- Visualización de la fecha de registro.
- Visualización de la fecha de la última modificación.

Las tablas utilizan Bootstrap y un ancho mínimo para evitar que las columnas se compriman excesivamente. En pantallas pequeñas es posible desplazarse horizontalmente para consultar todas las columnas.

### 3.3. Fechas de registro y modificación

Cada incidencia contiene dos fechas importantes:

- **Fecha de registro:** indica cuándo se creó originalmente la incidencia y se conserva aunque esta sea editada posteriormente.
- **Fecha de última modificación:** permite identificar cuándo se modificaron los datos de una incidencia.

La fecha de modificación se actualiza automáticamente mediante MySQL cuando cambia un dato del registro, incluidos la descripción, la prioridad o el estado.

Cuando la incidencia no ha sido modificada, el panel muestra el mensaje **Sin modificar**.

### 3.4. Inicio de sesión administrativo

El archivo `login.php` permite iniciar sesión mediante un nombre de usuario y una contraseña.

Si la base de datos todavía no contiene administradores, se habilita el formulario para crear la primera cuenta administrativa. Una vez creada, el sistema muestra el formulario de inicio de sesión habitual.

Las contraseñas se almacenan utilizando `password_hash()` de PHP y se verifican con `password_verify()`. No se guardan como texto plano.

Las páginas administrativas comprueban la autenticación y la existencia de la cuenta correspondiente. Si una cuenta ya no existe en la base de datos, no debe conservar acceso al panel.

### 3.5. Administración de usuarios

El archivo `usuarios.php` permite gestionar las cuentas administrativas.

Sus funciones son:

- Crear nuevos usuarios administrativos.
- Consultar los usuarios registrados.
- Visualizar su nombre, nombre de usuario y fecha de creación.
- Eliminar cuentas administrativas que ya no se necesiten.

La eliminación de usuarios utiliza SweetAlert2 para solicitar confirmación antes de ejecutar la operación.

El sistema también incluye estas protecciones:

- No permite que un administrador elimine su propia cuenta mientras tiene la sesión abierta.
- Impide eliminar la última cuenta administrativa.
- Comprueba que los datos enviados sean válidos.
- Utiliza consultas preparadas de MySQLi para eliminar registros.

Las contraseñas y sus hashes no se muestran en la tabla de usuarios.

### 3.6. Confirmaciones con SweetAlert2

Las eliminaciones de incidencias y usuarios muestran una ventana de confirmación con dos opciones:

- **Confirmar eliminación:** envía el formulario para ejecutar la operación.
- **Cancelar:** cierra la ventana sin eliminar el registro.

La confirmación mejora la experiencia de uso y reduce el riesgo de eliminaciones accidentales. El servidor también valida la solicitud antes de modificar la base de datos.

### 3.7. Interfaz y navegación

El diseño utiliza una paleta de colores verde agua, con tonos turquesa y verde oscuro.

El encabezado y el pie de página se encuentran centralizados en la carpeta `includes/`, por lo que las distintas páginas comparten una misma estructura visual.

La navegación cambia según el estado de autenticación:

- Los visitantes pueden reportar incidencias y acceder al inicio de sesión.
- Los administradores pueden acceder al panel de incidencias, al formulario de registro, a la administración de usuarios y al cierre de sesión.

El navbar de Bootstrap se adapta a pantallas pequeñas y las tablas mantienen sus columnas legibles mediante desplazamiento horizontal.

## 4. Estructura del proyecto

```text
sistema-incidencias-laboratorio/
│
├── index.php
├── admin.php
├── login.php
├── usuarios.php
├── database.sql
├── README.md
│
├── config/
│   └── conexion.php
│
├── includes/
│   ├── header.php
│   └── footer.php
│
└── css/
    └── estilos.css
```

### Descripción de los archivos

| Archivo | Función |
|---|---|
| `index.php` | Formulario público para registrar incidencias. |
| `admin.php` | Listado, búsqueda, filtros, edición, cambio de estado y eliminación de incidencias. |
| `login.php` | Inicio de sesión, cierre de sesión y creación del primer administrador. |
| `usuarios.php` | Creación, consulta y eliminación de usuarios administrativos. |
| `database.sql` | Script SQL para crear la base de datos y sus tablas. |
| `config/conexion.php` | Conexión con MySQL mediante MySQLi. |
| `includes/header.php` | Encabezado y navbar compartidos. |
| `includes/footer.php` | Pie de página y carga de los recursos JavaScript. |
| `css/estilos.css` | Estilos visuales y adaptación a diferentes tamaños de pantalla. |
| `README.md` | Documentación e instrucciones de instalación. |

## 5. Requisitos para ejecutar el sistema

Antes de instalar el proyecto, se necesita:

- Una computadora compatible con XAMPP.
- XAMPP con Apache, PHP y MySQL/MariaDB.
- Un navegador web actualizado.
- Acceso a Internet para cargar Bootstrap y SweetAlert2 desde sus CDN.

No es necesario instalar un servidor web adicional ni contratar un hosting para ejecutar la aplicación localmente.

## 6. Instalación en XAMPP

### Paso 1. Descargar el proyecto

Se puede obtener el código mediante Git:

```bash
git clone https://github.com/TU-USUARIO/sistema-incidencias-laboratorio.git
```

Se debe sustituir `TU-USUARIO` por el nombre real del propietario del repositorio de GitHub.

También es posible descargar el repositorio como archivo ZIP desde GitHub.

### Paso 2. Copiar el proyecto a `htdocs`

Extraer o copiar la carpeta del proyecto dentro del directorio `htdocs` de XAMPP.

Una ruta habitual en Windows es:

```text
C:\xampp\htdocs\sistema-incidencias-laboratorio
```

Si XAMPP está instalado en una ubicación diferente, debe utilizarse el directorio `htdocs` correspondiente a esa instalación.

### Paso 3. Iniciar Apache y MySQL

1. Abrir el panel de control de XAMPP.
2. Iniciar el módulo **Apache**.
3. Iniciar el módulo **MySQL**.
4. Verificar que ambos servicios estén funcionando correctamente.

Apache permite ejecutar las páginas PHP y MySQL/MariaDB proporciona el almacenamiento de datos.

### Paso 4. Crear la base de datos mediante `database.sql`

1. Abrir el navegador y entrar en `http://localhost/phpmyadmin/`.
2. Seleccionar la pestaña **Importar**.
3. Elegir el archivo `database.sql` ubicado en la raíz del proyecto.
4. Ejecutar la importación.
5. Verificar que aparezca la base de datos `sistema_incidencias`.

El script crea las siguientes tablas:

- `administradores`
- `incidencias`

También define sus campos, tipos de datos, valores predeterminados e índices.

No es necesario crear las tablas manualmente.

### Paso 5. Configurar la conexión a MySQL

Abrir el archivo `config/conexion.php`.

La configuración habitual de XAMPP es:

```php
<?php

$host = "localhost";
$usuario = "root";
$password = "";
$base_datos = "sistema_incidencias";

$conn = new mysqli(
    $host,
    $usuario,
    $password,
    $base_datos
);

if ($conn->connect_error) {
    error_log("Error MySQL: " . $conn->connect_error);
    die("No se pudo conectar con la base de datos.");
}

$conn->set_charset("utf8mb4");

?>
```

Si MySQL utiliza una contraseña para `root` o un puerto diferente, deben ajustarse los parámetros según la configuración local.

### Paso 6. Crear el primer administrador

Abrir:

```text
http://localhost/sistema-incidencias-laboratorio/login.php
```

Si la tabla `administradores` está vacía, aparecerá el formulario para crear el administrador inicial.

1. Introducir el nombre completo.
2. Elegir un nombre de usuario de entre 3 y 50 caracteres.
3. Crear una contraseña de al menos 10 caracteres.
4. Confirmar la contraseña.
5. Crear la cuenta.
6. Iniciar sesión con las credenciales registradas.

Después de crear la primera cuenta, el formulario de configuración inicial deja de mostrarse y queda habilitado el inicio de sesión habitual.

No existe una contraseña predeterminada que deba compartirse o publicarse.

### Paso 7. Ejecutar la aplicación

Para registrar incidencias, abrir:

```text
http://localhost/sistema-incidencias-laboratorio/index.php
```

Para iniciar sesión y administrar el sistema, abrir:

```text
http://localhost/sistema-incidencias-laboratorio/login.php
```

Una vez autenticado, se puede acceder al panel de incidencias y a la administración de usuarios desde el navbar.

Si Apache utiliza un puerto diferente al predeterminado, debe incluirse dicho puerto en la dirección del navegador.

## 7. Estructura de la base de datos

### Tabla `incidencias`

| Campo | Descripción |
|---|---|
| `id` | Identificador único generado automáticamente. |
| `reportante` | Nombre de la persona que comunica la falla. |
| `laboratorio` | Laboratorio donde se presenta el problema. |
| `equipo` | Computadora, periférico u otro equipo afectado. |
| `descripcion` | Descripción del problema reportado. |
| `prioridad` | Baja, Media, Alta o Urgente. |
| `estado` | Pendiente, En proceso o Resuelta. |
| `fecha_registro` | Fecha y hora de creación del registro. |
| `fecha_actualizacion` | Fecha y hora de la última modificación. |

El estado predeterminado es `Pendiente` y la prioridad predeterminada es `Media`.

### Tabla `administradores`

| Campo | Descripción |
|---|---|
| `id` | Identificador único del administrador. |
| `nombre` | Nombre completo del usuario administrativo. |
| `usuario` | Nombre de usuario único para iniciar sesión. |
| `password` | Hash de la contraseña. |
| `fecha_creacion` | Fecha y hora de creación de la cuenta. |

## 8. Instalar el sistema en otra computadora

Para trasladar el proyecto a otro equipo sin depender de la instalación original:

1. Descargar o clonar el repositorio público de GitHub.
2. Copiar el proyecto a `htdocs`.
3. Iniciar Apache y MySQL en XAMPP.
4. Importar el archivo `database.sql` desde phpMyAdmin.
5. Revisar la configuración de `config/conexion.php`.
6. Abrir `login.php` y crear el primer administrador.
7. Iniciar sesión y comprobar el funcionamiento del sistema.

### ¿Qué contiene `database.sql`?

El archivo contiene las instrucciones para crear la base de datos, las tablas, los índices y los valores predeterminados.

El script no copia automáticamente las incidencias ni los usuarios que existan en otra computadora. Para trasladar esos registros, es necesario exportar la base de datos desde phpMyAdmin e importar el respaldo en el equipo de destino.

Los respaldos pueden contener información sensible y hashes de contraseñas, por lo que no deben publicarse en un repositorio público.

## 9. Actualizar el repositorio de GitHub

Después de modificar el código o la documentación, abrir una terminal de Visual Studio Code en la carpeta del proyecto.

Ejecutar:

```bash
git status
git add .
git commit -m "Actualizar documentacion y funciones administrativas"
git push origin main
```

Antes de ejecutar `git add .`, comprobar que no se incluyan archivos temporales, credenciales reales ni respaldos de bases de datos.

Finalmente, revisar el repositorio público para confirmar que contenga el código fuente completo, `database.sql` y `README.md`.

## 10. Solución de problemas frecuentes

### Apache no inicia

Puede existir otro programa utilizando el puerto configurado para Apache. Revisar los puertos en el panel de XAMPP.

### MySQL no inicia

Verificar si otro servicio ocupa el puerto configurado para MySQL y revisar los mensajes del panel de XAMPP.

### Error de conexión con la base de datos

Comprobar que la base de datos se llame `sistema_incidencias` y que el usuario, la contraseña y el puerto de `config/conexion.php` coincidan con la configuración local.

### No aparece el formulario para crear el primer administrador

Si ya existe al menos un registro en `administradores`, el sistema muestra el formulario de inicio de sesión en lugar del formulario de creación inicial.

### No aparecen los estilos o la navegación adaptable

Verificar que el navegador pueda cargar el CSS y JavaScript de Bootstrap desde su CDN.

### No aparecen las ventanas de SweetAlert2

Verificar la conexión a Internet y que la librería de SweetAlert2 se esté cargando correctamente desde el CDN configurado en `includes/footer.php`.

### La tabla no cabe completamente en la pantalla

Las tablas mantienen un ancho mínimo para evitar que las columnas se compriman. En pantallas pequeñas se puede utilizar el desplazamiento horizontal para consultar todos los campos.

## 11. Consideraciones de seguridad

- Las páginas administrativas requieren autenticación.
- Las contraseñas se almacenan mediante hashes de PHP.
- Las consultas que reciben datos externos utilizan consultas preparadas de MySQLi.
- Se validan los campos obligatorios antes de guardar información.
- Los formularios de operaciones administrativas utilizan tokens CSRF.
- Las eliminaciones requieren confirmación de la persona usuaria.
- No se permite eliminar la propia cuenta administrativa desde su sesión ni eliminar la última cuenta.
- No deben publicarse contraseñas ni respaldos sensibles en GitHub.

Esta aplicación está diseñada para un entorno académico y de ejecución local. Su despliegue en un servidor público requeriría revisar y reforzar su configuración de seguridad.

---

**Proyecto académico — UISIL**  
Facultad de Ingeniería de Sistemas  
Sistema de Gestión de Incidencias de Laboratorios