<?php
namespace App\Controllers;

use App\Models\CompanyModel;

class CompanyController extends BaseController
{
    public function settings()
    {
        // Only admin can access company settings globally?
        // Wait, normally a branch manager can edit their branch settings.
        // Let's restrict it to admin for now, or users with 'can_edit' permission.
        if (session()->get('role') !== 'admin') {
            return redirect()->to('/logistics')->with('error', 'Admin access required to edit Company Settings!');
        }

        $companyId = session()->get('selected_company_id');
        if (!$companyId) {
            return redirect()->to('/company-selection');
        }

        $companyModel = new CompanyModel();
        $data['company'] = $companyModel->find($companyId);
        $data['user'] = session()->get();
        $data['company_name'] = session()->get('selected_company_name');
        $data['permissions'] = session()->get('permissions') ?? [];

        // Fetch Google Maps API Key from .env environment configuration
        $data['google_maps_api_key'] = env('GOOGLE_MAPS_API_KEY') ?: (getenv('GOOGLE_MAPS_API_KEY') ?: '');

        return view('company/settings', $data);
    }

    public function updateSettings()
    {
        if (session()->get('role') !== 'admin') {
            return redirect()->to('/logistics')->with('error', 'Admin access required!');
        }

        $companyId = session()->get('selected_company_id');
        if (!$companyId) {
            return redirect()->to('/company-selection');
        }

        $companyModel = new CompanyModel();
        
        $data = [
            'address' => $this->request->getPost('address'),
            'email' => $this->request->getPost('email'),
            'mobile' => $this->request->getPost('mobile'),
            'gstin' => $this->request->getPost('gstin'),
            'pan' => $this->request->getPost('pan'),
            'sac_code' => $this->request->getPost('sac_code'),
            'cgst_rate' => $this->request->getPost('cgst_rate') ?: 0,
            'sgst_rate' => $this->request->getPost('sgst_rate') ?: 0,
            'igst_rate' => $this->request->getPost('igst_rate') ?: 0,
            'terms_conditions' => $this->request->getPost('terms_conditions'),
        ];

        // 1. Handle Base64 Signature Canvas
        $signatureBase64 = $this->request->getPost('signature_base64');
        if (!empty($signatureBase64)) {
            if (strpos($signatureBase64, 'image/png') !== false) {
                return redirect()->back()->with('error', 'Please do a HARD REFRESH (Ctrl+Shift+R or Ctrl+F5) of your browser to apply the latest signature fixes, then draw again.');
            }
            $parts = explode(',', $signatureBase64);
            if (count($parts) == 2) {
                $image_base64 = base64_decode($parts[1]);
                $fileName = 'signature_' . time() . '_' . rand(1000, 9999) . '.jpg';
                $uploadPath = FCPATH . 'uploads/signatures';
                
                if (!is_dir($uploadPath)) {
                    mkdir($uploadPath, 0777, true);
                }
                
                file_put_contents($uploadPath . '/' . $fileName, $image_base64);
                $data['signature_path'] = 'uploads/signatures/' . $fileName;
            }
        }

        // 2. Handle File Upload
        $signatureFile = $this->request->getFile('signature_image');
        if ($signatureFile && $signatureFile->isValid() && !$signatureFile->hasMoved()) {
            if (!in_array($signatureFile->getMimeType(), ['image/png', 'image/jpeg', 'image/jpg', 'image/gif'])) {
                return redirect()->back()->with('error', 'Signature must be a PNG, JPG, or GIF image.');
            }
            $newName = 'signature_' . time() . '_' . rand(1000, 9999) . '.' . $signatureFile->getExtension();
            $signatureFile->move(FCPATH . 'uploads/signatures', $newName);
            $data['signature_path'] = 'uploads/signatures/' . $newName;
        }

        $companyModel->update($companyId, $data);

        // Save Google Maps API Key to the .env file
        $apiKey = trim($this->request->getPost('google_maps_api_key') ?? '');
        $this->updateEnvFile('GOOGLE_MAPS_API_KEY', $apiKey);

        return redirect()->to('/company/settings')->with('success', 'Company settings updated successfully!');
    }

    public function deleteSignature()
    {
        if (session()->get('role') !== 'admin') {
            return redirect()->to('/logistics')->with('error', 'Admin access required!');
        }

        $companyId = session()->get('selected_company_id');
        if (!$companyId) return redirect()->to('/company-selection');

        $companyModel = new CompanyModel();
        $company = $companyModel->find($companyId);

        if (!empty($company['signature_path']) && file_exists(FCPATH . $company['signature_path'])) {
            unlink(FCPATH . $company['signature_path']);
        }

        $companyModel->update($companyId, ['signature_path' => null]);
        
        return redirect()->to('/company/settings')->with('success', 'Signature deleted successfully!');
    }

    /**
     * Helper to write/update key in the .env file
     */
    private function updateEnvFile(string $key, string $value)
    {
        $path = ROOTPATH . '.env';
        if (!file_exists($path)) {
            return;
        }

        $content = file_get_contents($path);
        
        // Find existing key definition (can be commented out or active)
        $pattern = "/^#?\s*(" . preg_quote($key, '/') . ")\s*=\s*(.*)$/m";
        
        if (preg_match($pattern, $content)) {
            $content = preg_replace($pattern, "$key = '$value'", $content);
        } else {
            $content = rtrim($content) . "\n\n# Google Maps API Integration\n$key = '$value'\n";
        }
        
        file_put_contents($path, $content);
    }
}
