<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

abstract class BaseController extends Controller
{
    /**
     * @return void
     */
    // public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    // {
    //     // REMOVE session_start() - CI handles it automatically ✅
        
    //     // Load helpers
    //     helper(['form', 'url']);
        
    //     parent::initController($request, $response, $logger);
        
    //     // CI Session - NO manual session_start needed
    //     // $this->session = \Config\Services::session();
    // }

   public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
   {
    helper(['form', 'url']);
    
    parent::initController($request, $response, $logger);
    
    // ANTI-BACK BUTTON TOKEN
    $session = session();
    $token = $session->get('security_token');
    
    if (!$token) {
        $token = bin2hex(random_bytes(32));
        $session->set('security_token', $token);
    }
    
    // TOKEN VALIDATION FOR PROTECTED PAGES
    $uri = $request->getUri()->getPath();
    if (strpos($uri, 'logistics') === 0 && $session->get('selected_company_id')) {
        $pageToken = $request->getGet('token');
        if ($pageToken !== $token) {
            // INVALID TOKEN = BACK BUTTON DETECTED
            return redirect()->to('/company-selection')
                ->with('error', 'Session expired. Please select company again.');
        }
    }
   }

}