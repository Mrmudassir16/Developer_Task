$(document).ready(function () {
    const $form = $('#register-form');
    const $alertBox = $('#alert-box');
    const $alertMsg = $('#alert-message');
    const $loader = $('#loader-overlay');

    function showAlert(message, isSuccess = false) {
        $alertBox.removeClass('alert-success');
        $alertBox.css('display', 'none');
        $alertMsg.text(message);
        
        if (isSuccess) {
            $alertBox.addClass('alert-success');
            $alertBox.find('i').attr('class', 'fa-solid fa-circle-check me-2');
        } else {
            $alertBox.find('i').attr('class', 'fa-solid fa-circle-exclamation me-2');
        }
        
        $alertBox.css('display', 'flex').hide().fadeIn(300);
    }

    $form.on('submit', function (e) {
        e.preventDefault();
        
        const name = $('#name').val().trim();
        const email = $('#email').val().trim();
        const password = $('#password').val();
        const confirmPassword = $('#confirm-password').val();

        // Client-side validations
        if (!name || !email || !password || !confirmPassword) {
            showAlert('All fields are required.');
            return;
        }

        // Email validation pattern
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            showAlert('Please enter a valid email address.');
            return;
        }

        if (password.length < 6) {
            showAlert('Password must be at least 6 characters long.');
            return;
        }

        if (password !== confirmPassword) {
            showAlert('Passwords do not match.');
            return;
        }

        // Hide alert and show loader
        $alertBox.fadeOut(200);
        $loader.addClass('active');

        // jQuery AJAX Call (Strictly no form submission)
        $.ajax({
            url: 'php/register.php',
            type: 'POST',
            dataType: 'json',
            data: {
                name: name,
                email: email,
                password: password
            },
            success: function (response) {
                $loader.removeClass('active');
                if (response.success) {
                    showAlert(response.message, true);
                    $form.trigger('reset');
                    // Redirect to login after delay
                    setTimeout(function () {
                        window.location.href = 'login.html';
                    }, 1500);
                } else {
                    showAlert(response.message || 'Registration failed.');
                }
            },
            error: function (xhr, status, error) {
                $loader.removeClass('active');
                let errorMsg = 'An error occurred during registration. Please try again.';
                try {
                    const res = JSON.parse(xhr.responseText);
                    if (res && res.message) errorMsg = res.message;
                } catch (e) {}
                showAlert(errorMsg);
            }
        });
    });
});
