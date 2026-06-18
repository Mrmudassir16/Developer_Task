// Check if session token exists in localStorage and redirect accordingly
const token = localStorage.getItem('session_token');
if (token) {
    window.location.replace('profile.html');
} else {
    window.location.replace('login.html');
}
