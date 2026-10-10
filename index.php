<?php
session_start();

require_once __DIR__ . "/config/conexion.php";

// Crear token de seguridad para el formulario.
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$errores = [];
$valores = [
    "reportante" => "",
    "laboratorio" => "",
    "equipo" => "",
    "descripcion" => "",
    "prioridad" => "Media"
];

// Recuperar mensaje después de guardar una incidencia.
$mensajeExito = $_SESSION["mensaje_incidencia"] ?? "";
unset($_SESSION["mensaje_incidencia"]);

$prioridadesPermitidas = ["Baja", "Media", "Alta", "Urgente"];

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $token = $_POST["csrf_token"] ?? "";

    if (
        !is_string($token) ||
        !hash_equals($_SESSION["csrf_token"], $token)
    ) {
        $errores[] = "El formulario ha caducado o la solicitud no es válida. Recarga la página.";

    } else {

        // Recoger los datos de forma segura.
        foreach ($valores as $campo => $valorActual) {
            $valorRecibido = $_POST[$campo] ?? "";

            $valores[$campo] = is_string($valorRecibido)
                ? trim($valorRecibido)
                : "";
        }

        // Validar campos obligatorios.
        if ($valores["reportante"] === "") {
            $errores[] = "El nombre de quien reporta es obligatorio.";
        } elseif (strlen($valores["reportante"]) > 100) {
            $errores[] = "El nombre no puede superar los 100 caracteres.";
        }

        if ($valores["laboratorio"] === "") {
            $errores[] = "El laboratorio es obligatorio.";
        } elseif (strlen($valores["laboratorio"]) > 100) {
            $errores[] = "El nombre del laboratorio no puede superar los 100 caracteres.";
        }

        if ($valores["equipo"] === "") {
            $errores[] = "Debes indicar el equipo afectado.";
        } elseif (strlen($valores["equipo"]) > 150) {
            $errores[] = "El nombre del equipo no puede superar los 150 caracteres.";
        }

        if ($valores["descripcion"] === "") {
            $errores[] = "La descripción del problema es obligatoria.";
        } elseif (strlen($valores["descripcion"]) > 5000) {
            $errores[] = "La descripción no puede superar los 5000 bytes.";
        }

        if (!in_array(
            $valores["prioridad"],
            $prioridadesPermitidas,
            true
        )) {
            $errores[] = "Selecciona una prioridad válida.";
        }

        // Registrar la incidencia si todo está correcto.
        if (empty($errores)) {

            $stmt = $conn->prepare(
                "INSERT INTO incidencias
                (reportante, laboratorio, equipo, descripcion, prioridad)
                VALUES (?, ?, ?, ?, ?)"
            );

            if ($stmt) {
                $stmt->bind_param(
                    "sssss",
                    $valores["reportante"],
                    $valores["laboratorio"],
                    $valores["equipo"],
                    $valores["descripcion"],
                    $valores["prioridad"]
                );

                if ($stmt->execute()) {
                    $_SESSION["mensaje_incidencia"] =
                        "La incidencia se registró correctamente. Su número de reporte es #" .
                        $stmt->insert_id . ".";

                    $stmt->close();

                    // Evitar duplicados al actualizar el navegador.
                    header("Location: index.php");
                    exit;

                } else {
                    error_log("Error al registrar incidencia: " . $stmt->error);
                    $errores[] = "No se pudo registrar la incidencia. Inténtalo nuevamente.";
                    $stmt->close();
                }
            } else {
                error_log("Error al preparar el registro: " . $conn->error);
                $errores[] = "Ocurrió un problema con la base de datos.";
            }
        }
    }
}

function escaparHTML(string $texto): string
{
    return htmlspecialchars($texto, ENT_QUOTES, "UTF-8");
}
?>

<?php
$tituloPagina = "Registrar incidencia | UISIL";
$paginaActual = "registro";

require_once __DIR__ . "/includes/header.php";
?>

<main class="container public-container">

    <section class="hero">
        <p class="eyebrow">SOPORTE TECNOLÓGICO · UISIL</p>
        <h1>Reportar una incidencia</h1>
        <p>
            Utiliza este formulario para comunicar fallas en los equipos
            o servicios tecnológicos de los laboratorios.
        </p>
    </section>

    <section class="card">

        <div class="card-heading">
            <div>
                <h2>Información del reporte</h2>
                <p>Completa los datos del problema para registrar la solicitud.</p>
            </div>
            <span class="required-note">* Campos obligatorios</span>
        </div>

        <?php if ($mensajeExito !== ""): ?>
            <div class="alert alert-success" role="status">
                <?= escaparHTML($mensajeExito) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($errores)): ?>
            <div class="alert alert-error" role="alert">
                <strong>No se pudo completar el registro:</strong>
                <ul>
                    <?php foreach ($errores as $error): ?>
                        <li><?= escaparHTML($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="index.php" method="POST">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= escaparHTML($_SESSION["csrf_token"]) ?>"
            >

            <div class="form-grid">

                <div class="field">
                    <label for="reportante">Nombre de quien reporta *</label>
                    <input
                        type="text"
                        id="reportante"
                        name="reportante"
                        maxlength="100"
                        autocomplete="name"
                        placeholder="Nombre completo"
                        value="<?= escaparHTML($valores["reportante"]) ?>"
                        required
                    >
                </div>

                <div class="field">
                    <label for="laboratorio">Laboratorio *</label>
                    <input
                        type="text"
                        id="laboratorio"
                        name="laboratorio"
                        maxlength="100"
                        placeholder="Ej. Laboratorio 2"
                        value="<?= escaparHTML($valores["laboratorio"]) ?>"
                        required
                    >
                </div>

                <div class="field">
                    <label for="equipo">Equipo afectado *</label>
                    <input
                        type="text"
                        id="equipo"
                        name="equipo"
                        maxlength="150"
                        placeholder="Ej. Computadora PC-15"
                        value="<?= escaparHTML($valores["equipo"]) ?>"
                        required
                    >
                </div>

                <div class="field">
                    <label for="prioridad">Prioridad *</label>
                    <select id="prioridad" name="prioridad" required>
                        <?php foreach ($prioridadesPermitidas as $prioridad): ?>
                            <option
                                value="<?= escaparHTML($prioridad) ?>"
                                <?= $valores["prioridad"] === $prioridad
                                    ? "selected"
                                    : "" ?>
                            >
                                <?= escaparHTML($prioridad) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small>Selecciona la urgencia del problema.</small>
                </div>

                <div class="field field-full">
                    <label for="descripcion">Descripción del problema *</label>
                    <textarea
                        id="descripcion"
                        name="descripcion"
                        rows="5"
                        maxlength="5000"
                        placeholder="Describe la falla y cualquier detalle importante..."
                        required
                    ><?= escaparHTML($valores["descripcion"]) ?></textarea>
                </div>

            </div>

            <div class="form-actions">
                <button type="submit" class="btn-primary">
                    Registrar incidencia
                </button>
                <button type="reset" class="btn-secondary">
                    Limpiar formulario
                </button>
            </div>

        </form>
    </section>

    <p class="page-note">
        El estado inicial de cada reporte será Pendiente.
        La fecha de registro se asigna automáticamente.
    </p>

</main>

<?php
require_once __DIR__ . "/includes/footer.php";
?>