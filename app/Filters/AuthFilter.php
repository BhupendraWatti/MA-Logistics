<?php
namespace App\Filters;

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

        if ((strpos($cleanUri, 'logistics') === 0 || strpos($cleanUri, 'admin') === 0) && !$isExemptCompanyRoute) {
            if (! session()->get('selected_company_id')) {
                return redirect()->to('/company-selection');
            }
        }

        $userRole = session()->get('role');

        // ✅ NEW: TRACKING ROLE CHECK
        if ($userRole === 'tracking') {
            if ($cleanUri === '' || $cleanUri === 'logistics') {
                return redirect()->to('/logistics/manage');
            }
            
            $allowedPaths = [
                'logistics/manage',
                'logistics/search',
                'logistics/searchResult',
                'logistics/ajax-datatable',
                'tracking/history',
                'tracking/save',
                'company-selection',
                'logistics/setCompany',
                'logistics/clearCompany',
                'auth/logout',
            ];
            
            $isAllowed = false;
            foreach ($allowedPaths as $allowed) {
                if ($cleanUri === $allowed || strpos($cleanUri, $allowed . '/') === 0) {
                    $isAllowed = true;
                    break;
                }
            }
            if (!$isAllowed) {
                return redirect()->to('/logistics/manage')->with('error', 'Access Restricted: Tracking role can only access tracking updates.');
            }
        }

        // ✅ NEW: PERMISSION CHECKS
        $permissions = session()->get('permissions') ?? [];
        
        // ADMIN PANEL - Admin only
        if (strpos($cleanUri, 'admin') === 0 && $userRole !== 'admin') {
            return redirect()->to('/logistics')->with('error', 'Admin access denied!');
        }
        
        // CREATE - Check can_create
        if ($cleanUri === 'logistics/create' && !($permissions['can_create'] ?? 0)) {
            return redirect()->to('/logistics')->with('error', 'Create permission denied!');
        }
        
        // EDIT/DELETE - Check can_edit/can_delete
        if (preg_match('/logistics\/(view|edit|delete)\/(\d+)/', $cleanUri, $matches)) {
            $bookingId = intval($matches[2]);
            $action = $matches[1];

            if ($action === 'edit' && !($permissions['can_edit'] ?? 0)) {
                return redirect()->to('/logistics')->with('error', 'Edit permission denied!');
            }
            if ($action === 'delete' && !($permissions['can_delete'] ?? 0)) {
                return redirect()->to('/logistics')->with('error', 'Delete permission denied!');
            }

            // Perform branch-level row isolation checks for non-admins
            if ($userRole !== 'admin') {
                $db = \Config\Database::connect();
                $booking = $db->table('bookings')->where('id', $bookingId)->select('branch_id')->get()->getRowArray();
                if ($booking) {
                    $userBranchId = session()->get('branch_id') ?? 1;
                    $bookingBranchId = intval($booking['branch_id'] ?? 1);
                    if ($bookingBranchId !== intval($userBranchId)) {
                        return redirect()->to('/logistics')->with('error', 'Access Denied: You cannot modify bookings originating outside your branch.');
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
