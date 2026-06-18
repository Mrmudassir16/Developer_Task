$(document).ready(function () {
    const $form = $('#login-form');
    const $alertBox = $('#alert-box');
    const $alertMsg = $('#alert-message');
    const $loader = $('#loader-overlay');

    // Check if user is already logged in
    if (localStorage.getItem('session_token')) {
        window.location.replace('profile.html');
        return;
    }

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
        
        const email = $('#email').val().trim();
        const password = $('#password').val();

        if (!email || !password) {
            showAlert('Please enter both email and password.');
            return;
        }

        // Hide alert and show loader
        $alertBox.fadeOut(200);
        $loader.addClass('active');

        // jQuery AJAX Call
        $.ajax({
            url: 'php/login.php',
            type: 'POST',
            dataType: 'json',
            data: {
                email: email,
                password: password
            },
            success: function (response) {
                $loader.removeClass('active');
                if (response.success && response.token) {
                    showAlert(response.message || 'Login successful! Redirecting...', true);
                    
                    // Save session token in localStorage
                    localStorage.setItem('session_token', response.token);
                    
                    // Redirect to profile page
                    setTimeout(function () {
                        window.location.href = 'profile.html';
                    }, 1000);
                } else {
                    showAlert(response.message || 'Invalid email or password.');
                }
            },
            error: function (xhr, status, error) {
                $loader.removeClass('active');
                let errorMsg = 'An error occurred during login. Please try again.';
                try {
                    const res = JSON.parse(xhr.responseText);
                    if (res && res.message) errorMsg = res.message;
                } catch (e) {}
                showAlert(errorMsg);
            }
        });
    });
});
