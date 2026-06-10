// ============================================
// AJAX FUNCTIONS
// HRGetafe - Human Resources Information System
// ============================================

/**
 * Make AJAX Request
 */
function makeAjaxRequest(url, method = 'GET', data = null, callback = null) {
    const options = {
        method: method,
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    };
    
    if (data && method !== 'GET') {
        options.body = JSON.stringify(data);
    }
    
    fetch(url, options)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (callback) {
                callback(null, data);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            if (callback) {
                callback(error, null);
            }
        });
}

/**
 * Submit Form via AJAX
 */
function submitFormAjax(formId, url, callback = null) {
    const form = document.getElementById(formId);
    if (!form) return;
    
    const formData = new FormData(form);
    
    // Show loading state
    const submitBtn = form.querySelector('button[type="submit"]');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
    }
    
    fetch(url, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Submit';
        }
        
        if (callback) {
            callback(data);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Submit';
        }
        showAlert('An error occurred. Please try again.', 'danger');
    });
}

/**
 * Load Data via AJAX
 */
function loadDataAjax(url, containerId, options = {}) {
    const container = document.getElementById(containerId);
    if (!container) return;
    
    // Show loading spinner
    container.innerHTML = '<div class="text-center"><span class="spinner-border"></span></div>';
    
    fetch(url, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Failed to load data');
        }
        return response.text();
    })
    .then(html => {
        container.innerHTML = html;
        
        // Re-initialize tooltips and popovers
        if (typeof initializeTooltips === 'function') {
            initializeTooltips();
        }
        if (typeof initializePopovers === 'function') {
            initializePopovers();
        }
        
        if (options.callback) {
            options.callback();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        container.innerHTML = '<div class="alert alert-danger">Failed to load data. Please try again.</div>';
    });
}

/**
 * Delete Record via AJAX
 */
function deleteRecordAjax(url, recordId, callback = null) {
    if (!confirm('Are you sure you want to delete this record?')) {
        return;
    }
    
    makeAjaxRequest(url, 'POST', { id: recordId }, function(error, response) {
        if (error) {
            showAlert('Error deleting record. Please try again.', 'danger');
        } else if (response.success) {
            showAlert('Record deleted successfully!', 'success');
            if (callback) {
                callback(response);
            }
        } else {
            showAlert(response.message || 'Error deleting record.', 'danger');
        }
    });
}

/**
 * Update Record via AJAX
 */
function updateRecordAjax(url, data, callback = null) {
    makeAjaxRequest(url, 'POST', data, function(error, response) {
        if (error) {
            showAlert('Error updating record. Please try again.', 'danger');
        } else if (response.success) {
            showAlert('Record updated successfully!', 'success');
            if (callback) {
                callback(response);
            }
        } else {
            showAlert(response.message || 'Error updating record.', 'danger');
        }
    });
}

/**
 * Fetch Data with Parameters
 */
function fetchData(url, params = {}) {
    const queryString = new URLSearchParams(params).toString();
    const fullUrl = queryString ? `${url}?${queryString}` : url;
    
    return fetch(fullUrl, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .catch(error => {
        console.error('Error:', error);
        return null;
    });
}

/**
 * POST Request with JSON
 */
function postJSON(url, data) {
    return fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .catch(error => {
        console.error('Error:', error);
        return { success: false, message: 'An error occurred' };
    });
}

/**
 * Check if Record Exists
 */
function checkRecordExists(url, id, callback) {
    fetchData(url, { id: id })
        .then(response => {
            if (callback) {
                callback(response);
            }
        });
}

/**
 * Search Records
 */
function searchRecords(url, query, callback) {
    fetchData(url, { search: query })
        .then(response => {
            if (callback) {
                callback(response);
            }
        });
}

/**
 * Filter Data
 */
function filterData(url, filters, callback) {
    fetchData(url, filters)
        .then(response => {
            if (callback) {
                callback(response);
            }
        });
}

/**
 * Paginate Data
 */
function paginateData(url, page, callback) {
    fetchData(url, { page: page })
        .then(response => {
            if (callback) {
                callback(response);
            }
        });
}

/**
 * Upload File
 */
function uploadFile(url, fileInputId, callback) {
    const fileInput = document.getElementById(fileInputId);
    if (!fileInput || !fileInput.files.length) {
        showAlert('Please select a file.', 'warning');
        return;
    }
    
    const formData = new FormData();
    formData.append('file', fileInput.files[0]);
    
    fetch(url, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (callback) {
            callback(data);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error uploading file. Please try again.', 'danger');
    });
}
