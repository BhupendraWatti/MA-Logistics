/**
 * ERP Utilities
 * 
 * Provides modular wrappers for SweetAlert2 and DataTables
 * for a consistent, production-ready operational UI.
 */

// Global DataTables configuration to disable ugly alerts
if (typeof $.fn !== 'undefined' && typeof $.fn.dataTable !== 'undefined') {
    $.fn.dataTable.ext.errMode = 'none';
    $(document).on('error.dt', function (e, settings, techNote, message) {
        console.error('DataTables Error:', message);
        ERPUtils.showError('Session or Data Error', 'Failed to load data. Your session may have expired. Please refresh the page.');
        $('.dataTables_processing').hide(); // Fixes "font style getting light" issue by removing the stuck processing overlay
    });
}

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
            html: text,
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
            html: text,
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
            html: text,
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

// ==========================================
// GLOBAL UNSAVED CHANGES TRACKING
// ==========================================
$(document).ready(function() {
    let isDirty = false;
    let allowLeave = false;
    
    // Check both local isDirty and global window.isDirty (for page-specific script modifications)
    function checkDirty() {
        return isDirty || !!window.isDirty;
    }
    
    function resetDirty() {
        isDirty = false;
        window.isDirty = false;
    }

    // Delay change binding to ignore browser autofill and initial JS triggers on page load
    setTimeout(function() {
        $('input:not(.search-bar input), select:not(.search-bar select), textarea:not(.search-bar textarea)').on('change input', function(e) {
            // Ignore programmatic jQuery triggers
            if (e.isTrigger) return;
            
            // Ignore non-form action inputs like permission switches, select-all, or row checkboxes
            if ($(this).hasClass('toggle-permission') || $(this).hasClass('booking-check') || $(this).attr('id') === 'selectAll') {
                return;
            }
            
            // Ignore inputs inside GET (search/filter) forms or forms with explicit track exclusions
            const $form = $(this).closest('form');
            if ($form.length > 0) {
                if ($form.hasClass('no-track') || $form.attr('data-no-track') === 'true' || $form.attr('method')?.toLowerCase() === 'get') {
                    return;
                }
            }

            if (!checkDirty() && $(this).closest('.dataTables_filter').length === 0 && $(this).closest('.dataTables_length').length === 0) {
                isDirty = true;
                // Push state so we can trap back button
                history.pushState(null, null, window.location.href);
            }
        });
    }, 1500);

    // Intercept back button
    window.addEventListener('popstate', function(event) {
        if (checkDirty() && !allowLeave) {
            history.pushState(null, null, window.location.href);
            Swal.fire({
                title: 'Unsaved Changes!',
                text: "You have unsaved changes. Are you sure you want to go back?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, leave',
                cancelButtonText: 'Stay'
            }).then((result) => {
                if (result.isConfirmed) {
                    allowLeave = true;
                    resetDirty();
                    history.go(-2);
                }
            });
        }
    });

    // Intercept internal link clicks
    $('a').on('click', function(e) {
        const targetUrl = $(this).attr('href');
        // Check if real internal navigation
        if (checkDirty() && !allowLeave && targetUrl && !targetUrl.startsWith('#') && !targetUrl.startsWith('javascript:')) {
            // Ignore offcanvas triggers that might use href=# (just a safeguard)
            if ($(this).attr('data-bs-toggle') === 'offcanvas') return;
            
            e.preventDefault();
            Swal.fire({
                title: 'Unsaved Changes!',
                text: "You have unsaved changes. Are you sure you want to leave?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, leave',
                cancelButtonText: 'Stay'
            }).then((result) => {
                if (result.isConfirmed) {
                    allowLeave = true;
                    resetDirty();
                    window.location.href = targetUrl;
                }
            });
        }
    });

    // Fallback for tab closing / page refresh
    window.addEventListener("beforeunload", function(event) {
        if (checkDirty() && !allowLeave) {
            event.preventDefault();
            event.returnValue = "You have unsaved changes. Are you sure you want to leave?";
        }
    });

    // Clear dirty flag when forms are submitted
    $('form').on('submit', function() {
        allowLeave = true;
        resetDirty();
    });
});

// Global jQuery AJAX Setup to inject CSRF tokens into all requests
if (typeof $ !== 'undefined') {
    $.ajaxSetup({
        beforeSend: function(xhr, settings) {
            // Only inject for POST/PUT/DELETE requests
            if (settings.type === 'POST' || settings.type === 'PUT' || settings.type === 'DELETE') {
                let csrfHeader = document.querySelector('meta[name="csrf-header"]')?.getAttribute('content') || 'X-CSRF-TOKEN';
                let csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                if (csrfToken) {
                    xhr.setRequestHeader(csrfHeader, csrfToken);
                }
            }
        }
    });
}
