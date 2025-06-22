// Admin Dashboard JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Initialize the dashboard
    initializeDashboard();
    
    // Set up event listeners
    setupEventListeners();
    
    // Calculate and display statistics
    updateStatistics();
    
    // Initialize tooltips if needed
    initializeTooltips();
});

/**
 * Initialize the dashboard with animations and setup
 */
function initializeDashboard() {
    // Add fade-in animation to main elements
    const mainElements = document.querySelectorAll('.stat-card, .table-container');
    mainElements.forEach((element, index) => {
        setTimeout(() => {
            element.classList.add('fade-in');
        }, index * 100);
    });
    
    // Check for URL parameters for success/error messages
    checkUrlParameters();
    
    console.log('Admin Dashboard initialized successfully');
}

/**
 * Set up all event listeners
 */
function setupEventListeners() {
    // Search functionality
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', debounce(handleSearch, 300));
    }
    
    // Status filter
    const statusFilter = document.getElementById('statusFilter');
    if (statusFilter) {
        statusFilter.addEventListener('change', handleStatusFilter);
    }
    
    // Form submissions with loading states
    const statusForms = document.querySelectorAll('.status-form');
    statusForms.forEach(form => {
        form.addEventListener('submit', handleFormSubmission);
    });
    
    // Status select change handlers for immediate visual feedback
    const statusSelects = document.querySelectorAll('.status-select');
    statusSelects.forEach(select => {
        select.addEventListener('change', handleStatusSelectChange);
    });
}

/**
 * Handle search functionality
 */
function handleSearch(event) {
    const searchTerm = event.target.value.toLowerCase().trim();
    const tableRows = document.querySelectorAll('#ordersTable tbody tr:not(.no-orders)');
    let visibleCount = 0;
    
    tableRows.forEach(row => {
        const orderID = row.querySelector('.order-id')?.textContent.toLowerCase() || '';
        const customerName = row.querySelector('.customer-name')?.textContent.toLowerCase() || '';
        const itemName = row.querySelector('.item-name')?.textContent.toLowerCase() || '';
        
        const isMatch = orderID.includes(searchTerm) || 
                       customerName.includes(searchTerm) || 
                       itemName.includes(searchTerm);
        
        if (isMatch || searchTerm === '') {
            row.style.display = '';
            row.classList.add('slide-in');
            visibleCount++;
        } else {
            row.style.display = 'none';
            row.classList.remove('slide-in');
        }
    });
    
    // Show/hide no results message
    toggleNoResultsMessage(visibleCount === 0 && searchTerm !== '');
}

/**
 * Handle status filter
 */
function handleStatusFilter(event) {
    const selectedStatus = event.target.value.toLowerCase();
    const tableRows = document.querySelectorAll('#ordersTable tbody tr:not(.no-orders)');
    let visibleCount = 0;
    
    tableRows.forEach(row => {
        const rowStatus = row.getAttribute('data-status')?.toLowerCase() || '';
        
        if (selectedStatus === '' || rowStatus === selectedStatus) {
            row.style.display = '';
            row.classList.add('slide-in');
            visibleCount++;
        } else {
            row.style.display = 'none';
            row.classList.remove('slide-in');
        }
    });
    
    // Clear search when filtering
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.value = '';
    }
    
    // Show/hide no results message
    toggleNoResultsMessage(visibleCount === 0 && selectedStatus !== '');
    
    // Update statistics based on filter
    updateStatistics();
}

/**
 * Handle form submission with loading states
 */
function handleFormSubmission(event) {
    const form = event.target;
    const submitButton = form.querySelector('.update-btn');
    const originalText = submitButton.innerHTML;
    
    // Show loading state
    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
    submitButton.disabled = true;
    
    // Show loading spinner
    showLoadingSpinner();
    
    // Note: The actual form submission will proceed normally
    // The loading state will be reset when the page reloads
}

/**
 * Handle status select change for visual feedback
 */
function handleStatusSelectChange(event) {
    const select = event.target;
    const row = select.closest('tr');
    const statusBadge = row.querySelector('.status-badge');
    const newStatus = select.value;
    
    // Update the visual status immediately for better UX
    if (statusBadge) {
        statusBadge.className = `status-badge status-${newStatus}`;
        statusBadge.textContent = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
        
        // Add a subtle animation
        statusBadge.style.transform = 'scale(1.1)';
        setTimeout(() => {
            statusBadge.style.transform = 'scale(1)';
        }, 200);
    }
    
    // Update row data attribute
    row.setAttribute('data-status', newStatus);
    
    // Update statistics
    updateStatistics();
}

/**
 * Update statistics counters
 */
function updateStatistics() {
    const visibleRows = document.querySelectorAll('#ordersTable tbody tr:not(.no-orders):not([style*="display: none"])');
    const statistics = {
        pending: 0,
        processing: 0,
        shipped: 0,
        delivered: 0
    };
    
    visibleRows.forEach(row => {
        const status = row.getAttribute('data-status');
        if (statistics.hasOwnProperty(status)) {
            statistics[status]++;
        }
    });
    
    // Update the counter elements with animation
    Object.keys(statistics).forEach(status => {
        const countElement = document.getElementById(`${status}Count`);
        if (countElement) {
            animateCounter(countElement, statistics[status]);
        }
    });
}

/**
 * Animate counter with counting effect
 */
function animateCounter(element, targetValue) {
    const currentValue = parseInt(element.textContent) || 0;
    const increment = targetValue > currentValue ? 1 : -1;
    const steps = Math.abs(targetValue - currentValue);
    let current = currentValue;
    
    if (steps === 0) return;
    
    const timer = setInterval(() => {
        current += increment;
        element.textContent = current;
        
        if (current === targetValue) {
            clearInterval(timer);
            // Add a small bounce effect
            element.style.transform = 'scale(1.1)';
            setTimeout(() => {
                element.style.transform = 'scale(1)';
            }, 150);
        }
    }, 50 / steps);
}

/**
 * Show/hide loading spinner
 */
function showLoadingSpinner() {
    const spinner = document.getElementById('loadingSpinner');
    if (spinner) {
        spinner.classList.add('show');
        
        // Hide after 2 seconds as a fallback
        setTimeout(() => {
            hideLoadingSpinner();
        }, 2000);
    }
}

function hideLoadingSpinner() {
    const spinner = document.getElementById('loadingSpinner');
    if (spinner) {
        spinner.classList.remove('show');
    }
}

/**
 * Show toast notification
 */
function showToast(type, message) {
    const toastId = type === 'success' ? 'successToast' : 'errorToast';
    const messageId = type === 'success' ? 'successMessage' : 'errorMessage';
    
    const toastElement = document.getElementById(toastId);
    const messageElement = document.getElementById(messageId);
    
    if (toastElement && messageElement) {
        messageElement.textContent = message;
        const toast = new bootstrap.Toast(toastElement);
        toast.show();
    }
}

/**
 * Toggle no results message
 */
function toggleNoResultsMessage(show) {
    let noResultsRow = document.querySelector('.no-results-row');
    
    if (show && !noResultsRow) {
        const tbody = document.querySelector('#ordersTable tbody');
        noResultsRow = document.createElement('tr');
        noResultsRow.className = 'no-results-row';
        noResultsRow.innerHTML = `
            <td colspan="7" class="text-center empty-state">
                <i class="fas fa-search fa-2x mb-2"></i>
                <h6>No matching orders found</h6>
                <p class="text-muted">Try adjusting your search or filter criteria</p>
            </td>
        `;
        tbody.appendChild(noResultsRow);
    } else if (!show && noResultsRow) {
        noResultsRow.remove();
    }
}

/**
 * Check URL parameters for messages
 */
function checkUrlParameters() {
    const urlParams = new URLSearchParams(window.location.search);
    const success = urlParams.get('success');
    const error = urlParams.get('error');
    
    if (success) {
        showToast('success', decodeURIComponent(success));
        // Clean up URL
        cleanUpUrl();
    }
    
    if (error) {
        showToast('error', decodeURIComponent(error));
        // Clean up URL
        cleanUpUrl();
    }
}

/**
 * Clean up URL parameters
 */
function cleanUpUrl() {
    const url = new URL(window.location);
    url.searchParams.delete('success');
    url.searchParams.delete('error');
    window.history.replaceState({}, document.title, url.pathname + url.search);
}

/**
 * Initialize tooltips
 */
function initializeTooltips() {
    // Initialize Bootstrap tooltips if available
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
}

/**
 * Debounce function to limit function calls
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
 * Bulk operations functionality
 */
function initializeBulkOperations() {
    const selectAllCheckbox = document.getElementById('selectAll');
    const rowCheckboxes = document.querySelectorAll('.row-checkbox');
    const bulkActionsPanel = document.getElementById('bulkActionsPanel');
    const bulkStatusSelect = document.getElementById('bulkStatusSelect');
    const applyBulkButton = document.getElementById('applyBulkAction');
    
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            rowCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            toggleBulkActionsPanel();
        });
    }
    
    rowCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateSelectAllState();
            toggleBulkActionsPanel();
        });
    });
    
    if (applyBulkButton) {
        applyBulkButton.addEventListener('click', handleBulkAction);
    }
}

/**
 * Handle bulk actions
 */
function handleBulkAction() {
    const selectedCheckboxes = document.querySelectorAll('.row-checkbox:checked');
    const bulkStatusSelect = document.getElementById('bulkStatusSelect');
    
    if (selectedCheckboxes.length === 0) {
        showToast('error', 'Please select at least one order');
        return;
    }
    
    if (!bulkStatusSelect.value) {
        showToast('error', 'Please select a status');
        return;
    }
    
    const confirmation = confirm(`Are you sure you want to update ${selectedCheckboxes.length} orders to ${bulkStatusSelect.value}?`);
    
    if (confirmation) {
        // Show loading state
        showLoadingSpinner();
        
        // Collect order IDs
        const orderIds = [];
        selectedCheckboxes.forEach(checkbox => {
            const row = checkbox.closest('tr');
            const orderIdCell = row.querySelector('.order-id');
            if (orderIdCell) {
                orderIds.push(orderIdCell.textContent.trim());
            }
        });
        
        // Here you would typically make an AJAX call to update multiple orders
        // For now, we'll simulate the update
        setTimeout(() => {
            selectedCheckboxes.forEach(checkbox => {
                const row = checkbox.closest('tr');
                const statusSelect = row.querySelector('.status-select');
                if (statusSelect) {
                    statusSelect.value = bulkStatusSelect.value;
                    statusSelect.dispatchEvent(new Event('change'));
                }
                checkbox.checked = false;
            });
            
            hideLoadingSpinner();
            showToast('success', `Successfully updated ${orderIds.length} orders`);
            toggleBulkActionsPanel();
            updateSelectAllState();
        }, 1000);
    }
}

/**
 * Toggle bulk actions panel visibility
 */
function toggleBulkActionsPanel() {
    const selectedCount = document.querySelectorAll('.row-checkbox:checked').length;
    const bulkActionsPanel = document.getElementById('bulkActionsPanel');
    const selectedCountElement = document.getElementById('selectedCount');
    
    if (bulkActionsPanel) {
        if (selectedCount > 0) {
            bulkActionsPanel.style.display = 'block';
            if (selectedCountElement) {
                selectedCountElement.textContent = selectedCount;
            }
        } else {
            bulkActionsPanel.style.display = 'none';
        }
    }
}

/**
 * Update select all checkbox state
 */
function updateSelectAllState() {
    const selectAllCheckbox = document.getElementById('selectAll');
    const rowCheckboxes = document.querySelectorAll('.row-checkbox');
    const checkedCount = document.querySelectorAll('.row-checkbox:checked').length;
    
    if (selectAllCheckbox) {
        if (checkedCount === 0) {
            selectAllCheckbox.checked = false;
            selectAllCheckbox.indeterminate = false;
        } else if (checkedCount === rowCheckboxes.length) {
            selectAllCheckbox.checked = true;
            selectAllCheckbox.indeterminate = false;
        } else {
            selectAllCheckbox.checked = false;
            selectAllCheckbox.indeterminate = true;
        }
    }
}

/**
 * Export functionality
 */
function exportOrders(format) {
    const visibleRows = document.querySelectorAll('#ordersTable tbody tr:not(.no-orders):not([style*="display: none"])');
    
    if (visibleRows.length === 0) {
        showToast('error', 'No orders to export');
        return;
    }
    
    showLoadingSpinner();
    
    // Collect data from visible rows
    const data = [];
    visibleRows.forEach(row => {
        const orderData = {
            'Order ID': row.querySelector('.order-id')?.textContent.trim() || '',
            'Customer': row.querySelector('.customer-name')?.textContent.trim() || '',
            'Item': row.querySelector('.item-name')?.textContent.trim() || '',
            'Quantity': row.querySelector('.quantity')?.textContent.trim() || '',
            'Total': row.querySelector('.total')?.textContent.trim() || '',
            'Status': row.querySelector('.status-badge')?.textContent.trim() || '',
            'Date': row.querySelector('.order-date')?.textContent.trim() || ''
        };
        data.push(orderData);
    });
    
    if (format === 'csv') {
        exportToCSV(data);
    } else if (format === 'json') {
        exportToJSON(data);
    }
    
    hideLoadingSpinner();
    showToast('success', `Orders exported successfully as ${format.toUpperCase()}`);
}

/**
 * Export to CSV
 */
function exportToCSV(data) {
    const headers = Object.keys(data[0]);
    const csvContent = [
        headers.join(','),
        ...data.map(row => headers.map(header => `"${row[header]}"`).join(','))
    ].join('\n');
    
    downloadFile(csvContent, 'orders_export.csv', 'text/csv');
}

/**
 * Export to JSON
 */
function exportToJSON(data) {
    const jsonContent = JSON.stringify(data, null, 2);
    downloadFile(jsonContent, 'orders_export.json', 'application/json');
}

/**
 * Download file
 */
function downloadFile(content, fileName, contentType) {
    const blob = new Blob([content], { type: contentType });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = fileName;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    window.URL.revokeObjectURL(url);
}

// Initialize additional features when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    initializeBulkOperations();
    
    // Add export button event listeners
    const exportCsvBtn = document.getElementById('exportCsv');
    const exportJsonBtn = document.getElementById('exportJson');
    
    if (exportCsvBtn) {
        exportCsvBtn.addEventListener('click', () => exportOrders('csv'));
    }
    
    if (exportJsonBtn) {
        exportJsonBtn.addEventListener('click', () => exportOrders('json'));
    }
    
    // Add keyboard shortcuts
    document.addEventListener('keydown', function(event) {
        // Ctrl/Cmd + F for search
        if ((event.ctrlKey || event.metaKey) && event.key === 'f') {
            event.preventDefault();
            const searchInput = document.getElementById('searchInput');
            if (searchInput) {
                searchInput.focus();
            }
        }
        
        // Escape to clear search
        if (event.key === 'Escape') {
            const searchInput = document.getElementById('searchInput');
            if (searchInput && searchInput === document.activeElement) {
                searchInput.value = '';
                searchInput.dispatchEvent(new Event('input'));
                searchInput.blur();
            }
        }
    });
});