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
-