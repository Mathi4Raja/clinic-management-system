/**
 * Auth UI & Logic
 */

document.addEventListener('DOMContentLoaded', () => {
    const loginForm = document.getElementById('login-form');
    const loginError = document.getElementById('login-error');
    const authContainer = document.getElementById('auth-container');
    const dashboardContainer = document.getElementById('dashboard-container');
    const logoutBtn = document.getElementById('logout-btn');

    // Handle Login
    if (loginForm) {
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const email = document.getElementById('login-email').value;
            const password = document.getElementById('login-password').value;

            try {
                const data = await API.request('auth/login.php', {
                    method: 'POST',
                    body: JSON.stringify({ email, password })
                });

                if (data.success) {
                    API.setCSRF(data.csrf_token);
                    showDashboard(data.user);
                }
            } catch (err) {
                loginError.textContent = err.message;
                loginError.classList.remove('hidden');
            }
        });
    }

    // Handle Signup
    const signupForm = document.getElementById('signup-form');
    const signupError = document.getElementById('signup-error');
    if (signupForm) {
        signupForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const payload = {
                email: document.getElementById('signup-email').value,
                password: document.getElementById('signup-password').value,
                full_name: document.getElementById('signup-name').value,
                dob: document.getElementById('signup-dob').value,
                gender: document.getElementById('signup-gender').value,
                phone: document.getElementById('signup-phone').value
            };

            try {
                const data = await API.request('auth/register.php', {
                    method: 'POST',
                    body: JSON.stringify(payload)
                });

                if (data.success) {
                    // Show success and switch to login
                    signupError.textContent = data.message;
                    signupError.classList.remove('hidden', 'text-red-500');
                    signupError.classList.add('text-green-600', 'bg-green-50');

                    setTimeout(() => {
                        window.toggleAuth('login');
                        document.getElementById('login-email').value = payload.email;
                        signupError.classList.add('hidden');
                        signupError.classList.remove('text-green-600', 'bg-green-50');
                        signupError.classList.add('text-red-500');
                    }, 2000);
                }
            } catch (err) {
                signupError.textContent = err.message;
                signupError.classList.remove('hidden');
            }
        });
    }

    // Handle Logout
    if (logoutBtn) {
        logoutBtn.addEventListener('click', async () => {
            try {
                await API.request('auth/logout.php', { method: 'POST' });
                location.reload(); // Refresh to clear state
            } catch (err) {
                console.error('Logout failed');
            }
        });
    }

    /**
     * Transition to Dashboard View
     * @param {object} user 
     */
    function showDashboard(user) {
        // Hide Landing View
        const landingNav = document.getElementById('landing-nav');
        const landingView = document.getElementById('landing-view');
        if (landingNav) landingNav.classList.add('hidden');
        if (landingView) landingView.classList.add('hidden');

        // Show Dashboard
        authContainer.classList.add('hidden');
        dashboardContainer.classList.remove('hidden');

        // Reset scroll and update UI
        window.scrollTo(0, 0);
        document.body.style.overflow = 'auto';

        document.getElementById('role-badge').textContent = user.role;
        document.getElementById('user-display').textContent = `User: #${user.id}`;

        // Initialize Dashboard Content based on role
        if (window.Dashboard) {
            window.Dashboard.init(user.role);
        }
    }
});
