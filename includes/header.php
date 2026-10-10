<?php
$tituloPagina = $tituloPagina ?? "Sistema de Incidencias | UISIL";
$paginaActual = $paginaActual ?? "";

$autenticado = isset($_SESSION["admin_id"]);

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$tokenNavbar = htmlspecialchars(
    $_SESSION["csrf_token"],
    ENT_QUOTES,
    "UTF-8"
);

$nombreNavbar = htmlspecialchars(
    $_SESSION["admin_nombre"] ?? "Administrador",
    ENT_QUOTES,
    "UTF-8"
);

$tituloSeguro = htmlspecialchars(
    $tituloPagina,
    ENT_QUOTES,
    "UTF-8"
);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title><?= $tituloSeguro ?></title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB"
        crossorigin="anonymous"
    >

    <link rel="stylesheet" href="css/estilos.css">
</head>

<body>

<nav class="navbar navbar-expand-xl navbar-dark navbar-verde shadow-sm">
    <div class="container-fluid px-3 px-xl-5">

        <a class="navbar-brand d-flex align-items-center gap-2"
           href="index.php">

            <span class="marca-logo">IT</span>

            <span class="marca-texto">
                <strong>Mesa de ayuda</strong>
                <small>Incidencias de laboratorio</small>
            </span>

        </a>

        <button
            class="navbar-toggler"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#navbarPrincipal"
            aria-controls="navbarPrincipal"
            aria-expanded="false"
            aria-label="Abrir menú de navegación"
        >
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarPrincipal">

            <ul class="navbar-nav me-auto mb-2 mb-lg-0">

                <?php if ($autenticado): ?>

                    <li class="nav-item">
                        <a
                            class="nav-link <?= $paginaActual === "incidencias" ? "active" : "" ?>"
                            href="admin.php"
                            <?= $paginaActual === "incidencias" ? 'aria-current="page"' : "" ?>
                        >
                            Incidencias
                        </a>
                    </li>

                    <li class="nav-item">
                        <a
                            class="nav-link <?= $paginaActual === "registro" ? "active" : "" ?>"
                            href="index.php"
                            <?= $paginaActual === "registro" ? 'aria-current="page"' : "" ?>
                        >
                            Registrar incidencia
                        </a>
                    </li>

                    <li class="nav-item">
                        <a
                            class="nav-link <?= $paginaActual === "usuarios" ? "active" : "" ?>"
                            href="usuarios.php"
                            <?= $paginaActual === "usuarios" ? 'aria-current="page"' : "" ?>
                        >
                            Administradores
                        </a>
                    </li>

                <?php else: ?>

                    <li class="nav-item">
                        <a
                            class="nav-link <?= $paginaActual === "registro" ? "active" : "" ?>"
                            href="index.php"
                        >
                            Reportar incidencia
                        </a>
                    </li>

                    <li class="nav-item">
                        <a
                            class="nav-link <?= $paginaActual === "login" ? "active" : "" ?>"
                            href="login.php"
                        >
                            Administración
                        </a>
                    </li>

                <?php endif; ?>

            </ul>

            <?php if ($autenticado): ?>

                <div class="navbar-usuario">

                    <span class="usuario-saludo">
                        Hola, <strong><?= $nombreNavbar ?></strong>
                    </span>

                    <form action="login.php" method="POST">
                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= $tokenNavbar ?>"
                        >

                        <input type="hidden" name="accion" value="logout">

                        <button type="submit" class="btn btn-outline-light btn-sm">
                            Cerrar sesión
                        </button>
                    </form>

                </div>

            <?php endif; ?>

        </div>
    </div>
</nav>
