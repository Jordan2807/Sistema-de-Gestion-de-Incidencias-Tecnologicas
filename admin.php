<?php
session_start();

// Solo los administradores autenticados pueden entrar.
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

// Crear el token si todavía no existe.
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

function escaparAdmin(string $valor): string
{
    return htmlspecialchars($valor, ENT_QUOTES, "UTF-8");
}

function redirigirMensaje(
    string $mensaje,
    string $tipo = "exito",
    string $destino = "admin.php"
): void {
    $_SESSION["mensaje_admin"] = [
        "texto" => $mensaje,
        "tipo" => $tipo
    ];

    header("Location: " . $destino);
    exit;
}

function longitudTexto(string $texto): int
{
    return function_exists("mb_strlen")
        ? mb_strlen($texto, "UTF-8")
        : strlen($texto);
}

$estadosPermitidos = ["Pendiente", "En proceso", "Resuelta"];
$prioridadesPermitidas = ["Baja", "Media", "Alta", "Urgente"];

// Procesar todas las operaciones administrativas.
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $token = $_POST["csrf_token"] ?? "";

    if (
        !is_string($token) ||
        !hash_equals($_SESSION["csrf_token"], $token)
    ) {
        redirigirMensaje(
            "La solicitud no es válida. Recarga la página e inténtalo nuevamente.",
            "error"
        );
    }

    $accion = $_POST["accion"] ?? "";

    // -------------------------------------------------
    // Actualizar una incidencia
    // -------------------------------------------------
    if ($accion === "actualizar") {

        $id = filter_var(
            $_POST["id"] ?? null,
            FILTER_VALIDATE_INT,
            ["options" => ["min_range" => 1]]
        );

        $reportante = is_string($_POST["reportante"] ?? null)
            ? trim($_POST["reportante"]) : "";

        $laboratorio = is_string($_POST["laboratorio"] ?? null)
            ? trim($_POST["laboratorio"]) : "";

        $equipo = is_string($_POST["equipo"] ?? null)
            ? trim($_POST["equipo"]) : "";

        $descripcion = is_string($_POST["descripcion"] ?? null)
            ? trim($_POST["descripcion"]) : "";

        $prioridad = is_string($_POST["prioridad"] ?? null)
            ? $_POST["prioridad"] : "";

        $errores = [];

        if (!$id) {
            $errores[] = "El identificador de la incidencia no es válido.";
        }

        if ($reportante === "" || longitudTexto($reportante) > 100) {
            $errores[] = "El nombre es obligatorio y no debe superar 100 caracteres.";
        }

        if ($laboratorio === "" || longitudTexto($laboratorio) > 100) {
            $errores[] = "El laboratorio es obligatorio y no debe superar 100 caracteres.";
        }

        if ($equipo === "" || longitudTexto($equipo) > 150) {
            $errores[] = "El equipo es obligatorio y no debe superar 150 caracteres.";
        }

        if ($descripcion === "" || longitudTexto($descripcion) > 5000) {
            $errores[] = "La descripción es obligatoria y no debe superar 5000 caracteres.";
        }

        if (!in_array($prioridad, $prioridadesPermitidas, true)) {
            $errores[] = "La prioridad seleccionada no es válida.";
        }

        if (!empty($errores)) {
            redirigirMensaje(
                implode(" ", $errores),
                "error",
                $id ? "admin.php?editar=" . $id : "admin.php"
            );
        }

        $stmt = $conn->prepare(
            "UPDATE incidencias
             SET reportante = ?,
                 laboratorio = ?,
                 equipo = ?,
                 descripcion = ?,
                 prioridad = ?
             WHERE id = ?"
        );

        if (!$stmt) {
            redirigirMensaje("No se pudo preparar la actualización.", "error");
        }

        $stmt->bind_param(
            "sssssi",
            $reportante,
            $laboratorio,
            $equipo,
            $descripcion,
            $prioridad,
            $id
        );

        $correcto = $stmt->execute();
        $afectadas = $stmt->affected_rows;
        $stmt->close();

        if ($correcto) {
            redirigirMensaje(
                $afectadas > 0
                    ? "La incidencia se actualizó correctamente."
                    : "La operación terminó. No había cambios que guardar o los datos ya no necesitaban actualización."
            );
        }

        redirigirMensaje("No se pudo actualizar la incidencia.", "error");
    }

    // -------------------------------------------------
    // Cambiar el estado de una incidencia
    // -------------------------------------------------
    elseif ($accion === "cambiar_estado") {

        $id = filter_var(
            $_POST["id"] ?? null,
            FILTER_VALIDATE_INT,
            ["options" => ["min_range" => 1]]
        );

        $estado = is_string($_POST["estado"] ?? null)
            ? $_POST["estado"] : "";

        if (
            !$id ||
            !in_array($estado, $estadosPermitidos, true)
        ) {
            redirigirMensaje("El identificador o estado no es válido.", "error");
        }

        $stmt = $conn->prepare(
            "UPDATE incidencias SET estado = ? WHERE id = ?"
        );

        if (!$stmt) {
            redirigirMensaje("No se pudo preparar el cambio de estado.", "error");
        }

        $stmt->bind_param("si", $estado, $id);
        $correcto = $stmt->execute();
        $stmt->close();

        redirigirMensaje(
            $correcto
                ? "El estado de la incidencia fue actualizado."
                : "No se pudo cambiar el estado.",
            $correcto ? "exito" : "error"
        );
    }

    // -------------------------------------------------
    // Eliminar una incidencia
    // -------------------------------------------------
    elseif ($accion === "eliminar") {

        $id = filter_var(
            $_POST["id"] ?? null,
            FILTER_VALIDATE_INT,
            ["options" => ["min_range" => 1]]
        );

        if (!$id) {
            redirigirMensaje("El identificador no es válido.", "error");
        }

        $stmt = $conn->prepare(
            "DELETE FROM incidencias WHERE id = ?"
        );

        if (!$stmt) {
            redirigirMensaje("No se pudo preparar la eliminación.", "error");
        }

        $stmt->bind_param("i", $id);
        $correcto = $stmt->execute();
        $eliminadas = $stmt->affected_rows;
        $stmt->close();

        if ($correcto && $eliminadas > 0) {
            redirigirMensaje("La incidencia fue eliminada correctamente.");
        }

        redirigirMensaje(
            $correcto
                ? "No se encontró la incidencia que intentabas eliminar."
                : "No se pudo eliminar la incidencia.",
            "error"
        );
    }

    else {
        redirigirMensaje("La operación solicitada no es válida.", "error");
    }
}

// Leer mensajes de las operaciones anteriores.
$mensaje = $_SESSION["mensaje_admin"] ?? null;
unset($_SESSION["mensaje_admin"]);

// Leer los filtros enviados por GET.
$filtroLaboratorio = $_GET["laboratorio"] ?? "";
$filtroEstado = $_GET["estado"] ?? "";
$filtroPrioridad = $_GET["prioridad"] ?? "";

$filtroLaboratorio = is_string($filtroLaboratorio)
    ? trim($filtroLaboratorio) : "";

$filtroEstado = is_string($filtroEstado)
    ? $filtroEstado : "";

$filtroPrioridad = is_string($filtroPrioridad)
    ? $filtroPrioridad : "";

if (!in_array($filtroEstado, $estadosPermitidos, true)) {
    $filtroEstado = "";
}

if (!in_array($filtroPrioridad, $prioridadesPermitidas, true)) {
    $filtroPrioridad = "";
}

if (longitudTexto($filtroLaboratorio) > 100) {
    $filtroLaboratorio = substr($filtroLaboratorio, 0, 100);
}

$busquedaLaboratorio = "%" . $filtroLaboratorio . "%";

// Consultar incidencias aplicando los filtros.
$stmt = $conn->prepare(
    "SELECT id, reportante, laboratorio, equipo, descripcion,
            prioridad, estado, fecha_registro, fecha_actualizacion
     FROM incidencias
     WHERE (? = '' OR laboratorio LIKE ?)
       AND (? = '' OR estado = ?)
       AND (? = '' OR prioridad = ?)
     ORDER BY fecha_registro DESC, id DESC"
);

$stmt->bind_param(
    "ssssss",
    $filtroLaboratorio,
    $busquedaLaboratorio,
    $filtroEstado,
    $filtroEstado,
    $filtroPrioridad,
    $filtroPrioridad
);

$stmt->execute();
$incidencias = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Consultar una incidencia para editarla en esta misma página.
$incidenciaEditar = null;

if (isset($_GET["editar"])) {

    $idEditar = filter_var(
        $_GET["editar"],
        FILTER_VALIDATE_INT,
        ["options" => ["min_range" => 1]]
    );

    if ($idEditar) {
        $stmt = $conn->prepare(
            "SELECT id, reportante, laboratorio, equipo,
                    descripcion, prioridad
             FROM incidencias
             WHERE id = ?"
        );

        $stmt->bind_param("i", $idEditar);
        $stmt->execute();

        $incidenciaEditar = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$incidenciaEditar && !$mensaje) {
            $mensaje = [
                "texto" => "La incidencia solicitada no existe.",
                "tipo" => "error"
            ];
        }
    }
}

$totalVisible = count($incidencias);
$tokenHTML = escaparAdmin($_SESSION["csrf_token"]);
$nombreAdmin = escaparAdmin($_SESSION["admin_nombre"] ?? "Administrador");
?>

<?php
$tituloPagina = "Panel administrativo | UISIL";
$paginaActual = "incidencias";

require_once __DIR__ . "/includes/header.php";
?>

<main class="container admin-container">

    <section class="hero">
        <p class="eyebrow">GESTIÓN Y SEGUIMIENTO</p>
        <h1>Incidencias registradas</h1>
        <p>
            Consulta los reportes, actualiza su información y da seguimiento
            a los problemas tecnológicos de los laboratorios.
        </p>
    </section>

    <?php if ($mensaje): ?>
        <div
            class="alert <?= ($mensaje["tipo"] ?? "") === "error"
                ? "alert-error"
                : "alert-success" ?>"
            role="status"
        >
            <?= escaparAdmin($mensaje["texto"] ?? "") ?>
        </div>
    <?php endif; ?>

    <?php if ($incidenciaEditar): ?>
        <section class="card edit-card" id="formulario-edicion">
            <div class="card-heading">
                <div>
                    <p class="eyebrow">EDITAR REPORTE #<?= (int) $incidenciaEditar["id"] ?></p>
                    <h2>Modificar incidencia</h2>
                    <p>Actualiza la información y guarda los cambios.</p>
                </div>

                <a class="btn-secondary btn-link" href="admin.php">
                    Cancelar edición
                </a>
            </div>

            <form action="admin.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $tokenHTML ?>">
                <input type="hidden" name="accion" value="actualizar">
                <input type="hidden" name="id" value="<?= (int) $incidenciaEditar["id"] ?>">

                <div class="form-grid">

                    <div class="field">
                        <label for="edit-reportante">Nombre de quien reporta *</label>
                        <input
                            type="text"
                            id="edit-reportante"
                            name="reportante"
                            maxlength="100"
                            value="<?= escaparAdmin($incidenciaEditar["reportante"]) ?>"
                            required
                        >
                    </div>

                    <div class="field">
                        <label for="edit-laboratorio">Laboratorio *</label>
                        <input
                            type="text"
                            id="edit-laboratorio"
                            name="laboratorio"
                            maxlength="100"
                            value="<?= escaparAdmin($incidenciaEditar["laboratorio"]) ?>"
                            required
                        >
                    </div>

                    <div class="field">
                        <label for="edit-equipo">Equipo afectado *</label>
                        <input
                            type="text"
                            id="edit-equipo"
                            name="equipo"
                            maxlength="150"
                            value="<?= escaparAdmin($incidenciaEditar["equipo"]) ?>"
                            required
                        >
                    </div>

                    <div class="field">
                        <label for="edit-prioridad">Prioridad *</label>
                        <select id="edit-prioridad" name="prioridad" required>
                            <?php foreach ($prioridadesPermitidas as $prioridad): ?>
                                <option
                                    value="<?= escaparAdmin($prioridad) ?>"
                                    <?= $incidenciaEditar["prioridad"] === $prioridad
                                        ? "selected" : "" ?>
                                >
                                    <?= escaparAdmin($prioridad) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="field field-full">
                        <label for="edit-descripcion">Descripción del problema *</label>
                        <textarea
                            id="edit-descripcion"
                            name="descripcion"
                            rows="5"
                            maxlength="5000"
                            required
                        ><?= escaparAdmin($incidenciaEditar["descripcion"]) ?></textarea>
                    </div>

                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-primary">
                        Guardar cambios
                    </button>

                    <a class="btn-secondary btn-link" href="admin.php">
                        Cancelar
                    </a>
                </div>
            </form>
        </section>
    <?php endif; ?>

    <section class="card filters-card">
        <div class="card-heading">
            <div>
                <h2>Buscar y filtrar</h2>
                <p>Reduce los resultados según los criterios seleccionados.</p>
            </div>
        </div>

        <form method="GET" action="admin.php" class="filters-grid">

            <div class="field">
                <label for="f-laboratorio">Laboratorio</label>
                <input
                    type="text"
                    id="f-laboratorio"
                    name="laboratorio"
                    maxlength="100"
                    placeholder="Ej. Laboratorio 1"
                    value="<?= escaparAdmin($filtroLaboratorio) ?>"
                >
            </div>

            <div class="field">
                <label for="f-estado">Estado</label>
                <select id="f-estado" name="estado">
                    <option value="">Todos los estados</option>
                    <?php foreach ($estadosPermitidos as $estado): ?>
                        <option
                            value="<?= escaparAdmin($estado) ?>"
                            <?= $filtroEstado === $estado ? "selected" : "" ?>
                        >
                            <?= escaparAdmin($estado) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field">
                <label for="f-prioridad">Prioridad</label>
                <select id="f-prioridad" name="prioridad">
                    <option value="">Todas las prioridades</option>
                    <?php foreach ($prioridadesPermitidas as $prioridad): ?>
                        <option
                            value="<?= escaparAdmin($prioridad) ?>"
                            <?= $filtroPrioridad === $prioridad ? "selected" : "" ?>
                        >
                            <?= escaparAdmin($prioridad) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-actions">
                <button type="submit" class="btn-primary">Filtrar</button>
                <a href="admin.php" class="btn-secondary btn-link">Limpiar</a>
            </div>

        </form>
    </section>

    <section class="table-section">

        <div class="table-title">
            <div>
                <h2>Listado de incidencias</h2>
                <p>
                    Registros encontrados:
                    <strong><?= $totalVisible ?></strong>
                </p>
            </div>
        </div>

        <?php if (empty($incidencias)): ?>

            <div class="card empty-state">
                <h3>No hay incidencias para mostrar</h3>
                <p>
                    Prueba con otros filtros o registra una incidencia nueva.
                </p>
                <a href="index.php" class="btn-primary btn-link">
                    Registrar incidencia
                </a>
            </div>

        <?php else: ?>

            <div class="table-responsive table-wrapper">
                <table class="table table-hover align-middle mb-0 incidencias-table">
                    
                    <thead>
                        <tr>
                            <th>N.º</th>
                            <th>Reporte</th>
                            <th>Laboratorio / Equipo</th>
                            <th>Prioridad</th>
                            <th>Estado</th>
                            <th>Fecha de registro</th>
                            <th>Última modificación</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>


                    <tbody>
                        <?php foreach ($incidencias as $incidencia): ?>
                            <tr>
                                <td>
                                    <strong>#<?= (int) $incidencia["id"] ?></strong>
                                </td>

                                <td>
                                    <strong>
                                        <?= escaparAdmin($incidencia["reportante"]) ?>
                                    </strong>
                                    <span class="table-description">
                                        <?= nl2br(escaparAdmin($incidencia["descripcion"])) ?>
                                    </span>
                                </td>

                                <td>
                                    <strong>
                                        <?= escaparAdmin($incidencia["laboratorio"]) ?>
                                    </strong>
                                    <span class="table-description">
                                        <?= escaparAdmin($incidencia["equipo"]) ?>
                                    </span>
                                </td>

                                <td>
                                    <span class="priority-badge priority-<?= strtolower(escaparAdmin($incidencia["prioridad"])) ?>">
                                        <?= escaparAdmin($incidencia["prioridad"]) ?>
                                    </span>
                                </td>

                                <td>
                                    <form method="POST" action="admin.php" class="state-form">
                                        <input type="hidden" name="csrf_token" value="<?= $tokenHTML ?>">
                                        <input type="hidden" name="accion" value="cambiar_estado">
                                        <input type="hidden" name="id" value="<?= (int) $incidencia["id"] ?>">

                                        <label class="visually-hidden" for="estado-<?= (int) $incidencia["id"] ?>">
                                            Cambiar estado de la incidencia <?= (int) $incidencia["id"] ?>
                                        </label>

                                        <select
                                            id="estado-<?= (int) $incidencia["id"] ?>"
                                            name="estado"
                                            onchange="this.form.submit()"
                                            class="state-select"
                                        >
                                            <?php foreach ($estadosPermitidos as $estado): ?>
                                                <option
                                                    value="<?= escaparAdmin($estado) ?>"
                                                    <?= $incidencia["estado"] === $estado
                                                        ? "selected" : "" ?>
                                                >
                                                    <?= escaparAdmin($estado) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>

                                        <noscript>
                                            <button type="submit" class="btn-small">
                                                Guardar estado
                                            </button>
                                        </noscript>
                                    </form>
                                </td>

                                <td>
                                    <span class="date-text">
                                        <?= date(
                                            "d/m/Y H:i",
                                            strtotime($incidencia["fecha_registro"])
                                        ) ?>
                                    </span>
                                </td>

                                <td>
                                    <?php
                                    $sinModificar =
                                        strtotime($incidencia["fecha_actualizacion"]) ===
                                        strtotime($incidencia["fecha_registro"]);
                                    ?>

                                    <?php if ($sinModificar): ?>

                                        <span class="date-text">Sin modificar</span>

                                    <?php else: ?>

                                        <span class="date-text">
                                            <?= date(
                                                "d/m/Y H:i",
                                                strtotime($incidencia["fecha_actualizacion"])
                                            ) ?>
                                        </span>

                                    <?php endif; ?>
                                </td>

                                <td>
                                    <div class="action-buttons">

                                        <a
                                            class="btn-small btn-edit"
                                            href="admin.php?editar=<?= (int) $incidencia["id"] ?>#formulario-edicion"
                                        >
                                            Editar
                                        </a>

                                        
                                        <form
                                            action="admin.php"
                                            method="POST"
                                            class="delete-form"
                                            data-id="<?= (int) $incidencia["id"] ?>"
                                        >
                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?= $tokenHTML ?>"
                                            >

                                            <input type="hidden" name="accion" value="eliminar">

                                            <input
                                                type="hidden"
                                                name="id"
                                                value="<?= (int) $incidencia["id"] ?>"
                                            >

                                            <button type="submit" class="btn-small btn-delete">
                                                Eliminar
                                            </button>
                                        </form>


                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php endif; ?>

    </section>

</main>

<?php
require_once __DIR__ . "/includes/footer.php";
?>
