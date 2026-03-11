/**
 * UI Components for Dashboard Modules
 */

window.UI = {
    variants: {},
    initVariants(data) {
        this.variants = data;
        if (!localStorage.getItem('cms_ui_variant')) {
            const variant = Math.random() > 0.5 ? 'B' : 'A';
            localStorage.setItem('cms_ui_variant', variant);
        }
    },
    getVariant(name) {
        const v = localStorage.getItem('cms_ui_variant') || 'A';
        return this.variants[name]?.[v] || this.variants[name]?.['A'] || '';
    },
    /**
     * Render a form to register new patient
     */
    renderPatientRegistration() {
        return `
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 max-w-2xl mx-auto">
                <div class="flex items-center space-x-3 mb-6">
                    <div class="w-10 h-10 bg-primary/10 text-primary rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" /></svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-800">Register New Patient</h3>
                </div>
                <form id="reg-patient-form" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Full Name</label>
                        <input type="text" name="full_name" required class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Email</label>
                        <input type="email" name="email" required class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Password</label>
                        <input type="password" name="password" required class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Date of Birth</label>
                        <input type="date" name="dob" required class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Gender</label>
                        <select name="gender" class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all">
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Phone Number</label>
                        <input type="text" name="phone" required class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all">
                    </div>
                    <div class="md:col-span-2 mt-2">
                        <button type="submit" class="w-full bg-primary text-white py-3 rounded-xl font-bold shadow-lg shadow-primary/20 ${this.getVariant('cta_hover')}">Register Patient</button>
                    </div>
                </form>
            </div>
        `;
    },

    /**
     * Render Appointment List / Queue
     * @param {Array} appointments 
     */
    renderAppointmentQueue(appointments) {
        return `
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                    <h3 class="font-bold text-gray-800">Live Appointment Queue</h3>
                    <button onclick="Dashboard.loadModule()" class="text-sm text-orange-600 font-medium hover:underline">Refresh</button>
                </div>
                <table class="w-full text-left">
                    <thead class="text-xs text-gray-400 uppercase bg-white">
                        <tr>
                            <th class="px-6 py-3">Patient</th>
                            <th class="px-6 py-3">Doctor</th>
                            <th class="px-6 py-3">Time</th>
                            <th class="px-6 py-3">Status</th>
                            <th class="px-6 py-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        ${appointments.map(a => `
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-6 py-4 font-medium text-gray-900">${a.patient_name}</td>
                                <td class="px-6 py-4 text-gray-600">${a.doctor_name}</td>
                                <td class="px-6 py-4 text-gray-600">${a.start_time.substring(0, 5)}</td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 rounded-full text-[10px] font-bold uppercase ${this.getStatusClass(a.status)}">
                                        ${a.status}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <button class="text-orange-600 hover:text-orange-800 font-medium text-sm">View</button>
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
                ${appointments.length === 0 ? '<div class="p-8 text-center text-gray-400">No appointments scheduled</div>' : ''}
            </div>
        `;
    },

    getStatusClass(status) {
        switch (status) {
            case 'scheduled': return 'bg-orange-100 text-orange-700 border border-orange-200';
            case 'completed': return 'bg-green-100 text-green-700 border border-green-200';
            case 'cancelled': return 'bg-red-100 text-red-700 border border-red-200';
            default: return 'bg-slate-100 text-slate-600 border border-slate-200';
        }
    },
    /**
     * Render Pharmacist Dashboard View
     */
    renderPharmacistView(prescriptions) {
        return `
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 bg-slate-50/50 border-b border-slate-200 flex items-center justify-between">
                    <h3 class="font-bold text-slate-800">Pending Prescriptions</h3>
                    <span class="text-xs font-bold text-slate-400 uppercase">${prescriptions.length} items</span>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                    ${prescriptions.map(p => `
                        <div class="border border-slate-100 rounded-2xl p-5 hover:border-primary/30 transition-all bg-white shadow-sm hover:shadow-md">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <h4 class="font-bold text-slate-900">${p.patient_name}</h4>
                                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-tight">Prescribed: ${p.created_at}</p>
                                </div>
                                <span class="bg-slate-100 text-slate-600 text-[10px] font-bold px-2 py-1 rounded-lg border border-slate-200 uppercase">RX#${p.id}</span>
                            </div>
                            <div class="space-y-2 mb-6 bg-slate-50/50 p-3 rounded-xl border border-slate-100">
                                ${p.items.map(i => `
                                    <div class="flex justify-between text-xs">
                                        <span class="text-slate-600 underline decoration-slate-200 underline-offset-4">${i.medicine_name}</span>
                                        <span class="font-bold text-slate-900">$${i.price}</span>
                                    </div>
                                `).join('')}
                            </div>
                            <div class="flex justify-between items-center bg-slate-900 -mx-5 -mb-5 p-4 rounded-b-2xl">
                                <span class="font-bold text-white text-sm">Total: $${p.items.reduce((acc, i) => acc + parseFloat(i.price), 0).toFixed(2)}</span>
                                <button onclick="Dashboard.dispensePrescription(${p.id}, ${p.items.reduce((acc, i) => acc + parseFloat(i.price), 0)})" class="bg-primary text-white px-4 py-2 rounded-xl text-xs font-bold hover:bg-primary-dark transition-all">Dispense & Bill</button>
                            </div>
                        </div>
                    `).join('')}
                    ${prescriptions.length === 0 ? '<div class="col-span-2 text-center text-slate-400 py-12">No pending prescriptions</div>' : ''}
                </div>
            </div>
        `;
    },

    /**
     * Render Admin Dashboard View
     */
    renderAdminView(data) {
        return `
            <div class="space-y-8">
                <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100 bg-gradient-to-br from-white to-orange-50/20">
                    <h2 class="text-2xl font-bold text-gray-800">System Audit Dashboard</h2>
                    <p class="text-gray-500">Global overview of clinic operations</p>
                    
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mt-8">
                        ${[
                { label: 'Patients', val: data.stats.total_patients, icon: 'Users' },
                { label: 'Doctors', val: data.stats.total_doctors, icon: 'Stethoscope' },
                { label: 'Appointments', val: data.stats.total_appointments, icon: 'Calendar' },
                { label: 'Pending Lab', val: data.stats.pending_lab_tests, icon: 'Activity' },
                { label: 'Revenue', val: '$' + data.stats.total_revenue, icon: 'DollarSign' }
            ].map(s => `
                            <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200 shadow-sm transition-all hover:bg-white hover:border-primary/20">
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">${s.label}</p>
                                <p class="text-2xl font-black text-slate-900 mt-1 line-clamp-1">${s.val}</p>
                            </div>
                        `).join('')}
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="px-6 py-4 bg-gray-50/50 border-b border-gray-100">
                        <h3 class="font-bold text-gray-800">Recent User Activity</h3>
                    </div>
                    <table class="w-full text-left">
                        <tbody class="divide-y divide-gray-50">
                            ${data.recent_users.map(u => `
                                <tr>
                                    <td class="px-6 py-4 font-medium">${u.email}</td>
                                    <td class="px-6 py-4 uppercase text-xs font-bold text-gray-400">${u.role}</td>
                                    <td class="px-6 py-4 text-right text-gray-400 font-mono text-sm">${u.created_at}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            </div>
        `;
    },

    /**
     * Render Patient Dashboard View
     */
    renderPatientView(appointments, doctors) {
        return `
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div class="lg:col-span-2 space-y-8">
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="px-6 py-4 bg-gray-50/50 border-b border-gray-100">
                             <h3 class="font-bold text-gray-800">My Appointments</h3>
                        </div>
                        ${this.renderAppointmentQueue(appointments)}
                    </div>

                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
                        <h3 class="font-bold text-gray-800 mb-6">Leave a Review</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            ${doctors.map(d => `
                                <div class="border p-4 rounded-xl flex justify-between items-center group hover:border-orange-500 transition-all">
                                    <span class="font-bold">${d.name}</span>
                                    <button onclick="Dashboard.showReviewModal(${d.id}, '${d.name}')" class="text-orange-600 font-bold text-sm bg-orange-50 px-3 py-1 rounded-full hover:bg-orange-600 hover:text-white transition-colors">Review</button>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                </div>
                <div class="space-y-6">
                    <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200">
                        <div class="flex items-center space-x-3 mb-6">
                            <div class="w-8 h-8 bg-primary/10 text-primary rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                            </div>
                            <h3 class="font-bold text-slate-800">Quick Booking</h3>
                        </div>
                        <form id="book-appointment-form" class="space-y-4">
                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Select Doctor</label>
                                <select name="doctor_id" required class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all">
                                    ${doctors.map(d => `<option value="${d.id}">${d.name} (${d.specialization})</option>`).join('')}
                                </select>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Date</label>
                                    <input type="date" name="date" required class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Time</label>
                                    <input type="time" name="start_time" required class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm">
                                </div>
                            </div>
                            <button type="submit" class="w-full bg-slate-900 text-white py-3 rounded-xl font-bold hover:bg-slate-800 transition-all shadow-lg text-sm">Schedule Appointment</button>
                        </form>
                    </div>
                </div>
            </div>

            <div id="review-modal" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 z-50">
                <div class="bg-white rounded-3xl max-w-sm w-full p-8 shadow-2xl border border-slate-200">
                    <h3 class="text-xl font-bold text-slate-900 mb-2 text-center" id="review-title"></h3>
                    <p class="text-slate-500 text-center text-sm mb-8">How was your clinical experience?</p>
                    <form id="review-form" class="space-y-6">
                        <input type="hidden" name="doctor_id" id="review-doctor-id">
                        <div>
                            <div class="flex items-center justify-center space-x-2 mb-4">
                                <input type="number" name="rating" min="1" max="5" value="5" class="w-16 text-2xl font-black text-center bg-slate-50 border-none rounded-xl text-primary focus:ring-0">
                                <span class="text-slate-400 font-bold">/ 5 STARS</span>
                            </div>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Feedback Details</label>
                            <textarea name="comment" placeholder="Share your experience..." class="w-full bg-slate-50 border border-slate-200 p-4 rounded-2xl text-sm focus:ring-2 focus:ring-primary outline-none" rows="3"></textarea>
                        </div>
                        <div class="flex space-x-3 pt-4">
                            <button type="button" onclick="Dashboard.closeReviewModal()" class="flex-1 px-4 py-3 border border-slate-200 rounded-xl text-slate-500 font-bold hover:bg-slate-50 transition-all">Cancel</button>
                            <button type="submit" class="flex-1 px-4 py-3 bg-primary text-white rounded-xl font-bold shadow-lg shadow-primary/20">Submit Review</button>
                        </div>
                    </form>
                </div>
            </div>
        `;
    },

    /**
     * Render Doctor Dashboard View
     */
    renderDoctorView(appointments) {
        return `
            <div class="space-y-8">
                <div class="bg-white p-8 rounded-2xl shadow-sm border border-slate-200 bg-gradient-to-br from-white to-orange-50/20">
                    <h2 class="text-2xl font-black text-slate-900 font-['Poppins']">Physician Workspace</h2>
                    <p class="text-slate-500">Manage your daily patient queue and clinical records</p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <div class="lg:col-span-2 space-y-8">
                        <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
                            <div class="px-8 py-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/30">
                                <div>
                                    <h3 class="font-black text-slate-900 uppercase tracking-widest text-xs">Today's Appointments</h3>
                                    <p class="text-[10px] text-slate-400 font-bold mt-0.5">Click a patient to start consultation</p>
                                </div>
                                <span class="bg-orange-100 text-orange-700 text-[10px] font-black px-3 py-1 rounded-full uppercase border border-orange-200">${appointments.length} PENDING</span>
                            </div>
                            <table class="w-full text-left">
                                <tbody class="divide-y divide-slate-50">
                                    ${appointments.map(a => `
                                        <tr class="hover:bg-slate-50/50 transition-all cursor-pointer group">
                                            <td class="px-8 py-5">
                                                <div class="flex items-center space-x-3">
                                                    <div class="w-10 h-10 bg-slate-100 rounded-xl flex items-center justify-center font-black text-slate-400 group-hover:bg-primary group-hover:text-white transition-colors">${a.patient_name[0]}</div>
                                                    <div>
                                                        <p class="font-bold text-slate-900">${a.patient_name}</p>
                                                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-tighter">Patient ID: #${a.patient_id}</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-8 py-5">
                                                <div class="flex flex-col">
                                                    <span class="text-slate-900 font-bold text-sm">${a.start_time.substring(0, 5)}</span>
                                                    <span class="text-[10px] text-slate-400 font-bold uppercase">Scheduled</span>
                                                </div>
                                            </td>
                                            <td class="px-8 py-5 text-right">
                                                <button class="bg-slate-900 text-white px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-primary transition-colors">Treat</button>
                                            </td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                            ${appointments.length === 0 ? '<div class="p-12 text-center text-slate-400 font-bold">No patients in queue</div>' : ''}
                        </div>
                    </div>
                    <div>
                        <div id="patient-interaction" class="bg-slate-900 rounded-[2.5rem] p-8 text-white min-h-[400px] flex flex-col items-center justify-center text-center">
                            <div class="w-16 h-16 bg-white/10 rounded-[1.5rem] flex items-center justify-center mb-6">
                                <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                            </div>
                            <h4 class="text-xl font-bold mb-2">No Active Session</h4>
                            <p class="text-slate-400 text-sm">Select a patient from the queue to start documenting their visit.</p>
                        </div>
                    </div>
                </div>
            </div>
        `;
    },

    /**
     * Render Diagnosis Form
     */
    renderDiagnosisForm(patientId, patientName) {
        return `
            <div class="stagger-in">
                <div class="flex items-center space-x-3 mb-8">
                    <button onclick="Dashboard.loadModule('doctor')" class="p-2 hover:bg-white/10 rounded-lg transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                    </button>
                    <div>
                        <h3 class="text-lg font-bold">Consulting: ${patientName}</h3>
                        <p class="text-xs text-slate-400">Clinical Documentation</p>
                    </div>
                </div>
                <form id="diagnosis-form" class="space-y-6">
                    <input type="hidden" name="patient_id" value="${patientId}">
                    <div>
                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2">Clinical Diagnosis</label>
                        <textarea name="diagnosis" required placeholder="Observations and findings..." class="w-full bg-white/5 border border-white/10 rounded-2xl p-4 text-sm outline-none focus:border-primary transition-all" rows="3"></textarea>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2">Notes & Recommendations</label>
                        <textarea name="clinical_notes" placeholder="Follow-up instructions..." class="w-full bg-white/5 border border-white/10 rounded-2xl p-4 text-sm outline-none focus:border-primary transition-all" rows="2"></textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-3 pt-2">
                        <button type="button" onclick="Dashboard.orderLabTest(${patientId})" class="bg-white/10 text-white py-3 rounded-xl font-bold text-xs hover:bg-white/20 transition-all">Order Lab</button>
                        <button type="submit" class="bg-primary text-white py-3 rounded-xl font-bold text-xs shadow-lg shadow-primary/20 hover:scale-[1.02] transition-all">Save & Complete</button>
                    </div>
                </form>
            </div>
        `;
    },

    /**
     * Render Lab Technician View
     */
    renderLabTechView(tests) {
        return `
            <div class="space-y-8">
                <div class="bg-white p-8 rounded-2xl shadow-sm border border-slate-200">
                    <h2 class="text-2xl font-black text-slate-900 font-['Poppins']">Laboratory Worklist</h2>
                    <p class="text-slate-500">Process pending diagnostic tests and upload results</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    ${tests.map(t => `
                        <div class="bg-white border-2 border-slate-100 rounded-[2rem] p-6 hover:border-primary/20 transition-all group">
                            <div class="flex justify-between items-start mb-6">
                                <div class="w-12 h-12 bg-slate-50 rounded-2xl flex items-center justify-center text-primary group-hover:bg-primary group-hover:text-white transition-colors">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a2 2 0 00-1.96 1.414l-.722 2.166a2 2 0 01-1.689 1.414l-2.387.477a2 2 0 01-1.022-.547l-1.414-1.414a2 2 0 01-.547-1.022l-.477-2.387a2 2 0 011.414-1.96l2.166-.722a2 2 0 011.414-1.689l.477-2.387a2 2 0 01.547-1.022l1.414-1.414z" /></svg>
                                </div>
                                <span class="bg-slate-100 text-slate-500 text-[10px] font-black px-2 py-1 rounded-lg uppercase tracking-widest">TEST#${t.id}</span>
                            </div>
                            <h4 class="text-lg font-black text-slate-900 mb-1">${t.test_type}</h4>
                            <p class="text-xs text-slate-400 font-bold uppercase tracking-widest mb-6">${t.patient_name}</p>
                            
                            <button onclick="Dashboard.showUploadForm(${t.id}, '${t.patient_name}')" class="w-full bg-slate-900 text-white py-3 rounded-xl font-bold text-xs hover:bg-primary transition-all flex items-center justify-center space-x-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" /></svg>
                                <span>Upload Results</span>
                            </button>
                        </div>
                    `).join('')}
                    ${tests.length === 0 ? '<div class="col-span-full p-12 text-center text-slate-400 bg-white rounded-3xl border-2 border-dashed border-slate-100 font-bold">No tests in queue</div>' : ''}
                </div>
            </div>
        `;
    }
};
