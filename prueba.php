<?php

require_once __DIR__ . "/config/conexion.php";

$resultado = $conn->query(
    "SELECT COUNT(*) AS total FROM incidencias"
);

if (!$resultado) {
    die("No se pudo consultar la tabla incidencias.");
}

$fila = $resultado->fetch_assoc();

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Prueba de conexión</title>
</head>
<body>
    <h1>Conexión exitosa</h1>

    <p>PHP se conectó correctamente a MySQL.</p>

    <p>
        Base de datos:
        sistema_incidencias
    </p>

    <p>
        Incidencias registradas:
        <?= (int) $fila["total"] ?>
    </p>

</body>
</html>

<?php
$conn->close();
?>
