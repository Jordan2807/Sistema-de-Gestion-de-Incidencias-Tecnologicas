
<footer class="app-footer">
    <div class="container-fluid px-3 px-xl-5">
        <p class="mb-1">
            <strong>UISIL</strong> · Facultad de Ingeniería de Sistemas
        </p>
        <small>Sistema de Gestión de Incidencias de Laboratorios</small>
    </div>
</footer>

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
    crossorigin="anonymous"
></script>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


<script>
document.addEventListener("submit", function (event) {
    const formulario = event.target;

    if (
        !(formulario instanceof HTMLFormElement) ||
        !formulario.matches("form.delete-form")
    ) {
        return;
    }

    event.preventDefault();

    const tipo = formulario.dataset.tipo === "usuario"
        ? "usuario"
        : "incidencia";

    const id = formulario.dataset.id || "";

    const esUsuario = tipo === "usuario";

    const titulo = esUsuario
        ? "¿Eliminar usuario administrativo?"
        : "¿Eliminar incidencia?";

    const mensaje = esUsuario
        ? `Se eliminará la cuenta administrativa #${id}. La persona ya no podrá iniciar sesión con esa cuenta.`
        : `Se eliminará la incidencia #${id}. Esta acción no se puede deshacer.`;

    // Respaldo por si SweetAlert2 no está disponible.
    if (typeof Swal === "undefined") {
        if (window.confirm(titulo + "\n\n" + mensaje)) {
            formulario.submit();
        }
        return;
    }

    Swal.fire({
        title: titulo,
        text: mensaje,
        icon: "warning",

        showCancelButton: true,
        confirmButtonText: esUsuario
            ? "Sí, eliminar usuario"
            : "Sí, eliminar",

        cancelButtonText: "Cancelar",

        confirmButtonColor: "#dc3545",
        cancelButtonColor: "#0f766e",

        reverseButtons: true,
        focusCancel: true,
        allowOutsideClick: false
    }).then(function (resultado) {
        if (resultado.isConfirmed) {
            formulario.submit();
        }
    });
});
</script>


</body>
</html>
