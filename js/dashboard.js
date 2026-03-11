/**
 * Main Dashboard Controller
 */

window.Dashboard = {
    /**
     * Initialize dashboard based on user role
     * @param {string} role 
     */
    init(role) {
        const content = document.getElementById('dashboard-content');
        content.innerHTML = `<div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100">
            <h2 class="text-2xl font-bold text-gray-800">Welcome to your ${role} portal</h2>
            <p class="text-gray-500 mt-2">Loading your customized workspace...</p>
        </div>`;

        this.loadModule(role);
    },

    /**
     * Load role-specific modules
     * @param {string} role 
     */
    async loadModule(role) {
        const content = document.getElementById('dashboard-content');

        try {
            if (role === 'receptionist') {
                const data = await API.request('appointments/list.php?status=scheduled');
                content.innerHTML = `
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        <div class="lg:col-span-2 space-y-8">${UI.renderAppointmentQueue(data.appointments)}</div>
                        <div class="space-y-8">${UI.renderPatientRegistration()}</div>
                    </div>
                `;
                this.bindReceptionistEvents();
            }
            else if (role === 'doctor') {
                const data = await API.request('appointments/list.php?status=scheduled');
                content.innerHTML = UI.renderDoctorView(data.appointments);
                this.bindDoctorEvents();
            }
            else if (role === 'lab_tech') {
                const data = await API.request('lab/tests.php');
                content.innerHTML = UI.renderLabTechView(data.tests);
                this.bindLabEvents();
            }
            else if (role === 'pharmacist') {
                const data = await API.request('pharmacy/prescriptions.php');
                content.innerHTML = UI.renderPharmacistView(data.prescriptions);
            }
            else if (role === 'patient') {
                const appData = await API.request('appointments/list.php?status=all');
                const docData = await API.request('doctors/list.php');
                content.innerHTML = UI.renderPatientView(appData.appointments, docData.doctors);
                this.bindPatientEvents();
            }
            else if (role === 'admin') {
                const data = await API.request('admin/audit.php');
                content.innerHTML = UI.renderAdminView(data);
            }
            else {
                content.innerHTML = `<div class="p-8 text-center text-gray-500">Role module mismatch.</div>`;
            }
        } catch (err) {
            content.innerHTML = `<div class="p-8 text-center text-red-500">Error loading module: ${err.message}</div>`;
        }
    },

    showReviewModal(id, name) {
        document.getElementById('review-modal').classList.remove('hidden');
        document.getElementById('review-title').textContent = `Review ${name}`;
        document.getElementById('review-doctor-id').value = id;
        this.bindReviewForm();
    },

    closeReviewModal() {
        document.getElementById('review-modal').classList.add('hidden');
    },

    bindReviewForm() {
        const revForm = document.getElementById('review-form');
        revForm.onsubmit = async (e) => {
            e.preventDefault();
            const formData = new FormData(revForm);
            const data = Object.fromEntries(formData.entries());
            try {
                await API.request('patients/review.php', { method: 'POST', body: JSON.stringify(data) });
                alert('Review Submitted!');
                this.closeReviewModal();
            } catch (err) { alert(err.message); }
        };
    },

    async dispensePrescription(id, amount) {
        if (confirm(`Dispense Prescription #${id} and generate invoice for $${amount}?`)) {
            try {
                // In a real app, we'd get patient_id from the prescription object
                await API.request('billing/create_invoice.php', {
                    method: 'POST',
                    body: JSON.stringify({ patient_id: 1, reference_type: 'prescription', reference_id: id, amount: amount })
                });
                alert('Invoice Generated & Prescription Dispensed!');
                this.loadModule('pharmacist');
            } catch (err) { alert(err.message); }
        }
    },

    bindPatientEvents() {
        const bookForm = document.getElementById('book-appointment-form');
        if (bookForm) {
            bookForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData(bookForm);
                const data = Object.fromEntries(formData.entries());
                try {
                    await API.request('appointments/create.php', { method: 'POST', body: JSON.stringify(data) });
                    alert('Appointment Booked!');
                    this.loadModule('patient');
                } catch (err) { alert(err.message); }
            });
        }
    },

    bindDoctorEvents() {
        const queueRows = document.querySelectorAll('tbody tr');
        queueRows.forEach(row => {
            row.style.cursor = 'pointer';
            row.addEventListener('click', () => {
                const patientName = row.cells[0].textContent;
                // For demo, we'll assume ID 1 or find it in metadata if we had it
                const patientId = 1;
                document.getElementById('patient-interaction').innerHTML = UI.renderDiagnosisForm(patientId, patientName);
                this.bindDiagnosisForm();
            });
        });
    },

    bindDiagnosisForm() {
        const diagForm = document.getElementById('diagnosis-form');
        diagForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(diagForm);
            const data = Object.fromEntries(formData.entries());
            try {
                await API.request('medical/create_record.php', { method: 'POST', body: JSON.stringify(data) });
                alert('Medical Record Saved!');
                this.loadModule('doctor');
            } catch (err) { alert(err.message); }
        });
    },

    async orderLabTest(patientId) {
        const testType = prompt("Enter Test Type (e.g., Blood Platelets, X-Ray):");
        if (testType) {
            try {
                await API.request('lab/tests.php', { method: 'POST', body: JSON.stringify({ patient_id: patientId, test_type: testType }) });
                alert('Lab Test Ordered!');
            } catch (err) { alert(err.message); }
        }
    },

    bindLabEvents() {
        const uploadForm = document.getElementById('upload-form');
        uploadForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(uploadForm);
            try {
                const response = await fetch('api/lab/upload_report.php', { method: 'POST', body: formData });
                const result = await response.json();
                if (result.success) {
                    alert('Report Uploaded!');
                    this.closeModal();
                    this.loadModule('lab_tech');
                } else throw new Error(result.error);
            } catch (err) { alert(err.message); }
        });
    },

    showUploadForm(id, name) {
        document.getElementById('upload-modal').classList.remove('hidden');
        document.getElementById('upload-title').textContent = `Upload Report for ${name}`;
        document.getElementById('upload-test-id').value = id;
    },

    closeModal() {
        document.getElementById('upload-modal').classList.add('hidden');
    },

    bindReceptionistEvents() {
        const regForm = document.getElementById('reg-patient-form');
        if (regForm) {
            regForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData(regForm);
                const data = Object.fromEntries(formData.entries());

                try {
                    await API.request('patients/register.php', {
                        method: 'POST',
                        body: JSON.stringify(data)
                    });
                    alert('Patient Registered!');
                    this.loadModule('receptionist');
                } catch (err) {
                    alert('Error: ' + err.message);
                }
            });
        }
    }
};
