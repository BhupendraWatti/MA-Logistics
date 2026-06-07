<?php $c = $customer ?? []; ?>

<div class="row g-4">
    <!-- LEFT COLUMN: Customer & FINANCE DETAILS -->
    <div class="col-lg-7">
        
        <!-- General Info -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                <h6 class="fw-bold text-primary mb-0"><i class="fas fa-building me-2"></i> Customer Details</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label text-muted fs-7 fw-semibold">Customer Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control form-control-sm shadow-none fw-bold" value="<?= esc($c['name'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-muted fs-7 fw-semibold">Code</label>
                        <input type="text" name="code" class="form-control form-control-sm shadow-none" value="<?= esc($c['code'] ?? '') ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label text-muted fs-7 fw-semibold">Address</label>
                        <textarea name="address" class="form-control form-control-sm shadow-none" rows="2"><?= esc($c['address'] ?? '') ?></textarea>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-muted fs-7 fw-semibold">City</label>
                        <input type="text" name="city" class="form-control form-control-sm shadow-none city-field" value="<?= esc($c['city'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-muted fs-7 fw-semibold">State</label>
                        <input type="text" name="state" class="form-control form-control-sm shadow-none state-field" value="<?= esc($c['state'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-muted fs-7 fw-semibold">Pincode</label>
                        <input type="text" name="pincode" class="form-control form-control-sm shadow-none pincode-lookup" value="<?= esc($c['pincode'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-muted fs-7 fw-semibold">Country</label>
                        <input type="text" name="country" class="form-control form-control-sm shadow-none country-field" value="<?= esc($c['country'] ?? '') ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- Tax & Finance Info -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                <h6 class="fw-bold text-primary mb-0"><i class="fas fa-file-invoice-dollar me-2"></i> Tax & Finance</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label text-muted fs-7 fw-semibold">PAN Number</label>
                        <input type="text" name="pan" class="form-control form-control-sm shadow-none text-uppercase" value="<?= esc($c['pan'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-muted fs-7 fw-semibold">GST Number</label>
                        <input type="text" name="gst_number" class="form-control form-control-sm shadow-none text-uppercase" value="<?= esc($c['gst_number'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-muted fs-7 fw-semibold">GST State</label>
                        <input type="text" name="gst_state" class="form-control form-control-sm shadow-none" value="<?= esc($c['gst_state'] ?? '') ?>">
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label text-muted fs-7 fw-semibold">TDS Percentage (%)</label>
                        <input type="number" step="0.01" name="tds_percentage" class="form-control form-control-sm shadow-none" value="<?= esc($c['tds_percentage'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-muted fs-7 fw-semibold">Payment Type</label>
                        <select name="payment_type" class="form-select form-select-sm shadow-none">
                            <option value="">--Select--</option>
                            <?php foreach($lookups['payment_type'] ?? [] as $l): ?>
                                <option value="<?= esc($l['value']) ?>" <?= ($c['payment_type'] ?? '') == $l['value'] ? 'selected' : '' ?>><?= esc($l['value']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-muted fs-7 fw-semibold">Currency</label>
                        <select name="currency" class="form-select form-select-sm shadow-none">
                            <option value="INR" <?= ($c['currency'] ?? '') == 'INR' ? 'selected' : '' ?>>INR</option>
                            <option value="USD" <?= ($c['currency'] ?? '') == 'USD' ? 'selected' : '' ?>>USD</option>
                            <option value="EUR" <?= ($c['currency'] ?? '') == 'EUR' ? 'selected' : '' ?>>EUR</option>
                            <option value="GBP" <?= ($c['currency'] ?? '') == 'GBP' ? 'selected' : '' ?>>GBP</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Legacy / Generic Fields -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                <h6 class="fw-bold text-primary mb-0"><i class="fas fa-info-circle me-2"></i> Additional Information</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-6">
                        <label class="form-label text-muted fs-7 fw-semibold">MIS Email IDs</label>
                        <textarea name="mis_email_ids" class="form-control form-control-sm shadow-none" rows="2"><?= esc($c['mis_email_ids'] ?? '') ?></textarea>
                    </div>
                    <div class="col-6">
                        <label class="form-label text-muted fs-7 fw-semibold">MIS CC Email IDs</label>
                        <textarea name="mis_cc_email_ids" class="form-control form-control-sm shadow-none" rows="2"><?= esc($c['mis_cc_email_ids'] ?? '') ?></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted fs-7 fw-semibold">Other 1</label>
                        <input type="text" name="other_1" class="form-control form-control-sm shadow-none" value="<?= esc($c['other_1'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted fs-7 fw-semibold">Other 2</label>
                        <input type="text" name="other_2" class="form-control form-control-sm shadow-none" value="<?= esc($c['other_2'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted fs-7 fw-semibold">Other 3</label>
                        <input type="text" name="other_3" class="form-control form-control-sm shadow-none" value="<?= esc($c['other_3'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted fs-7 fw-semibold">Other 4</label>
                        <input type="text" name="other_4" class="form-control form-control-sm shadow-none" value="<?= esc($c['other_4'] ?? '') ?>">
                    </div>
                    
                    <div class="col-12 mt-4">
                        <h6 class="text-muted border-bottom pb-2 mb-3">Legacy Addresses</h6>
                    </div>
                    <div class="col-12">
                        <label class="form-label text-muted fs-7 fw-semibold">Bill To <small>(Billing Address)</small></label>
                        <textarea name="bill_to" class="form-control form-control-sm shadow-none" rows="2"><?= esc($c['bill_to'] ?? '') ?></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label text-muted fs-7 fw-semibold">Consignee <small>(Default Delivery Address)</small></label>
                        <textarea name="consignee" class="form-control form-control-sm shadow-none" rows="2"><?= esc($c['consignee'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- RIGHT COLUMN: CONTACT PERSONS -->
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                <h6 class="fw-bold text-primary mb-0"><i class="fas fa-users me-2"></i> Contact Persons</h6>
            </div>
            <div class="card-body">
                <div class="accordion" id="contactsAccordion">
                    
                    <?php 
                    $roles = [
                        'operation' => 'Operations',
                        'purchase'  => 'Purchase',
                        'sales'     => 'Sales',
                        'plant_head'=> 'Plant Head',
                        'billing'   => 'Billing'
                    ];
                    $i = 0;
                    foreach ($roles as $prefix => $label): 
                    ?>
                        <div class="accordion-item border-0 mb-2 shadow-sm rounded">
                            <h2 class="accordion-header" id="heading_<?= $prefix ?>">
                                <button class="accordion-button <?= $i > 0 ? 'collapsed' : '' ?> fw-semibold bg-light text-dark rounded" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_<?= $prefix ?>" aria-expanded="<?= $i === 0 ? 'true' : 'false' ?>">
                                    <i class="fas fa-user-circle text-muted me-2"></i> <?= $label ?> Contact
                                </button>
                            </h2>
                            <div id="collapse_<?= $prefix ?>" class="accordion-collapse collapse <?= $i === 0 ? 'show' : '' ?>" data-bs-parent="#contactsAccordion">
                                <div class="accordion-body bg-white border border-top-0 rounded-bottom">
                                    <div class="mb-3">
                                        <label class="form-label text-muted fs-8 fw-semibold mb-1">Name</label>
                                        <input type="text" name="<?= $prefix ?>_contact_name" class="form-control form-control-sm shadow-none" value="<?= esc($c["{$prefix}_contact_name"] ?? '') ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label text-muted fs-8 fw-semibold mb-1">Number</label>
                                        <input type="text" name="<?= $prefix ?>_contact_number" class="form-control form-control-sm shadow-none" value="<?= esc($c["{$prefix}_contact_number"] ?? '') ?>">
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label text-muted fs-8 fw-semibold mb-1">Email ID</label>
                                        <input type="email" name="<?= $prefix ?>_contact_email" class="form-control form-control-sm shadow-none" value="<?= esc($c["{$prefix}_contact_email"] ?? '') ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php $i++; endforeach; ?>
                    
                    <!-- Legacy Generic Contacts -->
                    <h6 class="fw-bold text-muted mt-4 mb-2">Legacy Contacts</h6>
                    <?php foreach ([1, 2, 3] as $p): ?>
                        <div class="accordion-item border-0 mb-2 shadow-sm rounded">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed fw-semibold bg-light text-dark rounded" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_legacy_<?= $p ?>">
                                    <i class="fas fa-user-circle text-muted me-2"></i> Generic Contact <?= $p ?>
                                </button>
                            </h2>
                            <div id="collapse_legacy_<?= $p ?>" class="accordion-collapse collapse" data-bs-parent="#contactsAccordion">
                                <div class="accordion-body bg-white border border-top-0 rounded-bottom">
                                    <div class="mb-3">
                                        <label class="form-label text-muted fs-8 fw-semibold mb-1">Name</label>
                                        <input type="text" name="person<?= $p ?>_name" class="form-control form-control-sm shadow-none" value="<?= esc($c["person{$p}_name"] ?? '') ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label text-muted fs-8 fw-semibold mb-1">Phone</label>
                                        <input type="text" name="person<?= $p ?>_phone" class="form-control form-control-sm shadow-none" value="<?= esc($c["person{$p}_phone"] ?? '') ?>">
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label text-muted fs-8 fw-semibold mb-1">Email</label>
                                        <input type="email" name="person<?= $p ?>_email" class="form-control form-control-sm shadow-none" value="<?= esc($c["person{$p}_email"] ?? '') ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Dynamic Google Maps / Postal Pincode Auto-Fill Integration -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    $(document).ready(function() {
        var apiKey = <?= json_encode($google_maps_api_key ?? '') ?>;
        
        // 1. Initialize Google Maps Autocomplete
        function initGoogleAutocomplete() {
            if (typeof google !== 'undefined' && google.maps && google.maps.places) {
                $('.pincode-lookup').each(function() {
                    var inputEl = this;
                    
                    // Prevent double binding if initialized multiple times
                    if ($(inputEl).data('autocomplete-bound')) return;
                    $(inputEl).data('autocomplete-bound', true);
                    
                    var autocomplete = new google.maps.places.Autocomplete(inputEl, {
                        types: ['(regions)'],
                        componentRestrictions: { country: 'in' }
                    });
                    
                    autocomplete.addListener('place_changed', function() {
                        var place = autocomplete.getPlace();
                        if (place && place.address_components) {
                            var city = '', state = '', country = '';
                            var zip = '';
                            
                            for (var i = 0; i < place.address_components.length; i++) {
                                var component = place.address_components[i];
                                var types = component.types;
                                
                                if (types.includes('locality') || types.includes('administrative_area_level_2')) {
                                    city = component.long_name;
                                }
                                if (types.includes('administrative_area_level_1')) {
                                    state = component.long_name;
                                }
                                if (types.includes('country')) {
                                    country = component.long_name;
                                }
                                if (types.includes('postal_code')) {
                                    zip = component.long_name;
                                }
                            }
                            
                            var row = $(inputEl).closest('.row');
                            if (city) row.find('.city-field').val(city);
                            if (state) row.find('.state-field').val(state);
                            if (country) row.find('.country-field').val(country);
                            if (zip) $(inputEl).val(zip);
                            
                            row.find('.city-field, .state-field, .country-field').trigger('change');
                        }
                    });
                });
            }
        }
        
        // Load Google Maps API Key dynamically if configured
        if (apiKey) {
            if (typeof google === 'undefined' || typeof google.maps === 'undefined') {
                var script = document.createElement('script');
                script.src = 'https://maps.googleapis.com/maps/api/js?key=' + encodeURIComponent(apiKey) + '&libraries=places';
                script.async = true;
                script.defer = true;
                script.onload = function() {
                    initGoogleAutocomplete();
                };
                document.head.appendChild(script);
            } else {
                initGoogleAutocomplete();
            }
        }
        
        // 2. Fetch via Postal Pincode Directory API
        function fetchPostalPincode(pincode, $input) {
            var url = 'https://api.postalpincode.in/pincode/' + pincode;
            var row = $input.closest('.row');
            
            // Show subtle loading state
            var fields = row.find('.city-field, .state-field, .country-field');
            fields.addClass('bg-light').prop('readonly', true);
            
            $.ajax({
                url: url,
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    fields.removeClass('bg-light').prop('readonly', false);
                    if (response && response[0] && response[0].Status === 'Success' && response[0].PostOffice && response[0].PostOffice.length > 0) {
                        var postOffice = response[0].PostOffice[0];
                        var district = postOffice.District;
                        var state = postOffice.State;
                        var country = 'India';
                        
                        var city = postOffice.Block && postOffice.Block !== 'NA' ? postOffice.Block : district;
                        
                        if (city) row.find('.city-field').val(city);
                        if (state) row.find('.state-field').val(state);
                        row.find('.country-field').val(country);
                        
                        row.find('.city-field, .state-field, .country-field').trigger('change');
                    }
                },
                error: function() {
                    fields.removeClass('bg-light').prop('readonly', false);
                }
            });
        }
        
        // 3. Fallback and direct entry keyup/blur listener
        $(document).on('change keyup blur', '.pincode-lookup', function(e) {
            var pincode = $(this).val().trim();
            var $this = $(this);
            
            // Match Indian 6-digit postal code pattern
            if (/^[1-9][0-9]{5}$/.test(pincode)) {
                if ($this.data('last-pincode') === pincode) {
                    return; // Avoid multiple calls
                }
                $this.data('last-pincode', pincode);
                
                if (typeof google !== 'undefined' && google.maps && google.maps.Geocoder) {
                    var geocoder = new google.maps.Geocoder();
                    geocoder.geocode({ address: pincode, componentRestrictions: { country: 'in' } }, function(results, status) {
                        if (status === 'OK' && results[0]) {
                            var city = '', state = '', country = '';
                            var components = results[0].address_components;
                            for (var i = 0; i < components.length; i++) {
                                var componentTypes = components[i].types;
                                if (componentTypes.includes('locality') || componentTypes.includes('administrative_area_level_2')) {
                                    city = components[i].long_name;
                                }
                                if (componentTypes.includes('administrative_area_level_1')) {
                                    state = components[i].long_name;
                                }
                                if (componentTypes.includes('country')) {
                                    country = components[i].long_name;
                                }
                            }
                            var row = $this.closest('.row');
                            if (city) row.find('.city-field').val(city);
                            if (state) row.find('.state-field').val(state);
                            if (country) row.find('.country-field').val(country);
                            
                            row.find('.city-field, .state-field, .country-field').trigger('change');
                        } else {
                            fetchPostalPincode(pincode, $this);
                        }
                    });
                } else {
                    fetchPostalPincode(pincode, $this);
                }
            }
        });
    });
});
</script>
