document.addEventListener("DOMContentLoaded", function () {
    // Inicializar Tooltips de Bootstrap en toda la aplicación
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Confirmación para eliminación o cambios críticos
    const confirmButtons = document.querySelectorAll('.btn-confirm-action');
    confirmButtons.forEach(btn => {
        btn.addEventListener('click', function (e) {
            if (!confirm('¿Está seguro de ejecutar esta acción en la base de datos de la Municipalidad?')) {
                e.preventDefault();
            }
        });
    });
});