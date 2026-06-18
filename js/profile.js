$(document).ready(function () {
    const token = localStorage.getItem('session_token');
    
    // Auth redirect check
    if (!token) {
        window.location.replace('login.html');
        return;
    }

    const $form = $('#profile-form');
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

    // Load user profile details
    function loadProfile() {
        $loader.addClass('active');
        
        $.ajax({
            url: 'php/profile.php',
            type: 'GET',
            dataType: 'json',
            headers: {
                'Authorization': 'Bearer ' + token
            },
            success: function (response) {
                $loader.removeClass('active');
                if (response.success) {
                    // Fill read-only account details
                    $('#account-name').val(response.data.name);
                    $('#account-email').val(response.data.email);
                    $('#profile-name-title').text(response.data.name);
                    $('#profile-email-title').text(response.data.email);
                    
                    // Fill editable profile details from MongoDB
                    if (response.data.profile) {
                        $('#profile-age').val(response.data.profile.age || '');
                        $('#profile-dob').val(response.data.profile.dob || '');
                        $('#profile-contact').val(response.data.profile.contact || '');
                        $('#profile-bio').val(response.data.profile.bio || '');
                    }
                } else {
                    showAlert(response.message || 'Failed to load profile.');
                }
            },
            error: function (xhr, status, error) {
                $loader.removeClass('active');
                if (xhr.status === 401) {
                    // Session expired or invalid, clear localStorage and redirect
                    localStorage.removeItem('session_token');
                    window.location.replace('login.html');
                } else {
                    let errorMsg = 'Failed to fetch profile. Please reload the page.';
                    try {
                        const res = JSON.parse(xhr.responseText);
                        if (res && res.message) errorMsg = res.message;
                    } catch (e) {}
                    showAlert(errorMsg);
                }
            }
        });
    }

    // Call loadProfile on ready
    loadProfile();

    // Handle Profile Form Submission (Update)
    $form.on('submit', function (e) {
        e.preventDefault();

        const age = $('#profile-age').val();
        const dob = $('#profile-dob').val();
        const contact = $('#profile-contact').val().trim();
        const bio = $('#profile-bio').val().trim();

        // Validations
        if (!age || !dob || !contact) {
            showAlert('Please fill in all the required profile details.');
            return;
        }

        if (age < 1 || age > 120) {
            showAlert('Please enter a valid age between 1 and 120.');
            return;
        }

        // Hide alert and show loader
        $alertBox.fadeOut(200);
        $loader.addClass('active');

        // jQuery AJAX Call to update profile
        $.ajax({
            url: 'php/profile.php',
            type: 'POST',
            dataType: 'json',
            headers: {
                'Authorization': 'Bearer ' + token
            },
            data: {
                age: age,
                dob: dob,
                contact: contact,
                bio: bio
            },
            success: function (response) {
                $loader.removeClass('active');
                if (response.success) {
                    showAlert(response.message || 'Profile updated successfully!', true);
                } else {
                    showAlert(response.message || 'Failed to update profile.');
                }
            },
            error: function (xhr, status, error) {
                $loader.removeClass('active');
                if (xhr.status === 401) {
                    localStorage.removeItem('session_token');
                    window.location.replace('login.html');
                } else {
                    let errorMsg = 'An error occurred while updating profile. Please try again.';
                    try {
                        const res = JSON.parse(xhr.responseText);
                        if (res && res.message) errorMsg = res.message;
                    } catch (e) {}
                    showAlert(errorMsg);
                }
            }
        });
    });

    // Handle Logout
    function handleLogout() {
        // Clear session from localStorage
        localStorage.removeItem('session_token');
        window.location.replace('login.html');
    }

    $('#logout-btn-top').on('click', handleLogout);
    $('#logout-btn-bottom').on('click', handleLogout);
});
