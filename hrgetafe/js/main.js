// ============================================
// MAIN JAVASCRIPT
// HRGetafe - Human Resources Information System
// ============================================

// DOM Ready
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    initializeTooltips();
    
    // Initialize popovers
    initializePopovers();
    
    // Setup auto-hide alerts
    setupAlerts();
});

/**
 * Initialize Bootstrap Tooltips
 */
function initializeTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

/**
 * Initialize Bootstrap Popovers
 */
function initializePopovers() {
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
}

/**
 * Setup Auto-hide Alerts
 */
function setupAlerts() {
    const alerts = document.querySelectorAll('.alert:not(.alert-sticky)');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000); // 5 seconds
    });
}

/**
 * Show Alert Message
 */
function showAlert(message, type = 'info', container = '#alertContainer') {
    const alertHTML = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            <i class="fas fa-info-circle"></i> ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    const alertContainer = document.querySelector(container);
    if (alertContainer) {
        alertContainer.innerHTML = alertHTML;
        setupAlerts();
    }
}

/**
 * Confirm Delete Action
 */
function confirmDelete(message = 'Are you sure you want to delete this record?') {
    return confirm(message);
}

/**
 * Format Date
 */
function formatDate(date) {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

/**
 * Format Currency
 */
function formatCurrency(value) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'PHP'
    }).format(value);
}

/**
 * Toggle Sidebar
 */
function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    if (sidebar) {
        sidebar.classList.toggle('show');
    }
}

/**
 * Logout Confirmation
 */
function confirmLogout() {
    if (confirm('Are you sure you want to logout?')) {
        window.location.href = '/HRGetafe/logout.php';
    }
}

/**
 * Set Form Data
 */
function setFormData(data) {
    Object.keys(data).forEach(key => {
        const input = document.querySelector(`[name="${key}"]`);
        if (input) {
            input.value = data[key];
        }
    });
}

/**
 * Get Form Data
 */
function getFormData(formId) {
    const form = document.getElementById(formId);
    const formData = new FormData(form);
    const data = {};
    
    formData.forEach((value, key) => {
        if (data[key]) {
            if (!Array.isArray(data[key])) {
                data[key] = [data[key]];
            }
            data[key].push(value);
        } else {
            data[key] = value;
        }
    });
    
    return data;
}

/**
 * Clear Form
 */
function clearForm(formId) {
    const form = document.getElementById(formId);
    if (form) {
        form.reset();
    }
}

/**
 * Disable Form Elements
 */
function disableForm(formId, disabled = true) {
    const form = document.getElementById(formId);
    if (form) {
        form.querySelectorAll('input, select, textarea, button').forEach(element => {
            element.disabled = disabled;
        });
    }
}

/**
 * Show Loading State
 */
function showLoading(buttonId) {
    const button = document.getElementById(buttonId);
    if (button) {
        button.disabled = true;
        button.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Loading...';
    }
}

/**
 * Hide Loading State
 */
function hideLoading(buttonId, originalText) {
    const button = document.getElementById(buttonId);
    if (button) {
        button.disabled = false;
        button.innerHTML = originalText;
    }
}

/**
 * Print Page
 */
function printPage() {
    window.print();
}

/**
 * Export to CSV
 */
function exportToCSV(tableId, filename) {
    const table = document.getElementById(tableId);
    let csv = [];
    
    // Get headers
    const headers = table.querySelectorAll('thead th');
    const headerRow = Array.from(headers).map(header => header.textContent.trim());
    csv.push(headerRow.join(','));
    
    // Get rows
    const rows = table.querySelectorAll('tbody tr');
    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        const rowData = Array.from(cells).map(cell => cell.textContent.trim());
        csv.push(rowData.join(','));
    });
    
    // Create blob and download
    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = filename + '.csv';
    link.click();
    window.URL.revokeObjectURL(url);
}
