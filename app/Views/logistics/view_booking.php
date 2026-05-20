<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-eye"></i> AWB Details: <?= esc($booking['awb_no']) ?></h2>
        <div>
            <a href="<?= base_url('logistics') ?>" class="btn btn-secondary me-2">
                <i class="fas fa-arrow-left"></i> Back
            </a>
            <?php if ((session()->get('permissions')['can_edit'] ?? 0) == 1): ?>
                <a href="<?= base_url('logistics/edit/' . $booking['id']) ?>" class="btn btn-primary">
                    <i class="fas fa-edit"></i> Edit
                </a>
            <?php endif; ?>
            <!-- Add after Edit button -->
            <a href="<?= base_url('logistics/exportPdf/' . $booking['id']) ?>" class="btn btn-danger">
                <i class="fas fa-file-pdf"></i> Export PDF
            </a>
        </div>
    </div>

    <!-- Basic Info Card -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5><i class="fas fa-file-alt"></i> Basic Booking Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3"><strong>AWB No:</strong> <?= esc($booking['awb_no']) ?></div>
                        <div class="col-md-2"><strong>Date:</strong> <?= date('d-M-Y H:i', strtotime($booking['booking_date'])) ?></div>
                        <div class="col-md-2"><strong>Company:</strong> <?= esc($booking['company_name']) ?></div>
                        <div class="col-md-2"><strong>Status:</strong> 
                            <span class="badge bg-<?= $booking['status'] == 'Delivered' ? 'success' : 
                                ($booking['status'] == 'In-Transit' ? 'warning' : 'secondary') ?>">
                                <?= ucfirst($booking['status']) ?>
                            </span>
                        </div>
                        <div class="col-md-3"><strong>Created By:</strong> <?= esc($booking['created_by_name']) ?></div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-2"><strong>Origin:</strong> <?= esc($booking['origin']) ?></div>
                        <div class="col-md-2"><strong>Destination:</strong> <?= esc($booking['destination']) ?></div>
                        <div class="col-md-2"><strong>Mode:</strong> <?= esc($booking['mode_transport']) ?></div>
                        <div class="col-md-2"><strong>Material:</strong> <?= esc($booking['material_type']) ?></div>
                        <div class="col-md-2"><strong>Total Pieces:</strong> <?= $booking['total_pieces'] ?></div>
                        <div class="col-md-2"><strong>Flight:</strong> <?= esc($booking['flight_number']) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Shipment Items -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h6><i class="fas fa-boxes"></i> Shipment Items (<?= count($shipments) ?> items)</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Customer</th>
                                    <th>Bill To</th>
                                    <th>Consignee</th>
                                    <th>Docket</th>
                                    <th>Actual Wt</th>
                                    <th>Dimensions</th>
                                    <th>Chargeable Wt</th>
                                    <th>Rate</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($shipments as $item): ?>
                                <tr>
                                    <td><?= esc($item['customer_name']) ?></td>
                                    <td><?= esc($item['bill_to']) ?></td>
                                    <td><?= esc($item['consignee']) ?></td>
                                    <td><?= esc($item['docket_no']) ?></td>
                                    <td><?= number_format($item['actual_weight'], 2) ?>kg</td>
                                    <td><?= $item['length'] ?>x<?= $item['width'] ?>x<?= $item['height'] ?>cm</td>
                                    <td><strong><?= number_format($item['chargeable_weight'], 2) ?>kg</strong></td>
                                    <td>₹<?= number_format($item['rate'], 2) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sales Charges -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h6><i class="fas fa-dollar-sign"></i> Sales Charges</h6>
                </div>
                <div class="card-body">
                    <?php if ($sales): ?>
                        <div class="mb-2"><strong>Flight:</strong> <?= esc($sales['flight_number']) ?></div>
                        <div class="mb-2"><strong>Total Amount:</strong> <span class="text-success fs-5">₹<?= number_format($sales['total_amount'], 2) ?></span></div>
                        <hr>
                        <small class="text-muted">
                            DDC: ₹<?= number_format($sales['ddc'], 2) ?><br>
                            SSC: ₹<?= number_format($sales['ssc'], 2) ?><br>
                            BTC: ₹<?= number_format($sales['btc'], 2) ?><br>
                            FLC: ₹<?= number_format($sales['flc'], 2) ?>
                        </small>
                    <?php else: ?>
                        <span class="text-muted">No sales charges</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>