/**
 * Auth UI & Logic
 */

// Make showDashboard globally accessible
window.showDashboard = function(user) {
    console.log("Transitioning to dashboard for role:", user.role);
    
    // Elements
    const landingNav = document.getElementById('landing-nav');
    const landingView = document.getElementById('landing-view');
    const authContainer = document.getElementById('auth-container');
    const dashboardContainer = document.getElementById('dashboard-container');

    // Hide Landing & Auth
    if (landingNav) landingNav.classList.add('hidden');
    if (landingView) landingView.classList.add('hidden');
    if (authContainer) authContainer.classList.add('hidden');

    // Show Dashboard
    if (dashboardContainer) {
        dashboardContainer.classList.remove('hidden');
        dashboardContainer.classList.add('flex'); // Ensure flex is applied
    }

    // Reset scroll and update UI
    window.scrollTo(0, 0);
    document.body.style.overflow = 'auto';

    const roleBadge = document.getElementById('role-badge');
    const userDisplay = document.getElementById('user-display');
    
    if (roleBadge) roleBadge.textContent = user.role;
    if (userDisplay) userDisplay.textContent = user.display_name || user.email || `User #${user.id}`;

    // Initialize Dashboard Content based on role
    if (window.Dashboard) {
        window.Dashboard.init(user.role);
    } else {
        console.error("Dashboard controller not found");
    }
};

document.addEventListener('DOMContentLoaded', async () => {
    const loginForm = document.getElementById('login-form');
    const loginError = document.getElementById('login-error');
    const logoutBtn = document.getElementById('logout-btn');

    // Check for existing session on load
    try {
        const data = await API.request('auth/login.php', { method: 'GET' });
        if (data.authenticated && data.user) {
            window.showDashboard(data.user);
        }
    } catch (err) {
        // Not authenticated, stay on landing
        console.log("No active session found.");
    }

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

                if (data.success && data.user) {
                    API.setCSRF(data.csrf_token);
                    window.showDashboard(data.user);
                } else {
                    throw new Error("Login failed: Invalid user data received");
                }
            } catch (err) {
                if (loginError) {
                    loginError.textContent = err.message;
                    loginError.classList.remove('hidden');
                }
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
                    if (signupError) {
                        signupError.textContent = data.message;
                        signupError.classList.remove('hidden', 'text-red-500');
                        signupError.classList.add('text-green-600', 'bg-green-50');
                    }

                    setTimeout(() => {
                        if (window.toggleAuth) window.toggleAuth('login');
                        document.getElementById('login-email').value = payload.email;
                        if (signupError) {
                            signupError.classList.add('hidden');
                            signupError.classList.remove('text-green-600', 'bg-green-50');
                            signupError.classList.add('text-red-500');
                        }
                    }, 2000);
                }
            } catch (err) {
                if (signupError) {
                    signupError.textContent = err.message;
                    signupError.classList.remove('hidden');
                }
            }
        });
    }

    // Handle Logout
    if (logoutBtn) {
        logoutBtn.addEventListener('click', async () => {
            try {
                await API.request('auth/logout.php', { method: 'POST' });
                location.reload(); 
            } catch (err) {
                console.error('Logout failed');
            }
        });
    }
});
