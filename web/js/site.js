/**
 * Main application JavaScript
 */

// Global utilities
window.StockMS = {
    // Format currency
    formatCurrency: function(amount, symbol = '$') {
        return symbol + parseFloat(amount).toFixed(2);
    },
    
    // Format number
    formatNumber: function(number, decimals = 2) {
        return parseFloat(number).toFixed(decimals);
    },
    
    // Debounce function
    debounce: function(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },
    
    // AJAX helper
    ajax: function(url, options = {}) {
        const defaults = {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            }
        };
        
        const config = Object.assign(defaults, options);
        
        return fetch(url, config)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            });
    },
    
    // Show loading state
    showLoading: function(element) {
        if (typeof element === 'string') {
            element = document.querySelector(element);
        }
        if (element) {
            element.classList.add('loading');
        }
    },
    
    // Hide loading state
    hideLoading: function(element) {
        if (typeof element === 'string') {
            element = document.querySelector(element);
        }
        if (element) {
            element.classList.remove('loading');
        }
    },
    
    // Confirm dialog
    confirm: function(message, callback) {
        if (confirm(message)) {
            callback();
        }
    },
    
    // Print element
    print: function(elementId) {
        const element = document.getElementById(elementId);
        if (element) {
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <html>
                <head>
                    <title>Print</title>
                    <link rel="stylesheet" href="/css/site.css">
                    <style>
                        body { margin: 0; padding: 20px; }
                        @media print { body { padding: 0; } }
                    </style>
                </head>
                <body>
                    ${element.innerHTML}
                    <script>
                        window.onload = function() {
                            window.print();
                            window.close();
                        };
                    </script>
                </body>
                </html>
            `);
            printWindow.document.close();
        }
    }
};

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Initialize popovers
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // Auto-hide alerts
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
        alerts.forEach(function(alert) {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
    
    // Confirm delete actions
    document.addEventListener('click', function(e) {
        if (e.target.matches('[data-confirm]') || e.target.closest('[data-confirm]')) {
            const element = e.target.matches('[data-confirm]') ? e.target : e.target.closest('[data-confirm]');
            const message = element.getAttribute('data-confirm') || 'Are you sure?';
            
            if (!confirm(message)) {
                e.preventDefault();
                return false;
            }
        }
    });
    
    // Auto-focus first input in modals
    document.addEventListener('shown.bs.modal', function(e) {
        const firstInput = e.target.querySelector('input:not([type="hidden"]), select, textarea');
        if (firstInput) {
            firstInput.focus();
        }
    });
    
    // Number formatting on blur
    document.addEventListener('blur', function(e) {
        if (e.target.matches('.format-number')) {
            const value = parseFloat(e.target.value);
            if (!isNaN(value)) {
                e.target.value = value.toFixed(2);
            }
        }
    }, true);
    
    // Barcode scanner support
    let barcodeBuffer = '';
    let barcodeTimeout;
    
    document.addEventListener('keypress', function(e) {
        // Only process if focused on barcode input or no input is focused
        const activeElement = document.activeElement;
        const isBarcodeInput = activeElement && activeElement.classList.contains('barcode-input');
        const isNoInputFocused = !activeElement || activeElement.tagName === 'BODY';
        
        if (isBarcodeInput || isNoInputFocused) {
            clearTimeout(barcodeTimeout);
            
            if (e.key === 'Enter') {
                if (barcodeBuffer.length > 3) {
                    // Trigger barcode scanned event
                    const event = new CustomEvent('barcodeScanned', {
                        detail: { barcode: barcodeBuffer }
                    });
                    document.dispatchEvent(event);
                }
                barcodeBuffer = '';
            } else {
                barcodeBuffer += e.key;
                
                // Clear buffer after 100ms of inactivity
                barcodeTimeout = setTimeout(() => {
                    barcodeBuffer = '';
                }, 100);
            }
        }
    });
    
    // Handle barcode scanned events
    document.addEventListener('barcodeScanned', function(e) {
        const barcode = e.detail.barcode;
        
        // If on invoice page, try to add product by barcode
        if (window.location.pathname.includes('/invoice/')) {
            if (typeof window.addProductByBarcode === 'function') {
                window.addProductByBarcode(barcode);
            }
        }
        
        // If barcode input is focused, set its value
        const barcodeInput = document.querySelector('.barcode-input:focus');
        if (barcodeInput) {
            barcodeInput.value = barcode;
            barcodeInput.dispatchEvent(new Event('input'));
        }
    });
});

// Export for global use
window.StockMS = StockMS;
