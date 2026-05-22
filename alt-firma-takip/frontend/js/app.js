/**
 * Main App Logic
 * Handles SPA routing and page navigation
 */

// Current page
let currentPage = null;

// Initialize app
document.addEventListener('DOMContentLoaded', () => {
    // Listen for hash changes
    window.addEventListener('hashchange', handleRouteChange);
    
    // Initial route
    handleRouteChange();
});

/**
 * Handle route change
 */
async function handleRouteChange() {
    const hash = window.location.hash || '#login';
    const route = parseRoute(hash);
    
    await loadPage(route);
}

/**
 * Parse route from hash
 */
function parseRoute(hash) {
    // Remove # from hash
    const path = hash.substring(1);
    
    // Parse route and params
    const parts = path.split('/');
    const page = parts[0] || 'login';
    const params = parts.slice(1);
    
    return { page, params };
}

/**
 * Load page based on route
 */
async function loadPage(route) {
    const { page, params } = route;
    
    // Show loading
    showLoading();
    
    try {
        // Check authentication for protected pages
        if (page !== 'login') {
            const isAuth = Auth.isAuthenticated();
            if (!isAuth) {
                window.location.href = '#login';
                return;
            }
        }
        
        // Load appropriate page
        switch (page) {
            case 'login':
                await loadLoginPage();
                break;
                
            case 'dashboard':
                await loadDashboardPage();
                break;
                
            case 'subcontractor':
                if (params.length > 0) {
                    await loadSubcontractorPage(params[0]);
                } else {
                    window.location.href = '#dashboard';
                }
                break;
                
            default:
                // Unknown route - redirect to dashboard or login
                if (Auth.isAuthenticated()) {
                    window.location.href = '#dashboard';
                } else {
                    window.location.href = '#login';
                }
                break;
        }
        
        currentPage = page;
    } catch (error) {
        console.error('Load page error:', error);
        showError('Sayfa yüklenirken hata oluştu');
    } finally {
        hideLoading();
    }
}

/**
 * Load login page
 */
async function loadLoginPage() {
    if (Auth.isAuthenticated()) {
        window.location.hash = '#dashboard';
        return;
    }
    
    const loginPage = document.getElementById('loginPage');
    const dashboardPage = document.getElementById('dashboardPage');
    const subcontractorPage = document.getElementById('subcontractorPage');
    
    dashboardPage.classList.remove('active');
    subcontractorPage.classList.remove('active');
    
    const response = await fetch('pages/login.html?v=' + Date.now());
    const html = await response.text();
    loginPage.innerHTML = html;
    loginPage.classList.add('active');

    // innerHTML ile inject edilen script tagları çalışmaz, manuel çalıştır
    executeScripts(loginPage);
}

/**
 * Load dashboard page
 */
async function loadDashboardPage() {
    const loginPage = document.getElementById('loginPage');
    const dashboardPage = document.getElementById('dashboardPage');
    const subcontractorPage = document.getElementById('subcontractorPage');
    
    loginPage.classList.remove('active');
    subcontractorPage.classList.remove('active');
    
    const response = await fetch('pages/dashboard.html?v=' + Date.now());
    const html = await response.text();
    dashboardPage.innerHTML = html;
    dashboardPage.classList.add('active');

    await new Promise(r => setTimeout(r, 0));
    if (typeof initDashboard === 'function') {
        await initDashboard();
    }
}
async function loadSubcontractorPage(id) {
    const loginPage = document.getElementById('loginPage');
    const dashboardPage = document.getElementById('dashboardPage');
    const subcontractorPage = document.getElementById('subcontractorPage');
    
    loginPage.classList.remove('active');
    dashboardPage.classList.remove('active');
    
    const response = await fetch('pages/subcontractor.html?v=' + Date.now());
    const html = await response.text();
    subcontractorPage.innerHTML = html;
    subcontractorPage.classList.add('active');

    await new Promise(r => setTimeout(r, 0));
    if (typeof initSubcontractor === 'function') {
        await initSubcontractor(parseInt(id));
    }
}

/**
 * Execute scripts in loaded HTML
 */
function executeScripts(container) {
    const scripts = container.querySelectorAll('script');
    scripts.forEach(script => {
        const newScript = document.createElement('script');
        
        if (script.src) {
            newScript.src = script.src;
        } else {
            newScript.textContent = script.textContent;
        }
        
        // Replace old script with new one to execute it
        script.parentNode.replaceChild(newScript, script);
    });
}

/**
 * Navigate to page
 */
function navigateTo(page, params = []) {
    const hash = params.length > 0 ? `#${page}/${params.join('/')}` : `#${page}`;
    window.location.href = hash;
}

// Make navigateTo globally available
window.navigateTo = navigateTo;

// Global navigation helper
window.goTo = function(hash) {
    if (window.location.hash === hash) {
        handleRouteChange();
    } else {
        window.location.hash = hash;
    }
};
