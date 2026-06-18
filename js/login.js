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
        const warningSvg = `<svg class="icon-svg" style="color: #fca5a5;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>`;
        const successSvg = `<svg class="icon-svg" style="color: #a7f3d0;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>`;
        
        $alertBox.removeClass('alert-success');
        $alertBox.css('display', 'none');
        $alertMsg.text(message);
        
        const $iconContainer = $('#alert-icon-container');
        if (isSuccess) {
            $alertBox.addClass('alert-success');
            $iconContainer.html(successSvg);
        } else {
            $iconContainer.html(warningSvg);
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
