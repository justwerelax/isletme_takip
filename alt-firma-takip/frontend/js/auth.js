/**
 * Authentication Module
 * Handles user authentication, token management, and session
 */

class Auth {
    /**
     * Login with username and password
     * @param {string} username
     * @param {string} password
     * @returns {Promise<object>}
     */
    static async login(username, password) {
        try {
            const response = await api.login(username, password);
            
            if (response.success) {
                // Store user data
                const user = response.data ? response.data.user : response.user;
                localStorage.setItem('user', JSON.stringify(user));
            }
            
            return response;
        } catch (error) {
            console.error('Login error:', error);
            return {
                success: false,
                error: error.message
            };
        }
    }
    
    /**
     * Logout and clear session
     */
    static logout() {
        api.clearToken();
        localStorage.removeItem('user');
        window.location.href = '#login';
    }
    
    /**
     * Check if user is authenticated
     * @returns {boolean}
     */
    static isAuthenticated() {
        return !!localStorage.getItem('token');
    }
    
    /**
     * Get current user data
     * @returns {object|null}
     */
    static getUser() {
        const userJson = localStorage.getItem('user');
        return userJson ? JSON.parse(userJson) : null;
    }
    
    /**
     * Verify token validity
     * @returns {Promise<boolean>}
     */
    static async verifyToken() {
        if (!this.isAuthenticated()) {
            return false;
        }
        
        try {
            const response = await api.verifyToken();
            return response.success;
        } catch (error) {
            console.error('Token verification error:', error);
            return false;
        }
    }
    
    /**
     * Require authentication - redirect to login if not authenticated
     */
    static async requireAuth() {
        if (!this.isAuthenticated()) {
            window.location.href = '#login';
            return false;
        }
        
        // Verify token is still valid
        const isValid = await this.verifyToken();
        if (!isValid) {
            this.logout();
            return false;
        }
        
        return true;
    }
}
