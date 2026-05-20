<?php
namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class PermissionsFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $uri = $request->getUri()->getPath();
        $permissions = session()->get('permissions') ?? [];
        
        // Logistics permissions
        if (strpos($uri, 'logistics') === 0) {
            if (strpos($uri, 'create') !== false || strpos($uri, 'store') !== false) {
                if (!($permissions['can_create'] ?? 0)) {
                    return redirect()->to('/logistics')->with('error', '❌ No permission to create!');
                }
            }
            if (strpos($uri, 'edit') !== false || strpos($uri, 'update') !== false || 
                strpos($uri, 'view') !== false || strpos($uri, 'manage') !== false) {
                if (!($permissions['can_edit'] ?? 0)) {
                    return redirect()->to('/logistics')->with('error', '❌ No permission to edit/view!');
                }
            }
            if (strpos($uri, 'delete') !== false) {
                if (!($permissions['can_delete'] ?? 0)) {
                    return $this->response->setJSON(['success' => false, 'message' => 'No delete permission']);
                }
            }
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Nothing needed
    }
}