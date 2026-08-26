<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Logistics::index');
$routes->get('login', 'AuthController::login');
$routes->post('auth/attemptLogin', 'AuthController::attemptLogin');
$routes->get('auth/logout', 'AuthController::logout');
$routes->get('company/settings', 'CompanyController::settings');
$routes->post('company/settings/update', 'CompanyController::updateSettings');
$routes->get('company/settings/deleteSignature', 'CompanyController::deleteSignature');
$routes->get('company/settings/deleteLogo', 'CompanyController::deleteLogo');

// ====== ADMIN/USER MANAGEMENT ======
$routes->get('admin', 'AdminController::index');
$routes->post('admin/createUser', 'AdminController::createUser');
$routes->post('admin/togglePermission', 'AdminController::togglePermission');
$routes->post('admin/changePassword', 'AdminController::changePassword');
$routes->post('admin/toggleStatus', 'AdminController::toggleStatus');
$routes->post('admin/deleteUser', 'AdminController::deleteUser');
$routes->post('admin/ajax-datatable', 'AdminController::ajaxDatatable');

// LOGISTICS ROUTES
$routes->get('logistics', 'Logistics::index');
$routes->get('logistics/manage', 'Logistics::manageBookings');
$routes->post('logistics/ajax-datatable', 'Logistics::ajaxDatatable');
$routes->get('logistics/search', 'Logistics::search');
$routes->post('logistics/searchResult', 'Logistics::searchResult');
$routes->match(['get', 'post'], 'logistics/create', 'Logistics::create');
$routes->post('logistics/store', 'Logistics::store');
$routes->post('logistics/check-awb-unique', 'Logistics::checkAwbUnique');
$routes->get('logistics/view/(:num)', 'Logistics::view/$1');
$routes->get('logistics/edit/(:num)', 'Logistics::edit/$1');
$routes->post('logistics/update/(:num)', 'Logistics::update/$1');
$routes->post('logistics/delete/(:num)', 'Logistics::delete/$1');
$routes->get('logistics/deleteSignature/(:num)', 'Logistics::deleteSignature/$1');
$routes->get('logistics/consolidation', 'Logistics::consolidation');
$routes->get('logistics/exportDocketPdf/(:num)', 'Logistics::exportDocketPdf/$1');
$routes->get('logistics/printDocketPdf/(:num)', 'Logistics::printDocketPdf/$1');

$routes->get('logistics/all-invoices', 'Logistics::allInvoices');
$routes->get('logistics/all-invoices/downloads', 'Logistics::ajaxInvoiceDownloads');
$routes->post('logistics/all-invoices/search', 'Logistics::ajaxSearchShipmentRecords');
$routes->post('logistics/all-invoices/generate', 'Logistics::generateConsolidatedInvoice');
$routes->get('logistics/all-invoices/downloads/(:num)', 'Logistics::viewInvoiceDownload/$1');
$routes->post('logistics/all-invoices/downloads/delete/(:num)', 'Logistics::deleteInvoiceDownload/$1');

$routes->get('logistics/clearCompany', 'Logistics::clearCompany');

// COMPANY SELECTION (CRITICAL - MUST BE BEFORE FALLBACK)
$routes->get('company-selection', 'Logistics::companySelection');
$routes->match(['get', 'post'], 'logistics/setCompany', 'Logistics::setCompany');
$routes->match(['get', 'post'], 'logistics/createCompany', 'Logistics::createCompany');
$routes->match(['get', 'post'], 'logistics/deleteCompany/(:num)', 'Logistics::deleteCompany/$1');

// ====== MASTER MODULE ======
$routes->get('masters/company',                       'CompanyController::settings');
$routes->post('masters/company/update',               'CompanyController::updateSettings');
$routes->post('masters/dockets/generate',             'MasterController::generateDocket');
$routes->post('masters/dockets/preview',              'MasterController::previewDocket');
$routes->post('masters/dockets/check-unique',         'MasterController::checkDocketUnique');

$routes->get('masters/customers',                     'MasterController::customers');
$routes->post('masters/customers/create',             'MasterController::createCustomer');
$routes->get('masters/customers/edit/(:num)',          'MasterController::editCustomer/$1');
$routes->post('masters/customers/update/(:num)',       'MasterController::updateCustomer/$1');
$routes->post('masters/customers/delete/(:num)',       'MasterController::deleteCustomer/$1');
$routes->post('masters/customers/rate-lookup',         'MasterController::lookupCustomerRate');
$routes->post('masters/customers/rate-save',           'MasterController::saveCustomerRate');

$routes->get('masters/transporters',                  'MasterController::transporters');
$routes->post('masters/transporters/create',          'MasterController::createTransporter');
$routes->post('masters/transporters/update/(:num)',   'MasterController::updateTransporter/$1');
$routes->post('masters/transporters/delete/(:num)',   'MasterController::deleteTransporter/$1');

$routes->get('masters/drivers',                       'MasterController::drivers');
$routes->post('masters/drivers/create',               'MasterController::createDriver');
$routes->post('masters/drivers/update/(:num)',         'MasterController::updateDriver/$1');
$routes->post('masters/drivers/delete/(:num)',         'MasterController::deleteDriver/$1');

$routes->get('masters/airlines',                      'MasterController::airlines');
$routes->post('masters/airlines/create',              'MasterController::createAirline');
$routes->post('masters/airlines/update/(:num)',        'MasterController::updateAirline/$1');
$routes->post('masters/airlines/delete/(:num)',        'MasterController::deleteAirline/$1');

$routes->get('masters/bank-accounts',                     'MasterController::bankAccounts');
$routes->post('masters/bank-accounts/create',             'MasterController::createBankAccount');
$routes->post('masters/bank-accounts/update/(:num)',       'MasterController::updateBankAccount/$1');
$routes->post('masters/bank-accounts/delete/(:num)',       'MasterController::deleteBankAccount/$1');
$routes->get('masters/invoice-templates',                  'MasterController::invoiceTemplates');
$routes->post('masters/invoice-templates/create',          'MasterController::createInvoiceTemplate');
$routes->post('masters/invoice-templates/update/(:num)',   'MasterController::updateInvoiceTemplate/$1');
$routes->post('masters/invoice-templates/delete/(:num)',   'MasterController::deleteInvoiceTemplate/$1');
$routes->get('masters/docket-series',                      'MasterController::docketSeries');
$routes->post('masters/docket-series/create',              'MasterController::createDocketSeries');
$routes->post('masters/docket-series/update/(:num)',       'MasterController::updateDocketSeries/$1');
$routes->post('masters/docket-series/delete/(:num)',       'MasterController::deleteDocketSeries/$1');

$routes->get('masters/lookups/(:segment)',              'MasterController::lookups/$1');
$routes->post('masters/lookups/(:segment)/create',      'MasterController::createLookup/$1');
$routes->post('masters/lookups/delete/(:num)',         'MasterController::deleteLookup/$1');

// DataTables SSP Route
$routes->post('masters/ajax-datatable/(:segment)',      'MasterController::ajaxDatatable/$1');

// ====== JSON API ENDPOINTS ======
$routes->get('api/masters/customers',                 'MasterController::apiCustomers');
$routes->get('api/masters/customers/(:num)',           'MasterController::apiCustomer/$1');
$routes->get('api/masters/transporters',              'MasterController::apiTransporters');
$routes->get('api/masters/transporters/(:num)',        'MasterController::apiTransporter/$1');
$routes->get('api/masters/drivers',                   'MasterController::apiDrivers');
$routes->get('api/masters/drivers/(:num)',             'MasterController::apiDriver/$1');
$routes->get('api/masters/airlines',                  'MasterController::apiAirlines');
$routes->get('api/masters/lookup/(:segment)',            'MasterController::apiLookup/$1');
$routes->get('api/masters/company-gst',               'MasterController::apiCompanyGst');

// ====== PUBLIC API ENDPOINTS ======
$routes->get('track', 'TrackingController::index');
$routes->get('tracking', 'TrackingController::index');
$routes->get('api/track/(:any)', 'TrackingController::trackByAwb/$1');

// ====== VERSIONED JSON API (Basic Auth or login session) ======
$routes->post('api/v1/auth/login', 'Api\V1Controller::login');
$routes->group('api/v1', ['filter' => 'apiBasic'], static function ($routes) {
    $routes->post('auth/logout', 'Api\V1Controller::logout');
    $routes->get('companies', 'Api\V1Controller::companies');
    $routes->post('companies/select', 'Api\V1Controller::selectCompany');

    $routes->get('masters/company', 'Api\V1Controller::company');
    $routes->get('masters/customers', 'Api\V1Controller::customers');
    $routes->get('masters/customers/(:num)', 'Api\V1Controller::customer/$1');
    $routes->post('masters/customers', 'Api\V1Controller::createCustomer');
    $routes->delete('masters/customers/(:num)', 'Api\V1Controller::deleteCustomer/$1');
    $routes->post('masters/customer-rates/lookup', 'Api\V1Controller::lookupCustomerRate');
    $routes->post('masters/dockets/generate', 'Api\V1Controller::generateDocket');
    $routes->get('masters/transporters', 'Api\V1Controller::transporters');
    $routes->get('masters/drivers', 'Api\V1Controller::drivers');
    $routes->get('masters/airlines', 'Api\V1Controller::airlines');
    $routes->get('masters/lookups/(:segment)', 'Api\V1Controller::lookups/$1');

    $routes->post('bookings/check-awb', 'Api\V1Controller::checkAwb');
    $routes->match(['get', 'post'], 'bookings/search', 'Api\V1Controller::searchBookings');
    $routes->post('bookings', 'Api\V1Controller::createBooking');
    $routes->get('bookings/(:num)', 'Api\V1Controller::booking/$1');
    $routes->match(['put', 'patch', 'post'], 'bookings/(:num)', 'Api\V1Controller::updateBooking/$1');
    $routes->delete('bookings/(:num)', 'Api\V1Controller::deleteBooking/$1');
    $routes->post('bookings/(:num)/delete', 'Api\V1Controller::deleteBooking/$1');
    $routes->get('bookings/(:num)/docket-pdf', 'Api\V1Controller::docketPdf/$1');
    $routes->get('bookings/(:num)/tracking', 'Api\V1Controller::trackingHistory/$1');

    $routes->post('tracking', 'Api\V1Controller::saveTracking');
    $routes->delete('tracking/(:num)', 'Api\V1Controller::deleteTracking/$1');
    $routes->post('tracking/(:num)/delete', 'Api\V1Controller::deleteTracking/$1');
    $routes->post('invoices/consolidated', 'Api\V1Controller::consolidatedInvoice');
    $routes->get('invoices/downloads/(:num)', 'Api\V1Controller::invoiceDownload/$1');
    $routes->delete('invoices/downloads/(:num)', 'Api\V1Controller::deleteInvoiceDownload/$1');
});

// DEFAULT FALLBACK (LAST!)
$routes->get('(:segment)', 'Logistics::index');

// ExportPDF:
$routes->get('logistics/exportPdf/(:num)', 'Logistics::exportPdf/$1');

// Export CSV Full
$routes->get('logistics/export', 'Logistics::export');

// ExportExcel
$routes->get('logistics/exportExcel', 'Logistics::exportExcel');
$routes->match(['get', 'post'], 'logistics/exportExcel', 'Logistics::exportExcel');
// Tracking & POD
$routes->get('tracking/history/(:num)', 'TrackingController::getHistory/$1');
$routes->post('tracking/save', 'TrackingController::saveUpdate');
$routes->post('tracking/delete/(:num)', 'TrackingController::deleteUpdate/$1');
