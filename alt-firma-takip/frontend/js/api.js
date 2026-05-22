/**
 * API Client for Alt Firma Takip Sistemi
 * Handles all HTTP requests to the backend REST API
 */

class API {
    constructor() {
        // API base URL - update this for production
        this.baseURL = '/isletme-takip-sistemi/alt-firma-takip/backend/api';
        this.token = localStorage.getItem('token');
    }
    
    /**
     * Generic request method
     * @param {string} endpoint - API endpoint (e.g., '/auth/login')
     * @param {object} options - Fetch options
     * @returns {Promise<object>} - Response data
     */
    async request(endpoint, options = {}) {
        const headers = {
            'Content-Type': 'application/json',
            ...options.headers
        };
        
        // Add Authorization header if token exists
        if (this.token) {
            headers['Authorization'] = `Bearer ${this.token}`;
        }
        
        const config = {
            ...options,
            headers
        };
        
        try {
            const response = await fetch(`${this.baseURL}${endpoint}`, config);
            const data = await response.json();
            
            // Handle 401 Unauthorized - token expired or invalid
            if (response.status === 401) {
                this.clearToken();
                if (window.location.hash !== '#login') {
                    window.location.href = '#login';
                }
            }
            
            return data;
        } catch (error) {
            console.error('API request error:', error);
            throw new Error('Bağlantı hatası. Lütfen internet bağlantınızı kontrol edin.');
        }
    }
    
    /**
     * Set authentication token
     * @param {string} token - JWT token
     */
    setToken(token) {
        this.token = token;
        localStorage.setItem('token', token);
    }
    
    /**
     * Clear authentication token
     */
    clearToken() {
        this.token = null;
        localStorage.removeItem('token');
    }
    
    // ==================== Authentication Methods ====================
    
    /**
     * Login with username and password
     * @param {string} username
     * @param {string} password
     * @returns {Promise<object>}
     */
    async login(username, password) {
        const response = await this.request('/auth/login', {
            method: 'POST',
            body: JSON.stringify({ username, password })
        });
        
        if (response.success && response.data && response.data.token) {
            this.setToken(response.data.token);
        }
        
        return response;
    }
    
    /**
     * Verify JWT token
     * @returns {Promise<object>}
     */
    async verifyToken() {
        return await this.request('/auth/verify', {
            method: 'POST'
        });
    }
    
    // ==================== Subcontractor Methods ====================
    
    /**
     * Get all subcontractors with balances
     * @returns {Promise<object>}
     */
    async getSubcontractors() {
        return await this.request('/subcontractors', {
            method: 'GET'
        });
    }
    
    /**
     * Get single subcontractor with jobs and payments
     * @param {number} id - Subcontractor ID
     * @returns {Promise<object>}
     */
    async getSubcontractor(id) {
        return await this.request(`/subcontractors/${id}`, {
            method: 'GET'
        });
    }
    
    /**
     * Create new subcontractor
     * @param {object} data - Subcontractor data
     * @returns {Promise<object>}
     */
    async createSubcontractor(data) {
        return await this.request('/subcontractors', {
            method: 'POST',
            body: JSON.stringify(data)
        });
    }
    
    /**
     * Update subcontractor
     * @param {number} id - Subcontractor ID
     * @param {object} data - Updated data
     * @returns {Promise<object>}
     */
    async updateSubcontractor(id, data) {
        return await this.request(`/subcontractors/${id}`, {
            method: 'PUT',
            body: JSON.stringify(data)
        });
    }
    
    /**
     * Toggle subcontractor status (aktif/pasif)
     * @param {number} id - Subcontractor ID
     * @returns {Promise<object>}
     */
    async toggleSubcontractorStatus(id) {
        return await this.request(`/subcontractors/${id}/status`, {
            method: 'PATCH'
        });
    }

    async deleteSubcontractor(id) {
        return await this.request(`/subcontractors/${id}`, { method: 'DELETE' });
    }
    
    // ==================== Job Methods ====================
    
    /**
     * Create new job
     * @param {object} data - Job data
     * @returns {Promise<object>}
     */
    async createJob(data) {
        return await this.request('/jobs', {
            method: 'POST',
            body: JSON.stringify(data)
        });
    }
    
    /**
     * Update job
     * @param {number} id - Job ID
     * @param {object} data - Updated data
     * @returns {Promise<object>}
     */
    async updateJob(id, data) {
        return await this.request(`/jobs/${id}`, {
            method: 'PUT',
            body: JSON.stringify(data)
        });
    }
    
    /**
     * Delete job
     * @param {number} id - Job ID
     * @returns {Promise<object>}
     */
    async deleteJob(id) {
        return await this.request(`/jobs/${id}`, {
            method: 'DELETE'
        });
    }

    async toggleJobTeslim(id) {
        return await this.request(`/jobs/${id}`, { method: 'PATCH' });
    }
    
    // ==================== Payment Methods ====================
    
    /**
     * Create new payment
     * @param {object} data - Payment data
     * @returns {Promise<object>}
     */
    async createPayment(data) {
        return await this.request('/payments', {
            method: 'POST',
            body: JSON.stringify(data)
        });
    }
    
    /**
     * Update payment
     * @param {number} id - Payment ID
     * @param {object} data - Updated data
     * @returns {Promise<object>}
     */
    async updatePayment(id, data) {
        return await this.request(`/payments/${id}`, {
            method: 'PUT',
            body: JSON.stringify(data)
        });
    }
    
    /**
     * Delete payment
     * @param {number} id - Payment ID
     * @returns {Promise<object>}
     */
    async deletePayment(id) {
        return await this.request(`/payments/${id}`, {
            method: 'DELETE'
        });
    }
    
    // ==================== Report Methods ====================
    
    /**
     * Get summary report for date range
     * @param {string} startDate - Start date (Y-m-d format)
     * @param {string} endDate - End date (Y-m-d format)
     * @returns {Promise<object>}
     */
    async getSummaryReport(startDate = null, endDate = null) {
        let endpoint = '/reports/summary';
        
        if (startDate && endDate) {
            endpoint += `?start_date=${startDate}&end_date=${endDate}`;
        }
        
        return await this.request(endpoint, {
            method: 'GET'
        });
    }

    // ==================== Export Methods ====================

    /**
     * Tüm veriyi JSON olarak indir (tam yedek)
     */
    exportJSON() {
        const url = `${this.baseURL}/export?format=json`;
        this._downloadWithAuth(url, `alt-firma-yedek-${this._today()}.json`);
    }

    /**
     * Tüm veriyi CSV olarak indir
     */
    exportCSV() {
        const url = `${this.baseURL}/export?format=csv`;
        this._downloadWithAuth(url, `alt-firma-yedek-${this._today()}.csv`);
    }

    /**
     * Tek bir alt firmanın verisini JSON olarak indir
     * @param {number} subId
     * @param {string} firmaAd
     */
    exportSubJSON(subId, firmaAd) {
        const safeName = firmaAd.replace(/[^a-zA-Z0-9ğüşıöçĞÜŞİÖÇ\s]/g, '').trim().replace(/\s+/g, '-');
        const url = `${this.baseURL}/export?format=json&sub_id=${subId}`;
        this._downloadWithAuth(url, `${safeName}-${this._today()}.json`);
    }

    /**
     * Tek bir alt firmanın verisini CSV olarak indir
     * @param {number} subId
     * @param {string} firmaAd
     */
    exportSubCSV(subId, firmaAd) {
        const safeName = firmaAd.replace(/[^a-zA-Z0-9ğüşıöçĞÜŞİÖÇ\s]/g, '').trim().replace(/\s+/g, '-');
        const url = `${this.baseURL}/export?format=csv&sub_id=${subId}`;
        this._downloadWithAuth(url, `${safeName}-${this._today()}.csv`);
    }

    /**
     * Token ile kimlik doğrulamalı dosya indirme
     * @private
     */
    _downloadWithAuth(url, filename) {
        // fetch ile al, blob olarak indir (Authorization header gerektiği için)
        fetch(url, {
            headers: { 'Authorization': `Bearer ${this.token}` }
        })
        .then(res => {
            if (!res.ok) throw new Error('Export başarısız');
            return res.blob();
        })
        .then(blob => {
            const a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(a.href);
        })
        .catch(err => {
            console.error('Export hatası:', err);
            alert('Dışa aktarma sırasında bir hata oluştu.');
        });
    }

    // ==================== Onay Methods ====================

    async onayGonder(data) {
        return await this.request('/onay', { method: 'POST', body: JSON.stringify(data) });
    }

    async onayListele(durum = 'bekliyor') {
        return await this.request(`/onay?durum=${durum}`, { method: 'GET' });
    }

    async onayOnayla(id) {
        return await this.request(`/onay/${id}/onayla`, { method: 'PUT' });
    }

    async onayReddet(id) {
        return await this.request(`/onay/${id}/reddet`, { method: 'PUT' });
    }
        return new Date().toISOString().split('T')[0];
    }
}

// Create global API instance
const api = new API();
