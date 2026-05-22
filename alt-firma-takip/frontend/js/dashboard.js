/**
 * Dashboard Page Logic
 * Handles subcontractor list, summary cards, and CRUD operations
 */

let subcontractorsData = [];
let currentEditId = null;

// Called by app.js after dashboard HTML is injected
async function initDashboard() {
    setupEventListeners();
    await loadDashboard();
}

/**
 * Setup event listeners
 */
function setupEventListeners() {
    // Logout button
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', () => {
            if (confirmAction('Çıkış yapmak istediğinizden emin misiniz?')) {
                Auth.logout();
            }
        });
    }
    
    // Add subcontractor button
    const addBtn = document.getElementById('addSubcontractorBtn');
    if (addBtn) {
        addBtn.addEventListener('click', () => {
            openAddModal();
        });
    }
    
    // Subcontractor form
    const form = document.getElementById('subcontractorForm');
    if (form) {
        form.addEventListener('submit', handleSubcontractorSubmit);
    }
}

/**
 * Load dashboard data
 */
async function loadDashboard() {
    const loadingState = document.getElementById('loadingState');
    const emptyState = document.getElementById('emptyState');
    const table = document.getElementById('subcontractorsTable');
    
    try {
        loadingState.style.display = 'block';
        emptyState.style.display = 'none';
        table.style.display = 'none';
        
        const response = await api.getSubcontractors();
        
        if (response.success) {
            subcontractorsData = response.data.subcontractors || [];
            const summary = response.data.summary || { totalDebt: 0, totalCredit: 0 };
            
            // Update summary cards
            updateSummaryCards(summary, subcontractorsData);
            
            // Render table
            if (subcontractorsData.length > 0) {
                renderSubcontractorsTable(subcontractorsData);
                table.style.display = 'table';
            } else {
                emptyState.style.display = 'block';
            }
        } else {
            showError(response.error || 'Veriler yüklenemedi');
        }
    } catch (error) {
        console.error('Load dashboard error:', error);
        showError(error.message);
    } finally {
        loadingState.style.display = 'none';
    }
}

/**
 * Update summary cards
 */
function updateSummaryCards(summary, subcontractors) {
    // Total debt (positive balances)
    const totalDebt = subcontractors
        .filter(s => s.balance > 0)
        .reduce((sum, s) => sum + parseFloat(s.balance), 0);
    
    // Total credit (negative balances)
    const totalCredit = Math.abs(subcontractors
        .filter(s => s.balance < 0)
        .reduce((sum, s) => sum + parseFloat(s.balance), 0));
    
    // Active count
    const activeCount = subcontractors.filter(s => s.durum === 'aktif').length;
    
    document.getElementById('totalDebt').textContent = formatCurrency(totalDebt);
    document.getElementById('totalCredit').textContent = formatCurrency(totalCredit);
    document.getElementById('activeCount').textContent = subcontractors.length;
}

/**
 * Render subcontractors table
 */
function renderSubcontractorsTable(subcontractors) {
    const tbody = document.getElementById('subcontractorsTableBody');
    tbody.innerHTML = '';
    
    subcontractors.forEach(subcontractor => {
        const row = document.createElement('tr');
        
        const balance = parseFloat(subcontractor.balance);
        const balanceDisplay = getBalanceDisplay(balance);
        
        row.innerHTML = `
            <td><strong>${subcontractor.ad}</strong></td>
            <td>${subcontractor.telefon || '-'}</td>
            <td>${getDurumBadge(subcontractor.durum)}</td>
            <td class="balance-${balanceDisplay.color}">${balanceDisplay.amount}</td>
            <td>
                <div class="actions">
                    <button class="btn-small btn-detail" onclick="viewDetail(${subcontractor.id})">
                        Detay
                    </button>
                    <button class="btn-small btn-edit" onclick="openEditModal(${subcontractor.id})">
                        Düzenle
                    </button>
                    <button class="btn-small btn-toggle" onclick="toggleStatus(${subcontractor.id})">
                        ${subcontractor.durum === 'aktif' ? 'Pasifleştir' : 'Aktifleştir'}
                    </button>
                </div>
            </td>
        `;
        
        tbody.appendChild(row);
    });
}

/**
 * View subcontractor detail
 */
function viewDetail(id) {
    window.location.href = `#subcontractor/${id}`;
}

/**
 * Open add modal
 */
function openAddModal() {
    currentEditId = null;
    document.getElementById('modalTitle').textContent = 'Yeni Alt Firma Ekle';
    document.getElementById('subcontractorForm').reset();
    document.getElementById('subcontractorId').value = '';
    document.getElementById('subcontractorModal').classList.add('show');
}

/**
 * Open edit modal
 */
function openEditModal(id) {
    const subcontractor = subcontractorsData.find(s => s.id === id);
    if (!subcontractor) return;
    
    currentEditId = id;
    document.getElementById('modalTitle').textContent = 'Alt Firma Düzenle';
    document.getElementById('subcontractorId').value = id;
    document.getElementById('ad').value = subcontractor.ad;
    document.getElementById('telefon').value = subcontractor.telefon || '';
    document.getElementById('adres').value = subcontractor.adres || '';
    document.getElementById('notlar').value = subcontractor.notlar || '';
    document.getElementById('subcontractorModal').classList.add('show');
}

/**
 * Close modal
 */
function closeModal() {
    document.getElementById('subcontractorModal').classList.remove('show');
    document.getElementById('subcontractorForm').reset();
    currentEditId = null;
}

/**
 * Handle subcontractor form submit
 */
async function handleSubcontractorSubmit(e) {
    e.preventDefault();
    
    const formData = {
        ad: document.getElementById('ad').value.trim(),
        telefon: document.getElementById('telefon').value.trim(),
        adres: document.getElementById('adres').value.trim(),
        notlar: document.getElementById('notlar').value.trim()
    };
    
    // Validate
    if (!formData.ad) {
        showError('Alt firma adı gereklidir');
        return;
    }
    
    try {
        let response;
        
        if (currentEditId) {
            // Update
            response = await api.updateSubcontractor(currentEditId, formData);
        } else {
            // Create
            response = await api.createSubcontractor(formData);
        }
        
        if (response.success) {
            showSuccess(response.message || 'İşlem başarılı');
            closeModal();
            await loadDashboard();
        } else {
            if (response.errors && Array.isArray(response.errors)) {
                showError(response.errors.join(', '));
            } else {
                showError(response.error || 'İşlem başarısız');
            }
        }
    } catch (error) {
        console.error('Submit error:', error);
        showError(error.message);
    }
}

/**
 * Toggle subcontractor status
 */
async function toggleStatus(id) {
    const subcontractor = subcontractorsData.find(s => s.id === id);
    if (!subcontractor) return;
    
    const newStatus = subcontractor.durum === 'aktif' ? 'pasif' : 'aktif';
    const confirmMsg = `${subcontractor.ad} firmasını ${newStatus} yapmak istediğinizden emin misiniz?`;
    
    if (!confirmAction(confirmMsg)) return;
    
    try {
        const response = await api.toggleSubcontractorStatus(id);
        
        if (response.success) {
            showSuccess(response.message || 'Durum güncellendi');
            await loadDashboard();
        } else {
            showError(response.error || 'İşlem başarısız');
        }
    } catch (error) {
        console.error('Toggle status error:', error);
        showError(error.message);
    }
}

// Make functions globally available
window.viewDetail = viewDetail;
window.openEditModal = openEditModal;
window.closeModal = closeModal;
window.toggleStatus = toggleStatus;
