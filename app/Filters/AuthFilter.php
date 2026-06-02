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
        $publicRoutes = ['login', 'auth/attemptLogin', 'auth/logout', 'company-selection', 'logistics/clearCompany'];
        if (strpos($cleanUri, 'api/track') === 0 || in_array($cleanUri, $publicRoutes)) {
            return;
        }

        // MUST BE LOGGED IN
        if (! session()->get('user_id')) {
            return redirect()->to('/login');
        }

        // COMPANY REQUIRED
        if (strpos($uri, 'logistics') === 0 || strpos($uri, 'admin') === 0) {
            if (! session()->get('selected_company_id')) {
                return redirect()->to('/company-selection');
            }
        }

        // ✅ NEW: PERMISSION CHECKS
        $permissions = session()->get('permissions') ?? [];
        $userRole = session()->get('role');
        
        // ADMIN PANEL - Admin only
        if (strpos($uri, 'admin') === 0 && $userRole !== 'admin') {
            return redirect()->to('/logistics')->with('error', 'Admin access denied!');
        }
        
        // CREATE - Check can_create
        if ($uri === 'logistics/create' && !($permissions['can_create'] ?? 0)) {
            return redirect()->to('/logistics')->with('error', 'Create permission denied!');
        }
        
        // EDIT/DELETE - Check can_edit/can_delete
        if (preg_match('/logistics\/(view|edit|delete)\/(\d+)/', $uri, $matches)) {
            if ($matches[1] === 'edit' && !($permissions['can_edit'] ?? 0)) {
                return redirect()->to('/logistics')->with('error', 'Edit permission denied!');
            }
            if ($matches[1] === 'delete' && !($permissions['can_delete'] ?? 0)) {
                return redirect()->to('/logistics')->with('error', 'Delete permission denied!');
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