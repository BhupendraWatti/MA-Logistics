<!-- Tracking & POD Drawer (Offcanvas) -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="trackingDrawer" aria-labelledby="trackingDrawerLabel" style="width: 800px;">
    <div class="offcanvas-header bg-light border-bottom">
        <h5 class="offcanvas-title fw-bold text-primary" id="trackingDrawerLabel">
            <i class="fa-solid fa-location-dot me-2"></i> Tracking & POD Management
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    
    <div class="offcanvas-body p-4 bg-white" style="overflow-y: auto;">
        
        <!-- Add / Update Location Form -->
        <div class="card shadow-sm border-0 mb-4 rounded-3">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold text-dark"><i class="fa-solid fa-plus-circle me-2 text-primary"></i>Manual Courier Tracking - Add / Update Location</h6>
            </div>
            <div class="card-body bg-light">
                <form id="trackingForm" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" id="track_id">
                    <input type="hidden" name="booking_id" id="track_booking_id">
                    
                    <div class="row g-3 mb-3">
                        <div class="col-md-12">
                            <label class="form-label fw-medium small text-muted mb-1">Tracking / AWB Number <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" name="awb_no" id="track_awb_no" readonly>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-medium small text-muted mb-1">Current Location <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" name="current_location" id="track_current_location" placeholder="e.g., Delhi Hub" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-medium small text-muted mb-1">Status <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm" name="status" id="track_status" required>
                                <option value="">Select Status</option>
                                <option value="Booked">Booked</option>
                                <option value="Picked Up">Picked Up</option>
                                <option value="In Transit">In Transit</option>
                                <option value="Arrived at Hub">Arrived at Hub</option>
                                <option value="Out for Delivery">Out for Delivery</option>
                                <option value="Delivered">Delivered</option>
                                <option value="Failed Delivery">Failed Delivery</option>
                                <option value="Returned">Returned</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-medium small text-muted mb-1">Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control form-control-sm" name="event_date" id="track_event_date" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-medium small text-muted mb-1">Time <span class="text-danger">*</span></label>
                            <input type="time" class="form-control form-control-sm" name="event_time" id="track_event_time" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-medium small text-muted mb-1">Details / Remarks</label>
                            <textarea class="form-control form-control-sm" name="remarks" id="track_remarks" rows="3" placeholder="Enter details about this update..."></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium small text-muted mb-1">Upload Proof (optional)</label>
                            <div class="border border-dashed rounded p-3 text-center position-relative bg-white" style="cursor: pointer; border-color: #cbd5e1 !important;" onclick="document.getElementById('track_proof_image').click()">
                                <input type="file" name="proof_image" id="track_proof_image" class="d-none" accept="image/*,application/pdf">
                                <div class="text-primary mb-1">
                                    <i class="fa-solid fa-cloud-arrow-up fs-5"></i>
                                </div>
                                <div class="small text-muted" id="proofFileName">Click to upload image (e.g., POD signature)</div>
                                <img id="proofImagePreview" src="" alt="Preview" class="img-thumbnail mt-2" style="display: none; max-height: 100px; margin: 0 auto;">
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                        <button type="button" class="btn btn-sm btn-outline-secondary px-4 fw-medium" id="resetTrackingBtn">Reset</button>
                        <button type="submit" class="btn btn-sm btn-primary px-4 fw-bold shadow-sm" id="saveTrackingBtn">Save Update</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tracking History DataTable -->
        <div class="card shadow-sm border-0 rounded-3">
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold text-dark"><i class="fa-solid fa-clock-rotate-left me-2 text-primary"></i>Shipment Tracking History</h6>
                <span class="badge bg-light text-dark border px-3 py-2 fw-bold" id="historyAwbLabel">AWB: -</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 w-100" id="trackingHistoryTable">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3" style="width: 5%">#</th>
                                <th style="width: 15%">Date</th>
                                <th style="width: 10%">Time</th>
                                <th style="width: 20%">Location</th>
                                <th style="width: 15%">Status</th>
                                <th style="width: 25%">Remarks</th>
                                <th class="pe-3 text-end" style="width: 10%">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Data populated via AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<?php $this->section('scripts') ?>
<script>
let trackingDataTable;

$(document).ready(function() {
    // Initialize DataTable
    trackingDataTable = $('#trackingHistoryTable').DataTable({
        paging: false,
        searching: false,
        info: false,
        ordering: false,
        language: {
            emptyTable: "No tracking history available for this shipment."
        }
    });

    // Handle file input change to show filename
    $('#track_proof_image').on('change', function() {
        const file = this.files[0];
        if (file) {
            $('#proofFileName').text(file.name);
            if (file.type.startsWith('image/')) {
                let reader = new FileReader();
                reader.onload = function(e) {
                    $('#proofImagePreview').attr('src', e.target.result).show();
                }
                reader.readAsDataURL(file);
            } else {
                $('#proofImagePreview').hide().attr('src', '');
            }
        } else {
            $('#proofFileName').text('Click to upload image (e.g., POD signature)');
            $('#proofImagePreview').hide().attr('src', '');
        }
    });

    // Reset Form (preserves Booking ID and AWB)
    $('#resetTrackingBtn').on('click', function() {
        let currentBookingId = $('#track_booking_id').val();
        let currentAwb = $('#track_awb_no').val();
        
        $('#trackingForm')[0].reset();
        
        $('#track_booking_id').val(currentBookingId);
        $('#track_awb_no').val(currentAwb);
        $('#track_id').val('');
        
        $('#proofFileName').text('Click to upload image (e.g., POD signature)');
        $('#proofImagePreview').hide().attr('src', '');
        
        // Set current date/time
        const now = new Date();
        $('#track_event_date').val(now.toISOString().split('T')[0]);
        $('#track_event_time').val(now.toTimeString().split(' ')[0].substring(0, 5));
    });

    // Form Submit
    $('#trackingForm').on('submit', function(e) {
        e.preventDefault();
        
        let formData = new FormData(this);
        let saveBtn = $('#saveTrackingBtn');
        let originalText = saveBtn.text();
        
        saveBtn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Saving...');
        
        $.ajax({
            url: '<?= base_url("tracking/save") ?>',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if(response.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Saved!',
                        text: response.message,
                        timer: 1500,
                        showConfirmButton: false
                    });
                    // Reload history
                    loadTrackingHistory($('#track_booking_id').val());
                    
                    // Reload the main bookings datatable if it exists on the current page
                    if (typeof dataTable !== 'undefined' && dataTable !== null && typeof dataTable.ajax !== 'undefined') {
                        dataTable.ajax.reload(null, false);
                    }
                    
                    // Reset form fields but keep AWB/Booking ID
                    $('#resetTrackingBtn').click();
                } else {
                    Swal.fire('Error', response.message || 'Something went wrong', 'error');
                }
            },
            error: function() {
                Swal.fire('Error', 'Server error occurred', 'error');
            },
            complete: function() {
                saveBtn.prop('disabled', false).text(originalText);
            }
        });
    });
});

// Function called when location icon is clicked on dashboard
function openTrackingDrawer(bookingId, awbNo, customerName) {
    // Reset form
    $('#resetTrackingBtn').click();
    
    // Set IDs
    $('#track_booking_id').val(bookingId);
    $('#track_awb_no').val(awbNo);
    $('#historyAwbLabel').text('AWB: ' + awbNo);
    
    // Open drawer
    var bsOffcanvas = new bootstrap.Offcanvas(document.getElementById('trackingDrawer'));
    bsOffcanvas.show();
    
    // Load data
    loadTrackingHistory(bookingId);
}

function loadTrackingHistory(bookingId) {
    trackingDataTable.clear().draw();
    
    $.ajax({
        url: '<?= base_url("tracking/history/") ?>' + bookingId,
        type: 'GET',
        cache: false, // Prevent aggressive browser/LiteSpeed caching
        data: {
            _: new Date().getTime() // Cache-busting parameter
        },
        success: function(response) {
            if(response.status === 'success' && response.data) {
                // Save to window globally to bypass HTML quoting crash with single quotes in remarks
                window.trackingHistoryData = response.data;
                
                let rows = [];
                response.data.forEach((item, index) => {
                    // Status Badge Logic
                    let bg = 'bg-secondary';
                    if(item.status === 'Delivered') bg = 'bg-success';
                    else if(item.status === 'In Transit') bg = 'bg-warning text-dark';
                    else if(item.status === 'Picked Up') bg = 'bg-info text-dark';
                    else if(item.status === 'Booked') bg = 'bg-primary';
                    
                    let badge = `<span class="badge ${bg}">${item.status}</span>`;
                    
                    let proofLink = item.proof_image ? `<a href="<?= base_url() ?>${item.proof_image}" target="_blank" class="ms-2 text-primary" title="View Proof"><i class="fa-solid fa-image"></i></a>` : '';
                    
                    let displayTime = item.event_time;
                    if (displayTime && displayTime.length > 5) {
                        displayTime = displayTime.substring(0, 5); // Show HH:mm
                    }
                    
                    let actionBtns = `
                        <button type="button" class="btn btn-sm btn-light text-primary border me-1" onclick="editTrackingByIndex(${index})" title="Edit">
                            <i class="fa-solid fa-pen"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-light text-danger border" onclick="deleteTracking(${item.id}, ${bookingId})" title="Delete">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    `;

                    rows.push([
                        `<span class="text-muted">${index + 1}</span>`,
                        `<span class="fw-medium">${item.event_date}</span>`,
                        displayTime,
                        `<span class="fw-bold text-dark"><i class="fa-solid fa-location-dot me-1 text-primary"></i>${item.current_location}</span>`,
                        badge,
                        (item.remarks || '-') + proofLink,
                        actionBtns
                    ]);
                });
                trackingDataTable.rows.add(rows).draw();
            }
        }
    });
}

function editTrackingByIndex(index) {
    if (window.trackingHistoryData && window.trackingHistoryData[index]) {
        editTracking(window.trackingHistoryData[index]);
    }
}

function editTracking(item) {
    $('#track_id').val(item.id);
    $('#track_current_location').val(item.current_location);
    $('#track_status').val(item.status);
    $('#track_event_date').val(item.event_date);
    
    // Convert "HH:mm:ss" to "HH:mm" for the time input field to work properly
    let timeVal = item.event_time;
    if (timeVal && timeVal.length > 5) {
        timeVal = timeVal.substring(0, 5);
    }
    $('#track_event_time').val(timeVal);
    
    // When editing, show existing preview if it exists
    if (item.proof_image) {
        $('#proofImagePreview').attr('src', '<?= base_url() ?>' + item.proof_image).show();
        $('#proofFileName').html('Existing proof loaded.<br><small class="text-primary">Click to upload new to replace</small>');
    } else {
        $('#proofImagePreview').hide().attr('src', '');
        $('#proofFileName').text('Click to upload image (e.g., POD signature)');
    }
}

function deleteTracking(id, bookingId) {
    Swal.fire({
        title: 'Delete this tracking record?',
        text: "This action cannot be undone!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '<?= base_url("tracking/delete/") ?>' + id,
                type: 'POST',
                data: {
                    '<?= csrf_token() ?>': '<?= csrf_hash() ?>'
                },
                success: function(response) {
                    if(response.status === 'success') {
                        Swal.fire('Deleted!', 'Record removed.', 'success');
                        loadTrackingHistory(bookingId);
                        
                        // Reload the main bookings datatable if it exists on the current page
                        if (typeof dataTable !== 'undefined' && dataTable !== null && typeof dataTable.ajax !== 'undefined') {
                            dataTable.ajax.reload(null, false);
                        }
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                }
            });
        }
    });
}
</script>

<?php $this->endSection() ?>
