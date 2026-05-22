/**
 * Utility Functions
 * Helper functions for formatting, validation, and UI operations
 */

/**
 * Format number as Turkish currency
 * @param {number} amount
 * @returns {string}
 */
function formatCurrency(amount) {
    if (amount === null || amount === undefined) {
        return '0 ₺';
    }
    
    const formatted = parseFloat(amount).toLocaleString('tr-TR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
    
    return `${formatted} ₺`;
}

/**
 * Format date string to Turkish format
 * @param {string} dateString - Date in Y-m-d format
 * @returns {string}
 */
function formatDate(dateString) {
    if (!dateString) return '';
    
    const date = new Date(dateString);
    return date.toLocaleDateString('tr-TR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

/**
 * Format date for input field (Y-m-d)
 * @param {Date|string} date
 * @returns {string}
 */
function formatDateForInput(date) {
    if (!date) {
        date = new Date();
    }
    
    if (typeof date === 'string') {
        date = new Date(date);
    }
    
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    
    return `${year}-${month}-${day}`;
}

/**
 * Get balance display info (text and color)
 * @param {number} balance
 * @returns {object}
 */
function getBalanceDisplay(balance) {
    if (balance > 0) {
        return {
            text: `Ana firma alt firmaya ${formatCurrency(balance)} borçlu`,
            color: 'positive',
            amount: formatCurrency(balance)
        };
    } else if (balance < 0) {
        return {
            text: `Alt firma ana firmaya ${formatCurrency(Math.abs(balance))} borçlu`,
            color: 'negative',
            amount: formatCurrency(Math.abs(balance))
        };
    } else {
        return {
            text: 'Hesaplar dengede',
            color: 'zero',
            amount: formatCurrency(0)
        };
    }
}

/**
 * Show error message
 * @param {string} message
 */
function showError(message) {
    if (window.showToast) {
        window.showToast(message, 'error');
    } else {
        alert(message);
    }
}

/**
 * Show success message
 * @param {string} message
 */
function showSuccess(message) {
    if (window.showToast) {
        window.showToast(message, 'success');
    } else {
        alert(message);
    }
}

/**
 * Show loading state
 */
function showLoading() {
    const loadingScreen = document.getElementById('loadingScreen');
    if (loadingScreen) {
        loadingScreen.style.display = 'flex';
    }
}

/**
 * Hide loading state
 */
function hideLoading() {
    const loadingScreen = document.getElementById('loadingScreen');
    if (loadingScreen) {
        loadingScreen.style.display = 'none';
    }
}

/**
 * Validate required field
 * @param {any} value
 * @param {string} fieldName
 * @returns {string|null} - Error message or null
 */
function validateRequired(value, fieldName) {
    if (!value || (typeof value === 'string' && value.trim() === '')) {
        return `${fieldName} alanı gereklidir`;
    }
    return null;
}

/**
 * Validate numeric field
 * @param {any} value
 * @param {string} fieldName
 * @returns {string|null}
 */
function validateNumeric(value, fieldName) {
    if (isNaN(value) || value === '') {
        return `${fieldName} sayısal bir değer olmalıdır`;
    }
    return null;
}

/**
 * Validate positive number
 * @param {any} value
 * @param {string} fieldName
 * @returns {string|null}
 */
function validatePositive(value, fieldName) {
    if (parseFloat(value) <= 0) {
        return `${fieldName} pozitif bir sayı olmalıdır`;
    }
    return null;
}

/**
 * Validate date field
 * @param {string} value
 * @param {string} fieldName
 * @returns {string|null}
 */
function validateDate(value, fieldName) {
    if (!value) {
        return `${fieldName} alanı gereklidir`;
    }
    
    const date = new Date(value);
    if (isNaN(date.getTime())) {
        return `${fieldName} geçerli bir tarih olmalıdır`;
    }
    
    return null;
}

/**
 * Validate form and return errors
 * @param {object} data - Form data
 * @param {object} rules - Validation rules
 * @returns {array} - Array of error messages
 */
function validateForm(data, rules) {
    const errors = [];
    
    for (const [field, fieldRules] of Object.entries(rules)) {
        const value = data[field];
        
        if (fieldRules.required) {
            const error = validateRequired(value, fieldRules.label || field);
            if (error) {
                errors.push(error);
                continue;
            }
        }
        
        if (fieldRules.numeric && value) {
            const error = validateNumeric(value, fieldRules.label || field);
            if (error) {
                errors.push(error);
                continue;
            }
        }
        
        if (fieldRules.positive && value) {
            const error = validatePositive(value, fieldRules.label || field);
            if (error) {
                errors.push(error);
                continue;
            }
        }
        
        if (fieldRules.date && value) {
            const error = validateDate(value, fieldRules.label || field);
            if (error) {
                errors.push(error);
            }
        }
    }
    
    return errors;
}

/**
 * Calculate job totals
 * @param {number} metrekare
 * @param {number} birimFiyat
 * @param {string} teslimatTipi
 * @returns {object}
 */
function calculateJobTotals(metrekare, birimFiyat, teslimatTipi) {
    const toplamTutar = metrekare * birimFiyat;
    const komisyonOrani = teslimatTipi === 'alt_firma_teslim' ? 0.40 : 0.30;
    const komisyonTutari = toplamTutar * komisyonOrani;
    
    return {
        toplamTutar,
        komisyonOrani,
        komisyonTutari
    };
}

/**
 * Confirm action with user
 * @param {string} message
 * @returns {boolean}
 */
function confirmAction(message) {
    return confirm(message);
}

/**
 * Debounce function
 * @param {function} func
 * @param {number} wait
 * @returns {function}
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Check if online
 * @returns {boolean}
 */
function isOnline() {
    return navigator.onLine;
}

/**
 * Get teslimat tipi display text
 * @param {string} teslimatTipi
 * @returns {string}
 */
function getTeslimatTipiText(teslimatTipi) {
    return teslimatTipi === 'alt_firma_teslim' ? 'Alt Firma Teslim' : 'Ana Firma Teslim';
}

/**
 * Get hareket tipi display text
 * @param {string} hareketTipi
 * @returns {string}
 */
function getHareketTipiText(hareketTipi) {
    return hareketTipi === 'odeme' ? 'Ödeme' : 'Tahsilat';
}

/**
 * Get durum badge HTML
 * @param {string} durum
 * @returns {string}
 */
function getDurumBadge(durum) {
    const badgeClass = durum === 'aktif' ? 'badge-active' : 'badge-inactive';
    const text = durum === 'aktif' ? 'Aktif' : 'Pasif';
    return `<span class="badge ${badgeClass}">${text}</span>`;
}
