document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('login');
    const submitButton = document.getElementById('submit');

    function checkForm() {
        submitButton.disabled = !form.checkValidity();
    }

    form.addEventListener('input', checkForm);

    checkForm();
});
