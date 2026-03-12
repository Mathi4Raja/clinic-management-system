<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clinic CMS - Advanced Healthcare Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            DEFAULT: '#ea580c', // Deeper orange for better contrast
                            light: '#fb923c',
                            dark: '#c2410c'
                        },
                        secondary: '#f97316',
                        dark: '#0f172a',
                        surface: '#f8fafc', // Light slate neutral for backgrounds
                    }
                }
            }
        }
    </script>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="js/landing.css?v=3">
</head>

<body class="bg-white text-slate-900 selection:bg-orange-500/30">
    <!-- Navigation -->
    <nav id="landing-nav"
        class="fixed top-0 w-full z-50 transition-all duration-300 border-b border-slate-200 bg-white/80 backdrop-blur-md">
        <div class="max-w-7xl mx-auto px-6 h-20 flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <div class="w-8 h-8 bg-primary rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <span class="text-xl font-bold tracking-tight font-['Poppins'] text-slate-900">Clinic CMS</span>
            </div>
            <div class="hidden md:flex items-center space-x-8">
                <a href="#features"
                    class="text-sm font-semibold text-slate-600 hover:text-primary transition-colors">Features</a>
                <a href="#ecosystem"
                    class="text-sm font-semibold text-slate-600 hover:text-primary transition-colors">Ecosystem</a>
                <a href="#stats"
                    class="text-sm font-semibold text-slate-600 hover:text-primary transition-colors">Results</a>
                <button onclick="showLogin()"
                    class="px-6 py-2 bg-slate-900 hover:bg-slate-800 rounded-full text-sm font-bold text-white transition-all shadow-lg shadow-slate-200/50">Launch
                    Portal</button>
            </div>
        </div>
    </nav>

    <!-- App Root -->
    <div id="app" class="relative">

        <div id="landing-view">
            <!-- Hero Section -->
            <section id="landing-hero"
                class="min-h-screen flex items-center relative overflow-hidden pt-20 bg-[#fffaf0]">
                <div class="absolute inset-0 hero-gradient"></div>
                <div class="max-w-7xl mx-auto px-6 grid md:grid-cols-2 gap-12 items-center relative z-10">
                    <div class="stagger-in">
                        <span
                            class="px-4 py-1.5 rounded-full bg-orange-500/10 border border-orange-500/20 text-orange-600 text-xs font-bold uppercase tracking-widest mb-6 inline-block">Next-Gen
                            Clinic Management</span>
                        <h1 class="text-5xl md:text-7xl font-bold font-['Poppins'] leading-tight text-slate-900 mb-6">
                            Healthcare at the speed of <span class="text-orange-500">thought.</span>
                        </h1>
                        <p class="text-lg text-slate-600 max-w-lg mb-10 leading-relaxed">
                            The fully-integrated clinical ecosystem. Streamline patient care, pharmacy management, and
                            lab
                            operations through one unified interface.
                        </p>
                        <div class="flex flex-col sm:flex-row gap-4 mb-12">
                            <button onclick="showLogin()"
                                class="px-8 py-4 bg-primary hover:bg-primary-dark text-white rounded-2xl font-bold transition-all hover:scale-105 shadow-xl shadow-primary/20">Launch
                                Portal</button>
                            <button
                                class="px-8 py-4 bg-white hover:bg-slate-50 text-slate-900 rounded-2xl font-bold border border-slate-200 transition-all shadow-sm">Book
                                Demo</button>
                        </div>
                    </div>
                    <div class="relative animate-float hidden md:block">
                        <div class="absolute -inset-10 bg-orange-500/20 blur-3xl rounded-full opacity-50"></div>
                        <div class="relative bg-white rounded-3xl border border-orange-100 shadow-2xl p-6 space-y-4">
                            <!-- Mock Dashboard UI -->
                            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                                <div class="flex items-center space-x-2">
                                    <div class="w-6 h-6 bg-primary rounded-md"></div>
                                    <span class="text-xs font-bold text-slate-700">Clinic CMS</span>
                                </div>
                                <div class="flex space-x-1"><div class="w-2 h-2 bg-green-400 rounded-full"></div><div class="w-2 h-2 bg-yellow-400 rounded-full"></div><div class="w-2 h-2 bg-red-400 rounded-full"></div></div>
                            </div>
                            <div class="grid grid-cols-3 gap-3">
                                <div class="bg-orange-50 rounded-xl p-3 border border-orange-100"><p class="text-[10px] text-orange-600 font-bold uppercase">Patients</p><p class="text-xl font-black text-slate-900">1,248</p></div>
                                <div class="bg-blue-50 rounded-xl p-3 border border-blue-100"><p class="text-[10px] text-blue-600 font-bold uppercase">Appts</p><p class="text-xl font-black text-slate-900">84</p></div>
                                <div class="bg-green-50 rounded-xl p-3 border border-green-100"><p class="text-[10px] text-green-600 font-bold uppercase">Revenue</p><p class="text-xl font-black text-slate-900">$9.2k</p></div>
                            </div>
                            <div class="space-y-2">
                                <div class="flex items-center justify-between bg-slate-50 rounded-xl p-3">
                                    <div class="flex items-center space-x-2"><div class="w-7 h-7 bg-orange-100 rounded-lg flex items-center justify-center text-xs font-black text-orange-600">JD</div><span class="text-xs font-bold text-slate-700">John Doe</span></div>
                                    <span class="text-[10px] bg-green-100 text-green-700 px-2 py-0.5 rounded-full font-bold">Completed</span>
                                </div>
                                <div class="flex items-center justify-between bg-slate-50 rounded-xl p-3">
                                    <div class="flex items-center space-x-2"><div class="w-7 h-7 bg-blue-100 rounded-lg flex items-center justify-center text-xs font-black text-blue-600">MS</div><span class="text-xs font-bold text-slate-700">Maria Smith</span></div>
                                    <span class="text-[10px] bg-orange-100 text-orange-700 px-2 py-0.5 rounded-full font-bold">Scheduled</span>
                                </div>
                                <div class="flex items-center justify-between bg-slate-50 rounded-xl p-3">
                                    <div class="flex items-center space-x-2"><div class="w-7 h-7 bg-purple-100 rounded-lg flex items-center justify-center text-xs font-black text-purple-600">AK</div><span class="text-xs font-bold text-slate-700">Alex Kumar</span></div>
                                    <span class="text-[10px] bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full font-bold">Lab Pending</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Section 1: Advantages -->
            <section id="features" class="py-32 bg-white">
                <div class="max-w-7xl mx-auto px-6">
                    <div class="text-center mb-20">
                        <h2 class="text-4xl font-bold font-['Poppins'] text-slate-900 mb-4">Precision Engineering</h2>
                        <p class="text-slate-500 max-w-2xl mx-auto text-lg">Every module is designed to eliminate
                            friction
                            and maximize patient outcomes.</p>
                    </div>
                    <div class="grid md:grid-cols-3 gap-8">
                        <div class="glass-card p-10">
                            <div
                                class="w-14 h-14 bg-orange-100 rounded-2xl flex items-center justify-center text-orange-600 mb-8">
                                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-slate-900 mb-4">Lab Intelligence</h3>
                            <p class="text-slate-600 leading-relaxed">Automated diagnostic tracking with instant alerts
                                for
                                critical results.</p>
                        </div>
                        <div class="glass-card p-10">
                            <div
                                class="w-14 h-14 bg-orange-100 rounded-2xl flex items-center justify-center text-orange-600 mb-8">
                                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-slate-900 mb-4">Pharmacy Protocol</h3>
                            <p class="text-slate-600 leading-relaxed">Integrated e-prescriptions and live inventory
                                management system.</p>
                        </div>
                        <div class="glass-card p-10">
                            <div
                                class="w-14 h-14 bg-orange-100 rounded-2xl flex items-center justify-center text-orange-600 mb-8">
                                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-slate-900 mb-4">Lightning Performance</h3>
                            <p class="text-slate-600 leading-relaxed">Optimized for high-concurrency environments with
                                sub-second API response.</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Section 2: Modular Ecosystem -->
            <section id="ecosystem" class="py-32 bg-[#fffaf0]">
                <div class="max-w-7xl mx-auto px-6 grid md:grid-cols-2 gap-20 items-center">
                    <div>
                        <h2 class="text-4xl font-bold font-['Poppins'] text-slate-900 mb-8">The Modular Future</h2>
                        <div class="space-y-6">
                            <div class="flex items-start space-x-4">
                                <div
                                    class="w-6 h-6 bg-orange-500 rounded-full flex-shrink-0 mt-1 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                            d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="font-bold text-slate-900">Doctor-Centric Design</h4>
                                    <p class="text-slate-500">Intuitive diagnosis forms and historical data access.</p>
                                </div>
                            </div>
                            <div class="flex items-start space-x-4">
                                <div
                                    class="w-6 h-6 bg-orange-500 rounded-full flex-shrink-0 mt-1 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                            d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="font-bold text-slate-900">Patient Self-Service</h4>
                                    <p class="text-slate-500">Booking, reviews, and data access on any device.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-white p-6 rounded-3xl shadow-sm border border-orange-100 mt-12">
                            <p class="text-3xl font-black text-orange-500">50k+</p>
                            <p class="text-sm font-bold text-slate-400">Patients Managed</p>
                        </div>
                        <div class="bg-white p-6 rounded-3xl shadow-sm border border-orange-100">
                            <p class="text-3xl font-black text-orange-500">99.9%</p>
                            <p class="text-sm font-bold text-slate-400">Uptime Reliability</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Section 3: Results & Trust -->
            <section id="stats" class="py-32 bg-slate-50 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-96 h-96 bg-primary/5 rounded-full blur-[100px] -mr-48 -mt-48">
                </div>
                <div class="max-w-7xl mx-auto px-6 relative z-10">
                    <div
                        class="bg-slate-900 rounded-[3rem] p-12 md:p-20 text-white relative overflow-hidden shadow-2xl">
                        <div class="grid md:grid-cols-2 gap-16 items-center">
                            <div>
                                <span
                                    class="text-primary font-bold uppercase tracking-widest text-[10px] bg-primary/10 px-3 py-1 rounded-full mb-4 inline-block">Measurable
                                    Impact</span>
                                <h2 class="text-4xl md:text-5xl font-extrabold font-['Poppins'] mb-8 leading-tight">
                                    Clinical Excellence <br>in Numbers</h2>
                                <p class="text-slate-400 text-lg mb-10 leading-relaxed">We deliver more than just
                                    software; we provide the backbone for high-performance clinics across the globe.</p>
                                <div class="flex items-center space-x-12">
                                    <div>
                                        <p class="text-5xl font-black text-white">12<span class="text-primary">%</span>
                                        </p>
                                        <p class="text-xs text-slate-500 font-bold uppercase tracking-wider mt-1">Error
                                            Reduction</p>
                                    </div>
                                    <div class="w-px h-12 bg-slate-800"></div>
                                    <div>
                                        <p class="text-5xl font-black text-white">2.4<span class="text-primary">x</span>
                                        </p>
                                        <p class="text-xs text-slate-500 font-bold uppercase tracking-wider mt-1">
                                            Process Speed</p>
                                    </div>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 gap-6">
                                <div
                                    class="bg-white/5 border border-white/10 p-8 rounded-[2.5rem] hover:bg-white/10 transition-colors group">
                                    <div
                                        class="w-12 h-12 bg-primary/20 text-primary rounded-2xl flex items-center justify-center mb-6">
                                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                            <path
                                                d="M14.017 21L14.017 18C14.017 16.8954 13.1216 16 12.017 16H9.01701V12H12.017C13.1216 12 14.017 11.1046 14.017 10V7H17.017V10C17.017 12.7614 14.7784 15 12.017 15V18H15.017C16.1216 18 17.017 18.8954 17.017 20V21H14.017Z" />
                                        </svg>
                                    </div>
                                    <p class="text-slate-300 italic text-lg leading-relaxed mb-8">"The transition to
                                        Clinic CMS was seamless. Our diagnostic turnaround time improved by 40% in just
                                        two months."</p>
                                    <div class="flex items-center space-x-4 border-t border-white/10 pt-6">
                                        <div
                                            class="w-10 h-10 bg-primary/20 text-primary font-bold rounded-full flex items-center justify-center text-xs">
                                            SC</div>
                                        <div>
                                            <p class="font-bold text-white">Dr. Sarah Chen</p>
                                            <p class="text-[10px] text-slate-500 font-bold uppercase tracking-wider">
                                                Chief of Medicine, Metro Health</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <!-- Auth View Container (Initially Hidden) -->
        <div id="auth-container"
            class="hidden fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xl">
            <div
                class="max-w-md w-full max-h-[90vh] overflow-y-auto p-8 md:p-10 rounded-[2.5rem] bg-white shadow-2xl relative border border-orange-100">
                <button onclick="hideLogin()"
                    class="absolute top-6 right-6 text-slate-400 hover:text-slate-600 transition-colors z-20">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
                <div class="text-center mb-8">
                    <h2 class="text-2xl font-bold font-['Poppins'] text-slate-900">Welcome Back</h2>
                    <p class="text-slate-500 mt-2 text-sm">Access your professional dashboard</p>
                </div>

                <div class="flex p-1 bg-slate-100 rounded-xl mb-8">
                    <button id="toggle-login" onclick="toggleAuth('login')"
                        class="flex-1 py-3 text-sm font-bold bg-white text-orange-600 rounded-lg shadow-sm transition-all duration-300">Login</button>
                    <button id="toggle-signup" onclick="toggleAuth('signup')"
                        class="flex-1 py-3 text-sm font-bold text-slate-500 hover:text-slate-700 transition-all duration-300">Sign
                        Up</button>
                </div>

                <!-- Login Form -->
                <form id="login-form" class="space-y-6">
                    <div>
                        <label class="block text-sm font-semibold text-slate-600 mb-2">Email Address</label>
                        <input type="email" id="login-email" required
                            class="w-full px-5 py-4 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-orange-500 focus:border-transparent outline-none transition-all text-slate-900">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-600 mb-2">Password</label>
                        <input type="password" id="login-password" required
                            class="w-full px-5 py-4 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-orange-500 focus:border-transparent outline-none transition-all text-slate-900">
                    </div>
                    <button type="submit"
                        class="w-full bg-orange-600 hover:bg-orange-700 orange-gradient text-white font-bold py-5 rounded-2xl shadow-lg shadow-orange-500/20 hover:scale-[1.02] transition-all duration-300">Authorize
                        Access</button>
                    <div id="login-error" class="hidden text-red-500 text-sm text-center mt-4 p-3 bg-red-50 rounded-xl">
                    </div>
                </form>

                <!-- Sign Up Form -->
                <form id="signup-form" class="hidden space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2">
                            <label class="block text-sm font-semibold text-slate-600 mb-1">Full Name</label>
                            <input type="text" id="signup-name" required placeholder="John Doe"
                                class="w-full px-5 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-orange-500 focus:border-transparent outline-none transition-all">
                        </div>
                        <div class="col-span-2">
                            <label class="block text-sm font-semibold text-slate-600 mb-1">Email Address</label>
                            <input type="email" id="signup-email" required placeholder="john@example.com"
                                class="w-full px-5 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-orange-500 focus:border-transparent outline-none transition-all">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-600 mb-1">Date of Birth</label>
                            <input type="date" id="signup-dob" required
                                class="w-full px-5 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-orange-500 focus:border-transparent outline-none transition-all">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-600 mb-1">Gender</label>
                            <select id="signup-gender" required
                                class="w-full px-5 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-orange-500 focus:border-transparent outline-none transition-all">
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="col-span-2">
                            <label class="block text-sm font-semibold text-slate-600 mb-1">Phone Number</label>
                            <input type="tel" id="signup-phone" required placeholder="+1 234 567 890"
                                class="w-full px-5 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-orange-500 focus:border-transparent outline-none transition-all">
                        </div>
                        <div class="col-span-2">
                            <label class="block text-sm font-semibold text-slate-600 mb-1">Password</label>
                            <input type="password" id="signup-password" required placeholder="••••••••"
                                class="w-full px-5 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-orange-500 focus:border-transparent outline-none transition-all">
                        </div>
                    </div>
                    <button type="submit"
                        class="w-full bg-orange-600 hover:bg-orange-700 orange-gradient text-white font-bold py-4 rounded-2xl shadow-lg shadow-orange-500/20 hover:scale-[1.02] transition-all duration-300">Create
                        Patient Account</button>
                    <div id="signup-error"
                        class="hidden text-red-500 text-sm text-center mt-4 p-3 bg-red-50 rounded-xl"></div>
                </form>
            </div>
        </div>

        <!-- Dashboard View Container (Hidden by Default) -->
        <div id="dashboard-container" class="hidden flex flex-col min-h-screen bg-slate-50 text-slate-900">
            <nav
                class="bg-white border-b border-slate-200 px-6 py-4 flex items-center justify-between shadow-sm sticky top-0 z-30">
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 bg-primary rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <span class="text-lg font-bold text-slate-900">Clinic CMS</span>
                    <span id="role-badge"
                        class="px-2 py-0.5 bg-slate-100 text-slate-600 text-[10px] font-bold rounded-full uppercase tracking-wider border border-slate-200"></span>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="flex items-center space-x-3 border-r pr-4 border-slate-200">
                        <div
                            class="w-8 h-8 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-400">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z" />
                            </svg>
                        </div>
                        <span id="user-display" class="text-slate-600 text-sm font-medium"></span>
                    </div>
                    <button id="logout-btn"
                        class="flex items-center space-x-2 px-3 py-1.5 rounded-lg text-slate-500 hover:text-red-600 hover:bg-red-50 transition-all font-semibold text-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        <span>Logout</span>
                    </button>
                </div>
            </nav>
            <main id="dashboard-content" class="flex-grow p-6 md:p-8">
                <!-- Dynamic Content Loads Here -->
            </main>
        </div>

        <!-- Global Modals -->
        <div id="upload-modal"
            class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 z-[110]">
            <div
                class="bg-white rounded-[2.5rem] max-w-lg w-full p-8 md:p-12 shadow-2xl relative border border-slate-200">
                <button onclick="Dashboard.closeModal()"
                    class="absolute top-8 right-8 text-slate-400 hover:text-slate-600 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
                <div class="flex items-center space-x-4 mb-8">
                    <div class="w-12 h-12 bg-primary/10 text-primary rounded-2xl flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <div>
                        <h3 id="upload-title" class="text-2xl font-black text-slate-900 font-['Poppins']">Upload Lab
                            Report</h3>
                        <p class="text-slate-500 text-sm">Secure diagnostic data entry</p>
                    </div>
                </div>
                <form id="upload-form" class="space-y-5">
                    <input type="hidden" name="test_id" id="upload-test-id">
                    <div>
                        <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-2">Report
                            File (PDF / Image — optional)</label>
                        <input type="file" name="report_file" accept=".pdf,.jpg,.jpeg,.png"
                            class="w-full text-sm text-slate-600 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:bg-primary/10 file:text-primary file:font-bold file:cursor-pointer hover:file:bg-primary/20 transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-2">Findings
                            / Notes</label>
                        <textarea name="report_notes" placeholder="Enter diagnostic findings or summary..." required
                            class="w-full bg-slate-50 border-2 border-slate-100 rounded-[1.5rem] p-4 text-sm focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition-all"
                            rows="4"></textarea>
                    </div>
                    <button type="submit"
                        class="w-full bg-slate-900 text-white py-4 rounded-2xl font-black hover:bg-slate-800 transition-all shadow-xl shadow-slate-900/10">Submit
                        Report</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Core App Logic -->
    <script src="js/api.js"></script>
    <script src="js/ui.js"></script>
    <script>
        // Init UI A/B split-testing variants
        UI.initVariants({
            'cta_hover': {
                'A': 'hover:bg-primary-dark transition-all', // Control: standard fade
                'B': 'hover:-translate-y-1 hover:shadow-2xl transition-all duration-300' // Experimental: floating pop
            }
        });
    </script>
    <script src="js/auth.js"></script>
    <script src="js/dashboard.js"></script>
    <script>
        function showLogin() {
            console.log("Showing login modal");
            document.getElementById('auth-container').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            window.location.hash = 'login';
        }
        function hideLogin() {
            console.log("Hiding login modal");
            document.getElementById('auth-container').classList.add('hidden');
            document.body.style.overflow = 'auto';
            history.pushState("", document.title, window.location.pathname + window.location.search);
        }
        function toggleAuth(view) {
            const loginForm = document.getElementById('login-form');
            const signupForm = document.getElementById('signup-form');
            const toggleLogin = document.getElementById('toggle-login');
            const toggleSignup = document.getElementById('toggle-signup');
            const authTitle = document.querySelector('#auth-container h2');
            const authDesc = document.querySelector('#auth-container p');

            if (view === 'signup') {
                loginForm.classList.add('hidden');
                signupForm.classList.remove('hidden');
                toggleSignup.classList.add('bg-white', 'text-primary', 'shadow-sm');
                toggleSignup.classList.remove('text-slate-500');
                toggleLogin.classList.remove('bg-white', 'text-primary', 'shadow-sm');
                toggleLogin.classList.add('text-slate-500');
                authTitle.textContent = "Start Your Journey";
                authDesc.textContent = "Create your secure patient portal account";
            } else {
                signupForm.classList.add('hidden');
                loginForm.classList.remove('hidden');
                toggleLogin.classList.add('bg-white', 'text-primary', 'shadow-sm');
                toggleLogin.classList.remove('text-slate-500');
                toggleSignup.classList.remove('bg-white', 'text-primary', 'shadow-sm');
                toggleSignup.classList.add('text-slate-500');
                authTitle.textContent = "Welcome Back";
                authDesc.textContent = "Access your professional dashboard";
            }
        }
    </script>
</body>

</html>