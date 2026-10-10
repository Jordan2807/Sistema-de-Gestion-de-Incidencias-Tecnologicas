<?php
session_start();

require_once __DIR__ . "/config/conexion.php";

// Token para proteger los formularios.
if (!isset($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$mensaje = "";
$tipoMensaje = "error";

// Mostrar mensajes después de una redirección.
if (isset($_SESSION["mensaje_login"])) {
    $mensaje = $_SESSION["mensaje_login"];
    $tipoMensaje = "exito";
    unset($_SESSION["mensaje_login"]);
}

// Contar los administradores registrados.
function contarAdministradores(mysqli $conn): int
{
    $resultado = $conn->query(
        "SELECT COUNT(*) AS total FROM administradores"
    );

    if (!$resultado) {
        return 0;
    }

    $fila = $resultado->fetch_assoc();

    return (int) $fila["total"];
}

// Procesar formularios.
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $tokenRecibido = $_POST["csrf_token"] ?? "";

    if (
        !is_string($tokenRecibido) ||
        !hash_equals($_SESSION["csrf_token"], $tokenRecibido)
    ) {
        $mensaje = "La solicitud no es válida. Recarga la página e inténtalo de nuevo.";

    } else {

        $accion = $_POST["accion"] ?? "";

        // Cerrar sesión.
        if ($accion === "logout") {
        $_SESSION = [];

        if (ini_get("session.use_cookies")) {
            $parametros = session_get_cookie_params();

            setcookie(session_name(), "", [
                "expires" => time() - 42000,
                "path" => $parametros["path"],
                "domain" => $parametros["domain"],
                "secure" => $parametros["secure"],
                "httponly" => $parametros["httponly"],
                "samesite" => $parametros["samesite"] ?? "Lax"
            ]);
        }

    session_destroy();

    header("Location: login.php");
    exit;
}

        // Crear el primer administrador, solamente si no existe ninguno.
        elseif ($accion === "crear_admin") {

            if (contarAdministradores($conn) > 0) {
                $mensaje = "La configuración inicial ya fue completada.";

            } else {

                $nombre = trim($_POST["nombre"] ?? "");
                $usuario = trim($_POST["usuario"] ?? "");
                $password = $_POST["password"] ?? "";
                $confirmar = $_POST["confirmar"] ?? "";

                if (
                    !is_string($nombre) ||
                    !is_string($usuario) ||
                    !is_string($password) ||
                    !is_string($confirmar)
                ) {
                    $mensaje = "Los datos enviados no son válidos.";

                } elseif (
                    $nombre === "" ||
                    $usuario === "" ||
                    $password === "" ||
                    $confirmar === ""
                ) {
                    $mensaje = "Todos los campos son obligatorios.";

                } elseif (
                    !preg_match('/^[a-zA-Z0-9._-]{3,50}$/', $usuario)
                ) {
                    $mensaje = "El usuario debe tener entre 3 y 50 caracteres y solo puede incluir letras, números, punto, guion o guion bajo.";

                } elseif (strlen($password) < 10) {
                    $mensaje = "La contraseña debe tener al menos 10 caracteres.";

                } elseif ($password !== $confirmar) {
                    $mensaje = "Las contraseñas no coinciden.";

                } else {

                    $hash = password_hash(
                        $password,
                        PASSWORD_DEFAULT
                    );

                    $stmt = $conn->prepare(
                        "INSERT INTO administradores
                        (nombre, usuario, password)
                        VALUES (?, ?, ?)"
                    );

                    $stmt->bind_param(
                        "sss",
                        $nombre,
                        $usuario,
                        $hash
                    );

                    if ($stmt->execute()) {
                        $_SESSION["mensaje_login"] =
                            "Administrador creado correctamente. Ya puedes iniciar sesión.";

                        header("Location: login.php");
                        exit;
                    } else {
                        $mensaje = "No se pudo crear el administrador.";
                    }

                    $stmt->close();
                }
            }
        }

        // Iniciar sesión.
        elseif ($accion === "login") {

            $usuario = $_POST["usuario"] ?? "";
            $password = $_POST["password"] ?? "";

            if (
                !is_string($usuario) ||
                !is_string($password) ||
                trim($usuario) === "" ||
                $password === ""
            ) {
                $mensaje = "Ingresa tu usuario y contraseña.";

            } else {

                $stmt = $conn->prepare(
                    "SELECT id, nombre, usuario, password
                    FROM administradores
                    WHERE usuario = ?
                    LIMIT 1"
                );

                $usuario = trim($usuario);
                $stmt->bind_param("s", $usuario);
                $stmt->execute();

                $resultado = $stmt->get_result();
                $admin = $resultado->fetch_assoc();

                if (
                    $admin &&
                    password_verify($password, $admin["password"])
                ) {
                    // Renovar el identificador de sesión al autenticarse.
                    session_regenerate_id(true);

                    $_SESSION["admin_id"] = (int) $admin["id"];
                    $_SESSION["admin_nombre"] = $admin["nombre"];
                    $_SESSION["admin_usuario"] = $admin["usuario"];

                    header("Location: admin.php");
                    exit;

                } else {
                    $mensaje = "Usuario o contraseña incorrectos.";
                }

                $stmt->close();
            }

        } else {
            $mensaje = "Acción no válida.";
        }
    }
}

// Un administrador que ya inició sesión debe ir al panel.
if (
    $_SERVER["REQUEST_METHOD"] === "GET" &&
    isset($_SESSION["admin_id"])
) {
    header("Location: admin.php");
    exit;
}

$hayAdministradores = contarAdministradores($conn) > 0;

function escapar(string $valor): string
{
    return htmlspecialchars($valor, ENT_QUOTES, "UTF-8");
}
?>

<?php
$tituloPagina = "Acceso administrativo | UISIL";
$paginaActual = "login";

require_once __DIR__ . "/includes/header.php";
?>


<main class="login-wrapper">
    <div class="contenedor">

        <div class="encabezado">
            <h1>Sistema de Incidencias</h1>
            <p>Administración de laboratorios de computación</p>
        </div>

        <?php if ($mensaje !== ""): ?>
            <div
                class="alert <?= $tipoMensaje === "exito" ? "alert-success" : "alert-error" ?>"
                role="alert"
            >
                <?= escapar($mensaje) ?>
            </div>
        <?php endif; ?>

        <?php if (!$hayAdministradores): ?>

            <h2>Crear administrador inicial</h2>

            <p>
                No hay administradores registrados. Crea la primera cuenta
                para habilitar el acceso administrativo.
            </p>

            <form method="POST" action="login.php">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= escapar($_SESSION["csrf_token"]) ?>"
                >

                <input type="hidden" name="accion" value="crear_admin">

                <label for="nombre">Nombre completo</label>
                <input
                    type="text"
                    id="nombre"
                    name="nombre"
                    maxlength="100"
                    autocomplete="name"
                    required
                >

                <label for="usuario">Nombre de usuario</label>
                <input
                    type="text"
                    id="usuario"
                    name="usuario"
                    minlength="3"
                    maxlength="50"
                    pattern="[A-Za-z0-9._-]+"
                    autocomplete="username"
                    required
                >

                <label for="password">Contraseña</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    minlength="10"
                    autocomplete="new-password"
                    required
                >

                <label for="confirmar">Confirmar contraseña</label>
                <input
                    type="password"
                    id="confirmar"
                    name="confirmar"
                    minlength="10"
                    autocomplete="new-password"
                    required
                >

                <button type="submit">Crear administrador</button>

            </form>

        <?php else: ?>

            <h2>Iniciar sesión</h2>

            <form method="POST" action="login.php">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= escapar($_SESSION["csrf_token"]) ?>"
                >

                <input type="hidden" name="accion" value="login">

                <label for="usuario">Usuario</label>
                <input
                    type="text"
                    id="usuario"
                    name="usuario"
                    maxlength="50"
                    autocomplete="username"
                    required
                >

                <label for="password">Contraseña</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    autocomplete="current-password"
                    required
                >

                <button type="submit">Ingresar</button>

            </form>

        <?php endif; ?>

        <a class="enlace" href="index.php">
            Volver al registro de incidencias
        </a>

        <div class="nota">
            UISIL · Sistema de Gestión de Incidencias
        </div>

    </div>
</main>

<?php
require_once __DIR__ . "/includes/footer.php";
?>
