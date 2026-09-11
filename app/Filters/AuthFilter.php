<?php
namespace App\Filters;

use App\Models\UserCompanyAccessModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $uri = $request->getUri()->getPath();
        
        // PUBLIC ROUTES
        $cleanUri = ltrim($uri, '/');
        $publicRoutes = ['login', 'auth/attemptLogin', 'auth/logout', 'company-selection', 'logistics/clearCompany', 'track', 'tracking'];
        if (strpos($cleanUri, 'api/track') === 0 || in_array($cleanUri, $publicRoutes)) {
            return;
        }

        // MUST BE LOGGED IN
        if (! session()->get('user_id')) {
            return redirect()->to('/login');
        }

        // COMPANY REQUIRED (Except company management routes)
        $companyExempt = ['logistics/setCompany', 'logistics/createCompany', 'logistics/deleteCompany'];
        $isExemptCompanyRoute = false;
        foreach ($companyExempt as $exempt) {
            if ($cleanUri === $exempt || strpos($cleanUri, $exempt . '/') === 0) {
                $isExemptCompanyRoute = true;
                break;
            }
        }

        if ((strpos($cleanUri, 'logistics') === 0 || strpos($cleanUri, 'admin') === 0 || strpos($cleanUri, 'masters') === 0 || strpos($cleanUri, 'company') === 0) && !$isExemptCompanyRoute) {
            if (! session()->get('selected_company_id')) {
                return redirect()->to('/company-selection');
            }
        }

        $selectedCompanyId = (int) session()->get('selected_company_id');
        if ($selectedCompanyId > 0 && !$isExemptCompanyRoute) {
            $access = (new UserCompanyAccessModel())->getUserAccess((int) session()->get('user_id'), $selectedCompanyId);
            if (!$access || !(int) ($access['is_active'] ?? 0)) {
                session()->remove(['selected_company_id', 'selected_company_name', 'is_root_company', 'role', 'permissions']);
                if ($request->isAJAX()) {
                    return service('response')->setStatusCode(403)->setJSON([
                        'status' => 'error',
                        'message' => 'Your access to this company is no longer active.',
                    ]);
                }
                return redirect()->to('/company-selection')->with('error', 'Your access to this company is no longer active.');
            }

            session()->set([
                'role' => (string) $access['role'],
                'permissions' => [
                    'can_create' => (int) $access['can_create'],
                    'can_edit' => (int) $access['can_edit'],
                    'can_delete' => (int) $access['can_delete'],
                ],
            ]);
        }

        $userRole = session()->get('role');

        // PERMISSION CHECKS
        $permissions    = session()->get('permissions') ?? [];
        $canMasterEntry = ($userRole === 'admin' || !empty($permissions['can_create']));
        $canTracking    = ($userRole === 'admin' || !empty($permissions['can_edit']));
        $canUserMgmt    = ($userRole === 'admin' || !empty($permissions['can_delete']));

        // ADMIN PANEL - Root Admin with User Management only
        if (strpos($cleanUri, 'admin') === 0) {
            $selectedCompanyId = session()->get('selected_company_id');
            $isRootCompany = session()->get('is_root_company');
            $isRoot = ($isRootCompany || (int)$selectedCompanyId === 1);
            if (!$canUserMgmt || !$isRoot) {
                return redirect()->to('/logistics/manage')->with('error', 'User Management is centralized under MA Logistic root administration.');
            }
        }
        
        // TRACKING ROUTES - Require Tracking & POD permission
        if (strpos($cleanUri, 'tracking') === 0) {
            if (!$canTracking) {
                if ($request->isAJAX()) {
                    session_write_close();
                    return service('response')->setStatusCode(403)->setJSON([
                        'status'  => 'error',
                        'message' => 'Tracking & POD permission denied'
                    ]);
                }
                return redirect()->to('/logistics/manage')->with('error', 'Tracking & POD permission denied!');
            }
        }

        // MASTER ENTRY ROUTES - Require Master Entry permission (can edit, update, delete, view, create, masters)
        $isMasterEntryRoute = ($cleanUri === 'logistics/create'
            || $cleanUri === 'logistics/store'
            || $cleanUri === 'logistics/all-invoices'
            || strpos($cleanUri, 'masters') === 0
            || strpos($cleanUri, 'company') === 0
            || preg_match('/^logistics\/(view|edit|update|delete|exportDocketPdf|printDocketPdf)\//', $cleanUri));

        if ($isMasterEntryRoute && !$canMasterEntry) {
            if ($request->isAJAX()) {
                session_write_close();
                return service('response')->setStatusCode(403)->setJSON([
                    'status'  => 'error',
                    'message' => 'Master Entry permission denied'
                ]);
            }
            return redirect()->to('/logistics/manage')->with('error', 'Access Denied: You do not have permission to view or modify shipment details.');
        }

        // Perform branch-level row isolation checks for non-admins
        if (preg_match('/logistics\/(view|edit|delete)\/(\d+)/', $cleanUri, $matches)) {
            $bookingId = intval($matches[2]);
            if ($userRole !== 'admin') {
                $db = \Config\Database::connect();
                $booking = $db->table('bookings')->where('id', $bookingId)->select('branch_id')->get()->getRowArray();
                if ($booking) {
                    $userBranchId = session()->get('branch_id') ?? 1;
                    $bookingBranchId = intval($booking['branch_id'] ?? 1);
                    if ($bookingBranchId !== intval($userBranchId)) {
                        return redirect()->to('/logistics/manage')->with('error', 'Access Denied: You cannot modify bookings originating outside your branch.');
                    }
                }
            }
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        $response->setHeader('Cache-Control', 'private, no-cache, no-store, must-revalidate');
        $response->setHeader('Pragma', 'no-cache');
        $response->setHeader('Expires', 'Thu, 01 Jan 1970 00:00:00 GMT');
    }
}
