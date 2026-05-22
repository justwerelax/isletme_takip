// İşletme Takip Sistemi - App JS

document.addEventListener('DOMContentLoaded', function() {
    // Sidebar toggle (mobile)
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');

    if (menuToggle) {
        menuToggle.addEventListener('click', () => {
            sidebar.classList.toggle('open');
            overlay.classList.toggle('active');
        });
    }
    if (overlay) {
        overlay.addEventListener('click', () => {
            sidebar.classList.remove('open');
            overlay.classList.remove('active');
        });
    }

    // Auto-hide alerts after 5 seconds
    document.querySelectorAll('.alert').forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-8px)';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });

    // Revenue Chart
    initRevenueChart();
});

function initRevenueChart() {
    const canvas = document.getElementById('revenueChart');
    if (!canvas || !window.chartData) return;

    const ctx = canvas.getContext('2d');
    const gradient = ctx.createLinearGradient(0, 0, 0, 200);
    gradient.addColorStop(0, 'rgba(129, 140, 248, 0.3)');
    gradient.addColorStop(1, 'rgba(129, 140, 248, 0.01)');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: window.chartData.labels,
            datasets: [
                {
                    label: 'Gelir',
                    data: window.chartData.revenues,
                    borderColor: '#818cf8',
                    backgroundColor: gradient,
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 3,
                    pointBackgroundColor: '#818cf8',
                    pointBorderWidth: 0,
                    pointHoverRadius: 6,
                },
                {
                    label: 'Gider',
                    data: window.chartData.expenses,
                    borderColor: '#ef4444',
                    backgroundColor: 'rgba(239, 68, 68, 0.08)',
                    borderWidth: 2.5,
                    fill: false,
                    tension: 0.4,
                    pointRadius: 3,
                    pointBackgroundColor: '#ef4444',
                    pointBorderWidth: 0,
                    pointHoverRadius: 6,
                },
                {
                    label: 'Dış Gelir',
                    data: window.chartData.externals,
                    borderColor: '#34d399',
                    backgroundColor: 'rgba(52, 211, 153, 0.05)',
                    borderWidth: 2,
                    fill: false,
                    tension: 0.4,
                    pointRadius: 3,
                    pointBackgroundColor: '#34d399',
                    pointBorderWidth: 0,
                    borderDash: [5, 5],
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { intersect: false, mode: 'index' },
            plugins: {
                legend: {
                    labels: { color: '#94a3b8', font: { family: 'Inter', size: 11 }, usePointStyle: true, pointStyle: 'circle' }
                },
                tooltip: {
                    backgroundColor: '#1a1e2e',
                    titleColor: '#e2e8f0',
                    bodyColor: '#94a3b8',
                    borderColor: 'rgba(255,255,255,0.06)',
                    borderWidth: 1,
                    cornerRadius: 8,
                    padding: 12,
                    titleFont: { family: 'Inter', weight: '600' },
                    bodyFont: { family: 'Inter' },
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ₺' + context.parsed.y.toLocaleString('tr-TR', {minimumFractionDigits: 2});
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: { color: 'rgba(255,255,255,0.03)' },
                    ticks: { color: '#64748b', font: { family: 'Inter', size: 11 } }
                },
                y: {
                    grid: { color: 'rgba(255,255,255,0.03)' },
                    ticks: {
                        color: '#64748b',
                        font: { family: 'Inter', size: 11 },
                        callback: function(value) { return '₺' + (value/1000).toFixed(0) + 'K'; }
                    }
                }
            }
        }
    });
}

// --- Modal System ---
window.openModal = function(id) {
    const modal = document.getElementById(id);
    if (modal) {
        modal.classList.add('active');
    }
};

window.closeModal = function(id) {
    const modal = document.getElementById(id);
    if (modal) modal.classList.remove('active');
};

window.confirmDelete = function(formId, message) {
    const modalHtml = `
        <div class="modal-overlay active" id="confirmModal">
            <div class="modal-box" style="max-width: 400px; transform: translateY(0);">
                <div class="modal-header">
                    <h3 style="color: var(--danger)"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg> Silme Onayı</h3>
                    <button type="button" class="modal-close" onclick="document.getElementById('confirmModal').remove()"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg></button>
                </div>
                <div class="modal-body">
                    <p style="font-size: 14px; color: var(--text-secondary); margin: 0;">${message || 'Bu kaydı silmek istediğinize emin misiniz? Bu işlem geri alınamaz.'}</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-ghost" onclick="document.getElementById('confirmModal').remove()">İptal</button>
                    <button type="button" class="btn btn-danger" onclick="document.getElementById('${formId}').submit()">Evet, Sil</button>
                </div>
            </div>
        </div>
    `;
    document.body.insertAdjacentHTML('beforeend', modalHtml);
};

// Utility: Format number as Turkish currency
function formatMoney(amount) {
    return '₺' + Number(amount).toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

// Version Changelog Toggle
window.toggleChangelog = function() {
    const dd = document.getElementById('changelogDropdown');
    if (!dd) return;
    dd.classList.toggle('open');
};

// Close changelog when clicking outside
document.addEventListener('click', function(e) {
    const wrap = document.getElementById('versionWrap');
    const dd   = document.getElementById('changelogDropdown');
    if (!wrap || !dd) return;
    if (!wrap.contains(e.target)) {
        dd.classList.remove('open');
    }
});
