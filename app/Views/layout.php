<!DOCTYPE html>
<html>
<head>
    <title>Logistics ERP Operational Hub</title>
    <!-- ANTI-CACHE HEADERS -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <meta name="csrf-token" content="<?= csrf_hash() ?>">
    <meta name="csrf-header" content="<?= csrf_header() ?>">
    <script>
        if (performance.navigation.type === 2) {
            window.location.reload(true);
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- DataTables & SweetAlert2 CSS -->
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    
    <link href="<?= base_url('css/style.css') ?>" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        /* ERP Table Overrides */
        table.dataTable { border-collapse: collapse !important; }
        .dataTables_wrapper .dataTables_paginate .paginate_button { padding: 0 !important; margin: 0 !important; }
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover { border: none !important; background: none !important; }
    </style>
</head>
<body>
    <?php 
        $success = session()->getFlashdata('success'); 
        $error = session()->getFlashdata('error'); 
        $info = session()->getFlashdata('info'); 
    ?>
    <?php if (session()->get('user_id')): ?>
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <a class="sidebar-brand d-flex justify-content-center align-items-center py-2 w-100" href="<?= base_url('logistics') ?>">
                    <img src="<?= base_url('images/logo.png') ?>" alt="MA Logistic" class="img-fluid" style="max-height: 50px; width: auto;">
                </a>
            </div>
            
            <div class="sidebar-nav">
                <?php if (!session()->get('selected_company_id')): ?>
                <!-- Locked Sidebar State -->
                <div class="d-flex flex-column h-100">
                    <div class="sidebar-nav text-muted px-3 mt-4" style="pointer-events: none; opacity: 0.6;">
                        <h6 class="text-uppercase fw-bold text-muted mb-4" style="font-size:0.75rem; letter-spacing:1px;">Options</h6>
                        <div class="mb-4"><i class="fas fa-th-large me-3"></i> Dashboard</div>
                        <div class="mb-4"><i class="fas fa-truck me-3"></i> Shipment Entry</div>
                        <div class="mb-4"><i class="fas fa-list-ul me-3"></i> All Bookings</div>
                        <!-- <div class="mb-4"><i class="fas fa-database me-3"></i> Masters</div> -->
                        <!-- <div class="mb-4"><i class="fas fa-cog me-3"></i> Settings</div> -->
                    </div>
                    
                    <div class="mt-auto mb-5 text-center px-4" style="color: #64748b;">
                        <i class="fas fa-lock fa-2x mb-3 text-secondary opacity-50"></i>
                        <p style="font-size: 0.85rem; line-height: 1.4;" class="mb-0">Select a Company to<br>unlock Options</p>
                    </div>
                </div>
                <?php else: ?>
                <!-- Active Sidebar State -->
                <?php 
                    $uri = service('uri'); 
                    $totalSegs = $uri->getTotalSegments();
                    $seg1 = $totalSegs >= 1 ? $uri->getSegment(1) : ''; 
                    $seg2 = $totalSegs >= 2 ? $uri->getSegment(2) : ''; 
                ?>
                
                <?php if (session()->get('role') !== 'tracking'): ?>
                <a href="<?= base_url('logistics') ?>" class="sidebar-nav-item <?= ($seg1 == 'logistics' && $seg2 == '') ? 'active' : '' ?>">
                    <i class="fas fa-th-large"></i> Dashboard
                </a>
                <?php endif; ?>
                
                <?php if ((session()->get('permissions')['can_create'] ?? 0) == 1): ?>
                <a href="<?= base_url('logistics/create') ?>" class="sidebar-nav-item <?= ($seg2 == 'create') ? 'active' : '' ?>">
                    <i class="fas fa-truck"></i> Shipment Entry
                </a>
                <?php endif; ?>

                <a href="<?= base_url('logistics/manage') ?>" class="sidebar-nav-item <?= ($seg2 == 'manage') ? 'active' : '' ?> mt-2">
                    <i class="fas fa-list-ul"></i> All Bookings
                </a>
                
                <?php if (session()->get('role') === 'admin'): ?>
                <!-- Masters Collapse -->
                <div class="mt-2 px-3">
                    <button class="btn btn-light w-100 text-start text-secondary border shadow-none fw-semibold d-flex justify-content-between align-items-center" type="button" data-bs-toggle="collapse" data-bs-target="#mastersCollapse">
                        <span><i class="fas fa-database me-2 text-primary"></i> Masters</span>
                        <i class="fas fa-chevron-down fs-7"></i>
                    </button>
                    <div class="collapse <?= ($seg1 == 'masters' && $seg2 != 'company') ? 'show' : '' ?> mt-1" id="mastersCollapse">
                        <div class="card card-body p-1 border shadow-sm">
                            <h6 class="text-uppercase fs-7 text-muted ps-3 pt-2 mb-1">Primary Masters</h6>
                            <a class="sidebar-nav-item py-2 px-3 rounded <?= ($seg1 == 'masters' && $seg2 == 'customers') ? 'bg-primary text-white' : '' ?>" href="<?= base_url('masters/customers') ?>"><i class="fas fa-user-friends me-2 <?= ($seg1 == 'masters' && $seg2 == 'customers') ? 'text-white' : 'text-muted' ?>"></i> Customer Master</a>
                            <a class="sidebar-nav-item py-2 px-3 rounded <?= ($seg1 == 'masters' && $seg2 == 'transporters') ? 'bg-primary text-white' : '' ?>" href="<?= base_url('masters/transporters') ?>"><i class="fas fa-truck-moving me-2 <?= ($seg1 == 'masters' && $seg2 == 'transporters') ? 'text-white' : 'text-muted' ?>"></i> Transporters</a>
                            <a class="sidebar-nav-item py-2 px-3 rounded <?= ($seg1 == 'masters' && $seg2 == 'drivers') ? 'bg-primary text-white' : '' ?>" href="<?= base_url('masters/drivers') ?>"><i class="fas fa-id-card me-2 <?= ($seg1 == 'masters' && $seg2 == 'drivers') ? 'text-white' : 'text-muted' ?>"></i> Drivers</a>
                            <a class="sidebar-nav-item py-2 px-3 rounded <?= ($seg1 == 'masters' && $seg2 == 'airlines') ? 'bg-primary text-white' : '' ?>" href="<?= base_url('masters/airlines') ?>"><i class="fas fa-plane me-2 <?= ($seg1 == 'masters' && $seg2 == 'airlines') ? 'text-white' : 'text-muted' ?>"></i> Airlines</a>
                            <hr class="my-1">
                            <a class="sidebar-nav-item py-2 px-3 rounded <?= ($seg1 == 'masters' && $seg2 == 'lookups') ? 'bg-primary text-white' : '' ?>" href="<?= base_url('masters/lookups/origin') ?>"><i class="fas fa-list me-2 <?= ($seg1 == 'masters' && $seg2 == 'lookups') ? 'text-white' : 'text-muted' ?>"></i> Lookup Values</a>
                        </div>
                    </div>
                </div>
                
                <!-- Hiding Reports for Demo
                <a href="<?= base_url('logistics/export') ?>" class="sidebar-nav-item <?= ($seg2 == 'export') ? 'active' : '' ?>">
                    <i class="fas fa-chart-bar"></i> Reports
                </a>
                -->
                
                <!-- Settings Collapse -->
                <div class="mt-2 px-3 mb-4">
                    <button class="btn btn-light w-100 text-start text-secondary border shadow-none fw-semibold d-flex justify-content-between align-items-center" type="button" data-bs-toggle="collapse" data-bs-target="#settingsCollapse">
                        <span><i class="fas fa-cog me-2 text-secondary"></i> Settings</span>
                        <i class="fas fa-chevron-down fs-7"></i>
                    </button>
                    <div class="collapse <?= ($seg1 == 'masters' && $seg2 == 'company' || $seg1 == 'admin') ? 'show' : '' ?> mt-1" id="settingsCollapse">
                        <div class="card card-body p-1 border shadow-sm">
                            <a class="sidebar-nav-item py-2 px-3 rounded <?= ($seg1 == 'masters' && $seg2 == 'company') ? 'bg-primary text-white' : '' ?>" href="<?= base_url('masters/company') ?>"><i class="fas fa-building me-2 <?= ($seg1 == 'masters' && $seg2 == 'company') ? 'text-white' : 'text-muted' ?>"></i> Company Settings</a>
                            <hr class="my-1">
                            <a class="sidebar-nav-item py-2 px-3 rounded <?= ($seg1 == 'admin') ? 'bg-primary text-white' : '' ?>" href="<?= base_url('admin') ?>"><i class="fas fa-users-cog me-2 <?= ($seg1 == 'admin') ? 'text-white' : 'text-muted' ?>"></i> User Management</a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
            
            <div class="sidebar-footer">
                <img src="https://ui-avatars.com/api/?name=<?= urlencode(session()->get('username')) ?>&background=0f172a&color=fff&rounded=true&size=36" alt="Profile">
                <div class="user-info w-100 ps-2">
                    <div class="user-name" style="font-size: 0.9rem; font-weight: 600; color: #334155;"><?= esc(session()->get('username')) ?></div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span style="font-size: 0.8rem; color: #64748b;"><?= ucfirst(session()->get('role')) ?></span>
                        <a href="<?= base_url('auth/logout') ?>" class="text-danger" title="Logout"><i class="fas fa-sign-out-alt"></i></a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main-content d-flex flex-column min-vh-100">
            <!-- Top Header -->
            <div class="top-header d-flex justify-content-end">
                <div class="header-actions d-flex align-items-center">
                    <?php if(session()->get('selected_company_name')): ?>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle shadow-none fw-semibold" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-building text-primary me-1"></i> <?= esc(session()->get('selected_company_name')) ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-1">
                            <li><h6 class="dropdown-header">Active Company</h6></li>
                            <li><a class="dropdown-item py-2" href="<?= base_url('logistics/clearCompany') ?>"><i class="fas fa-exchange-alt me-2 text-muted"></i> Switch Company</a></li>
                        </ul>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="p-4 flex-grow-1">
                <?= $this->renderSection('content') ?>
            </div>

            <!-- Global Footer -->
            <footer class="footer mt-auto py-3 bg-light border-top text-center">
                <div class="container-fluid">
                    <span class="text-muted small fw-semibold">All Right Reserved @2026 MARL Express Pvt. Ltd. | Developed By - <a href="https://granthinfotech.in/" target="_blank" style="font-style:bold; color:#f48b24;"> Granth Infotech Pvt. Ltd.</a></span>
                </div>
            </footer>
        </div>
    <?php else: ?>
        <div class="d-flex flex-column min-vh-100 justify-content-between">
            <div class="container mt-5 flex-grow-1">
                <?= $this->renderSection('content') ?>
            </div>
            <footer class="footer mt-auto py-3 text-center">
                <div class="container">
                    <span class="text-muted small fw-semibold">All Right Reserved @2026 MARL Express Pvt. Ltd. | Developed By - <a href="https://granthinfotech.in/" target="_blank" style="font-style:bold; color:#f48b24;"> Granth Infotech Pvt. Ltd.</a></span>
                </div>
            </footer>
        </div>
    <?php endif; ?>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- DataTables & SweetAlert2 JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Global ERP Utilities -->
    <script>const BASE_URL = '<?= base_url() ?>';</script>
    <script src="<?= base_url('js/erp-utils.js') ?>"></script>
    
    <!-- Global Error Handler -->
    <script>
        window.onerror = function(msg, url, line, col, error) {
            console.error("Global Error Caught: ", msg, url, line, col, error);
            if(typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'JavaScript Error',
                    html: `<b>Message:</b> ${msg}<br><b>Line:</b> ${line}:${col}`,
                    toast: true,
                    position: 'bottom-end',
                    showConfirmButton: false,
                    timer: 10000
                });
            }
            return false;
        };

        // Flash Messages via SweetAlert
        <?php if ($success): ?>
            ERPUtils.showSuccess('Success!', <?= json_encode($success) ?>);
        <?php endif; ?>
        <?php if ($error): ?>
            ERPUtils.showError('Error!', <?= json_encode($error) ?>);
        <?php endif; ?>
        <?php if ($info): ?>
            Swal.fire({icon: 'info', title: 'Information', html: <?= json_encode($info) ?>, toast: true, position: 'top-end', showConfirmButton: false, timer: 3000});
        <?php endif; ?>
    </script>
    
    <?= $this->renderSection('scripts') ?>
</body>
</html>