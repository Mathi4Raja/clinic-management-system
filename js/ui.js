// @ts-nocheck
/**
 * UI Components for Dashboard Modules
 * All rendering functions return HTML strings; never mutate DOM directly.
 */

window.UI = {
    variants: {},

    initVariants(data) {
        this.variants = data;
        if (!localStorage.getItem('cms_ui_variant')) {
            localStorage.setItem('cms_ui_variant', Math.random() > 0.5 ? 'B' : 'A');
        }
    },

    getVariant(name) {
        const v = localStorage.getItem('cms_ui_variant') || 'A';
        return this.variants[name]?.[v] || this.variants[name]?.['A'] || '';
    },

    // ── STATUS BADGE ────────────────────────────────────────────────────────

    getStatusClass(status) {
        const map = {
            scheduled:  'bg-amber-100  text-amber-700  border border-amber-200',
            completed:  'bg-green-100  text-green-700  border border-green-200',
            cancelled:  'bg-red-100    text-red-700    border border-red-200',
            no_show:    'bg-slate-100  text-slate-500  border border-slate-200',
            pending:    'bg-blue-100   text-blue-700   border border-blue-200',
        };
        return map[status] || 'bg-slate-100 text-slate-600 border border-slate-200';
    },

    // ── RECEPTIONIST: Patient Registration Form ──────────────────────────────

    renderPatientRegistration() {
        return `
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
                <div class="flex items-center space-x-3 mb-6">
                    <div class="w-10 h-10 bg-primary/10 text-primary rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-slate-800">Register New Patient</h3>
                </div>
                <form id="reg-patient-form" class="space-y-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Full Name</label>
                        <input type="text" name="full_name" required placeholder="Jane Doe"
                            class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Email</label>
                            <input type="email" name="email" required placeholder="jane@example.com"
                                class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Password</label>
                            <input type="password" name="password" required placeholder="••••••••"
                                class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Date of Birth</label>
                            <input type="date" name="dob" required
                                class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Gender</label>
                            <select name="gender" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all">
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Phone</label>
                        <input type="tel" name="phone" required placeholder="+1 234 567 890"
                            class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all">
                    </div>
                    <button type="submit"
                        class="w-full bg-primary hover:bg-primary-dark text-white py-3 rounded-xl font-bold text-sm shadow-lg shadow-primary/20 transition-all mt-2 ${this.getVariant('cta_hover')}">
                        Register Patient
                    </button>
                </form>
            </div>`;
    },

    // ── SHARED: Appointment Queue ────────────────────────────────────────────

    renderAppointmentQueue(appointments, viewerRole) {
        const isReceptionist = viewerRole === 'receptionist';
        return `
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                    <h3 class="font-bold text-slate-800">
                        ${isReceptionist ? 'Live Appointment Queue' : 'My Appointments'}
                    </h3>
                    <button onclick="Dashboard.loadModule()" class="text-xs text-primary font-bold hover:underline">
                        ↻ Refresh
                    </button>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="text-[10px] text-slate-400 uppercase bg-slate-50 border-b border-slate-100">
                            <tr>
                                <th class="px-5 py-3">Patient</th>
                                <th class="px-5 py-3">Doctor</th>
                                <th class="px-5 py-3">Date / Time</th>
                                <th class="px-5 py-3">Status</th>
                                ${isReceptionist ? '<th class="px-5 py-3 text-right">Actions</th>' : '<th class="px-5 py-3 text-right">Action</th>'}
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            ${appointments.map(a => `
                                <tr class="hover:bg-slate-50/50 transition-colors">
                                    <td class="px-5 py-4 font-semibold text-slate-900">${a.patient_name}</td>
                                    <td class="px-5 py-4 text-slate-600">${a.doctor_name}</td>
                                    <td class="px-5 py-4 text-slate-600">${a.appointment_date} ${a.start_time.substring(0, 5)}</td>
                                    <td class="px-5 py-4">
                                        <span class="px-2 py-1 rounded-full text-[10px] font-bold uppercase ${this.getStatusClass(a.status)}">
                                            ${a.status}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-right">
                                        ${isReceptionist ? `
                                            <div class="flex items-center justify-end gap-2">
                                                ${a.status === 'scheduled' ? `
                                                    <button onclick="Dashboard.updateAppointmentStatus(${a.id}, 'completed')"
                                                        class="text-[10px] font-bold bg-green-50 text-green-700 px-2.5 py-1 rounded-lg hover:bg-green-100 transition-all border border-green-200">
                                                        Complete
                                                    </button>
                                                    <button onclick="Dashboard.updateAppointmentStatus(${a.id}, 'cancelled')"
                                                        class="text-[10px] font-bold bg-red-50 text-red-600 px-2.5 py-1 rounded-lg hover:bg-red-100 transition-all border border-red-200">
                                                        Cancel
                                                    </button>` : ''}
                                            </div>` : `
                                            ${a.status === 'scheduled' ? `
                                                <button onclick="Dashboard.cancelAppointment(${a.id})"
                                                    class="text-xs font-bold text-red-500 hover:text-red-700 hover:bg-red-50 px-3 py-1.5 rounded-lg transition-all">
                                                    Cancel
                                                </button>` : '—'}
                                        `}
                                    </td>
                                </tr>`).join('')}
                        </tbody>
                    </table>
                </div>
                ${appointments.length === 0 ? `
                    <div class="p-10 text-center">
                        <svg class="w-10 h-10 text-slate-200 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <p class="text-slate-400 font-semibold text-sm">No appointments found</p>
                    </div>` : ''}
            </div>`;
    },

    // ── DOCTOR Dashboard ─────────────────────────────────────────────────────

    renderDoctorView(appointments) {
        return `
            <div class="grid grid-cols-1 lg:grid-cols-5 gap-8">
                <div class="lg:col-span-3 space-y-6">
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
                        <div class="flex items-center justify-between mb-1">
                            <h2 class="text-xl font-black text-slate-900 font-['Poppins']">Physician Workspace</h2>
                            <span class="bg-amber-100 text-amber-700 text-[10px] font-black px-3 py-1 rounded-full border border-amber-200 uppercase">
                                ${appointments.length} in queue
                            </span>
                        </div>
                        <p class="text-slate-400 text-sm mb-6">Click <strong>Treat</strong> to begin a consultation</p>
                        <div class="space-y-2">
                            ${appointments.length === 0 ? `
                                <div class="py-10 text-center text-slate-400 font-semibold border-2 border-dashed border-slate-100 rounded-2xl">
                                    No patients scheduled for today
                                </div>` :
                            appointments.map(a => `
                                <div class="flex items-center justify-between p-4 rounded-2xl border border-slate-100 hover:border-primary/30 hover:bg-orange-50/30 transition-all group">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 bg-slate-100 rounded-xl flex items-center justify-center font-black text-slate-400 group-hover:bg-primary group-hover:text-white transition-colors text-sm">
                                            ${a.patient_name ? a.patient_name[0].toUpperCase() : '?'}
                                        </div>
                                        <div>
                                            <p class="font-bold text-slate-900 text-sm">${a.patient_name}</p>
                                            <p class="text-[10px] text-slate-400 font-bold uppercase">
                                                ${a.appointment_date} · ${a.start_time.substring(0,5)}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <button
                                            data-action="history"
                                            data-patient-id="${a.patient_id}"
                                            data-patient-name="${a.patient_name}"
                                            class="border border-slate-200 text-slate-500 px-3 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest hover:border-blue-300 hover:text-blue-600 transition-colors">
                                            History
                                        </button>
                                        <button
                                            data-action="treat"
                                            data-patient-id="${a.patient_id}"
                                            data-patient-name="${a.patient_name}"
                                            data-appointment-id="${a.id}"
                                            class="bg-slate-900 text-white px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-primary transition-colors">
                                            Treat
                                        </button>
                                    </div>
                                </div>`).join('')}
                        </div>
                    </div>
                </div>
                <div class="lg:col-span-2">
                    <div id="patient-interaction"
                        class="bg-slate-900 rounded-[2.5rem] p-8 text-white min-h-[460px] flex flex-col items-center justify-center text-center sticky top-24">
                        <div class="w-16 h-16 bg-white/10 rounded-[1.5rem] flex items-center justify-center mb-6">
                            <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <h4 class="text-xl font-bold mb-2">No Active Consultation</h4>
                        <p class="text-slate-400 text-sm">Select a patient from the queue to begin documenting their visit.</p>
                    </div>
                </div>
            </div>`;
    },

    // ── DOCTOR: Diagnosis + Prescription Form ────────────────────────────────

    renderDiagnosisForm(patientId, patientName, appointmentId, medicines) {
        return `
            <div class="h-full flex flex-col">
                <div class="flex items-center space-x-3 mb-6">
                    <button onclick="Dashboard.loadModule('doctor')"
                        class="p-2 hover:bg-white/10 rounded-xl transition-colors text-slate-300 hover:text-white">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </button>
                    <div>
                        <h3 class="text-base font-bold text-white">Consulting: ${patientName}</h3>
                        <p class="text-xs text-slate-400">Clinical Documentation</p>
                    </div>
                </div>
                <form id="diagnosis-form" class="space-y-4 flex-1 overflow-y-auto pr-1">
                    <input type="hidden" name="patient_id" value="${patientId}">
                    <input type="hidden" name="appointment_id" value="${appointmentId || ''}">
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">
                            Clinical Diagnosis <span class="text-red-400">*</span>
                        </label>
                        <textarea name="diagnosis" required rows="3"
                            placeholder="Observations, symptoms, and findings..."
                            class="w-full bg-white/5 border border-white/15 rounded-2xl px-4 py-3 text-sm text-white placeholder-slate-500 outline-none focus:border-primary transition-all resize-none"></textarea>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">
                            Notes &amp; Recommendations
                        </label>
                        <textarea name="clinical_notes" rows="2"
                            placeholder="Follow-up instructions, lifestyle advice..."
                            class="w-full bg-white/5 border border-white/15 rounded-2xl px-4 py-3 text-sm text-white placeholder-slate-500 outline-none focus:border-primary transition-all resize-none"></textarea>
                    </div>

                    <!-- Prescription Section -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest">
                                Prescription Items
                            </label>
                            <button type="button" onclick="Dashboard.addPrescriptionRow()"
                                class="text-[10px] font-bold text-primary bg-primary/10 px-3 py-1 rounded-lg hover:bg-primary/20 transition-all flex items-center space-x-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                <span>Add Medicine</span>
                            </button>
                        </div>
                        <div id="rx-items" class="space-y-2 min-h-[36px]">
                            ${medicines.length === 0 ? `
                                <p class="text-xs text-slate-500 italic">No medicines loaded.</p>` : ''}
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3 pt-2">
                        <button type="button" onclick="Dashboard.orderLabTest(${patientId})"
                            class="bg-white/10 text-white py-3 rounded-xl font-bold text-xs hover:bg-white/20 transition-all flex items-center justify-center space-x-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                            </svg>
                            <span>Order Lab</span>
                        </button>
                        <button type="submit"
                            class="bg-primary text-white py-3 rounded-xl font-bold text-xs shadow-lg shadow-primary/30 hover:bg-primary-dark hover:scale-[1.02] transition-all">
                            Save &amp; Complete
                        </button>
                    </div>
                </form>
            </div>`;
    },

    // ── LAB TECH Dashboard ───────────────────────────────────────────────────

    renderLabTechView(pendingTests, completedTests = []) {
        return `
            <div class="space-y-6">
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-black text-slate-900 font-['Poppins']">Laboratory Worklist</h2>
                        <p class="text-slate-400 text-sm">Process diagnostic tests and review completed results</p>
                    </div>
                    <div class="flex p-1 bg-slate-100 rounded-xl">
                        <button onclick="Dashboard.switchLabTab('pending')" id="labtab-pending"
                            class="px-5 py-2 text-sm font-bold bg-white text-primary rounded-lg shadow-sm transition-all">
                            Pending <span class="ml-1 bg-blue-100 text-blue-700 text-[10px] px-2 py-0.5 rounded-full">${pendingTests.length}</span>
                        </button>
                        <button onclick="Dashboard.switchLabTab('completed')" id="labtab-completed"
                            class="px-5 py-2 text-sm font-bold text-slate-500 hover:text-slate-700 rounded-lg transition-all">
                            Completed
                        </button>
                    </div>
                </div>

                <!-- Pending -->
                <div id="lab-tab-pending" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                    ${this.renderLabTestCards(pendingTests, 'pending')}
                </div>

                <!-- Completed (loads lazily) -->
                <div id="lab-tab-completed" class="hidden">
                    <div id="lab-completed-list" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                        <div class="col-span-full py-8 text-center text-slate-400 text-sm">Switch to this tab to load completed tests</div>
                    </div>
                </div>
            </div>

            <!-- Upload Modal -->
            <div id="upload-modal" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 z-50">
                <div class="bg-white rounded-3xl max-w-md w-full p-8 shadow-2xl border border-slate-200">
                    <h3 class="text-xl font-bold text-slate-900 mb-6" id="upload-title">Upload Report</h3>
                    <form id="upload-form" class="space-y-4" enctype="multipart/form-data">
                        <input type="hidden" name="test_id" id="upload-test-id">
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Report Notes</label>
                            <textarea name="notes" rows="3" placeholder="Enter findings and observations..."
                                class="w-full bg-slate-50 border border-slate-200 p-3 rounded-2xl text-sm focus:ring-2 focus:ring-primary outline-none resize-none"></textarea>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Report File (PDF/Image)</label>
                            <input type="file" name="report_file" accept=".pdf,.jpg,.jpeg,.png"
                                class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-primary/10 file:text-primary hover:file:bg-primary hover:file:text-white transition-all">
                        </div>
                        <div class="flex space-x-3 pt-2">
                            <button type="button" onclick="Dashboard.closeModal()"
                                class="flex-1 px-4 py-3 border border-slate-200 rounded-xl text-slate-500 font-bold hover:bg-slate-50 text-sm transition-all">
                                Cancel
                            </button>
                            <button type="submit"
                                class="flex-1 px-4 py-3 bg-primary text-white rounded-xl font-bold shadow-lg shadow-primary/20 text-sm hover:bg-primary-dark transition-all">
                                Submit Report
                            </button>
                        </div>
                    </form>
                </div>
            </div>`;
    },

    renderLabTestCards(tests, mode) {
        if (!tests.length) return `
            <div class="col-span-full p-14 text-center bg-white rounded-3xl border-2 border-dashed border-slate-100">
                <p class="text-slate-400 font-bold">No ${mode} lab tests</p>
            </div>`;

        return tests.map(t => `
            <div class="bg-white border-2 border-slate-100 rounded-[2rem] p-6 hover:border-primary/30 transition-all group">
                <div class="flex justify-between items-start mb-5">
                    <div class="w-12 h-12 bg-slate-50 rounded-2xl flex items-center justify-center text-primary group-hover:bg-primary group-hover:text-white transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                        </svg>
                    </div>
                    <span class="text-[10px] font-black px-2 py-1 rounded-lg uppercase tracking-widest ${mode === 'completed' ? 'bg-green-100 text-green-600' : 'bg-blue-100 text-blue-600'}">
                        TEST#${t.id}
                    </span>
                </div>
                <h4 class="text-base font-black text-slate-900 mb-1">${t.test_type}</h4>
                <p class="text-xs text-slate-400 font-bold uppercase tracking-widest mb-1">${t.patient_name}</p>
                ${t.doctor_name ? `<p class="text-xs text-slate-400 mb-3">Dr. ${t.doctor_name}</p>` : ''}
                ${mode === 'completed' && t.report_notes ? `
                    <p class="text-xs text-slate-600 bg-slate-50 rounded-xl p-3 mb-4 border border-slate-100">${t.report_notes}</p>` : ''}
                ${mode === 'pending' ? `
                    <button onclick="Dashboard.showUploadForm(${t.id}, '${t.patient_name}')"
                        class="w-full bg-slate-900 text-white py-3 rounded-xl font-bold text-xs hover:bg-primary transition-all flex items-center justify-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                        </svg>
                        <span>Upload Results</span>
                    </button>` : `
                    <span class="block text-center text-xs text-green-600 font-bold bg-green-50 rounded-xl py-2 border border-green-100">Completed</span>`}
            </div>`).join('');
    },

    // ── PHARMACIST Dashboard ─────────────────────────────────────────────────

    renderPharmacistView(prescriptions, invoices = []) {
        return `
            <div class="space-y-6">
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-black text-slate-900 font-['Poppins']">Pharmacy</h2>
                        <p class="text-slate-400 text-sm">Dispense prescriptions and manage billing</p>
                    </div>
                    <div class="flex p-1 bg-slate-100 rounded-xl">
                        <button onclick="Dashboard.switchPharmacistTab('prescriptions')" id="pharmtab-prescriptions"
                            class="px-5 py-2 text-sm font-bold bg-white text-primary rounded-lg shadow-sm transition-all">
                            Prescriptions <span class="ml-1 bg-purple-100 text-purple-700 text-[10px] px-2 py-0.5 rounded-full">${prescriptions.length}</span>
                        </button>
                        <button onclick="Dashboard.switchPharmacistTab('invoices')" id="pharmtab-invoices"
                            class="px-5 py-2 text-sm font-bold text-slate-500 hover:text-slate-700 rounded-lg transition-all">
                            Invoices
                        </button>
                    </div>
                </div>

                <!-- Prescriptions Tab -->
                <div id="pharm-tab-prescriptions">
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
                        ${prescriptions.map(p => {
                            const total = p.items.reduce((acc, i) => acc + parseFloat(i.price || 0), 0);
                            return `
                            <div class="border border-slate-200 rounded-2xl overflow-hidden bg-white shadow-sm hover:shadow-md transition-all">
                                <div class="p-5">
                                    <div class="flex justify-between items-start mb-3">
                                        <div>
                                            <h4 class="font-bold text-slate-900">${p.patient_name}</h4>
                                            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-tight mt-0.5">${p.created_at}</p>
                                        </div>
                                        <span class="bg-slate-100 text-slate-600 text-[10px] font-bold px-2 py-1 rounded-lg border border-slate-200 uppercase">RX#${p.id}</span>
                                    </div>
                                    <div class="space-y-1.5 mb-4 bg-slate-50 p-3 rounded-xl border border-slate-100">
                                        ${p.items.length === 0
                                            ? '<p class="text-xs text-slate-400 text-center py-1">No items</p>'
                                            : p.items.map(i => `
                                            <div class="flex justify-between text-xs">
                                                <span class="text-slate-600">${i.medicine_name}</span>
                                                <span class="font-bold text-slate-900">$${parseFloat(i.price).toFixed(2)}</span>
                                            </div>`).join('')}
                                    </div>
                                </div>
                                <div class="flex justify-between items-center bg-slate-900 px-5 py-4">
                                    <span class="font-bold text-white text-sm">Total: $${total.toFixed(2)}</span>
                                    <button onclick="Dashboard.dispensePrescription(${p.id}, ${p.patient_id}, ${total})"
                                        class="bg-primary text-white px-4 py-2 rounded-xl text-xs font-bold hover:bg-primary-dark transition-all">
                                        Dispense &amp; Bill
                                    </button>
                                </div>
                            </div>`; }).join('')}
                        ${prescriptions.length === 0 ? `
                            <div class="col-span-full py-14 text-center text-slate-400 bg-white rounded-2xl border-2 border-dashed border-slate-100 font-bold">
                                No pending prescriptions
                            </div>` : ''}
                    </div>
                </div>

                <!-- Invoices Tab -->
                <div id="pharm-tab-invoices" class="hidden">
                    ${this.renderInvoicesTab(invoices, true)}
                </div>
            </div>`;
    },

    // ── PATIENT Dashboard ────────────────────────────────────────────────────

    renderPatientView(appointments, doctors, records = [], labs = [], invoices = []) {
        return `
            <!-- Patient Tab Navigation -->
            <div class="flex p-1 bg-slate-100 rounded-xl w-full md:w-auto md:inline-flex mb-6">
                <button onclick="Dashboard.switchPatientTab('appointments')" id="ptab-appointments"
                    class="flex-1 md:flex-none px-5 py-2.5 text-sm font-bold bg-white text-primary rounded-lg shadow-sm transition-all">
                    Appointments
                </button>
                <button onclick="Dashboard.switchPatientTab('records')" id="ptab-records"
                    class="flex-1 md:flex-none px-5 py-2.5 text-sm font-bold text-slate-500 hover:text-slate-700 rounded-lg transition-all">
                    Medical Records
                </button>
                <button onclick="Dashboard.switchPatientTab('labs')" id="ptab-labs"
                    class="flex-1 md:flex-none px-5 py-2.5 text-sm font-bold text-slate-500 hover:text-slate-700 rounded-lg transition-all">
                    Lab Results
                </button>
                <button onclick="Dashboard.switchPatientTab('invoices')" id="ptab-invoices"
                    class="flex-1 md:flex-none px-5 py-2.5 text-sm font-bold text-slate-500 hover:text-slate-700 rounded-lg transition-all">
                    Invoices
                </button>
            </div>

            <!-- TAB: Appointments -->
            <div id="patient-tab-appointments">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <div class="lg:col-span-2 space-y-6">
                        ${this.renderAppointmentQueue(appointments, 'patient')}
                    </div>
                    <div class="space-y-5">
                        <!-- Quick Booking -->
                        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
                            <div class="flex items-center space-x-3 mb-5">
                                <div class="w-9 h-9 bg-primary/10 text-primary rounded-xl flex items-center justify-center">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                                <h3 class="font-bold text-slate-800">Book Appointment</h3>
                            </div>
                            <form id="book-appointment-form" class="space-y-3">
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Select Doctor</label>
                                    <select name="doctor_id" required
                                        class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:ring-2 focus:ring-primary transition-all">
                                        <option value="">Choose a doctor...</option>
                                        ${doctors.map(d => `<option value="${d.id}">${d.name} · ${d.specialization}</option>`).join('')}
                                    </select>
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Date</label>
                                        <input type="date" name="date" required
                                            class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:ring-2 focus:ring-primary transition-all">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Time</label>
                                        <input type="time" name="start_time" required
                                            class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:ring-2 focus:ring-primary transition-all">
                                    </div>
                                </div>
                                <button type="submit"
                                    class="w-full bg-slate-900 hover:bg-slate-800 text-white py-3 rounded-xl font-bold text-sm transition-all shadow-lg mt-1">
                                    Schedule Appointment
                                </button>
                            </form>
                        </div>
                        <!-- Doctors / Reviews -->
                        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
                            <h3 class="font-bold text-slate-800 mb-4">Leave a Review</h3>
                            <div class="space-y-2">
                                ${doctors.map(d => `
                                    <div class="flex items-center justify-between p-3 rounded-xl border border-slate-100 hover:border-orange-200 transition-all group">
                                        <div>
                                            <p class="font-semibold text-slate-900 text-sm">${d.name}</p>
                                            <p class="text-[10px] text-slate-400 font-bold uppercase">${d.specialization}</p>
                                        </div>
                                        <button onclick="Dashboard.showReviewModal(${d.id}, '${d.name}')"
                                            class="text-xs font-bold text-primary bg-primary/10 px-3 py-1.5 rounded-lg hover:bg-primary hover:text-white transition-all">
                                            Review
                                        </button>
                                    </div>`).join('')}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB: Medical Records -->
            <div id="patient-tab-records" class="hidden">
                ${this.renderMedicalRecordsTab(records)}
            </div>

            <!-- TAB: Lab Results -->
            <div id="patient-tab-labs" class="hidden">
                ${this.renderLabResultsTab(labs)}
            </div>

            <!-- TAB: Invoices -->
            <div id="patient-tab-invoices" class="hidden">
                ${this.renderInvoicesTab(invoices, false)}
            </div>

            <!-- Review Modal -->
            <div id="review-modal" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 z-50">
                <div class="bg-white rounded-3xl max-w-sm w-full p-8 shadow-2xl border border-slate-200">
                    <h3 class="text-xl font-bold text-slate-900 mb-2 text-center" id="review-title"></h3>
                    <p class="text-slate-500 text-center text-sm mb-6">How was your clinical experience?</p>
                    <form id="review-form" class="space-y-4">
                        <input type="hidden" name="doctor_id" id="review-doctor-id">
                        <div class="flex items-center justify-center space-x-2">
                            <label class="text-sm font-bold text-slate-500">Rating:</label>
                            <input type="number" name="rating" min="1" max="5" value="5"
                                class="w-16 text-xl font-black text-center bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-primary outline-none text-primary">
                            <span class="text-slate-400 font-bold text-sm">/ 5</span>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Your Feedback</label>
                            <textarea name="comment" rows="3"
                                placeholder="Share your experience with this doctor..."
                                class="w-full bg-slate-50 border border-slate-200 p-3 rounded-2xl text-sm focus:ring-2 focus:ring-primary outline-none resize-none"></textarea>
                        </div>
                        <div class="flex space-x-3 pt-2">
                            <button type="button" onclick="Dashboard.closeReviewModal()"
                                class="flex-1 px-4 py-3 border border-slate-200 rounded-xl text-slate-500 font-bold hover:bg-slate-50 text-sm transition-all">
                                Cancel
                            </button>
                            <button type="submit"
                                class="flex-1 px-4 py-3 bg-primary text-white rounded-xl font-bold shadow-lg shadow-primary/20 text-sm hover:bg-primary-dark transition-all">
                                Submit Review
                            </button>
                        </div>
                    </form>
                </div>
            </div>`;
    },

    // ── New Helper Views ─────────────────────────────────────────────────────

    renderMedicalRecordsTab(records) {
        if (!records.length) return `
            <div class="py-14 text-center bg-white rounded-2xl border-2 border-dashed border-slate-100">
                <p class="text-slate-400 font-bold">No medical records yet</p>
            </div>`;
        return `<div class="space-y-4">` + records.map(r => `
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 bg-slate-50 border-b border-slate-100 flex justify-between items-center">
                    <div>
                        <p class="font-bold text-slate-900 text-sm">Dr. ${r.doctor_name || 'Unknown'}</p>
                        <p class="text-xs text-slate-400">${r.created_at}</p>
                    </div>
                    <span class="text-[10px] font-black bg-blue-100 text-blue-700 px-3 py-1 rounded-full uppercase">Record #${r.id}</span>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase mb-1">Diagnosis</p>
                        <p class="text-sm text-slate-700">${r.diagnosis || '—'}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase mb-1">Clinical Notes</p>
                        <p class="text-sm text-slate-700">${r.clinical_notes || '—'}</p>
                    </div>
                    ${r.prescription_items && r.prescription_items.length ? `
                    <div class="md:col-span-2">
                        <p class="text-[10px] font-bold text-slate-400 uppercase mb-2">Prescription</p>
                        <div class="space-y-1">
                            ${r.prescription_items.map(i => `
                                <div class="flex items-center justify-between bg-slate-50 rounded-xl px-4 py-2 border border-slate-100">
                                    <span class="text-sm text-slate-700 font-medium">${i.medicine_name}</span>
                                    <span class="text-xs text-slate-500">${i.dosage} · ${i.frequency}</span>
                                </div>`).join('')}
                        </div>
                    </div>` : ''}
                </div>
            </div>`).join('') + `</div>`;
    },

    renderLabResultsTab(tests) {
        if (!tests.length) return `
            <div class="py-14 text-center bg-white rounded-2xl border-2 border-dashed border-slate-100">
                <p class="text-slate-400 font-bold">No lab results yet</p>
            </div>`;
        return `<div class="grid grid-cols-1 md:grid-cols-2 gap-4">` + tests.map(t => `
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                <div class="flex justify-between items-start mb-3">
                    <div>
                        <h4 class="font-bold text-slate-900">${t.test_type}</h4>
                        ${t.doctor_name ? `<p class="text-xs text-slate-400">Ordered by Dr. ${t.doctor_name}</p>` : ''}
                    </div>
                    <span class="text-[10px] font-black px-2 py-1 rounded-lg uppercase ${this.getStatusClass(t.status)}">${t.status}</span>
                </div>
                ${t.report_notes ? `
                    <div class="bg-slate-50 rounded-xl p-3 border border-slate-100 text-sm text-slate-600 mt-2">${t.report_notes}</div>` : ''}
                <p class="text-[10px] text-slate-400 mt-3 font-mono">${t.created_at}</p>
            </div>`).join('') + `</div>`;
    },

    renderInvoicesTab(invoices, canEdit = false) {
        if (!invoices.length) return `
            <div class="py-14 text-center bg-white rounded-2xl border-2 border-dashed border-slate-100">
                <p class="text-slate-400 font-bold">No invoices found</p>
            </div>`;
        return `
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-100">
                            <th class="px-6 py-3 text-[10px] font-black text-slate-400 uppercase">Patient</th>
                            <th class="px-6 py-3 text-[10px] font-black text-slate-400 uppercase">Type</th>
                            <th class="px-6 py-3 text-[10px] font-black text-slate-400 uppercase">Amount</th>
                            <th class="px-6 py-3 text-[10px] font-black text-slate-400 uppercase">Status</th>
                            <th class="px-6 py-3 text-[10px] font-black text-slate-400 uppercase">Date</th>
                            ${canEdit ? '<th class="px-6 py-3"></th>' : ''}
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        ${invoices.map(i => `
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-4 font-medium text-slate-900">${i.patient_name || '—'}</td>
                                <td class="px-6 py-4 text-slate-500 capitalize">${i.reference_type || '—'}</td>
                                <td class="px-6 py-4 font-bold text-slate-900">$${parseFloat(i.amount).toFixed(2)}</td>
                                <td class="px-6 py-4">
                                    <span class="text-[10px] font-black uppercase px-2 py-1 rounded-full ${this.getStatusClass(i.status)}">${i.status}</span>
                                </td>
                                <td class="px-6 py-4 text-slate-400 font-mono text-xs">${i.created_at}</td>
                                ${canEdit && i.status === 'pending' ? `
                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-2">
                                        <button onclick="Dashboard.markInvoicePaid(${i.id})"
                                            class="text-xs font-bold text-green-600 bg-green-50 border border-green-100 px-3 py-1.5 rounded-lg hover:bg-green-600 hover:text-white transition-all">
                                            Mark Paid
                                        </button>
                                        <button onclick="Dashboard.voidInvoice(${i.id})"
                                            class="text-xs font-bold text-red-500 bg-red-50 border border-red-100 px-3 py-1.5 rounded-lg hover:bg-red-500 hover:text-white transition-all">
                                            Void
                                        </button>
                                    </div>
                                </td>` : canEdit ? '<td class="px-6 py-4"></td>' : ''}
                            </tr>`).join('')}
                    </tbody>
                </table>
            </div>`;
    },

    renderPatientHistoryPanel(records, patientName) {
        return `
            <div class="bg-slate-800 rounded-2xl p-6 text-white">
                <div class="flex items-center justify-between mb-5">
                    <div>
                        <h3 class="text-lg font-black">History: ${patientName}</h3>
                        <p class="text-slate-400 text-xs">${records.length} record(s)</p>
                    </div>
                    <button onclick="document.getElementById('patient-interaction').innerHTML=''"
                        class="text-slate-400 hover:text-white p-2 rounded-xl hover:bg-white/10 transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                ${records.length === 0 ?
                    '<p class="text-slate-400 text-center py-8">No medical records for this patient</p>'
                    : records.map(r => `
                        <div class="bg-white/10 rounded-xl p-4 mb-3 border border-white/10">
                            <div class="flex justify-between mb-2">
                                <span class="text-xs font-bold text-slate-300">${r.created_at}</span>
                                <span class="text-[10px] bg-white/20 px-2 py-0.5 rounded-full uppercase font-bold">Record #${r.id}</span>
                            </div>
                            <p class="text-sm font-bold mb-1">${r.diagnosis || 'No diagnosis'}</p>
                            ${r.clinical_notes ? `<p class="text-xs text-slate-300 mb-2">${r.clinical_notes}</p>` : ''}
                            ${r.prescription_items && r.prescription_items.length ? `
                                <div class="mt-2">
                                    <p class="text-[10px] text-slate-400 uppercase font-bold mb-1">Rx</p>
                                    ${r.prescription_items.map(i => `
                                        <span class="inline-block text-[10px] bg-primary/30 text-primary-light px-2 py-0.5 rounded-lg mr-1 mb-1">${i.medicine_name} ${i.dosage}</span>`).join('')}
                                </div>` : ''}
                        </div>`).join('')}
            </div>`;
    },

    renderReceptionistSidePanel() {
        return `
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <!-- Tab Nav -->
                <div class="flex p-1.5 bg-slate-50 border-b border-slate-200">
                    <button onclick="Dashboard.switchReceptionistTab('register')" id="receptab-register"
                        class="flex-1 py-2 text-xs font-bold bg-white text-primary rounded-lg shadow-sm transition-all">
                        Register Patient
                    </button>
                    <button onclick="Dashboard.switchReceptionistTab('search')" id="receptab-search"
                        class="flex-1 py-2 text-xs font-bold text-slate-500 hover:text-slate-700 rounded-lg transition-all">
                        Search &amp; Edit
                    </button>
                </div>

                <!-- Register Tab -->
                <div id="recep-tab-register" class="p-5">
                    <form id="reg-patient-form" class="space-y-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Full Name</label>
                            <input type="text" name="full_name" required placeholder="Jane Doe"
                                class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-primary outline-none transition-all">
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Email</label>
                                <input type="email" name="email" required placeholder="jane@example.com"
                                    class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-primary outline-none transition-all">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Password</label>
                                <input type="password" name="password" required placeholder="••••••••"
                                    class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-primary outline-none transition-all">
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Date of Birth</label>
                                <input type="date" name="dob" required
                                    class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-primary outline-none transition-all">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Gender</label>
                                <select name="gender" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-primary outline-none transition-all">
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Phone</label>
                            <input type="tel" name="phone" required placeholder="+1 234 567 890"
                                class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-primary outline-none transition-all">
                        </div>
                        <button type="submit"
                            class="w-full bg-primary hover:bg-primary-dark text-white py-3 rounded-xl font-bold text-sm shadow-lg shadow-primary/20 transition-all mt-2">
                            Register Patient
                        </button>
                    </form>
                </div>

                <!-- Search & Edit Tab -->
                <div id="recep-tab-search" class="hidden p-5 space-y-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Search by Name or Email</label>
                        <input type="text" id="patient-search-input" placeholder="Start typing..."
                            class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-primary outline-none transition-all">
                    </div>
                    <div id="patient-search-results" class="space-y-2 max-h-80 overflow-y-auto">
                        <p class="text-xs text-slate-400 text-center py-4">Type at least 2 characters to search</p>
                    </div>
                </div>
            </div>`;
    },

    renderEditPatientModal(id, name, dob, gender, phone) {
        const g = (gender || '').toLowerCase();
        return `
            <div id="edit-patient-modal"
                class="fixed inset-0 z-[300] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
                onclick="if(event.target===this) Dashboard.closeEditPatient()">
                <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 space-y-5">
                    <div class="flex items-center justify-between">
                        <h3 class="font-bold text-lg text-slate-900">Edit Patient</h3>
                        <button onclick="Dashboard.closeEditPatient()"
                            class="text-slate-400 hover:text-slate-600 p-1">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    <form id="edit-patient-form" class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Full Name</label>
                            <input type="text" name="full_name" value="${name}" required
                                class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:ring-2 focus:ring-orange-400 transition-all">
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Date of Birth</label>
                                <input type="date" name="dob" value="${dob || ''}"
                                    class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:ring-2 focus:ring-orange-400 transition-all">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Gender</label>
                                <select name="gender"
                                    class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:ring-2 focus:ring-orange-400 transition-all">
                                    <option value="male" ${g === 'male' ? 'selected' : ''}>Male</option>
                                    <option value="female" ${g === 'female' ? 'selected' : ''}>Female</option>
                                    <option value="other" ${g === 'other' ? 'selected' : ''}>Other</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Phone</label>
                            <input type="tel" name="phone" value="${phone || ''}"
                                class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:ring-2 focus:ring-orange-400 transition-all">
                        </div>
                        <div class="flex gap-3 pt-1">
                            <button type="button" onclick="Dashboard.closeEditPatient()"
                                class="flex-1 px-4 py-3 border border-slate-200 text-slate-600 rounded-xl font-bold text-sm hover:bg-slate-50 transition-all">
                                Cancel
                            </button>
                            <button type="submit"
                                class="flex-1 bg-slate-900 hover:bg-slate-800 text-white py-3 rounded-xl font-bold text-sm transition-all shadow-lg">
                                Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>`;
    },

    // ── ADMIN Dashboard ──────────────────────────────────────────────────────

    renderAdminView(auditData, allStaff, settings = {}) {
        const stats      = auditData.stats       || {};
        const recentUsers = auditData.recent_users || [];

        const roleColors = {
            admin:        'bg-red-100    text-red-700',
            doctor:       'bg-blue-100   text-blue-700',
            receptionist: 'bg-green-100  text-green-700',
            lab_tech:     'bg-purple-100 text-purple-700',
            pharmacist:   'bg-amber-100  text-amber-700',
            patient:      'bg-slate-100  text-slate-600',
        };

        return `
            <div class="space-y-6">

                <!-- Tab Navigation -->
                <div class="flex p-1 bg-slate-100 rounded-xl w-full md:w-auto md:inline-flex">
                    <button onclick="Dashboard.switchAdminTab('overview')" id="tab-overview"
                        class="flex-1 md:flex-none px-6 py-2.5 text-sm font-bold bg-white text-orange-600 rounded-lg shadow-sm transition-all">
                        Overview
                    </button>
                    <button onclick="Dashboard.switchAdminTab('staff')" id="tab-staff"
                        class="flex-1 md:flex-none px-6 py-2.5 text-sm font-bold text-slate-500 hover:text-slate-700 rounded-lg transition-all">
                        Staff
                    </button>
                    <button onclick="Dashboard.switchAdminTab('settings')" id="tab-settings"
                        class="flex-1 md:flex-none px-6 py-2.5 text-sm font-bold text-slate-500 hover:text-slate-700 rounded-lg transition-all">
                        Settings
                    </button>
                </div>

                <!-- ── TAB: OVERVIEW ─────────────────────────────────────── -->
                <div id="admin-tab-overview" class="space-y-6">

                    <!-- Stats Row -->
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                        ${[
                            { label: 'Patients',     val: stats.total_patients     || 0, color: 'border-blue-100   text-blue-600'   },
                            { label: 'Doctors',      val: stats.total_doctors      || 0, color: 'border-green-100  text-green-600'  },
                            { label: 'Appointments', val: stats.total_appointments || 0, color: 'border-amber-100  text-amber-600'  },
                            { label: 'Pending Labs', val: stats.pending_lab_tests  || 0, color: 'border-purple-100 text-purple-600' },
                            { label: 'Revenue',      val: '$' + (stats.total_revenue || '0'), color: 'border-rose-100 text-rose-600' },
                        ].map(s => `
                            <div class="bg-white p-5 rounded-2xl border ${s.color.split(' ')[0]} shadow-sm">
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">${s.label}</p>
                                <p class="text-2xl font-black text-slate-900 mt-1">${s.val}</p>
                            </div>`).join('')}
                    </div>

                    <!-- Recent Activity -->
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                        <div class="px-6 py-4 bg-slate-50 border-b border-slate-200">
                            <h3 class="font-bold text-slate-800">Recent Account Activity</h3>
                        </div>
                        <table class="w-full text-left text-sm">
                            <tbody class="divide-y divide-slate-50">
                                ${recentUsers.map(u => `
                                    <tr class="hover:bg-slate-50 transition-colors">
                                        <td class="px-6 py-4 font-medium text-slate-900">${u.email}</td>
                                        <td class="px-6 py-4">
                                            <span class="text-[10px] font-black uppercase px-2 py-1 rounded-full ${roleColors[u.role] || 'bg-slate-100 text-slate-600'}">
                                                ${u.role}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-right text-slate-400 font-mono text-xs">${u.created_at}</td>
                                    </tr>`).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ── TAB: STAFF ────────────────────────────────────────── -->
                <div id="admin-tab-staff" class="hidden">
                    <div class="grid grid-cols-1 xl:grid-cols-2 gap-8">

                        <!-- Staff List -->
                        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                            <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                                <h3 class="font-bold text-slate-800">Staff Accounts</h3>
                                <span class="text-xs font-bold text-slate-400">${allStaff.filter(s => s.role !== 'patient').length} staff</span>
                            </div>
                            <div class="divide-y divide-slate-50 max-h-72 overflow-y-auto">
                                ${allStaff.filter(s => s.role !== 'patient').map(s => `
                                    <div class="px-6 py-3 flex items-center justify-between hover:bg-slate-50 transition-colors">
                                        <div>
                                            <p class="font-semibold text-slate-900 text-sm">${s.display_name || '—'}</p>
                                            <p class="text-xs text-slate-400">${s.email}</p>
                                        </div>
                                        <div class="flex items-center space-x-1">
                                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full uppercase ${roleColors[s.role] || 'bg-slate-100 text-slate-600'}">
                                                ${s.role}
                                            </span>
                                            <button onclick="Dashboard.openEditStaff(${s.id}, '${(s.display_name||'').replace(/'/g, "\\'")}', '${s.email}', '${s.role}')"
                                                class="text-slate-300 hover:text-blue-500 transition-colors p-1 rounded-lg hover:bg-blue-50" title="Edit">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                </svg>
                                            </button>
                                            <button onclick="Dashboard.deleteStaff(${s.id})"
                                                class="text-slate-300 hover:text-red-500 transition-colors p-1 rounded-lg hover:bg-red-50" title="Delete">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>`).join('')}
                            </div>
                        </div>

                        <!-- Create Staff Form -->
                        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                            <h3 class="font-bold text-slate-800 mb-5">Create Staff Account</h3>
                            <form id="create-staff-form" class="space-y-3">
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Full Name</label>
                                        <input type="text" name="name" required placeholder="Dr. Jane Smith"
                                            class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-primary outline-none transition-all">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Role</label>
                                        <select name="role" id="staff-role-select" required
                                            class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-primary outline-none transition-all">
                                            <option value="doctor">Doctor</option>
                                            <option value="receptionist">Receptionist</option>
                                            <option value="lab_tech">Lab Technician</option>
                                            <option value="pharmacist">Pharmacist</option>
                                            <option value="admin">Admin</option>
                                        </select>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Email</label>
                                    <input type="email" name="email" required placeholder="staff@clinic.com"
                                        class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-primary outline-none transition-all">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Password</label>
                                    <input type="password" name="password" required placeholder="••••••••"
                                        class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-primary outline-none transition-all">
                                </div>
                                <div id="doctor-extra-fields">
                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Specialization</label>
                                            <input type="text" name="specialization" placeholder="e.g. Cardiology"
                                                class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-primary outline-none transition-all">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-slate-400 uppercase mb-1">License #</label>
                                            <input type="text" name="license_number" placeholder="LIC-2025-001"
                                                class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-primary outline-none transition-all">
                                        </div>
                                    </div>
                                </div>
                                <button type="submit"
                                    class="w-full bg-slate-900 hover:bg-slate-800 text-white py-3 rounded-xl font-bold text-sm transition-all shadow-lg mt-1">
                                    Create Account
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- ── TAB: SETTINGS ─────────────────────────────────────── -->
                <div id="admin-tab-settings" class="hidden">
                    ${this.renderAdminSettings(settings)}
                </div>

            </div>`;
    },

    renderAdminSettings(s = {}) {
        const enc = s.smtp_encryption || 'tls';
        return `
            <div class="grid grid-cols-1 xl:grid-cols-2 gap-8">

                <!-- ── Clinic Information ───────────────────────────────── -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                    <div class="flex items-center space-x-3 mb-6">
                        <div class="w-10 h-10 bg-orange-100 text-orange-600 rounded-xl flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-slate-800">Clinic Information</h3>
                            <p class="text-xs text-slate-400">Used in email headers and printed reports</p>
                        </div>
                    </div>
                    <form id="clinic-settings-form" class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Clinic Name</label>
                            <input type="text" name="clinic_name" value="${s.clinic_name || ''}" required
                                class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:ring-2 focus:ring-orange-400 transition-all">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Address</label>
                            <input type="text" name="clinic_address" value="${s.clinic_address || ''}"
                                class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:ring-2 focus:ring-orange-400 transition-all">
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Phone</label>
                                <input type="tel" name="clinic_phone" value="${s.clinic_phone || ''}"
                                    class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:ring-2 focus:ring-orange-400 transition-all">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Reply-To Email</label>
                                <input type="email" name="clinic_email" value="${s.clinic_email || ''}"
                                    class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:ring-2 focus:ring-orange-400 transition-all">
                            </div>
                        </div>
                        <button type="submit"
                            class="w-full bg-slate-900 hover:bg-slate-800 text-white py-3 rounded-xl font-bold text-sm transition-all shadow-lg">
                            Save Clinic Info
                        </button>
                    </form>
                </div>

                <!-- ── Email / SMTP Configuration ──────────────────────── -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 space-y-5">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-blue-100 text-blue-600 rounded-xl flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-slate-800">Email / SMTP</h3>
                            <p class="text-xs text-slate-400">Outbound notifications for appointments &amp; results</p>
                        </div>
                    </div>

                    <form id="email-settings-form" class="space-y-3">

                        <!-- Notifications Toggle -->
                        <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 border border-slate-200">
                            <div>
                                <p class="text-sm font-bold text-slate-700">Email Notifications</p>
                                <p class="text-xs text-slate-400">Send automated emails for appointments &amp; lab results</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer flex-shrink-0">
                                <input type="checkbox" name="email_notifications" class="sr-only peer" ${s.email_notifications !== '0' ? 'checked' : ''}>
                                <div class="w-11 h-6 bg-slate-200 rounded-full peer
                                    peer-focus:ring-2 peer-focus:ring-orange-400
                                    peer-checked:bg-orange-500
                                    after:content-[''] after:absolute after:top-0.5 after:left-[2px]
                                    after:bg-white after:rounded-full after:h-5 after:w-5
                                    after:transition-all peer-checked:after:translate-x-full">
                                </div>
                            </label>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">From Name</label>
                            <input type="text" name="smtp_from_name" value="${s.smtp_from_name || 'Clinic CMS'}"
                                class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:ring-2 focus:ring-orange-400 transition-all">
                        </div>

                        <div class="grid grid-cols-5 gap-3">
                            <div class="col-span-3">
                                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">SMTP Host</label>
                                <input type="text" name="smtp_host" value="${s.smtp_host || ''}" placeholder="smtp.gmail.com"
                                    class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:ring-2 focus:ring-orange-400 transition-all">
                            </div>
                            <div class="col-span-2">
                                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Port</label>
                                <input type="number" name="smtp_port" value="${s.smtp_port || '587'}" placeholder="587"
                                    class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:ring-2 focus:ring-orange-400 transition-all">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Encryption</label>
                            <select name="smtp_encryption"
                                class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:ring-2 focus:ring-orange-400 transition-all">
                                <option value="tls"  ${enc === 'tls'  ? 'selected' : ''}>TLS (port 587 — recommended)</option>
                                <option value="ssl"  ${enc === 'ssl'  ? 'selected' : ''}>SSL (port 465)</option>
                                <option value="none" ${enc === 'none' ? 'selected' : ''}>None (port 25 — local only)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">SMTP Username</label>
                            <input type="email" name="smtp_user" value="${s.smtp_user || ''}" placeholder="you@gmail.com"
                                class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:ring-2 focus:ring-orange-400 transition-all">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">
                                SMTP Password
                                ${s.smtp_pass ? '<span class="text-[10px] text-green-600 font-bold normal-case">(set — leave blank to keep)</span>' : ''}
                            </label>
                            <input type="password" name="smtp_pass"
                                placeholder="${s.smtp_pass ? 'Leave blank to keep current password' : 'App password or SMTP password'}"
                                class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:ring-2 focus:ring-orange-400 transition-all">
                        </div>

                        <button type="submit"
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-xl font-bold text-sm transition-all shadow-lg">
                            Save Email Config
                        </button>
                    </form>

                    <!-- Test Email -->
                    <div class="border-t border-slate-100 pt-5">
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-3">Send Test Email</p>
                        <div class="flex gap-3">
                            <input type="email" id="test-email-to" placeholder="recipient@example.com"
                                class="flex-1 px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:ring-2 focus:ring-orange-400 transition-all">
                            <button id="test-email-btn" onclick="Dashboard.sendTestEmail()"
                                class="bg-slate-900 hover:bg-slate-700 text-white px-5 py-2.5 rounded-xl font-bold text-sm transition-all whitespace-nowrap">
                                Send Test
                            </button>
                        </div>
                        <div class="mt-3 p-3 bg-amber-50 border border-amber-100 rounded-xl">
                            <p class="text-[11px] text-amber-700 font-semibold">
                                💡 <strong>XAMPP users:</strong> PHP's mail() requires a sendmail relay.
                                Install <a href="#" class="underline">MailHog</a> and set
                                <code class="bg-amber-100 px-1 rounded">sendmail_path</code> in php.ini,
                                or use an SMTP-to-gmail bridge. Gmail App Passwords work on port 587 with TLS.
                            </p>
                        </div>
                    </div>
                </div>
            </div>`;
    },

    renderEditStaffModal(id, name, email, role) {
        const roles = ['admin', 'doctor', 'receptionist', 'lab_tech', 'pharmacist'];
        return `
            <div id="edit-staff-modal"
                class="fixed inset-0 z-[300] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
                onclick="if(event.target===this) Dashboard.closeEditStaff()">
                <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 space-y-5">
                    <div class="flex items-center justify-between">
                        <h3 class="font-bold text-lg text-slate-900">Edit Staff Account</h3>
                        <button onclick="Dashboard.closeEditStaff()"
                            class="text-slate-400 hover:text-slate-600 p-1">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <form id="edit-staff-form" class="space-y-4">
                        <input type="hidden" name="id" value="${id}">
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Full Name</label>
                            <input type="text" name="name" value="${name}" required
                                class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:ring-2 focus:ring-orange-400 transition-all">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Email</label>
                            <input type="email" name="email" value="${email}" required
                                class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:ring-2 focus:ring-orange-400 transition-all">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Role</label>
                            <select name="role"
                                class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:ring-2 focus:ring-orange-400 transition-all">
                                ${roles.map(r => `<option value="${r}" ${r === role ? 'selected' : ''}>${r.charAt(0).toUpperCase() + r.slice(1).replace('_', ' ')}</option>`).join('')}
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">
                                New Password
                                <span class="text-[10px] text-slate-400 normal-case font-normal">(leave blank to keep current)</span>
                            </label>
                            <input type="password" name="password" placeholder="••••••••"
                                class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:ring-2 focus:ring-orange-400 transition-all">
                        </div>
                        <div class="flex gap-3 pt-1">
                            <button type="button" onclick="Dashboard.closeEditStaff()"
                                class="flex-1 px-4 py-3 border border-slate-200 text-slate-600 rounded-xl font-bold text-sm hover:bg-slate-50 transition-all">
                                Cancel
                            </button>
                            <button type="submit"
                                class="flex-1 bg-slate-900 hover:bg-slate-800 text-white py-3 rounded-xl font-bold text-sm transition-all shadow-lg">
                                Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>`;
    }
};


