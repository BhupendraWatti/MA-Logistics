<?php

namespace App\Controllers;

use App\Models\CustomerModel;
use App\Models\TransporterModel;
use App\Models\DriverModel;
use App\Models\AirlineModel;
use App\Models\LookupValueModel;
use App\Models\CompanyModel;

class MasterController extends BaseController
{
    /**
     * Guard: admin only. Returns redirect response or null.
     */
    private function requireAdmin()
    {
        if (session()->get('role') !== 'admin') {
            return redirect()->to('/logistics')->with('error', 'Admin access required!');
        }
        return null;
    }

    /**
     * Returns the currently selected company ID from session.
     */
    private function companyId(): int
    {
        return (int) session()->get('selected_company_id');
    }

    // ============================================================
    // COMPANY SETTINGS
    // ============================================================

    public function editCompany()
    {
        if ($r = $this->requireAdmin()) return $r;
        $companyId = $this->companyId();
        if (!$companyId) return redirect()->to('/company-selection');

        return view('masters/company_settings', [
            'company' => (new CompanyModel())->find($companyId),
            'user'    => session()->get(),
        ]);
    }

    public function updateCompany()
    {
        if ($r = $this->requireAdmin()) return $r;
        $companyId = $this->companyId();
        if (!$companyId) return redirect()->to('/company-selection');

        $data = [
            'name'             => $this->request->getPost('name'),
            'address'          => $this->request->getPost('address'),
            'email'            => $this->request->getPost('email'),
            'mobile'           => $this->request->getPost('mobile'),
            'gstin'            => $this->request->getPost('gstin'),
            'pan'              => $this->request->getPost('pan'),
            'sac_code'         => $this->request->getPost('sac_code'),
            'cgst_rate'        => (float) $this->request->getPost('cgst_rate'),
            'sgst_rate'        => (float) $this->request->getPost('sgst_rate'),
            'igst_rate'        => (float) $this->request->getPost('igst_rate'),
            'terms_conditions' => $this->request->getPost('terms_conditions'),
        ];

        // 1. Handle Base64 Signature Canvas first
        $signatureBase64 = $this->request->getPost('signature_base64');
        if (!empty($signatureBase64)) {
            if (strpos($signatureBase64, 'image/png') !== false) {
                return redirect()->back()->with('error', 'Please do a HARD REFRESH (Ctrl+Shift+R or Ctrl+F5) of your browser to apply the latest signature fixes, then draw again.');
            }
            $parts = explode(',', $signatureBase64);
            if (count($parts) == 2) {
                $image_base64 = base64_decode($parts[1]);
                $fileName = 'sig_' . $companyId . '_' . time() . '_canvas.jpg';
                $uploadPath = FCPATH . 'uploads/signatures';
                
                if (!is_dir($uploadPath)) {
                    mkdir($uploadPath, 0777, true);
                }
                
                file_put_contents($uploadPath . '/' . $fileName, $image_base64);
                $data['signature_path'] = 'uploads/signatures/' . $fileName;
            }
        }

        // 2. Handle File Upload (overrides canvas if both are somehow submitted)
        $sig = $this->request->getFile('signature');
        if ($sig && $sig->isValid() && !$sig->hasMoved()) {
            if (!in_array($sig->getMimeType(), ['image/png', 'image/jpeg', 'image/jpg', 'image/gif'])) {
                return redirect()->back()->with('error', 'Signature must be a PNG, JPG, or GIF image.');
            }
            $fileName = 'sig_' . $companyId . '_' . time() . '.' . $sig->getExtension();
            $sig->move(FCPATH . 'uploads/signatures/', $fileName);
            $data['signature_path'] = 'uploads/signatures/' . $fileName;
        }

        (new CompanyModel())->update($companyId, $data);
        session()->set('selected_company_name', $data['name']);
        return redirect()->back()->with('success', 'Company settings saved!');
    }

    public function deleteSignature()
    {
        if ($r = $this->requireAdmin()) return $r;
        $companyId = $this->companyId();
        if (!$companyId) return redirect()->to('/company-selection');

        $companyModel = new CompanyModel();
        $company = $companyModel->find($companyId);

        if (!empty($company['signature_path']) && file_exists(FCPATH . $company['signature_path'])) {
            unlink(FCPATH . $company['signature_path']);
        } elseif (!empty($company['signature_path']) && file_exists(WRITEPATH . $company['signature_path'])) {
            unlink(WRITEPATH . $company['signature_path']);
        }

        $companyModel->update($companyId, ['signature_path' => null]);
        
        return redirect()->back()->with('success', 'Signature deleted successfully!');
    }

    // ============================================================
    // CUSTOMERS / SHIPPERS
    // ============================================================

    public function customers()
    {
        if ($r = $this->requireAdmin()) return $r;
        $companyId = $this->companyId();
        
        $lookups = [
            'payment_type' => (new LookupValueModel())->getByType($companyId, 'payment_type'),
        ];
        
        return view('masters/customers', [
            'customers' => (new CustomerModel())->getByCompany($companyId),
            'contacts'  => (new \App\Models\ContactsMasterModel())->getByCompany($companyId),
            'lookups'   => $lookups,
            'user'      => session()->get(),
        ]);
    }

    public function createCustomer()
    {
        if ($r = $this->requireAdmin()) return $r;
        $post = $this->request->getPost();
        
        // SECURITY FIX: Enforce session company_id (prevent IDOR)
        $post['company_id'] = $this->companyId();

        $model = new CustomerModel();
        if (!$model->insert($post)) {
            return redirect()->back()->with('error', implode(', ', $model->errors()));
        }
        return redirect()->to('/masters/customers')->with('success', 'Customer created!');
    }

    public function editCustomer(int $id)
    {
        if ($r = $this->requireAdmin()) return $r;
        $companyId = $this->companyId();
        $customer = (new CustomerModel())->where('id', $id)->where('company_id', $companyId)->first();
        if (!$customer) return redirect()->to('/masters/customers')->with('error', 'Customer not found!');
        
        $lookups = [
            'payment_type' => (new LookupValueModel())->getByType($companyId, 'payment_type'),
        ];
        
        return view('masters/customer_form', [
            'customer' => $customer,
            'contacts' => (new \App\Models\ContactsMasterModel())->getByCompany($companyId),
            'lookups'  => $lookups,
            'user'     => session()->get()
        ]);
    }

    public function updateCustomer(int $id)
    {
        if ($r = $this->requireAdmin()) return $r;
        $post = $this->request->getPost();
        
        // SECURITY FIX: Enforce session company_id (prevent IDOR)
        $post['company_id'] = $this->companyId();

        $model = new CustomerModel();
        if (!$model->where('id', $id)->where('company_id', $post['company_id'])->set($post)->update()) {
            return redirect()->back()->with('error', implode(', ', $model->errors()));
        }
        return redirect()->to('/masters/customers')->with('success', 'Customer updated!');
    }

    public function deleteCustomer(int $id)
    {
        if ($r = $this->requireAdmin()) return $r;
        (new CustomerModel())->where('id', $id)->where('company_id', $this->companyId())->delete();
        return $this->response->setJSON(['success' => true]);
    }

    // ============================================================
    // TRANSPORTERS
    // ============================================================

    public function transporters()
    {
        if ($r = $this->requireAdmin()) return $r;
        return view('masters/transporters', [
            'transporters' => (new TransporterModel())->getByCompany($this->companyId()),
            'user'         => session()->get(),
        ]);
    }

    public function createTransporter()
    {
        if ($r = $this->requireAdmin()) return $r;
        $post = $this->request->getPost();
        
        // SECURITY FIX: Enforce session company_id (prevent IDOR)
        $post['company_id'] = $this->companyId();

        $model = new TransporterModel();
        if (!$model->insert($post)) {
            return redirect()->back()->with('error', implode(', ', $model->errors()));
        }
        return redirect()->to('/masters/transporters')->with('success', 'Transporter created!');
    }

    public function updateTransporter(int $id)
    {
        if ($r = $this->requireAdmin()) return $r;
        $post = $this->request->getPost();
        
        // SECURITY FIX: Enforce session company_id (prevent IDOR)
        $post['company_id'] = $this->companyId();

        $model = new TransporterModel();
        if (!$model->where('id', $id)->where('company_id', $post['company_id'])->set($post)->update()) {
            return redirect()->back()->with('error', implode(', ', $model->errors()));
        }
        return redirect()->to('/masters/transporters')->with('success', 'Transporter updated!');
    }

    public function deleteTransporter(int $id)
    {
        if ($r = $this->requireAdmin()) return $r;
        (new TransporterModel())->where('id', $id)->where('company_id', $this->companyId())->delete();
        return $this->response->setJSON(['success' => true]);
    }

    // ============================================================
    // DRIVERS
    // ============================================================

    public function drivers()
    {
        if ($r = $this->requireAdmin()) return $r;
        return view('masters/drivers', [
            'drivers' => (new DriverModel())->getByCompany($this->companyId()),
            'user'    => session()->get(),
        ]);
    }

    public function createDriver()
    {
        if ($r = $this->requireAdmin()) return $r;
        $post = $this->request->getPost();
        
        // SECURITY FIX: Enforce session company_id (prevent IDOR)
        $post['company_id'] = $this->companyId();

        $model = new DriverModel();
        if (!$model->insert($post)) {
            return redirect()->back()->with('error', implode(', ', $model->errors()));
        }
        return redirect()->to('/masters/drivers')->with('success', 'Driver created!');
    }

    public function updateDriver(int $id)
    {
        if ($r = $this->requireAdmin()) return $r;
        $post = $this->request->getPost();
        
        // SECURITY FIX: Enforce session company_id (prevent IDOR)
        $post['company_id'] = $this->companyId();

        $model = new DriverModel();
        if (!$model->where('id', $id)->where('company_id', $post['company_id'])->set($post)->update()) {
            return redirect()->back()->with('error', implode(', ', $model->errors()));
        }
        return redirect()->to('/masters/drivers')->with('success', 'Driver updated!');
    }

    public function deleteDriver(int $id)
    {
        if ($r = $this->requireAdmin()) return $r;
        (new DriverModel())->where('id', $id)->where('company_id', $this->companyId())->delete();
        return $this->response->setJSON(['success' => true]);
    }

    // ============================================================
    // AIRLINES
    // ============================================================

    public function airlines()
    {
        if ($r = $this->requireAdmin()) return $r;
        return view('masters/airlines', [
            'airlines' => (new AirlineModel())->getByCompany($this->companyId()),
            'user'     => session()->get(),
        ]);
    }

    public function createAirline()
    {
        if ($r = $this->requireAdmin()) return $r;
        $post = $this->request->getPost();
        
        // SECURITY FIX: Enforce session company_id (prevent IDOR)
        $post['company_id'] = $this->companyId();

        $model = new AirlineModel();
        if (!$model->insert($post)) {
            return redirect()->back()->with('error', implode(', ', $model->errors()));
        }
        return redirect()->to('/masters/airlines')->with('success', 'Airline created!');
    }

    public function updateAirline(int $id)
    {
        if ($r = $this->requireAdmin()) return $r;
        $post = $this->request->getPost();
        
        // SECURITY FIX: Enforce session company_id (prevent IDOR)
        $post['company_id'] = $this->companyId();

        $model = new AirlineModel();
        if (!$model->where('id', $id)->where('company_id', $post['company_id'])->set($post)->update()) {
            return redirect()->back()->with('error', implode(', ', $model->errors()));
        }
        return redirect()->to('/masters/airlines')->with('success', 'Airline updated!');
    }

    public function deleteAirline(int $id)
    {
        if ($r = $this->requireAdmin()) return $r;
        (new AirlineModel())->where('id', $id)->where('company_id', $this->companyId())->delete();
        return $this->response->setJSON(['success' => true]);
    }

    // ============================================================
    // LOOKUP VALUES
    // ============================================================

    public function lookups(string $type)
    {
        if ($r = $this->requireAdmin()) return $r;
        if (!array_key_exists($type, LookupValueModel::TYPES)) {
            return redirect()->back()->with('error', 'Invalid lookup type!');
        }
        return view('masters/lookups', [
            'type'       => $type,
            'type_label' => LookupValueModel::TYPES[$type],
            'values'     => (new LookupValueModel())->getByType($this->companyId(), $type),
            'all_types'  => LookupValueModel::TYPES,
            'user'       => session()->get(),
        ]);
    }

    public function createLookup(string $type)
    {
        if ($r = $this->requireAdmin()) return $r;
        if (!array_key_exists($type, LookupValueModel::TYPES)) {
            return redirect()->back()->with('error', 'Invalid lookup type!');
        }
        $post = $this->request->getPost();
        
        // SECURITY FIX: Enforce session company_id (prevent IDOR)
        $post['company_id'] = $this->companyId();
        $data = [
            'company_id' => $post['company_id'],
            'type'       => $type,
            'is_active'  => isset($post['is_active']) ? 1 : 0
        ];

        if ($type === 'origin' || $type === 'destination') {
            $data['pincode'] = $post['pincode'] ?? null;
            $data['city'] = $post['city'] ?? null;
            $data['district'] = $post['district'] ?? null;
            $data['state'] = $post['state'] ?? null;
            
            $data['value'] = trim(($post['city'] ?? '') . ', ' . ($post['state'] ?? ''), ', ');
            if (empty($data['value'])) $data['value'] = 'Unknown Location';
        } else {
            $data['value'] = $post['value'];
        }

        (new LookupValueModel())->insert($data);
        return redirect()->to('/masters/lookups/' . $type)->with('success', 'Value added!');
    }

    /**
     * Remove a lookup value.
     */
    public function deleteLookup(int $id)
    {
        if ($r = $this->requireAdmin()) return $r;
        (new LookupValueModel())->where('id', $id)->where('company_id', $this->companyId())->delete();
        return $this->response->setJSON(['success' => true]);
    }

    // ============================================================
    // DATATABLES SERVER-SIDE PROCESSING (SSP)
    // ============================================================

    public function ajaxDatatable(string $type)
    {
        if ($r = $this->requireAdmin()) return $r;

        $post = $this->request->getPost();
        $draw = (int) ($post['draw'] ?? 1);
        $start = (int) ($post['start'] ?? 0);
        $length = (int) ($post['length'] ?? 10);
        $searchValue = $post['search']['value'] ?? '';
        
        $orderColumnIdx = $post['order'][0]['column'] ?? null;
        $orderDir = $post['order'][0]['dir'] ?? 'asc';
        $columns = $post['columns'] ?? [];

        $model = null;
        $searchFields = [];
        
        switch ($type) {
            case 'customers':
                $model = new CustomerModel();
                $searchFields = ['name', 'code', 'email', 'mobile'];
                break;
            case 'transporters':
                $model = new TransporterModel();
                $searchFields = ['name', 'mobile'];
                break;
            case 'drivers':
                $model = new DriverModel();
                $searchFields = ['name', 'mobile', 'vehicle_no', 'license_no'];
                break;
            case 'airlines':
                $model = new AirlineModel();
                $searchFields = ['name', 'code'];
                break;
            default:
                return $this->response->setJSON(['error' => 'Invalid type']);
        }

        $builder = $model->where('company_id', $this->companyId());

        // Total records
        $totalRecords = $builder->countAllResults(false);

        // Search
        if (!empty($searchValue)) {
            $builder->groupStart();
            foreach ($searchFields as $field) {
                $builder->orLike($field, $searchValue);
            }
            $builder->groupEnd();
        }

        $filteredRecords = $builder->countAllResults(false);

        // Order
        if ($orderColumnIdx !== null && isset($columns[$orderColumnIdx]['data'])) {
            $orderBy = $columns[$orderColumnIdx]['data'];
            // Basic security check: ensure column name is alphanumeric or underscore
            if (preg_match('/^[a-zA-Z0-9_]+$/', $orderBy)) {
                $builder->orderBy($orderBy, $orderDir);
            }
        } else {
            $builder->orderBy('id', 'desc');
        }

        // Pagination
        if ($length != -1) {
            $builder->limit($length, $start);
        }

        $data = $builder->get()->getResultArray();

        return $this->response->setJSON([
            'draw' => $draw,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data
        ]);
    }

    // ============================================================
    // JSON API ENDPOINTS — used by booking form JavaScript
    // ============================================================

    public function apiCustomers()
    {
        return $this->response->setJSON((new CustomerModel())->getByCompany($this->companyId()));
    }

    public function apiCustomer(int $id)
    {
        $c = (new CustomerModel())->where('id', $id)->where('company_id', $this->companyId())->first();
        return $this->response->setJSON($c ?: []);
    }

    public function apiTransporters()
    {
        return $this->response->setJSON((new TransporterModel())->getByCompany($this->companyId()));
    }

    public function apiTransporter(int $id)
    {
        $t = (new TransporterModel())->where('id', $id)->where('company_id', $this->companyId())->first();
        return $this->response->setJSON($t ?: []);
    }

    public function apiDrivers()
    {
        return $this->response->setJSON((new DriverModel())->getByCompany($this->companyId()));
    }

    public function apiDriver(int $id)
    {
        $d = (new DriverModel())->where('id', $id)->where('company_id', $this->companyId())->first();
        return $this->response->setJSON($d ?: []);
    }

    public function apiAirlines()
    {
        return $this->response->setJSON((new AirlineModel())->getByCompany($this->companyId()));
    }

    public function apiLookup(string $type)
    {
        return $this->response->setJSON((new LookupValueModel())->getByType($this->companyId(), $type));
    }

    public function apiCompanyGst()
    {
        $c = (new CompanyModel())->select('cgst_rate, sgst_rate, igst_rate')->find($this->companyId());
        return $this->response->setJSON($c ?: ['cgst_rate' => 0, 'sgst_rate' => 0, 'igst_rate' => 0]);
    }
}
