<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Logistics::index');
$routes->get('login', 'AuthController::login');
$routes->post('auth/attemptLogin', 'AuthController::attemptLogin');
$routes->get('auth/logout', 'AuthController::logout');
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

// ExportExcel
$routes->get('logistics/exportExcel', 'Logistics::exportExcel');
$routes->match(['get', 'post'], 'logistics/exportExcel', 'Logistics::exportExcel');