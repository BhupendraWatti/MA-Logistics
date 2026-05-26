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

// Fallback logic inside AdminController
$routes->get('admin', 'AdminController::index');
$routes->post('admin/togglePermission', 'AdminController::togglePermission');
$routes->post('admin/createUser', 'AdminController::createUser');

// LOGISTICS ROUTES
$routes->get('logistics', 'Logistics::index');
$routes->get('logistics/search', 'Logistics::search');
$routes->post('logistics/searchResult', 'Logistics::searchResult');
$routes->match(['get', 'post'], 'logistics/create', 'Logistics::create');
$routes->post('logistics/store', 'Logistics::store');
$routes->get('logistics/view/(:num)', 'Logistics::view/$1');
$routes->get('logistics/edit/(:num)', 'Logistics::edit/$1');
$routes->post('logistics/update/(:num)', 'Logistics::update/$1');
$routes->get('logistics/consolidation', 'Logistics::consolidation');

$routes->get('logistics/clearCompany', 'Logistics::clearCompany');

// COMPANY SELECTION (CRITICAL - MUST BE BEFORE FALLBACK)
$routes->get('company-selection', 'Logistics::companySelection');
$routes->post('logistics/setCompany', 'Logistics::setCompany');
$routes->post('logistics/createCompany', 'Logistics::createCompany');
$routes->post('logistics/deleteCompany/(:num)', 'Logistics::deleteCompany/$1');

// ====== MASTER MODULE ======
$routes->get('masters/company',                       'MasterController::editCompany');
$routes->post('masters/company/update',               'MasterController::updateCompany');

$routes->get('masters/customers',                     'MasterController::customers');
$routes->post('masters/customers/create',             'MasterController::createCustomer');
$routes->get('masters/customers/edit/(:num)',          'MasterController::editCustomer/$1');
$routes->post('masters/customers/update/(:num)',       'MasterController::updateCustomer/$1');
$routes->post('masters/customers/delete/(:num)',       'MasterController::deleteCustomer/$1');

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

$routes->get('masters/lookups/(:segment)',              'MasterController::lookups/$1');
$routes->post('masters/lookups/(:segment)/create',      'MasterController::createLookup/$1');
$routes->post('masters/lookups/delete/(:num)',         'MasterController::deleteLookup/$1');

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

// DEFAULT FALLBACK (LAST!)
$routes->get('(:segment)', 'Logistics::index');

// Manage Bookings
$routes->get('logistics/manage', 'Logistics::manageBookings');

// ExportPDF:
$routes->get('logistics/exportPdf/(:num)', 'Logistics::exportPdf/$1');

// Deactivate Button
$routes->post('admin/toggleStatus', 'AdminController::toggleStatus');

// Change Password Route
$routes->post('admin/changePassword', 'AdminController::changePassword');

// Export CSV Full
$routes->get('logistics/export', 'Logistics::export');

// ExportExcel
$routes->get('logistics/exportExcel', 'Logistics::exportExcel');
$routes->match(['get', 'post'], 'logistics/exportExcel', 'Logistics::exportExcel');