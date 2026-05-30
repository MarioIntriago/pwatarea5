document.addEventListener('DOMContentLoaded', () => {
    const forms = document.querySelectorAll('.needs-validation');

    forms.forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });

    document.querySelectorAll('.delete-form').forEach(form => {
        form.addEventListener('submit', event => {
            const ok = confirm('¿Seguro que deseas eliminar este registro?');
            if (!ok) {
                event.preventDefault();
            }
        });
    });

    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            bsAlert.close();
        }, 5000);
    });
});
