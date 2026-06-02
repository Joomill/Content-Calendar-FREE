/*
 *  package: Joomill Content Calendar FREE
 *  copyright: Copyright (c) 2026. Jeroen Moolenschot | Joomill
 *  license: GNU General Public License version 3 or later
 *  link: https://www.joomill-extensions.com
 */

window.ContentCalendar = (function () {
    'use strict';

    let debugMode = true; // Enable debug logging

    /**
     * Debug logging function
     */
    function debugLog(message, data) {
        if (debugMode && console && console.log) {
            console.log('[ContentCalendar]', message, data || '');
        }
    }

    /**
     * Initialize calendar functionality
     */
    function init() {
        debugLog('Initializing ContentCalendar...');

        // Find calendar container
        const calendarContainer = document.querySelector('.mod-contentcalendar');
        if (!calendarContainer) {
            debugLog('❌ Calendar container (.mod-contentcalendar) not found on page');
            return false;
        }

        debugLog('✅ Calendar container found', calendarContainer);

        // Basic calendar setup without drag and drop
        setupBasicCalendar(calendarContainer);

        debugLog('🚀 ContentCalendar initialization completed successfully');
        return true;
    }

    /**
     * Setup basic calendar functionality
     */
    function setupBasicCalendar(container) {
        try {
            // Add any basic calendar functionality here if needed
            // For now, just log that calendar is ready
            debugLog('✅ Basic calendar setup completed');

        } catch (error) {
            debugLog(`❌ Error in setupBasicCalendar: ${error.message}`);
        }
    }

    /**
     * Show success/error message
     */
    function showMessage(message, type) {
        // Use Joomla's message system if available
        if (typeof Joomla !== 'undefined' && Joomla.renderMessages) {
            const messages = {};
            messages[type] = [message];
            Joomla.renderMessages(messages);
        } else {
            // Fallback to simple alert
            alert(message);
        }
    }

    /**
     * Get Joomla text with fallback
     */
    function getJoomlaText(key, fallback) {
        if (typeof Joomla !== 'undefined' && Joomla.Text && typeof Joomla.Text._ === 'function') {
            return Joomla.Text._(key, fallback);
        }
        return fallback;
    }

    /**
     * Get CSRF token
     */
    function getCSRFToken() {
        if (typeof Joomla !== 'undefined' && Joomla.getOptions) {
            return Joomla.getOptions('csrf.token', '');
        }

        // Fallback: try to find token in meta tag
        const metaToken = document.querySelector('meta[name="csrf-token"]');
        if (metaToken) {
            return metaToken.getAttribute('content');
        }

        // Last resort: try to find in form
        const tokenInput = document.querySelector('input[name*="token"]');
        if (tokenInput) {
            return tokenInput.name;
        }

        return '';
    }

    // Public API
    return {
        init: init
    };

})();

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function () {
    console.log('[ContentCalendar] DOM Content Loaded event fired');

    let initAttempts = 0;
    const maxAttempts = 3;

    function tryInitialize() {
        initAttempts++;
        console.log(`[ContentCalendar] Initialization attempt ${initAttempts}`);

        if (typeof window.ContentCalendar === 'undefined') {
            console.error('[ContentCalendar] ContentCalendar object not available');
            return false;
        }

        try {
            const success = window.ContentCalendar.init();
            if (success) {
                console.log('[ContentCalendar] ✅ Successfully initialized');
                return true;
            }
        } catch (error) {
            console.error('[ContentCalendar] Error during initialization:', error);
        }

        // Retry if not successful and haven't exceeded max attempts
        if (initAttempts < maxAttempts) {
            console.log(`[ContentCalendar] Retrying initialization in 500ms...`);
            setTimeout(tryInitialize, 500);
        } else {
            console.error('[ContentCalendar] ❌ Failed to initialize after all attempts');
        }

        return false;
    }

    // Start initialization
    tryInitialize();
});

// Also try on window load as fallback
window.addEventListener('load', function () {
    console.log('[ContentCalendar] Window load event fired');

    // Only initialize if not already done
    if (typeof window.ContentCalendar !== 'undefined') {
        const container = document.querySelector('.mod-contentcalendar');
        if (container && !container.hasAttribute('data-calendar-initialized')) {
            window.ContentCalendar.init();
            container.setAttribute('data-calendar-initialized', 'true');
        }
    }
});
