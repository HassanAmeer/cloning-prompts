/**
 * Laya AI — Production Configuration for Hostinger hPanel & Web Deployments
 * 
 * Centralized settings for API connectivity, branding, and proxy configuration.
 */
(function() {
    // Derive script directory base path for reliable proxy resolution across all folder structures
    var getScriptBase = function() {
        if (typeof document !== 'undefined' && document.currentScript && document.currentScript.src) {
            try {
                var u = new URL(document.currentScript.src);
                return u.pathname.substring(0, u.pathname.lastIndexOf('/') + 1);
            } catch(e) {}
        }
        if (typeof document !== 'undefined') {
            var scripts = document.getElementsByTagName('script');
            for (var i = 0; i < scripts.length; i++) {
                var src = scripts[i].src || '';
                if (src.indexOf('config.js') !== -1) {
                    try {
                        var u2 = new URL(src, window.location.href);
                        return u2.pathname.substring(0, u2.pathname.lastIndexOf('/') + 1);
                    } catch(e) {}
                }
            }
        }
        return '';
    };

    var baseDir = getScriptBase();
    var proxyPath = (baseDir ? baseDir : '') + 'api_proxy.php';

    window.LAYA_CONFIG = {
        // Direct VPS Backend API Base URL (FastAPI)
        API_BASE_URL: "http://187.52.117.2:8000",
        
        // Brand Defaults
        BRAND: "jev",
        BRAND_CAP: "Jev",
        BRAND_UPPER: "JEV",
        
        // Base Web Path (clean root routes everywhere)
        BASE_PATH: "",
        
        // Hostinger Proxy Settings:
        // Automatically enabled on all external live domains/subdomains (e.g. cPanel, hPanel, Apache)
        // Never enabled on VPS port 8000 or local environments.
        USE_PROXY: (window.location.port !== '8000') && (
            (window.location.protocol === 'https:' && !window.location.host.includes('187.52.117.2')) ||
            (!window.location.host.includes('187.52.117.2') && !window.location.host.includes('localhost') && !window.location.host.includes('127.0.0.1'))
        ),
        PROXY_ENDPOINT: proxyPath
    };

    /**
     * Resolves the full URL for any API endpoint safely.
     * Strips legacy /laya or /jev prefixes to keep all routes completely clean.
     * @param {string} endpoint - e.g. '/query', '/contributors', '/users', '/health'
     * @returns {string}
     */
     window.getApiUrl = function(endpoint) {
        if (!endpoint) return '';
        endpoint = String(endpoint).trim();

        // If already full HTTP/HTTPS URL, return as-is
        if (endpoint.startsWith('http://') || endpoint.startsWith('https://')) {
            return endpoint;
        }

        // If already points to api_proxy.php, clean and return as-is
        if (endpoint.indexOf('api_proxy.php') !== -1) {
            return endpoint;
        }

        // Strip legacy /laya/ or /jev/ prefix
        if (endpoint.startsWith('/laya/')) {
            endpoint = endpoint.substring(5);
        } else if (endpoint.startsWith('/jev/')) {
            endpoint = endpoint.substring(4);
        } else if (endpoint.startsWith('laya/')) {
            endpoint = endpoint.substring(4);
        } else if (endpoint.startsWith('jev/')) {
            endpoint = endpoint.substring(3);
        }

        // Ensure leading slash
        if (!endpoint.startsWith('/')) {
            endpoint = '/' + endpoint;
        }

        if (window.LAYA_CONFIG.USE_PROXY) {
            var parts = endpoint.split('?');
            var path = parts[0];
            var query = parts.slice(1).join('?');
            var url = window.LAYA_CONFIG.PROXY_ENDPOINT + '?endpoint=' + encodeURIComponent(path);
            if (query) {
                url += '&' + query;
            }
            return url;
        }

        // If running directly on VPS port 8000, use relative route or same origin
        if (window.location.host && (window.location.host.includes('187.52.117.2') || window.location.port === '8000')) {
            return endpoint;
        }
        return window.LAYA_CONFIG.API_BASE_URL + endpoint;
    };

    /**
     * Enhanced fetch wrapper with automated auth headers (x-user-token or x-admin-token),
     * Content-Type preservation, and query parameter fallback for LiteSpeed/cPanel proxying.
     */
    window.authFetch = function(endpoint, options) {
        options = options || {};
        options.headers = options.headers || {};
        
        // Ensure Content-Type is application/json for POST/PUT if body is present and not set
        if (options.body && typeof options.body === 'string' && !options.headers['Content-Type']) {
            options.headers['Content-Type'] = 'application/json';
        }

        // Add User Token if present (generic or legacy)
        var userToken = localStorage.getItem('auth_user_token') || localStorage.getItem('laya_user_token');
        if (userToken && !options.headers['x-user-token']) {
            options.headers['x-user-token'] = userToken;
            if (!options.headers['Authorization']) {
                options.headers['Authorization'] = 'Bearer ' + userToken;
            }
        }

        // Add Admin Token ONLY if this is an admin route
        var isAdminRoute = endpoint.indexOf('admin') !== -1 || endpoint.indexOf('clear-logs') !== -1 || endpoint.indexOf('restart') !== -1 || endpoint.indexOf('logs') !== -1 || endpoint.indexOf('system') !== -1 || endpoint.indexOf('delete-') !== -1;
        var adminToken = localStorage.getItem('auth_admin_token') || localStorage.getItem('laya_admin_token');
        if (isAdminRoute && adminToken && !options.headers['x-admin-token']) {
            options.headers['x-admin-token'] = adminToken;
            options.headers['Authorization'] = 'Bearer ' + adminToken;
        }

        // Add API Key if present (generic or legacy)
        var apiKey = localStorage.getItem('auth_api_key') || localStorage.getItem('laya_api_key');
        if (apiKey && !options.headers['X-API-Key']) {
            options.headers['X-API-Key'] = apiKey;
        }

        // Append token query param to guarantee delivery across proxies
        var sep = endpoint.indexOf('?') !== -1 ? '&' : '?';
        if (isAdminRoute && adminToken && endpoint.indexOf('admin_token=') === -1) {
            endpoint += sep + 'admin_token=' + encodeURIComponent(adminToken);
            sep = '&';
        }
        if (userToken && endpoint.indexOf('user_token=') === -1) {
            endpoint += sep + 'user_token=' + encodeURIComponent(userToken);
        }

        var fullUrl = window.getApiUrl(endpoint);
        return fetch(fullUrl, options);
    };

    /**
     * Get currently logged-in user from localStorage
     */
    window.getCurrentUser = function() {
        try {
            var raw = localStorage.getItem('auth_user') || localStorage.getItem('laya_user');
            return raw ? JSON.parse(raw) : null;
        } catch(e) {
            return null;
        }
    };
})();
