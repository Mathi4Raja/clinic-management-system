/**
 * Main Dashboard Controller
 * Handles role-based modules, event binding, and async data operations.
 */

window.Dashboard = {
    currentRole: null,
    medicines: [],   // Pre-fetched medicine list for doctor prescriptions

    /**
     * Initialize dashboard for a given role.
     */
    init(role) {
        console.log('Dashboard init for role:', role);
        this.currentRole = role;

        const content = document.getElementById('dashboard-content');
        if (!content) { console.error('dashboard-content not found'); return; }

        content.innerHTML = `
            <div class="bg-white p-8 rounded-3xl shadow-sm border border-slate-200">
                <div class="h-6 bg-slate-100 rounded-xl w-1/3 mb-3"></div>
                <div class="h-4 bg-slate-50 rounded-xl w-1/2"></div>
            </div>`;

        if (role === 'doctor') this.fetchMedicines();
        this.loadModule(role);
    },

    async fetchMedicines() {
        try {
            const data = await API.request('medicines/list.php');
            this.medicines = data.medicines || [];
        } catch (err) {
            console.warn('Could not fetch medicines:', err.message);
        }
    },

    async loadModule(role) {
        role = role || this.currentRole;
        if (!role) return;
        this.currentRole = role;

        const content = document.getElementById('dashboard-content');
        if (!content) return;

        try {
            if (role === 'receptionist') {
                const queueData = await API.request('appointments/list.php?status=scheduled');
                content.innerHTML = `
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        <div class="lg:col-span-2 space-y-6">
                            ${UI.renderAppointmentQueue(queueData.appointments, 'receptionist')}
                        </div>
                        <div class="space-y-6">${UI.renderReceptionistSidePanel()}</div>
                    </div>`;
                this.bindReceptionistEvents();
            }
            else if (role === 'doctor') {
                const data = await API.request('appointments/list.php?status=scheduled');
                if (this.medicines.length === 0) await this.fetchMedicines();
                content.innerHTML = UI.renderDoctorView(data.appointments);
                this.bindDoctorEvents();
            }
            else if (role === 'lab_tech') {
                const data = await API.request('lab/tests.php');
                content.innerHTML = UI.renderLabTechView(data.tests, []);
                this.bindLabEvents();
            }
            else if (role === 'pharmacist') {
                const [rxData, invoiceData] = await Promise.all([
                    API.request('pharmacy/prescriptions.php'),
                    API.request('billing/list.php').catch(() => ({ invoices: [] }))
                ]);
                content.innerHTML = UI.renderPharmacistView(rxData.prescriptions, invoiceData.invoices || []);
                this.bindPharmacistEvents();
            }
            else if (role === 'patient') {
                const [appData, docData, recordsData, labData, invoiceData] = await Promise.all([
                    API.request('appointments/list.php?status=all'),
                    API.request('doctors/list.php'),
                    API.request('medical/list.php').catch(() => ({ records: [] })),
                    API.request('lab/tests.php').catch(() => ({ tests: [] })),
                    API.request('billing/list.php').catch(() => ({ invoices: [] }))
                ]);
                content.innerHTML = UI.renderPatientView(
                    appData.appointments,
                    docData.doctors,
                    recordsData.records || [],
                    labData.tests || [],
                    invoiceData.invoices || []
                );
                this.bindPatientEvents();
            }
            else if (role === 'admin') {
                const [auditData, staffData, settingsData] = await Promise.all([
                    API.request('admin/audit.php'),
                    API.request('admin/staff.php'),
                    API.request('admin/settings.php')
                ]);
                content.innerHTML = UI.renderAdminView(auditData, staffData.staff || [], settingsData.settings || {});
                this.bindAdminEvents();
            }
            else {
                content.innerHTML = `<div class="p-8 text-center text-slate-400">Unknown role module.</div>`;
            }
        } catch (err) {
            content.innerHTML = `
                <div class="p-8 text-center">
                    <div class="inline-flex items-center space-x-3 bg-red-50 text-red-600 px-6 py-4 rounded-2xl border border-red-100">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="font-semibold text-sm">Error loading module: ${err.message}</span>
                    </div>
                </div>`;
        }
    },

    // ─── PATIENT ────────────────────────────────────────────────────────────

    bindPatientEvents() {
        const bookForm = document.getElementById('book-appointment-form');
        if (bookForm) {
            bookForm.onsubmit = async (e) => {
                e.preventDefault();
                const btn = bookForm.querySelector('[type="submit"]');
                btn.disabled = true; btn.textContent = 'Scheduling...';
                const data = Object.fromEntries(new FormData(bookForm).entries());
                try {
                    await API.request('appointments/create.php', { method: 'POST', body: JSON.stringify(data) });
                    this.showNotification('Appointment scheduled!', 'success');
                    this.loadModule('patient');
                } catch (err) {
                    this.showNotification('Error: ' + err.message, 'error');
                    btn.disabled = false; btn.textContent = 'Schedule Appointment';
                }
            };
        }
    },

    async cancelAppointment(id) {
        if (!confirm('Cancel this appointment? This cannot be undone.')) return;
        try {
            await API.request('appointments/update.php', {
                method: 'POST', body: JSON.stringify({ id, status: 'cancelled' })
            });
            this.showNotification('Appointment cancelled.', 'success');
            this.loadModule(this.currentRole);
        } catch (err) { this.showNotification('Error: ' + err.message, 'error'); }
    },

    async updateAppointmentStatus(id, status) {
        try {
            await API.request('appointments/update.php', {
                method: 'POST', body: JSON.stringify({ id, status })
            });
            this.showNotification(`Appointment marked as ${status}.`, 'success');
            this.loadModule(this.currentRole);
        } catch (err) { this.showNotification('Error: ' + err.message, 'error'); }
    },

    // ─── DOCTOR ─────────────────────────────────────────────────────────────

    bindDoctorEvents() {
        document.querySelectorAll('[data-action="treat"]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.startConsultation(
                    btn.dataset.patientId,
                    btn.dataset.patientName,
                    btn.dataset.appointmentId
                );
            });
        });
        document.querySelectorAll('[data-action="history"]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.viewPatientHistory(btn.dataset.patientId, btn.dataset.patientName);
            });
        });
    },

    startConsultation(patientId, patientName, appointmentId) {
        const panel = document.getElementById('patient-interaction');
        if (panel) {
            panel.innerHTML = UI.renderDiagnosisForm(patientId, patientName, appointmentId, this.medicines);
            this.bindDiagnosisForm();
        }
    },

    bindDiagnosisForm() {
        const form = document.getElementById('diagnosis-form');
        if (!form) return;
        form.onsubmit = async (e) => {
            e.preventDefault();
            const btn = form.querySelector('[type="submit"]');
            btn.disabled = true; btn.textContent = 'Saving...';
            const data = {
                patient_id:     form.querySelector('[name="patient_id"]').value,
                appointment_id: form.querySelector('[name="appointment_id"]')?.value,
                diagnosis:      form.querySelector('[name="diagnosis"]').value,
                clinical_notes: form.querySelector('[name="clinical_notes"]').value,
                prescription:   this.collectPrescriptionItems()
            };
            try {
                await API.request('medical/create_record.php', { method: 'POST', body: JSON.stringify(data) });
                if (data.appointment_id) {
                    await API.request('appointments/update.php', {
                        method: 'POST', body: JSON.stringify({ id: data.appointment_id, status: 'completed' })
                    }).catch(() => {});
                }
                this.showNotification('Medical record saved!', 'success');
                this.loadModule('doctor');
            } catch (err) {
                this.showNotification('Save failed: ' + err.message, 'error');
                btn.disabled = false; btn.textContent = 'Save & Complete';
            }
        };
    },

    collectPrescriptionItems() {
        const items = [];
        document.querySelectorAll('.rx-item-row').forEach(row => {
            const medId  = row.querySelector('[name="medicine_id"]')?.value;
            const dosage = row.querySelector('[name="dosage"]')?.value?.trim();
            const freq   = row.querySelector('[name="frequency"]')?.value?.trim();
            if (medId && dosage) items.push({ medicine_id: medId, dosage, frequency: freq || '' });
        });
        return items;
    },

    addPrescriptionRow() {
        const container = document.getElementById('rx-items');
        if (!container) return;
        const row = document.createElement('div');
        row.className = 'rx-item-row grid grid-cols-12 gap-2 items-center mt-2';
        row.innerHTML = `
            <select name="medicine_id" required
                class="col-span-5 bg-white/10 border border-white/20 rounded-xl px-3 py-2 text-xs text-white outline-none focus:border-primary">
                <option value="">Select medicine...</option>
                ${this.medicines.map(m => `<option value="${m.id}">${m.name} ($${parseFloat(m.price).toFixed(2)})</option>`).join('')}
            </select>
            <input type="text" name="dosage" placeholder="Dosage e.g. 500mg" required
                class="col-span-3 bg-white/10 border border-white/20 rounded-xl px-3 py-2 text-xs text-white placeholder-white/40 outline-none focus:border-primary">
            <input type="text" name="frequency" placeholder="Twice daily"
                class="col-span-3 bg-white/10 border border-white/20 rounded-xl px-3 py-2 text-xs text-white placeholder-white/40 outline-none focus:border-primary">
            <button type="button" onclick="this.closest('.rx-item-row').remove()"
                class="col-span-1 text-red-400 hover:text-red-300 p-1 rounded-lg hover:bg-white/10 flex justify-center">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>`;
        container.appendChild(row);
    },

    async orderLabTest(patientId) {
        const testType = prompt('Enter test type (e.g., Blood Panel, CBC, X-Ray, Urinalysis):');
        if (testType && testType.trim()) {
            try {
                await API.request('lab/tests.php', {
                    method: 'POST', body: JSON.stringify({ patient_id: patientId, test_type: testType.trim() })
                });
                this.showNotification('Lab test ordered!', 'success');
            } catch (err) { this.showNotification('Error: ' + err.message, 'error'); }
        }
    },

    // ─── LAB TECH ───────────────────────────────────────────────────────────

    bindLabEvents() {
        const uploadForm = document.getElementById('upload-form');
        if (uploadForm) {
            uploadForm.onsubmit = async (e) => {
                e.preventDefault();
                const btn = uploadForm.querySelector('[type="submit"]');
                btn.disabled = true; btn.textContent = 'Uploading...';
                const formData = new FormData(uploadForm);
                try {
                    const response = await fetch('api/lab/upload_report.php', {
                        method: 'POST', body: formData,
                        headers: { 'X-CSRF-TOKEN': API.csrfToken }
                    });
                    const result = await response.json();
                    if (!response.ok) throw new Error(result.error || 'Upload failed');
                    this.showNotification('Report submitted!', 'success');
                    this.closeModal();
                    this.loadModule('lab_tech');
                } catch (err) {
                    this.showNotification('Error: ' + err.message, 'error');
                    btn.disabled = false; btn.textContent = 'Submit Report';
                }
            };
        }
    },

    showUploadForm(id, name) {
        document.getElementById('upload-modal').classList.remove('hidden');
        document.getElementById('upload-title').textContent = `Upload Report: ${name}`;
        document.getElementById('upload-test-id').value = id;
        this.bindLabEvents();
    },

    closeModal() {
        document.getElementById('upload-modal').classList.add('hidden');
        const form = document.getElementById('upload-form');
        if (form) form.reset();
    },

    // ─── REVIEWS ────────────────────────────────────────────────────────────

    showReviewModal(id, name) {
        const modal = document.getElementById('review-modal');
        if (modal) {
            modal.classList.remove('hidden');
            document.getElementById('review-title').textContent = `Review Dr. ${name}`;
            document.getElementById('review-doctor-id').value = id;
            this.bindReviewForm();
        }
    },

    closeReviewModal() {
        const modal = document.getElementById('review-modal');
        if (modal) modal.classList.add('hidden');
    },

    bindReviewForm() {
        const form = document.getElementById('review-form');
        if (!form) return;
        form.onsubmit = async (e) => {
            e.preventDefault();
            const data = Object.fromEntries(new FormData(form).entries());
            try {
                await API.request('patients/review.php', { method: 'POST', body: JSON.stringify(data) });
                this.showNotification('Review submitted! Thank you.', 'success');
                this.closeReviewModal();
            } catch (err) { this.showNotification('Error: ' + err.message, 'error'); }
        };
    },

    // ─── PATIENT TABS ────────────────────────────────────────────────────────

    switchPatientTab(tab) {
        ['appointments', 'records', 'labs', 'invoices'].forEach(t => {
            document.getElementById(`patient-tab-${t}`)?.classList.toggle('hidden', t !== tab);
            const btn = document.getElementById(`ptab-${t}`);
            if (!btn) return;
            if (t === tab) {
                btn.classList.add('bg-white', 'text-primary', 'shadow-sm');
                btn.classList.remove('text-slate-500', 'hover:text-slate-700');
            } else {
                btn.classList.remove('bg-white', 'text-primary', 'shadow-sm');
                btn.classList.add('text-slate-500', 'hover:text-slate-700');
            }
        });
    },

    // ─── PHARMACIST ─────────────────────────────────────────────────────────

    bindPharmacistEvents() {
        // Mark-paid buttons use inline onclick; nothing additional to bind here
    },

    switchPharmacistTab(tab) {
        ['prescriptions', 'invoices'].forEach(t => {
            document.getElementById(`pharm-tab-${t}`)?.classList.toggle('hidden', t !== tab);
            const btn = document.getElementById(`pharmtab-${t}`);
            if (!btn) return;
            if (t === tab) {
                btn.classList.add('bg-white', 'text-primary', 'shadow-sm');
                btn.classList.remove('text-slate-500', 'hover:text-slate-700');
            } else {
                btn.classList.remove('bg-white', 'text-primary', 'shadow-sm');
                btn.classList.add('text-slate-500', 'hover:text-slate-700');
            }
        });
    },

    async markInvoicePaid(id) {
        if (!confirm('Mark this invoice as PAID?')) return;
        try {
            await API.request('billing/update.php', {
                method: 'PATCH', body: JSON.stringify({ id, status: 'paid' })
            });
            this.showNotification('Invoice marked as paid!', 'success');
            this.loadModule(this.currentRole);
        } catch (err) { this.showNotification('Error: ' + err.message, 'error'); }
    },

    async voidInvoice(id) {
        if (!confirm('Void this invoice? This cannot be undone.')) return;
        try {
            await API.request('billing/update.php', {
                method: 'PATCH', body: JSON.stringify({ id, status: 'void' })
            });
            this.showNotification('Invoice voided.', 'success');
            this.loadModule(this.currentRole);
        } catch (err) { this.showNotification('Error: ' + err.message, 'error'); }
    },

    async dispensePrescription(prescriptionId, patientId, amount) {
        if (!confirm(`Dispense Prescription #${prescriptionId} and generate invoice for $${parseFloat(amount).toFixed(2)}?`)) return;
        try {
            await API.request('billing/create_invoice.php', {
                method: 'POST',
                body: JSON.stringify({
                    patient_id:     parseInt(patientId),
                    reference_type: 'prescription',
                    reference_id:   prescriptionId,
                    amount:         parseFloat(amount)
                })
            });
            this.showNotification('Invoice generated & prescription dispensed!', 'success');
            this.loadModule('pharmacist');
        } catch (err) { this.showNotification('Error: ' + err.message, 'error'); }
    },

    // ─── LAB TECH TABS ───────────────────────────────────────────────────────

    switchLabTab(tab) {
        ['pending', 'completed'].forEach(t => {
            document.getElementById(`lab-tab-${t}`)?.classList.toggle('hidden', t !== tab);
            const btn = document.getElementById(`labtab-${t}`);
            if (!btn) return;
            if (t === tab) {
                btn.classList.add('bg-white', 'text-primary', 'shadow-sm');
                btn.classList.remove('text-slate-500', 'hover:text-slate-700');
            } else {
                btn.classList.remove('bg-white', 'text-primary', 'shadow-sm');
                btn.classList.add('text-slate-500', 'hover:text-slate-700');
            }
        });
        if (tab === 'completed') this.loadCompletedTests();
    },

    async loadCompletedTests() {
        const container = document.getElementById('lab-completed-list');
        if (!container) return;
        container.innerHTML = `<div class="col-span-full py-8 text-center text-slate-400 text-sm">Loading...</div>`;
        try {
            const data = await API.request('lab/tests.php?status=completed');
            container.innerHTML = UI.renderLabTestCards(data.tests || [], 'completed');
        } catch (err) {
            container.innerHTML = `<div class="col-span-full py-8 text-center text-red-400 text-sm">Failed to load</div>`;
        }
    },

    // ─── RECEPTIONIST TABS ───────────────────────────────────────────────────

    switchReceptionistTab(tab) {
        ['register', 'search'].forEach(t => {
            document.getElementById(`recep-tab-${t}`)?.classList.toggle('hidden', t !== tab);
            const btn = document.getElementById(`receptab-${t}`);
            if (!btn) return;
            if (t === tab) {
                btn.classList.add('bg-white', 'text-primary', 'shadow-sm');
                btn.classList.remove('text-slate-500', 'hover:text-slate-700');
            } else {
                btn.classList.remove('bg-white', 'text-primary', 'shadow-sm');
                btn.classList.add('text-slate-500', 'hover:text-slate-700');
            }
        });
    },

    async searchPatients(query) {
        if (!query || query.trim().length < 2) return;
        const container = document.getElementById('patient-search-results');
        if (!container) return;
        container.innerHTML = `<p class="text-xs text-slate-400 text-center py-4">Searching...</p>`;
        try {
            const data = await API.request(`patients/list.php?q=${encodeURIComponent(query.trim())}`);
            const patients = data.patients || [];
            if (!patients.length) {
                container.innerHTML = `<p class="text-xs text-slate-400 text-center py-4">No patients found</p>`;
                return;
            }
            container.innerHTML = patients.map(p => `
                <div class="flex items-center justify-between p-3 rounded-xl border border-slate-100 hover:border-orange-200 transition-all">
                    <div>
                        <p class="font-semibold text-slate-900 text-sm">${this._esc(p.full_name)}</p>
                        <p class="text-[10px] text-slate-400 font-bold uppercase">${p.email}</p>
                    </div>
                    <button onclick="Dashboard.openEditPatient(${p.patient_profile_id || p.id}, '${this._esc(p.full_name)}', '${p.dob || ''}', '${p.gender || ''}', '${this._esc(p.phone || '')}')"
                        class="text-xs font-bold text-primary bg-primary/10 px-3 py-1.5 rounded-lg hover:bg-primary hover:text-white transition-all">
                        Edit
                    </button>
                </div>`).join('');
        } catch (err) {
            container.innerHTML = `<p class="text-xs text-red-400 text-center py-4">Search failed</p>`;
        }
    },

    openEditPatient(id, name, dob, gender, phone) {
        document.body.insertAdjacentHTML('beforeend', UI.renderEditPatientModal(id, name, dob, gender, phone));
        const form = document.getElementById('edit-patient-form');
        if (!form) return;
        form.onsubmit = async (e) => {
            e.preventDefault();
            const btn = form.querySelector('[type="submit"]');
            btn.disabled = true; btn.textContent = 'Saving...';
            const data = Object.fromEntries(new FormData(form).entries());
            data.id = id;
            try {
                await API.request('patients/update.php', { method: 'PATCH', body: JSON.stringify(data) });
                this.showNotification('Patient updated!', 'success');
                this.closeEditPatient();
            } catch (err) {
                this.showNotification('Error: ' + err.message, 'error');
                btn.disabled = false; btn.textContent = 'Save Changes';
            }
        };
    },

    closeEditPatient() {
        document.getElementById('edit-patient-modal')?.remove();
    },

    // ─── DOCTOR: PATIENT HISTORY ─────────────────────────────────────────────

    async viewPatientHistory(patientId, patientName) {
        const panel = document.getElementById('patient-interaction');
        if (!panel) return;
        panel.innerHTML = `
            <div class="bg-slate-800 rounded-2xl p-6 text-white">
                <p class="text-sm text-slate-300">Loading history for <strong>${this._esc(patientName)}</strong>...</p>
            </div>`;
        try {
            const data = await API.request(`medical/list.php?patient_id=${patientId}`);
            panel.innerHTML = UI.renderPatientHistoryPanel(data.records || [], patientName);
        } catch (err) {
            panel.innerHTML = `<div class="bg-red-900/30 p-4 rounded-2xl text-red-300 text-sm">Failed to load history.</div>`;
        }
    },

    _esc(str) {
        if (!str) return '';
        return String(str).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    },

    // ─── RECEPTIONIST ───────────────────────────────────────────────────────

    bindReceptionistEvents() {
        const regForm = document.getElementById('reg-patient-form');
        if (regForm) {
            regForm.onsubmit = async (e) => {
                e.preventDefault();
                const btn = regForm.querySelector('[type="submit"]');
                btn.disabled = true; btn.textContent = 'Registering...';
                const data = Object.fromEntries(new FormData(regForm).entries());
                try {
                    await API.request('patients/register.php', { method: 'POST', body: JSON.stringify(data) });
                    this.showNotification('Patient registered successfully!', 'success');
                    regForm.reset();
                    this.loadModule('receptionist');
                } catch (err) {
                    this.showNotification('Error: ' + err.message, 'error');
                    btn.disabled = false; btn.textContent = 'Register Patient';
                }
            };
        }

        const searchInput = document.getElementById('patient-search-input');
        if (searchInput) {
            let debounce;
            searchInput.addEventListener('input', () => {
                clearTimeout(debounce);
                debounce = setTimeout(() => this.searchPatients(searchInput.value), 400);
            });
        }
    },

    // ─── ADMIN ──────────────────────────────────────────────────────────────

    bindAdminEvents() {
        const staffForm = document.getElementById('create-staff-form');
        if (staffForm) {
            staffForm.onsubmit = async (e) => {
                e.preventDefault();
                const btn = staffForm.querySelector('[type="submit"]');
                btn.disabled = true; btn.textContent = 'Creating...';
                const data = Object.fromEntries(new FormData(staffForm).entries());
                try {
                    await API.request('admin/staff.php', { method: 'POST', body: JSON.stringify(data) });
                    this.showNotification('Staff account created!', 'success');
                    staffForm.reset();
                    this.loadModule('admin');
                } catch (err) {
                    this.showNotification('Error: ' + err.message, 'error');
                    btn.disabled = false; btn.textContent = 'Create Account';
                }
            };
        }

        const roleSelect = document.getElementById('staff-role-select');
        if (roleSelect) {
            roleSelect.addEventListener('change', () => {
                const docFields = document.getElementById('doctor-extra-fields');
                if (docFields) docFields.classList.toggle('hidden', roleSelect.value !== 'doctor');
            });
        }

        this.bindSettingsEvents();
    },

    switchAdminTab(tab) {
        ['overview', 'staff', 'settings'].forEach(t => {
            document.getElementById(`admin-tab-${t}`)?.classList.toggle('hidden', t !== tab);
            const btn = document.getElementById(`tab-${t}`);
            if (!btn) return;
            if (t === tab) {
                btn.classList.add('bg-white', 'text-orange-600', 'shadow-sm');
                btn.classList.remove('text-slate-500', 'hover:text-slate-700');
            } else {
                btn.classList.remove('bg-white', 'text-orange-600', 'shadow-sm');
                btn.classList.add('text-slate-500', 'hover:text-slate-700');
            }
        });
    },

    bindSettingsEvents() {
        const clinicForm = document.getElementById('clinic-settings-form');
        if (clinicForm) {
            clinicForm.onsubmit = async (e) => {
                e.preventDefault();
                const btn = clinicForm.querySelector('[type="submit"]');
                btn.disabled = true; btn.textContent = 'Saving…';
                const data = Object.fromEntries(new FormData(clinicForm).entries());
                try {
                    await API.request('admin/settings.php', { method: 'POST', body: JSON.stringify(data) });
                    this.showNotification('Clinic settings saved!', 'success');
                } catch (err) {
                    this.showNotification('Error: ' + err.message, 'error');
                } finally {
                    btn.disabled = false; btn.textContent = 'Save Clinic Info';
                }
            };
        }

        const emailForm = document.getElementById('email-settings-form');
        if (emailForm) {
            emailForm.onsubmit = async (e) => {
                e.preventDefault();
                const btn = emailForm.querySelector('[type="submit"]');
                btn.disabled = true; btn.textContent = 'Saving…';
                const data = Object.fromEntries(new FormData(emailForm).entries());
                // Checkbox is absent from FormData when unchecked — set explicitly
                data.email_notifications = emailForm.querySelector('[name="email_notifications"]')?.checked ? '1' : '0';
                // Don't send the smtp_pass field at all if it was left blank
                if (!data.smtp_pass) delete data.smtp_pass;
                try {
                    await API.request('admin/settings.php', { method: 'POST', body: JSON.stringify(data) });
                    this.showNotification('Email settings saved!', 'success');
                } catch (err) {
                    this.showNotification('Error: ' + err.message, 'error');
                } finally {
                    btn.disabled = false; btn.textContent = 'Save Email Config';
                }
            };
        }
    },

    async sendTestEmail() {
        const toInput = document.getElementById('test-email-to');
        const to = toInput?.value?.trim();
        if (!to) { this.showNotification('Enter a recipient email address', 'error'); return; }
        const btn = document.getElementById('test-email-btn');
        if (btn) { btn.disabled = true; btn.textContent = 'Sending…'; }
        try {
            const result = await API.request('admin/test_email.php', {
                method: 'POST', body: JSON.stringify({ to })
            });
            this.showNotification(result.message, result.success ? 'success' : 'error');
        } catch (err) {
            this.showNotification('Error: ' + err.message, 'error');
        } finally {
            if (btn) { btn.disabled = false; btn.textContent = 'Send Test'; }
        }
    },

    async deleteStaff(id) {
        if (!confirm('Permanently delete this staff account? This cannot be undone.')) return;
        try {
            await API.request('admin/staff.php', {
                method: 'DELETE', body: JSON.stringify({ id })
            });
            this.showNotification('Staff account deleted.', 'success');
            this.loadModule('admin');
        } catch (err) { this.showNotification('Error: ' + err.message, 'error'); }
    },

    openEditStaff(id, name, email, role) {
        document.body.insertAdjacentHTML('beforeend', UI.renderEditStaffModal(id, name, email, role));
        const form = document.getElementById('edit-staff-form');
        if (!form) return;
        form.onsubmit = async (e) => {
            e.preventDefault();
            const btn = form.querySelector('[type="submit"]');
            btn.disabled = true; btn.textContent = 'Saving…';
            const data = Object.fromEntries(new FormData(form).entries());
            // Don't send blank password
            if (!data.password) delete data.password;
            try {
                await API.request('admin/staff.php', { method: 'PATCH', body: JSON.stringify(data) });
                this.showNotification('Account updated!', 'success');
                this.closeEditStaff();
                this.loadModule('admin');
            } catch (err) {
                this.showNotification('Error: ' + err.message, 'error');
                btn.disabled = false; btn.textContent = 'Save Changes';
            }
        };
    },

    closeEditStaff() {
        document.getElementById('edit-staff-modal')?.remove();
    },

    // ─── UTILITIES ──────────────────────────────────────────────────────────

    showNotification(msg, type = 'success') {
        const existing = document.getElementById('cms-notification');
        if (existing) existing.remove();

        const n = document.createElement('div');
        n.id = 'cms-notification';
        n.className = `fixed bottom-6 right-6 z-[200] flex items-center space-x-3 px-6 py-4 rounded-2xl shadow-2xl font-semibold text-sm max-w-sm ${
            type === 'success'
                ? 'bg-slate-900 text-white'
                : 'bg-red-500 text-white'
        }`;
        n.innerHTML = `
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                ${type === 'success'
                    ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>'
                    : '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>'}
            </svg>
            <span>${msg}</span>`;
        document.body.appendChild(n);
        setTimeout(() => { if (n.parentNode) n.remove(); }, 4000);
    }
};
