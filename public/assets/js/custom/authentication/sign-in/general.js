"use strict";

var KTSigninGeneral = function () {
    var form;
    var submitButton;
    var validator;

    var handleValidation = function () {
        validator = FormValidation.formValidation(
            form,
            {
                fields: {
                    'username': {
                        validators: {
                            notEmpty: {
                                message: 'Username is required'
                            }
                        }
                    },
                    'password': {
                        validators: {
                            notEmpty: {
                                message: 'Password is required'
                            }
                        }
                    }
                },
                plugins: {
                    trigger: new FormValidation.plugins.Trigger(),
                    bootstrap: new FormValidation.plugins.Bootstrap5({
                        rowSelector: '.fv-row'
                        // Hapus eleInvalidClass & eleValidClass untuk gunakan default Bootstrap highlight
                    })
                }
            }
        );
    }

    var handleSubmitAjax = function () {
        submitButton.addEventListener('click', function (e) {
            e.preventDefault();

            validator.validate().then(function (status) {
                if (status === 'Valid') {
                    submitButton.setAttribute('data-kt-indicator', 'on');
                    submitButton.disabled = true;

                    axios.post(form.getAttribute('action'), new FormData(form))
                        .then(function (response) {
                            form.reset();
                            const redirectUrl = form.getAttribute('data-kt-redirect-url');
                            if (redirectUrl) {
                                location.href = redirectUrl;
                            }
                        })
                        .catch(function (error) {
                            let message = "An error occurred while submitting the form.";
                            if (error.response && error.response.status === 422 && error.response.data?.errors) {
                                const errors = error.response.data.errors;
                                message = Object.values(errors).flat()[0];
                            }

                            Swal.fire({
                                text: message,
                                icon: "error",
                                confirmButtonText: "Ok, got it!",
                                buttonsStyling: false,
                                customClass: {
                                    confirmButton: "btn btn-primary"
                                }
                            });
                        })
                        .finally(function () {
                            submitButton.removeAttribute('data-kt-indicator');
                            submitButton.disabled = false;
                        });
                } else {
                    Swal.fire({
                        text: "Please fill in all required fields correctly.",
                        icon: "error",
                        confirmButtonText: "Ok, got it!",
                        buttonsStyling: false,
                        customClass: {
                            confirmButton: "btn btn-primary"
                        }
                    });
                }
            });
        });
    }

    var handlePasswordVisibilityToggle = function () {
        const visibilityToggles = document.querySelectorAll('[data-kt-password-meter-control="visibility"]');

        visibilityToggles.forEach(toggle => {
            const input = toggle.closest('.mb-3, .fv-row').querySelector('input');

            toggle.addEventListener('click', function () {
                const eyeIcon = toggle.querySelector('.bi-eye');
                const eyeSlashIcon = toggle.querySelector('.bi-eye-slash');

                if (input.getAttribute('type') === 'password') {
                    input.setAttribute('type', 'text');
                    eyeSlashIcon.classList.add('d-none');
                    eyeIcon.classList.remove('d-none');
                } else {
                    input.setAttribute('type', 'password');
                    eyeSlashIcon.classList.remove('d-none');
                    eyeIcon.classList.add('d-none');
                }
            });
        });
    }

    return {
        init: function () {
            form = document.querySelector('#kt_sign_in_form');
            submitButton = document.querySelector('#kt_sign_in_submit');

            handleValidation();
            handleSubmitAjax();
            handlePasswordVisibilityToggle();
        }
    };
}();

KTUtil.onDOMContentLoaded(function () {
    KTSigninGeneral.init();
});
