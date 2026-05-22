/**
 * Subcontractor Detail Page Logic
 * Handles job and payment management for a single subcontractor
 */

let subcontractorId = null;
let subcontractorData = null;
let jobsData = [];
let paymentsData = [];
let currentEditJobId = null;
let currentEditPaymentId = null;

// Called by app.js after subcontractor HTML is injected
async function initSubcontractor(id) {
    subcontractorId = id;
    subcontractorData = null;
    jobsData = [];
    paymentsData = [];
    currentEditJobId = null;
    currentEditPaymentId = null;

    await loadSubcontractorDetail();
    setupEventListeners();
}

/**
 * Setup event listeners
 */
function setupEventListeners() {
    // Add job button
    const addJobBtn = document.getElementById('addJobBtn');
    if (addJobBtn) {
        addJobBtn.addEventListener('click', openAddJobModal);
    }
    
    // Add payment button
    const addPaymentBtn = document.getElementById('addPaymentBtn');
    if (addPaymentBtn) {
        addPaymentBtn.addEventListener('click', openAddPaymentModal);
    }
    
    // Job form
    const jobForm = document.getElementById('jobForm');
    if (jobForm) {
        jobForm.addEventListener('submit', handleJobSubmit);
        
        // Real-time calculation for job form
        const metrekareInput = document.getElementById('jobMetrekare');
        const birimFiyatInput = document.getElementById('jobBirimFiyat');
        const teslimatTipiSelect = document.getElementById('jobTeslimatTipi');
        
        const updateCalculation = () => {
            const metrekare = parseFloat(metrekareInput.value) || 0;
            const birimFiyat = parseFloat(birimFiyatInput.value) || 0;
            const teslimatTipi = teslimatTipiSelect.value;
            
            if (metrekare > 0 && birimFiyat > 0 && teslimatTipi) {
                const totals = calculateJobTotals(metrekare, birimFiyat, teslimatTipi);
                document.getElementById('jobToplamTutar').textContent = formatCurrency(totals.toplamTutar);
                document.getElementById('jobKomisyonTutari').textContent = formatCurrency(totals.komisyonTutari);
            } else {
                document.getElementById('jobToplamTutar').textContent = '0 ₺';
                document.getElementById('jobKomisyonTutari').textContent = '0 ₺';
            }
        };
        
        metrekareInput.addEventListener('input', updateCalculation);
        birimFiyatInput.addEventListener('input', updateCalculation);
        teslimatTipiSelect.addEventListener('change', updateCalculation);
    }
    
    // Payment form
    const paymentForm = document.getElementById('paymentForm');
    if (paymentForm) {
        paymentForm.addEventListener('submit', handlePaymentSubmit);
    }
}

/**
 * Load subcontractor detail
 */
async function loadSubcontractorDetail() {
    const loadingState = document.getElementById('loadingState');
    const contentArea = document.getElementById('contentArea');
    
    try {
        loadingState.style.display = 'block';
        contentArea.style.display = 'none';
        
        const response = await api.getSubcontractor(subcontractorId);
        
        if (response.success) {
            subcontractorData = response.data.subcontractor;
            jobsData = response.data.jobs || [];
            paymentsData = response.data.payments || [];
            const summary = response.data.summary || {};
            
            // Update UI
            updateSubcontractorHeader(subcontractorData);
            updateStatistics(summary);
            renderJobsTable(jobsData);
            renderPaymentsTable(paymentsData);
            
            contentArea.style.display = 'block';
        } else {
            showError(response.error || 'Veri yüklenemedi');
            window.location.href = '#dashboard';
        }
    } catch (error) {
        console.error('Load detail error:', error);
        showError(error.message);
        window.location.href = '#dashboard';
    } finally {
        loadingState.style.display = 'none';
    }
}

/**
 * Update subcontractor header
 */
function updateSubcontractorHeader(subcontractor) {
    document.getElementById('subcontractorName').textContent = subcontractor.ad;
    document.getElementById('subcontractorPhone').textContent = subcontractor.telefon ? `📞 ${subcontractor.telefon}` : '';
    document.getElementById('subcontractorAddress').textContent = subcontractor.adres ? `📍 ${subcontractor.adres}` : '';
    
    const balance = parseFloat(subcontractor.balance);
    const balanceDisplay = getBalanceDisplay(balance);
    
    const balanceDiv = document.getElementById('balanceDisplay');
    balanceDiv.className = `balance-display ${balanceDisplay.color}`;
    document.getElementById('balanceAmount').textContent = balanceDisplay.amount;
    document.getElementById('balanceText').textContent = balanceDisplay.text;
}

/**
 * Update statistics cards
 */
function updateStatistics(summary) {
    document.getElementById('totalJobs').textContent = summary.jobCount || 0;
    document.getElementById('totalSquareMeters').textContent = (summary.totalSquareMeters || 0).toFixed(2);
    document.getElementById('totalRevenue').textContent = formatCurrency(summary.totalRevenue || 0);
    document.getElementById('totalCommission').textContent = formatCurrency(summary.totalCommission || 0);
}

/**
 * Render jobs table
 */
function renderJobsTable(jobs) {
    const tbody = document.getElementById('jobsTableBody');
    tbody.innerHTML = '';
    
    if (jobs.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="empty-state">Henüz iş kaydı bulunmuyor</td></tr>';
        return;
    }
    
    jobs.forEach(job => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${formatDate(job.tarih)}</td>
            <td>${parseFloat(job.metrekare).toFixed(2)}</td>
            <td>${formatCurrency(job.birim_fiyat)}</td>
            <td>${formatCurrency(job.toplam_tutar)}</td>
            <td>${getTeslimatTipiText(job.teslimat_tipi)}</td>
            <td>${formatCurrency(job.komisyon_tutari)}</td>
            <td>
                <div class="actions">
                    <button class="btn-small btn-edit" onclick="openEditJobModal(${job.id})">
                        Düzenle
                    </button>
                    <button class="btn-small btn-delete" onclick="deleteJob(${job.id})">
                        Sil
                    </button>
                </div>
            </td>
        `;
        tbody.appendChild(row);
    });
}

/**
 * Render payments table
 */
function renderPaymentsTable(payments) {
    const tbody = document.getElementById('paymentsTableBody');
    tbody.innerHTML = '';
    
    if (payments.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="empty-state">Henüz para hareketi bulunmuyor</td></tr>';
        return;
    }
    
    payments.forEach(payment => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${formatDate(payment.tarih)}</td>
            <td>${formatCurrency(payment.tutar)}</td>
            <td>${getHareketTipiText(payment.hareket_tipi)}</td>
            <td>${payment.aciklama || '-'}</td>
            <td>
                <div class="actions">
                    <button class="btn-small btn-edit" onclick="openEditPaymentModal(${payment.id})">
                        Düzenle
                    </button>
                    <button class="btn-small btn-delete" onclick="deletePayment(${payment.id})">
                        Sil
                    </button>
                </div>
            </td>
        `;
        tbody.appendChild(row);
    });
}

// ==================== Job Modal Functions ====================

function openAddJobModal() {
    currentEditJobId = null;
    document.getElementById('jobModalTitle').textContent = 'Yeni İş Ekle';
    document.getElementById('jobForm').reset();
    document.getElementById('jobId').value = '';
    document.getElementById('jobTarih').value = formatDateForInput(new Date());
    document.getElementById('jobToplamTutar').textContent = '0 ₺';
    document.getElementById('jobKomisyonTutari').textContent = '0 ₺';
    document.getElementById('jobModal').classList.add('show');
}

function openEditJobModal(id) {
    const job = jobsData.find(j => j.id === id);
    if (!job) return;
    
    currentEditJobId = id;
    document.getElementById('jobModalTitle').textContent = 'İş Düzenle';
    document.getElementById('jobId').value = id;
    document.getElementById('jobTarih').value = job.tarih;
    document.getElementById('jobMetrekare').value = job.metrekare;
    document.getElementById('jobBirimFiyat').value = job.birim_fiyat;
    document.getElementById('jobTeslimatTipi').value = job.teslimat_tipi;
    document.getElementById('jobAciklama').value = job.aciklama || '';
    
    // Update calculated fields
    const totals = calculateJobTotals(
        parseFloat(job.metrekare),
        parseFloat(job.birim_fiyat),
        job.teslimat_tipi
    );
    document.getElementById('jobToplamTutar').textContent = formatCurrency(totals.toplamTutar);
    document.getElementById('jobKomisyonTutari').textContent = formatCurrency(totals.komisyonTutari);
    
    document.getElementById('jobModal').classList.add('show');
}

function closeJobModal() {
    document.getElementById('jobModal').classList.remove('show');
    document.getElementById('jobForm').reset();
    currentEditJobId = null;
}

async function handleJobSubmit(e) {
    e.preventDefault();
    
    const formData = {
        alt_firma_id: subcontractorId,
        tarih: document.getElementById('jobTarih').value,
        metrekare: parseFloat(document.getElementById('jobMetrekare').value),
        birim_fiyat: parseFloat(document.getElementById('jobBirimFiyat').value),
        teslimat_tipi: document.getElementById('jobTeslimatTipi').value,
        aciklama: document.getElementById('jobAciklama').value.trim()
    };
    
    // Validate
    const errors = validateForm(formData, {
        tarih: { required: true, date: true, label: 'Tarih' },
        metrekare: { required: true, numeric: true, positive: true, label: 'Metrekare' },
        birim_fiyat: { required: true, numeric: true, positive: true, label: 'Birim Fiyat' },
        teslimat_tipi: { required: true, label: 'Teslimat Tipi' }
    });
    
    if (errors.length > 0) {
        showError(errors.join(', '));
        return;
    }
    
    try {
        let response;
        
        if (currentEditJobId) {
            response = await api.updateJob(currentEditJobId, formData);
        } else {
            response = await api.createJob(formData);
        }
        
        if (response.success) {
            showSuccess(response.message || 'İşlem başarılı');
            closeJobModal();
            await loadSubcontractorDetail();
        } else {
            if (response.errors && Array.isArray(response.errors)) {
                showError(response.errors.join(', '));
            } else {
                showError(response.error || 'İşlem başarısız');
            }
        }
    } catch (error) {
        console.error('Job submit error:', error);
        showError(error.message);
    }
}

async function deleteJob(id) {
    if (!confirmAction('Bu iş kaydını silmek istediğinizden emin misiniz?')) return;
    
    try {
        const response = await api.deleteJob(id);
        
        if (response.success) {
            showSuccess(response.message || 'İş kaydı silindi');
            await loadSubcontractorDetail();
        } else {
            showError(response.error || 'İşlem başarısız');
        }
    } catch (error) {
        console.error('Delete job error:', error);
        showError(error.message);
    }
}

// ==================== Payment Modal Functions ====================

function openAddPaymentModal() {
    currentEditPaymentId = null;
    document.getElementById('paymentModalTitle').textContent = 'Yeni Para Hareketi';
    document.getElementById('paymentForm').reset();
    document.getElementById('paymentId').value = '';
    document.getElementById('paymentTarih').value = formatDateForInput(new Date());
    document.getElementById('paymentModal').classList.add('show');
}

function openEditPaymentModal(id) {
    const payment = paymentsData.find(p => p.id === id);
    if (!payment) return;
    
    currentEditPaymentId = id;
    document.getElementById('paymentModalTitle').textContent = 'Para Hareketi Düzenle';
    document.getElementById('paymentId').value = id;
    document.getElementById('paymentTarih').value = payment.tarih;
    document.getElementById('paymentTutar').value = payment.tutar;
    document.getElementById('paymentHareketTipi').value = payment.hareket_tipi;
    document.getElementById('paymentAciklama').value = payment.aciklama || '';
    document.getElementById('paymentModal').classList.add('show');
}

function closePaymentModal() {
    document.getElementById('paymentModal').classList.remove('show');
    document.getElementById('paymentForm').reset();
    currentEditPaymentId = null;
}

async function handlePaymentSubmit(e) {
    e.preventDefault();
    
    const formData = {
        alt_firma_id: subcontractorId,
        tarih: document.getElementById('paymentTarih').value,
        tutar: parseFloat(document.getElementById('paymentTutar').value),
        hareket_tipi: document.getElementById('paymentHareketTipi').value,
        aciklama: document.getElementById('paymentAciklama').value.trim()
    };
    
    // Validate
    const errors = validateForm(formData, {
        tarih: { required: true, date: true, label: 'Tarih' },
        tutar: { required: true, numeric: true, positive: true, label: 'Tutar' },
        hareket_tipi: { required: true, label: 'Hareket Tipi' }
    });
    
    if (errors.length > 0) {
        showError(errors.join(', '));
        return;
    }
    
    try {
        let response;
        
        if (currentEditPaymentId) {
            response = await api.updatePayment(currentEditPaymentId, formData);
        } else {
            response = await api.createPayment(formData);
        }
        
        if (response.success) {
            showSuccess(response.message || 'İşlem başarılı');
            closePaymentModal();
            await loadSubcontractorDetail();
        } else {
            if (response.errors && Array.isArray(response.errors)) {
                showError(response.errors.join(', '));
            } else {
                showError(response.error || 'İşlem başarısız');
            }
        }
    } catch (error) {
        console.error('Payment submit error:', error);
        showError(error.message);
    }
}

async function deletePayment(id) {
    if (!confirmAction('Bu para hareketini silmek istediğinizden emin misiniz?')) return;
    
    try {
        const response = await api.deletePayment(id);
        
        if (response.success) {
            showSuccess(response.message || 'Para hareketi silindi');
            await loadSubcontractorDetail();
        } else {
            showError(response.error || 'İşlem başarısız');
        }
    } catch (error) {
        console.error('Delete payment error:', error);
        showError(error.message);
    }
}

// Make functions globally available
window.openEditJobModal = openEditJobModal;
window.closeJobModal = closeJobModal;
window.deleteJob = deleteJob;
window.openEditPaymentModal = openEditPaymentModal;
window.closePaymentModal = closePaymentModal;
window.deletePayment = deletePayment;
