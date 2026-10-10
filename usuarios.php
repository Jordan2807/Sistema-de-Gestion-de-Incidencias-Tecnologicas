<?php
session_start();

// Proteger la página: solo los administradores autenticados pueden entrar.
if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . "/config/conexion.php";


$idSesion = (int) $_SESSION["admin_id"];

$stmtSesion = $conn->prepare(
    "SELECT id FROM administradores WHERE id = ? LIMIT 1"
);

if (!$stmtSesion) {
    http_response_code(500);
    exit("No se pudo verificar la sesión administrativa.");
}

$stmtSesion->bind_param("i", $idSesion);
$stmtSesion->execute();

$sesionValida = $stmtSesion->get_result()->num_rows === 1;

$stmtSesion->close();

if (!$sesionValida) {
    $_SESSION = [];
    session_destroy();

    header("Location: login.php");
    exit;
}

// Crear token de seguridad.
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

function escaparUsuario(string $texto): string
{
    return htmlspecialchars($texto, ENT_QUOTES, "UTF-8");
}

function longitudUsuario(string $texto): int
{
    return function_exists("mb_strlen")
        ? mb_strlen($texto, "UTF-8")
        : strlen($texto);
}

function redirigirUsuarios(string $texto, string $tipo = "exito"): void
{
    $_SESSION["mensaje_usuarios"] = [
        "texto" => $texto,
        "tipo" => $tipo
    ];

    header("Location: usuarios.php");
    exit;
}

// Procesar creación de usuarios administrativos.
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $token = $_POST["csrf_token"] ?? "";

    if (
        !is_string($token) ||
        !hash_equals($_SESSION["csrf_token"], $token)
    ) {
        redirigirUsuarios(
            "La solicitud no es válida. Recarga la página e inténtalo de nuevo.",
            "error"
        );
    }

    $accion = $_POST["accion"] ?? "";

    
if ($accion === "eliminar_usuario") {

    $idEliminar = filter_var(
        $_POST["id"] ?? null,
        FILTER_VALIDATE_INT,
        ["options" => ["min_range" => 1]]
    );

    if (!$idEliminar) {
        redirigirUsuarios("El identificador del usuario no es válido.", "error");
    }

    // Impedir que el administrador elimine su propia cuenta.
    if ((int) $idEliminar === (int) $_SESSION["admin_id"]) {
        redirigirUsuarios(
            "No puedes eliminar tu propia cuenta mientras tienes la sesión abierta.",
            "error"
        );
    }

    // Comprobar que no se elimine el último administrador.
    $consultaTotal = $conn->query(
        "SELECT COUNT(*) AS total FROM administradores"
    );

    if (!$consultaTotal) {
        redirigirUsuarios(
            "No se pudo comprobar la cantidad de administradores.",
            "error"
        );
    }

    $totalAdministradores = (int) $consultaTotal->fetch_assoc()["total"];

    if ($totalAdministradores <= 1) {
        redirigirUsuarios(
            "No se puede eliminar la última cuenta administrativa.",
            "error"
        );
    }

    // Eliminar utilizando una consulta preparada.
    $stmtEliminar = $conn->prepare(
        "DELETE FROM administradores WHERE id = ?"
    );

    if (!$stmtEliminar) {
        redirigirUsuarios(
            "No se pudo preparar la eliminación del usuario.",
            "error"
        );
    }

    $stmtEliminar->bind_param("i", $idEliminar);

    $correcto = $stmtEliminar->execute();
    $eliminados = $stmtEliminar->affected_rows;

    $stmtEliminar->close();

    if ($correcto && $eliminados > 0) {
        redirigirUsuarios(
            "El usuario administrativo fue eliminado correctamente."
        );
    }

    if ($correcto) {
        redirigirUsuarios(
            "No se encontró el usuario que intentabas eliminar.",
            "error"
        );
    }

    redirigirUsuarios(
        "No se pudo eliminar el usuario.",
        "error"
    );
}


    if ($accion !== "crear_usuario") {
        redirigirUsuarios("La operación solicitada no es válida.", "error");
    }

    $nombre = is_string($_POST["nombre"] ?? null)
        ? trim($_POST["nombre"]) : "";

    $usuario = is_string($_POST["usuario"] ?? null)
        ? trim($_POST["usuario"]) : "";

    $password = is_string($_POST["password"] ?? null)
        ? $_POST["password"] : "";

    $confirmar = is_string($_POST["confirmar"] ?? null)
        ? $_POST["confirmar"] : "";

    // Validar los campos obligatorios.
    if (
        $nombre === "" ||
        $usuario === "" ||
        $password === "" ||
        $confirmar === ""
    ) {
        redirigirUsuarios("Todos los campos son obligatorios.", "error");
    }

    if (longitudUsuario($nombre) > 100) {
        redirigirUsuarios(
            "El nombre no puede superar los 100 caracteres.",
            "error"
        );
    }

    if (!preg_match('/^[a-zA-Z0-9._-]{3,50}$/', $usuario)) {
        redirigirUsuarios(
            "El usuario debe tener entre 3 y 50 caracteres y solo puede incluir letras, números, punto, guion o guion bajo.",
            "error"
        );
    }

    if (strlen($password) < 10) {
        redirigirUsuarios(
            "La contraseña debe tener al menos 10 caracteres.",
            "error"
        );
    }

    if ($password !== $confirmar) {
        redirigirUsuarios("Las contraseñas no coinciden.", "error");
    }

    // Nunca guardar la contraseña en texto plano.
    $hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare(
        "INSERT INTO administradores (nombre, usuario, password)
         VALUES (?, ?, ?)"
    );

    if (!$stmt) {
        error_log("Error al preparar la creación de usuario: " . $conn->error);

        redirigirUsuarios(
            "No se pudo preparar la creación del usuario.",
            "error"
        );
    }

    $stmt->bind_param("sss", $nombre, $usuario, $hash);
    $correcto = $stmt->execute();
    $codigoError = $stmt->errno;
    $stmt->close();

    if ($correcto) {
        redirigirUsuarios("El usuario administrativo fue creado correctamente.");
    }

    if ($codigoError === 1062) {
        redirigirUsuarios(
            "Ese nombre de usuario ya existe. Elige otro.",
            "error"
        );
    }

    redirigirUsuarios(
        "No se pudo crear el usuario. Revisa la conexión con la base de datos.",
        "error"
    );
}

// Recuperar mensajes y consultar usuarios.
$mensaje = $_SESSION["mensaje_usuarios"] ?? null;
unset($_SESSION["mensaje_usuarios"]);

$resultado = $conn->query(
    "SELECT id, nombre, usuario, fecha_creacion
     FROM administradores
     ORDER BY id DESC"
);

if (!$resultado) {
    error_log("Error al consultar administradores: " . $conn->error);
    die("No se pudieron consultar los usuarios administrativos.");
}

$usuarios = $resultado->fetch_all(MYSQLI_ASSOC);

$nombreAdmin = escaparUsuario(
    $_SESSION["admin_nombre"] ?? "Administrador"
);

$tokenHTML = escaparUsuario($_SESSION["csrf_token"]);
?>

<?php
$tituloPagina = "Usuarios administrativos | UISIL";
$paginaActual = "usuarios";

require_once __DIR__ . "/includes/header.php";
?>

<main class="container admin-container">

    <section class="hero">
        <p class="eyebrow">CONFIGURACIÓN DEL SISTEMA</p>
        <h1>Usuarios administrativos</h1>
        <p>
            Crea nuevas cuentas para las personas encargadas de gestionar
            las incidencias de los laboratorios.
        </p>
    </section>

    <?php if ($mensaje): ?>
        <div
            class="alert <?= ($mensaje["tipo"] ?? "") === "error"
                ? "alert-error"
                : "alert-success" ?>"
            role="status"
        >
            <?= escaparUsuario($mensaje["texto"] ?? "") ?>
        </div>
    <?php endif; ?>

    <section class="card">
        <div class="card-heading">
            <div>
                <h2>Crear usuario</h2>
                <p>Todos los campos son obligatorios.</p>
            </div>
        </div>

        <form action="usuarios.php" method="POST">

            <input type="hidden" name="csrf_token" value="<?= $tokenHTML ?>">
            <input type="hidden" name="accion" value="crear_usuario">

            <div class="form-grid">

                <div class="field">
                    <label for="nombre">Nombre completo *</label>
                    <input
                        type="text"
                        id="nombre"
                        name="nombre"
                        maxlength="100"
                        autocomplete="name"
                        placeholder="Nombre del administrador"
                        required
                    >
                </div>

                <div class="field">
                    <label for="usuario">Nombre de usuario *</label>
                    <input
                        type="text"
                        id="usuario"
                        name="usuario"
                        minlength="3"
                        maxlength="50"
                        pattern="[A-Za-z0-9._-]+"
                        autocomplete="username"
                        placeholder="Ej. soporte01"
                        required
                    >
                    <small>
                        Usa de 3 a 50 caracteres: letras, números, punto,
                        guion o guion bajo.
                    </small>
                </div>

                <div class="field">
                    <label for="password">Contraseña *</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        minlength="10"
                        autocomplete="new-password"
                        required
                    >
                    <small>Mínimo 10 caracteres.</small>
                </div>

                <div class="field">
                    <label for="confirmar">Confirmar contraseña *</label>
                    <input
                        type="password"
                        id="confirmar"
                        name="confirmar"
                        minlength="10"
                        autocomplete="new-password"
                        required
                    >
                </div>

            </div>

            <div class="form-actions">
                <button type="submit" class="btn-primary">
                    Crear usuario administrativo
                </button>

                <a href="admin.php" class="btn-secondary btn-link">
                    Cancelar
                </a>
            </div>

        </form>
    </section>

    <section class="card users-list-card">

        <div class="card-heading">
            <div>
                <h2>Usuarios registrados</h2>
                <p>
                    Total de cuentas:
                    <strong><?= count($usuarios) ?></strong>
                </p>
            </div>
        </div>

        <div class="table-responsive table-wrapper">
            <table class="table table-hover align-middle mb-0 incidencias-table users-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre completo</th>
                        <th>Usuario</th>
                        <th>Fecha de creación</th>
                        <th>Acciones</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($usuarios as $usuario): ?>
                        <tr>
                            <td>#<?= (int) $usuario["id"] ?></td>

                            <td>
                                <?= escaparUsuario($usuario["nombre"]) ?>

                                <?php if (
                                    (int) $usuario["id"] ===
                                    (int) $_SESSION["admin_id"]
                                ): ?>
                                    <span class="current-user-label">
                                        Tu cuenta
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td><?= escaparUsuario($usuario["usuario"]) ?></td>

                            <td>
                                <?= date(
                                    "d/m/Y H:i",
                                    strtotime($usuario["fecha_creacion"])
                                ) ?>
                            </td>
                            
                            <td>
                                <?php if (
                                    (int) $usuario["id"] === (int) $_SESSION["admin_id"]
                                ): ?>

                                    <span class="current-user-label">
                                        Tu cuenta
                                    </span>

                                <?php else: ?>

                                    <form
                                        action="usuarios.php"
                                        method="POST"
                                        class="delete-form"
                                        data-tipo="usuario"
                                        data-id="<?= (int) $usuario["id"] ?>"
                                    >
                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= $tokenHTML ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="accion"
                                            value="eliminar_usuario"
                                        >

                                        <input
                                            type="hidden"
                                            name="id"
                                            value="<?= (int) $usuario["id"] ?>"
                                        >

                                        <button type="submit" class="btn-small btn-delete">
                                            Eliminar
                                        </button>
                                    </form>

                                <?php endif; ?>
                            </td>

                        </tr>
                    <?php endforeach; ?>

                    <?php if (empty($usuarios)): ?>
                        <tr>
                            <td colspan="5">No hay usuarios registrados.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </section>

</main>

<?php
require_once __DIR__ . "/includes/footer.php";
?>
