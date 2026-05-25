<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>AWB Details: <?= esc($booking['awb_no']) ?></h2>
        <div>
            <a href="<?= base_url('logistics') ?>" class="btn btn-secondary me-2">
                Back
            </a>
            <?php if ((session()->get('permissions')['can_edit'] ?? 0) == 1): ?>
                <a href="<?= base_url('logistics/edit/' . $booking['id']) ?>" class="btn btn-primary">
                    Edit
                </a>
            <?php endif; ?>
            <!-- Add after Edit button -->
            <a href="<?= base_url('logistics/exportPdf/' . $booking['id']) ?>" class="btn btn-danger">
                Export PDF
            </a>
        </div>
    </div>

    <!-- Basic Info Card -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5>Basic Booking Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3"><strong>AWB No:</strong> <?= esc($booking['awb_no']) ?></div>
                        <div class="col-md-3"><strong>Date:</strong> <?= date('d-M-Y H:i', strtotime($booking['booking_date'])) ?></div>
                        <div class="col-md-3"><strong>Company:</strong> <?= esc($booking['company_name']) ?></div>
                        <!-- <div class="col-md-2"><strong>Status:</strong> 
                            <span class="badge bg-<?//= $booking['status'] == 'Delivered' ? 'success' : ($booking['status'] == 'In-Transit' ? 'warning' : 'secondary') ?>">
                                <?//= ucfirst($booking['status']) ?>
                            </span>
                        </div> -->
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
                    <h6>Shipment Items (<?= count($shipments) ?> items)</h6>
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
                                    <td><strong><?= number_format($item['final_chargeable_weight'], 2) ?>kg</strong></td>
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
                    <h6>Sales Charges</h6>
                </div>
                <div class="card-body">
                    <?php if ($sales): 
                        $calculatedTotal = ($sales['rate'] * $sales['weight']) + $sales['ddc'] + $sales['ssc'] + $sales['btc'] + $sales['flc'] + $sales['doc'] + $sales['inbound_tsp'] + $sales['outbound_tsp'] + $sales['tcp'] + $sales['utility_charges'] + $sales['xray_charges'] + $sales['ado'] + $sales['awb_fees_agent'] + $sales['awb_fees_carrier'] + $sales['admin_charges'] + $sales['delivery_order_charges'] + $sales['inbound_handling'] + $sales['inbound_storage'] + $sales['outbound_storage'] + $sales['misc_charges'];
                    ?>
                        <div class="mb-2"><strong>Flight:</strong> <?= esc($sales['flight_number']) ?></div>
                        <div class="mb-2"><strong>Airlines:</strong> <?= esc($sales['airlines']) ?></div>
                        <div class="mb-2"><strong>Weight:</strong> <?= number_format($sales['weight'] ?? 0, 2) ?> KG</div>
                        <div class="mb-2"><strong>Rate:</strong> ₹<?= number_format($sales['rate'] ?? 0, 2) ?></div>
                        <hr>
                        <div class="mb-2"><strong>Total Amount:</strong> <span class="text-success fs-5">₹<?= number_format($calculatedTotal, 2) ?></span></div>
                        <hr>
                        <small class="text-muted">
                            <div><strong>Charges Breakdown:</strong></div>
                            DDC: ₹<?= number_format($sales['ddc'] ?? 0, 2) ?> | SSC: ₹<?= number_format($sales['ssc'] ?? 0, 2) ?> | BTC: ₹<?= number_format($sales['btc'] ?? 0, 2) ?><br>
                            FLC: ₹<?= number_format($sales['flc'] ?? 0, 2) ?> | DOC: ₹<?= number_format($sales['doc'] ?? 0, 2) ?> | TCP: ₹<?= number_format($sales['tcp'] ?? 0, 2) ?><br>
                            Inbound TSP: ₹<?= number_format($sales['inbound_tsp'] ?? 0, 2) ?> | Outbound TSP: ₹<?= number_format($sales['outbound_tsp'] ?? 0, 2) ?><br>
                            Utility: ₹<?= number_format($sales['utility_charges'] ?? 0, 2) ?> | X-Ray: ₹<?= number_format($sales['xray_charges'] ?? 0, 2) ?> | ADO: ₹<?= number_format($sales['ado'] ?? 0, 2) ?><br>
                            AWB Agent: ₹<?= number_format($sales['awb_fees_agent'] ?? 0, 2) ?> | AWB Carrier: ₹<?= number_format($sales['awb_fees_carrier'] ?? 0, 2) ?><br>
                            Admin: ₹<?= number_format($sales['admin_charges'] ?? 0, 2) ?> | Delivery Order: ₹<?= number_format($sales['delivery_order_charges'] ?? 0, 2) ?><br>
                            Inbound Handling: ₹<?= number_format($sales['inbound_handling'] ?? 0, 2) ?> | Inbound Storage: ₹<?= number_format($sales['inbound_storage'] ?? 0, 2) ?><br>
                            Outbound Storage: ₹<?= number_format($sales['outbound_storage'] ?? 0, 2) ?> | Misc: ₹<?= number_format($sales['misc_charges'] ?? 0, 2) ?>
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