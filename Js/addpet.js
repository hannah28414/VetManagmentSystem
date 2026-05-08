document.addEventListener('DOMContentLoaded', function () {
    var form = document.querySelector('form[method="POST"]');
    if (!form) return;

    var messageBox = document.getElementById('petAjaxMessage');
    var submitButton = form.querySelector('button[type="submit"]');
    var submitUrl = form.getAttribute('action') || window.location.href;

    function showMessage(type, text) {
        if (!messageBox) return;
        messageBox.className = 'alert mt-3 ' + (type === 'success' ? 'alert-success' : 'alert-danger');
        messageBox.textContent = text;
        messageBox.style.display = 'block';
    }

    form.addEventListener('submit', function (event) {
        event.preventDefault();

        var formData = new FormData(form);
        var originalText = submitButton ? submitButton.innerHTML : '';

        if (submitButton) {
            submitButton.disabled = true;
            submitButton.innerHTML = 'Saving...';
        }

        fetch(submitUrl, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: formData
        })
            .then(function (response) {
                return response.text().then(function (text) {
                    var data;

                    try {
                        data = text ? JSON.parse(text) : {};
                    } catch (error) {
                        data = {
                            status: 'error',
                            message: text || 'Unexpected server response.'
                        };
                    }

                    return {
                        ok: response.ok,
                        data: data
                    };
                });
            })
            .then(function (result) {
                if (result.ok && result.data.status === 'success') {
                    showMessage('success', result.data.message || 'Pet added successfully.');
                    form.reset();

                    if (result.data.redirect) {
                        setTimeout(function () {
                            window.location.href = result.data.redirect;
                        }, 1000);
                    }
                } else {
                    showMessage('error', result.data.message || 'Unable to save pet. Please try again.');
                }
            })
            .catch(function () {
                showMessage('error', 'Network error. Please try again.');
            })
            .finally(function () {
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalText;
                }
            });
    });
});
