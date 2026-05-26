/**
 * ERP Utilities
 * 
 * Provides modular wrappers for SweetAlert2 and DataTables
 * for a consistent, production-ready operational UI.
 */

const ERPUtils = {
    
    // ==========================================
    // SWEETALERT2 HELPERS
    // ==========================================
    
    /**
     * Show a standardized success message
     */
    showSuccess: function(title, text = '') {
        return Swal.fire({
            icon: 'success',
            title: title,
            text: text,
            timer: 2000,
            showConfirmButton: false,
            toast: true,
            position: 'top-end'
        });
    },

    /**
     * Show a standardized error message
     */
    showError: function(title, text = '') {
        return Swal.fire({
            icon: 'error',
            title: title,
            text: text,
            confirmButtonColor: '#d33'
        });
    },

    /**
     * Show a standardized warning dialog
     */
    showWarning: function(title, text = '') {
        return Swal.fire({
            icon: 'warning',
            title: title,
            text: text,
            confirmButtonColor: '#f59e0b'
        });
    },

    /**
     * Reusable confirmation dialog (e.g. before navigating away)
     */
    confirmAction: function(title, text, confirmBtnText = 'Yes, proceed', icon = 'warning') {
        return Swal.fire({
            title: title,
            text: text,
            icon: icon,
            showCancelButton: true,
            confirmButtonColor: '#0d6efd',
            cancelButtonColor: '#6c757d',
            confirmButtonText: confirmBtnText
        });
    },

    /**
     * Specialized delete confirmation with AJAX execution
     * @param {string} url The backend endpoint to call
     * @param {function} onSuccess Callback on successful deletion
     */
    confirmDelete: function(url, onSuccess = null) {
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                // Execute AJAX delete
                let csrfHeader = document.querySelector('meta[name="csrf-header"]')?.getAttribute('content');
                let csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                let reqHeaders = { 
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                };
                if (csrfHeader && csrfToken) {
                    reqHeaders[csrfHeader] = csrfToken;
                }
                
                fetch(url, {
                    method: 'POST',
                    headers: reqHeaders
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        ERPUtils.showSuccess('Deleted!', data.message || 'Record has been deleted.');
                        if (onSuccess) onSuccess();
                    } else {
                        ERPUtils.showError('Failed!', data.message || 'Could not delete record.');
                    }
                })
                .catch(error => {
                    ERPUtils.showError('Error', 'An unexpected error occurred.');
                    console.error(error);
                });
            }
        });
    },

    // ==========================================
    // DATATABLES HELPERS
    // ==========================================

    /**
     * Initialize Server-Side DataTable
     * @param {string} selector The jQuery selector for the table
     * @param {string} url The AJAX endpoint
     * @param {Array} columns DataTables columns configuration array
     * @param {Object} extraOptions Additional DataTables options
     */
    initDataTable: function(selector, url, columns, extraOptions = {}) {
        let defaultOptions = {
            processing: true,
            serverSide: url !== null,
            columns: columns,
            language: {
                search: "",
                searchPlaceholder: "Search records...",
                processing: '<i class="fas fa-spinner fa-spin fa-2x text-primary"></i>'
            },
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            responsive: true,
            dom: '<"d-flex justify-content-between align-items-center mb-3"lf>rt<"d-flex justify-content-between align-items-center mt-3"ip>',
            drawCallback: function() {
                $('.dataTables_filter input').addClass('form-control form-control-sm border-secondary');
                $('.dataTables_length select').addClass('form-select form-select-sm border-secondary');
            }
        };

        if (url !== null) {
            defaultOptions.ajax = {
                url: url,
                type: 'POST',
                beforeSend: function (request) {
                    let csrfHeader = document.querySelector('meta[name="csrf-header"]')?.getAttribute('content');
                    let csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                    if (csrfHeader && csrfToken) {
                        request.setRequestHeader(csrfHeader, csrfToken);
                    }
                }
            };
        }

        return $(selector).DataTable($.extend(true, {}, defaultOptions, extraOptions));
    }
};
