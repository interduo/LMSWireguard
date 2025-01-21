document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('login');
    const submitButton = document.getElementById('submit');
    const inputs = form.querySelectorAll('input[required]');

    function checkForm() {
        let allValid = true;
        inputs.forEach(input => {
            if (!input.checkValidity()) {
                allValid = false;
            }
        });
        submitButton.disabled = !allValid;
    }

    inputs.forEach(input => {
        input.addEventListener('input', checkForm);
    });

    checkForm();
});