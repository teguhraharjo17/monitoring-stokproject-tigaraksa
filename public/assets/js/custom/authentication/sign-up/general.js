"use strict";

// Class definition
var KTSignupGeneral = function() {
    // Elements
    var form;
    var submitButton;
    var validator;
    var passwordMeter;

    // Handle form
    var handleForm  = function() {
        // Init form validation rules
        validator = FormValidation.formValidation(
            form,
            {
                fields: {
                    'name': {
                        validators: {
                            notEmpty: {
                                message: 'Name is required'
                            }
                        }
                    },
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
                            },
                            callback: {
                                message: 'Your password is too weak. Use a mix of letters, numbers & symbols.',
                                callback: function(input) {
                                    const score = passwordMeter.getScore();

                                    if (input.value.length === 0) return true;
                                    if (score <= 30) return false;

                                    return true;
                                }
                            }
                        }
                    },
                    'password_confirmation': {
                        validators: {
                            notEmpty: {
                                message: 'Password confirmation is required'
                            },
                            identical: {
                                compare: function() {
                                    return form.querySelector('[name="password"]').value;
                                },
                                message: 'Passwords do not match'
                            }
                        }
                    }
                },
                'role': {
                    validators: {
                        notEmpty: {
                            message: 'Role is required'
                        }
                    }
                },
                plugins: {
                    trigger: new FormValidation.plugins.Trigger({
                        event: {
                            password: false
                        }
                    }),
                    bootstrap: new FormValidation.plugins.Bootstrap5({
                        rowSelector: '.fv-row',
                        eleInvalidClass: '',
                        eleValidClass: ''
                    })
                }
            }
        );

        // Handle form submit
        submitButton.addEventListener('click', function (e) {
            e.preventDefault();

            validator.revalidateField('password');

            validator.validate().then(function(status) {
                if (status === 'Valid') {
                    submitButton.setAttribute('data-kt-indicator', 'on');
                    submitButton.disabled = true;

                    axios.post(form.getAttribute('action'), new FormData(form))
                        .then(function (response) {
                            form.reset();

                            const redirectUrl = form.getAttribute('data-kt-redirect-url');
                            if (redirectUrl) {
                                window.location.href = redirectUrl;
                            }
                        })
                        .catch(function (error) {
                            if (error.response && error.response.status === 422 && error.response.data.errors) {
                                // Loop dan tampilkan setiap error Laravel ke field yang sesuai
                                const errors = error.response.data.errors;

                                Object.keys(errors).forEach(function(field) {
                                    const message = errors[field][0];

                                    validator.updateValidatorOption(field, 'notEmpty', 'message', message);
                                    validator.updateFieldStatus(field, 'Invalid');
                                });
                            } else {
                                // Unexpected error
                                Swal.fire({
                                    text: "Something went wrong. Please try again.",
                                    icon: "error",
                                    confirmButtonText: "Ok, got it!",
                                    buttonsStyling: false,
                                    customClass: {
                                        confirmButton: "btn btn-primary"
                                    }
                                });
                            }
                        })
                        .finally(function () {
                            submitButton.removeAttribute('data-kt-indicator');
                            submitButton.disabled = false;
                        });
                }
            });
        });

        // Revalidate password on input change
        form.querySelector('input[name="password"]').addEventListener('input', function() {
            if (this.value.length > 0) {
                validator.updateFieldStatus('password', 'NotValidated');
            }
        });
    }

    // Public functions
    return {
        init: function() {
            form = document.querySelector('#kt_sign_up_form');
            submitButton = document.querySelector('#kt_sign_up_submit');
            passwordMeter = KTPasswordMeter.getInstance(form.querySelector('[data-kt-password-meter="true"]'));

            handleForm();
        }
    };
}();

// On document ready
KTUtil.onDOMContentLoaded(function() {
    KTSignupGeneral.init();
});
