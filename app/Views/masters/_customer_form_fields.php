<?php
$c = $customer ?? [];
$customerRates = $customer_rates ?? [];
$customerRateHistory = $customer_rate_history ?? [];
$originOptions = $lookups['origin'] ?? [];
$destinationOptions = $lookups['destination'] ?? [];
?>

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
                        <label class="form-label text-muted fs-7 fw-semibold">Customer Name <span
                                class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control form-control-sm shadow-none fw-bold"
                            value="<?= esc($c['name'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-muted fs-7 fw-semibold">Code</label>
                        <input type="text" name="code" class="form-control form-control-sm shadow-none"
                            value="<?= esc($c['code'] ?? '') ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label text-muted fs-7 fw-semibold">Address</label>
                        <textarea name="address" class="form-control form-control-sm shadow-none"
                            rows="2"><?= esc($c['address'] ?? '') ?></textarea>
                    </div>
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center mb-1 flex-wrap gap-2">
                            <label class="form-label text-muted fs-7 fw-semibold mb-0">Default Terms &amp; Conditions
                                <small class="text-muted">(Auto-applies to all dockets for this
                                    customer)</small></label>
                            <button type="button" class="btn btn-outline-primary btn-sm py-0 px-2"
                                onclick="loadSampleTermsIntoEditor('customer_default_terms')"><i
                                    class="fas fa-file-alt me-1"></i> Sample T&amp;C</button>
                        </div>
                        <textarea name="default_terms" id="customer_default_terms"
                            class="form-control form-control-sm shadow-none font-monospace" rows="3"
                            placeholder="Enter default terms &amp; conditions for this customer..."><?= esc($c['default_terms'] ?? '') ?></textarea>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-muted fs-7 fw-semibold">City</label>
                        <input type="text" name="city" class="form-control form-control-sm shadow-none"
                            value="<?= esc($c['city'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-muted fs-7 fw-semibold">State</label>
                        <input type="text" name="state" class="form-control form-control-sm shadow-none"
                            value="<?= esc($c['state'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-muted fs-7 fw-semibold">Pincode</label>
                        <input type="text" name="pincode" class="form-control form-control-sm shadow-none"
                            value="<?= esc($c['pincode'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-muted fs-7 fw-semibold">Country</label>
                        <input type="text" name="country" class="form-control form-control-sm shadow-none"
                            value="<?= esc($c['country'] ?? '') ?>">
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
                        <input type="text" name="pan" class="form-control form-control-sm shadow-none text-uppercase"
                            value="<?= esc($c['pan'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-muted fs-7 fw-semibold">GST Number</label>
                        <input type="text" name="gst_number"
                            class="form-control form-control-sm shadow-none text-uppercase"
                            value="<?= esc($c['gst_number'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-muted fs-7 fw-semibold">GST State</label>
                        <input type="text" name="gst_state" class="form-control form-control-sm shadow-none"
                            value="<?= esc($c['gst_state'] ?? '') ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label text-muted fs-7 fw-semibold">TDS Percentage (%)</label>
                        <input type="number" step="0.01" name="tds_percentage"
                            class="form-control form-control-sm shadow-none"
                            value="<?= esc($c['tds_percentage'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-muted fs-7 fw-semibold">Payment Type</label>
                        <select name="payment_type" class="form-select form-select-sm shadow-none">
                            <option value="">--Select--</option>
                            <?php foreach ($lookups['payment_type'] ?? [] as $l): ?>
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

        <!-- Location Wise Item Rates -->
        <div class="card border-0 shadow-sm mb-4">
            <div
                class="card-header bg-white border-bottom-0 pt-4 pb-0 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold text-primary mb-0"><i class="fas fa-route me-2"></i> Location Wise Item Rates</h6>
                <button type="button" class="btn btn-sm btn-outline-primary add-customer-rate-row">
                    <i class="fas fa-plus me-1"></i> Add Rate
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-2 customer-rate-table">
                        <thead class="table-light">
                            <tr>
                                <th>Origin</th>
                                <th>Destination</th>
                                <th>Category</th>
                                <th>Item Rate</th>
                                <th>Effective From</th>
                                <th style="width: 44px;"></th>
                            </tr>
                        </thead>
                        <tbody class="customer-rate-rows">
                            <?php if (!empty($customerRates)): ?>
                                <?php foreach ($customerRates as $rate): ?>
                                    <tr>
                                        <td>
                                            <?php $selectedOrigin = $rate['origin'] ?? ''; ?>
                                            <select name="rate_origin[]" class="form-select form-select-sm shadow-none">
                                                <option value="">Origin</option>
                                                <?php if ($selectedOrigin !== '' && !in_array($selectedOrigin, array_column($originOptions, 'value'), true)): ?>
                                                    <option value="<?= esc($selectedOrigin) ?>" selected><?= esc($selectedOrigin) ?>
                                                    </option>
                                                <?php endif; ?>
                                                <?php foreach ($originOptions as $origin): ?>
                                                    <option value="<?= esc($origin['value']) ?>"
                                                        <?= $selectedOrigin === $origin['value'] ? 'selected' : '' ?>>
                                                        <?= esc($origin['value']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td>
                                            <?php $selectedDestination = $rate['destination'] ?? ''; ?>
                                            <select name="rate_destination[]" class="form-select form-select-sm shadow-none">
                                                <option value="">Destination</option>
                                                <?php if ($selectedDestination !== '' && !in_array($selectedDestination, array_column($destinationOptions, 'value'), true)): ?>
                                                    <option value="<?= esc($selectedDestination) ?>" selected>
                                                        <?= esc($selectedDestination) ?></option>
                                                <?php endif; ?>
                                                <?php foreach ($destinationOptions as $destination): ?>
                                                    <option value="<?= esc($destination['value']) ?>"
                                                        <?= $selectedDestination === $destination['value'] ? 'selected' : '' ?>>
                                                        <?= esc($destination['value']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td><input type="text" name="rate_material_category[]"
                                                class="form-control form-control-sm shadow-none"
                                                value="<?= esc($rate['material_category'] ?? '') ?>" placeholder="Optional">
                                        </td>
                                        <td><input type="number" step="0.01" min="0" name="rate_value[]"
                                                class="form-control form-control-sm shadow-none tabular-nums"
                                                value="<?= esc($rate['rate'] ?? '') ?>" placeholder="0.00"></td>
                                        <td><input type="date" name="rate_effective_from[]"
                                                class="form-control form-control-sm shadow-none"
                                                value="<?= esc($rate['effective_from'] ?? date('Y-m-d')) ?>"></td>
                                        <td><button type="button"
                                                class="btn btn-sm btn-outline-danger remove-customer-rate-row"><i
                                                    class="fas fa-trash"></i></button></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td>
                                        <select name="rate_origin[]" class="form-select form-select-sm shadow-none">
                                            <option value="">Origin</option>
                                            <?php foreach ($originOptions as $origin): ?>
                                                <option value="<?= esc($origin['value']) ?>"><?= esc($origin['value']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td>
                                        <select name="rate_destination[]" class="form-select form-select-sm shadow-none">
                                            <option value="">Destination</option>
                                            <?php foreach ($destinationOptions as $destination): ?>
                                                <option value="<?= esc($destination['value']) ?>">
                                                    <?= esc($destination['value']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td><input type="text" name="rate_material_category[]"
                                            class="form-control form-control-sm shadow-none" placeholder="Optional"></td>
                                    <td><input type="number" step="0.01" min="0" name="rate_value[]"
                                            class="form-control form-control-sm shadow-none tabular-nums"
                                            placeholder="0.00"></td>
                                    <td><input type="date" name="rate_effective_from[]"
                                            class="form-control form-control-sm shadow-none" value="<?= date('Y-m-d') ?>">
                                    </td>
                                    <td><button type="button"
                                            class="btn btn-sm btn-outline-danger remove-customer-rate-row"><i
                                                class="fas fa-trash"></i></button></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="text-muted fs-8">Leave a row blank to ignore it. Booking item entry uses the matching
                    customer + origin + destination rate.</div>
            </div>
        </div>

        <?php if (!empty($customerRateHistory)): ?>
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                    <h6 class="fw-bold text-secondary mb-0"><i class="fas fa-history me-2"></i> Rate History</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Origin</th>
                                    <th>Destination</th>
                                    <th>Category</th>
                                    <th>Item Rate</th>
                                    <th>Effective From</th>
                                    <th>Effective To</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($customerRateHistory as $historyRate): ?>
                                    <tr>
                                        <td><?= esc($historyRate['origin'] ?? '') ?></td>
                                        <td><?= esc($historyRate['destination'] ?? '') ?></td>
                                        <td><?= esc($historyRate['material_category'] ?: 'All categories') ?></td>
                                        <td class="tabular-nums"><?= number_format((float) ($historyRate['rate'] ?? 0), 2) ?>
                                        </td>
                                        <td><?= esc($historyRate['effective_from'] ?? '') ?></td>
                                        <td><?= esc($historyRate['effective_to'] ?? '') ?></td>
                                        <td><span class="badge bg-secondary-subtle text-secondary">Closed</span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-muted fs-8 mt-2">Closed versions are retained for audit and past-date rate lookup.
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Legacy / Generic Fields -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                <h6 class="fw-bold text-primary mb-0"><i class="fas fa-info-circle me-2"></i> Additional Information
                </h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-6">
                        <label class="form-label text-muted fs-7 fw-semibold">MIS Email IDs</label>
                        <textarea name="mis_email_ids" class="form-control form-control-sm shadow-none"
                            rows="2"><?= esc($c['mis_email_ids'] ?? '') ?></textarea>
                    </div>
                    <div class="col-6">
                        <label class="form-label text-muted fs-7 fw-semibold">MIS CC Email IDs</label>
                        <textarea name="mis_cc_email_ids" class="form-control form-control-sm shadow-none"
                            rows="2"><?= esc($c['mis_cc_email_ids'] ?? '') ?></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted fs-7 fw-semibold">Other 1</label>
                        <input type="text" name="other_1" class="form-control form-control-sm shadow-none"
                            value="<?= esc($c['other_1'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted fs-7 fw-semibold">Other 2</label>
                        <input type="text" name="other_2" class="form-control form-control-sm shadow-none"
                            value="<?= esc($c['other_2'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted fs-7 fw-semibold">Other 3</label>
                        <input type="text" name="other_3" class="form-control form-control-sm shadow-none"
                            value="<?= esc($c['other_3'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted fs-7 fw-semibold">Other 4</label>
                        <input type="text" name="other_4" class="form-control form-control-sm shadow-none"
                            value="<?= esc($c['other_4'] ?? '') ?>">
                    </div>

                    <div class="col-12 mt-4">
                        <h6 class="text-muted border-bottom pb-2 mb-3">Legacy Addresses</h6>
                    </div>
                    <div class="col-12">
                        <label class="form-label text-muted fs-7 fw-semibold">Bill To <small>(Billing
                                Address)</small></label>
                        <textarea name="bill_to" class="form-control form-control-sm shadow-none"
                            rows="2"><?= esc($c['bill_to'] ?? '') ?></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label text-muted fs-7 fw-semibold">Consignee <small>(Default Delivery
                                Address)</small></label>
                        <textarea name="consignee" class="form-control form-control-sm shadow-none"
                            rows="2"><?= esc($c['consignee'] ?? '') ?></textarea>
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
                        'purchase' => 'Purchase',
                        'sales' => 'Sales',
                        'plant_head' => 'Plant Head',
                        'billing' => 'Billing'
                    ];
                    $i = 0;
                    foreach ($roles as $prefix => $label):
                        ?>
                        <div class="accordion-item border-0 mb-2 shadow-sm rounded">
                            <h2 class="accordion-header" id="heading_<?= $prefix ?>">
                                <button
                                    class="accordion-button <?= $i > 0 ? 'collapsed' : '' ?> fw-semibold bg-light text-dark rounded"
                                    type="button" data-bs-toggle="collapse" data-bs-target="#collapse_<?= $prefix ?>"
                                    aria-expanded="<?= $i === 0 ? 'true' : 'false' ?>">
                                    <i class="fas fa-user-circle text-muted me-2"></i> <?= $label ?> Contact
                                </button>
                            </h2>
                            <div id="collapse_<?= $prefix ?>"
                                class="accordion-collapse collapse <?= $i === 0 ? 'show' : '' ?>"
                                data-bs-parent="#contactsAccordion">
                                <div class="accordion-body bg-white border border-top-0 rounded-bottom">
                                    <div class="mb-3">
                                        <label class="form-label text-muted fs-8 fw-semibold mb-1">Name</label>
                                        <input type="text" name="<?= $prefix ?>_contact_name"
                                            class="form-control form-control-sm shadow-none"
                                            value="<?= esc($c["{$prefix}_contact_name"] ?? '') ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label text-muted fs-8 fw-semibold mb-1">Number</label>
                                        <input type="text" name="<?= $prefix ?>_contact_number"
                                            class="form-control form-control-sm shadow-none"
                                            value="<?= esc($c["{$prefix}_contact_number"] ?? '') ?>">
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label text-muted fs-8 fw-semibold mb-1">Email ID</label>
                                        <input type="email" name="<?= $prefix ?>_contact_email"
                                            class="form-control form-control-sm shadow-none"
                                            value="<?= esc($c["{$prefix}_contact_email"] ?? '') ?>">
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
                                <button class="accordion-button collapsed fw-semibold bg-light text-dark rounded"
                                    type="button" data-bs-toggle="collapse" data-bs-target="#collapse_legacy_<?= $p ?>">
                                    <i class="fas fa-user-circle text-muted me-2"></i> Generic Contact <?= $p ?>
                                </button>
                            </h2>
                            <div id="collapse_legacy_<?= $p ?>" class="accordion-collapse collapse"
                                data-bs-parent="#contactsAccordion">
                                <div class="accordion-body bg-white border border-top-0 rounded-bottom">
                                    <div class="mb-3">
                                        <label class="form-label text-muted fs-8 fw-semibold mb-1">Name</label>
                                        <input type="text" name="person<?= $p ?>_name"
                                            class="form-control form-control-sm shadow-none"
                                            value="<?= esc($c["person{$p}_name"] ?? '') ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label text-muted fs-8 fw-semibold mb-1">Phone</label>
                                        <input type="text" name="person<?= $p ?>_phone"
                                            class="form-control form-control-sm shadow-none"
                                            value="<?= esc($c["person{$p}_phone"] ?? '') ?>">
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label text-muted fs-8 fw-semibold mb-1">Email</label>
                                        <input type="email" name="person<?= $p ?>_email"
                                            class="form-control form-control-sm shadow-none"
                                            value="<?= esc($c["person{$p}_email"] ?? '') ?>">
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

<template id="customerRateRowTemplate">
    <tr>
        <td>
            <select name="rate_origin[]" class="form-select form-select-sm shadow-none">
                <option value="">Origin</option>
                <?php foreach ($originOptions as $origin): ?>
                    <option value="<?= esc($origin['value']) ?>"><?= esc($origin['value']) ?></option>
                <?php endforeach; ?>
            </select>
        </td>
        <td>
            <select name="rate_destination[]" class="form-select form-select-sm shadow-none">
                <option value="">Destination</option>
                <?php foreach ($destinationOptions as $destination): ?>
                    <option value="<?= esc($destination['value']) ?>"><?= esc($destination['value']) ?></option>
                <?php endforeach; ?>
            </select>
        </td>
        <td><input type="text" name="rate_material_category[]" class="form-control form-control-sm shadow-none"
                placeholder="Optional"></td>
        <td><input type="number" step="0.01" min="0" name="rate_value[]"
                class="form-control form-control-sm shadow-none tabular-nums" placeholder="0.00"></td>
        <td><input type="date" name="rate_effective_from[]" class="form-control form-control-sm shadow-none"
                value="<?= date('Y-m-d') ?>"></td>
        <td><button type="button" class="btn btn-sm btn-outline-danger remove-customer-rate-row"><i
                    class="fas fa-trash"></i></button></td>
    </tr>
</template>

<script>
    document.addEventListener('click', function (event) {
        const addBtn = event.target.closest('.add-customer-rate-row');
        if (addBtn) {
            const table = addBtn.closest('.card').querySelector('.customer-rate-rows');
            const template = document.getElementById('customerRateRowTemplate');
            if (table && template) {
                table.insertAdjacentHTML('beforeend', template.innerHTML.trim());
            }
        }

        const removeBtn = event.target.closest('.remove-customer-rate-row');
        if (removeBtn) {
            const row = removeBtn.closest('tr');
            if (row) row.remove();
        }
    });

    function applyTermsFontSize(targetId, fontSize) {
        const el = document.getElementById(targetId);
        if (!el) return;
        const start = el.selectionStart;
        const end = el.selectionEnd;
        const selected = el.value.substring(start, end);
        if (selected) {
            const replacement = `<span style="font-size:${fontSize};">${selected}</span>`;
            el.value = el.value.substring(0, start) + replacement + el.value.substring(end);
        } else {
            el.value += (el.value ? '\n' : '') + `<span style="font-size:${fontSize};">Sample text in ${fontSize}</span>`;
        }
    }

    function insertSampleWaybillTerms(targetId) {
        const el = document.getElementById(targetId);
        if (!el) return;
        const sampleTerms = ``;

        if (el.value.trim() !== '' && !confirm('Replace current terms with sample waybill T&C template?')) {
            return;
        }
        el.value = sampleTerms;
    }
</script>