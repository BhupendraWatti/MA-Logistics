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

        // Handle File Upload
        $signatureFile = $this->request->getFile('signature_image');
        if ($signatureFile && $signatureFile->isValid() && !$signatureFile->hasMoved()) {
            $newName = $signatureFile->getRandomName();
            $signatureFile->move(FCPATH . 'uploads/signatures', $newName);
            $data['signature_path'] = 'uploads/signatures/' . $newName;
        }

        $companyModel->update($companyId, $data);

        return redirect()->to('/company/settings')->with('success', 'Company settings updated successfully!');
    }
}
