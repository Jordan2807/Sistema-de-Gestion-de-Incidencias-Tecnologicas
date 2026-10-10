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
    die("Error de conexión con la base de datos.");
}

$conn->set_charset("utf8mb4");

?>
