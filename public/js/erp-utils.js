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
    
    /**
     * Escape HTML characters to prevent XSS in dynamic templates
     */
    escapeHtml: function(text) {
        if (text === null || text === undefined) return '';
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    },
    
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
    window.allowLeave = false;
    
    // Check both local isDirty and global window.isDirty (for page-specific script modifications)
    window.checkDirty = function() {
        return isDirty || !!window.isDirty;
    };
    
    function resetDirty() {
        isDirty = false;
        window.isDirty = false;
    }

    // Delay change binding to ignore browser autofill and initial JS triggers on page load
    setTimeout(function() {
        $('input:not(.search-bar input), select:not(.search-bar select), textarea:not(.search-bar textarea)').on('change input', function(e) {
            // Ignore programmatic jQuery triggers
            if (e.isTrigger) return;
            
            // Ignore non-form action inputs like permission switches, select-all, row checkboxes, or no-track items
            if ($(this).hasClass('toggle-permission') || $(this).hasClass('booking-check') || $(this).attr('id') === 'selectAll' || $(this).hasClass('no-track') || $(this).attr('data-no-track') === 'true') {
                return;
            }
            
            // Ignore inputs inside GET (search/filter) forms or forms with explicit track exclusions
            const $form = $(this).closest('form');
            if ($form.length > 0) {
                if ($form.hasClass('no-track') || $form.attr('data-no-track') === 'true' || $form.attr('method')?.toLowerCase() === 'get') {
                    return;
                }
            }

            if (!window.checkDirty() && $(this).closest('.dataTables_filter').length === 0 && $(this).closest('.dataTables_length').length === 0) {
                isDirty = true;
                // Push state so we can trap back button
                history.pushState(null, null, window.location.href);
            }
        });
    }, 1500);

    // Intercept back button
    window.addEventListener('popstate', function(event) {
        if (window.checkDirty() && !window.allowLeave) {
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
                    window.allowLeave = true;
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
        if (window.checkDirty() && !window.allowLeave && targetUrl && !targetUrl.startsWith('#') && !targetUrl.startsWith('javascript:')) {
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
                    window.allowLeave = true;
                    resetDirty();
                    window.location.href = targetUrl;
                }
            });
        }
    });

    // Fallback for tab closing / page refresh
    window.addEventListener("beforeunload", function(event) {
        if (window.checkDirty() && !window.allowLeave) {
            event.preventDefault();
            event.returnValue = "You have unsaved changes. Are you sure you want to leave?";
        }
    });

    // Clear dirty flag when forms are submitted
    $('form').on('submit', function() {
        window.allowLeave = true;
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

// ==========================================
// MS WORD STYLE RICH TEXT EDITOR FOR T&C
// ==========================================
if (typeof Quill !== 'undefined') {
    try {
        const Size = Quill.import('attributors/style/size');
        Size.whitelist = ['6px', '7px', '8px', '9px', '10px', '11px', '12px', '14px', '16px', '18px', '20px', '24px'];
        Quill.register(Size, true);

        const Font = Quill.import('attributors/style/font');
        Font.whitelist = ['sans-serif', 'serif', 'monospace', 'helvetica', 'arial', 'times-new-roman'];
        Quill.register(Font, true);
    } catch (e) {
        console.warn('Quill custom size/font registration warning:', e);
    }
}

window.initTermsRichEditor = function(textareaId) {
    const textarea = typeof textareaId === 'string' ? document.getElementById(textareaId) : textareaId;
    if (!textarea || textarea.dataset.quillInitted) return;
    textarea.dataset.quillInitted = 'true';

    const editorId = 'quill_editor_' + (textarea.id || Math.random().toString(36).substr(2, 9));

    const editorDiv = document.createElement('div');
    editorDiv.id = editorId;
    editorDiv.className = 'bg-white rounded border';
    editorDiv.style.minHeight = '150px';
    editorDiv.style.fontSize = '12px';

    textarea.parentNode.insertBefore(editorDiv, textarea.nextSibling);
    textarea.style.display = 'none';

    if (typeof Quill === 'undefined') {
        textarea.style.display = 'block';
        return;
    }

    const quill = new Quill('#' + editorId, {
        theme: 'snow',
        modules: {
            toolbar: [
                [{ 'font': ['sans-serif', 'serif', 'monospace', 'helvetica', 'arial', 'times-new-roman'] }],
                [{ 'size': ['6px', '7px', '8px', '9px', '10px', '11px', '12px', '14px', '16px', '18px', '20px', '24px'] }],
                ['bold', 'italic', 'underline', 'strike'],
                [{ 'color': [] }, { 'background': [] }],
                [{ 'script': 'sub'}, { 'script': 'super' }],
                [{ 'align': [] }],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }, { 'indent': '-1'}, { 'indent': '+1' }],
                ['clean']
            ]
        }
    });

    if (textarea.value.trim()) {
        quill.clipboard.dangerouslyPasteHTML(textarea.value);
    }

    const syncToTextarea = function() {
        let html = quill.root.innerHTML;
        if (html === '<p><br></p>') html = '';
        textarea.value = html;
    };

    quill.on('text-change', syncToTextarea);

    const form = textarea.closest('form');
    if (form) {
        form.addEventListener('submit', syncToTextarea);
    }

    editorDiv.__quill = quill;
    return quill;
};

window.loadSampleTermsIntoEditor = function(textareaId) {
    const textarea = typeof textareaId === 'string' ? document.getElementById(textareaId) : textareaId;
    if (!textarea) return;
    const sampleHtml = `<p><span style="font-size: 11px;"><strong>1. ACCEPTANCE OF TERMS:</strong> Tendering this consignment for carriage constitutes acceptance of all terms &amp; conditions herein.</span></p>
<p><span style="font-size: 11px;"><strong>2. PACKAGING &amp; MARKING:</strong> The Shipper must ensure adequate packaging and legibly marked address &amp; telephone details.</span></p>
<p><span style="font-size: 11px;"><strong>3. PROHIBITED GOODS:</strong> Consignor declares consignment contains no contraband or prohibited items under state/central laws.</span></p>
<p><span style="font-size: 11px;"><strong>4. INSPECTION:</strong> Carrier reserves the right to inspect any consignment tendered for carriage.</span></p>
<p><span style="font-size: 11px;"><strong>5. FREIGHT &amp; CHARGES:</strong> Charges calculated on Chargeable Weight = max(Actual Weight, Volumetric Weight @ 6000 cm3/kg).</span></p>
<p><span style="font-size: 11px;"><strong>6. INSURANCE &amp; LIABILITY:</strong> High value shipments must be insured by shipper. Carrier liability for uninsured lost/damaged goods is limited to Rs. 100/-.</span></p>
<p><span style="font-size: 11px;"><strong>7. JURISDICTION:</strong> All disputes subject to Pune Jurisdiction only. E. &amp; O.E.</span></p>`;

    if (textarea.value.trim() !== '' && !confirm('Replace current terms with sample waybill T&C template?')) {
        return;
    }
    textarea.value = sampleHtml;
    const editorDiv = document.getElementById('quill_editor_' + textarea.id);
    if (editorDiv && editorDiv.__quill) {
        editorDiv.__quill.clipboard.dangerouslyPasteHTML(sampleHtml);
    }
};

$(document).ready(function() {
    $('textarea[name="docket_terms"], textarea[name="default_terms"], textarea[name="terms_conditions"]').each(function() {
        if (!this.id) {
            this.id = 'tc_editor_' + Math.random().toString(36).substr(2, 7);
        }
        initTermsRichEditor(this.id);
    });
});
