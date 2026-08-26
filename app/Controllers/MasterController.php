<?php

namespace App\Controllers;

use App\Models\CustomerModel;
use App\Models\TransporterModel;
use App\Models\DriverModel;
use App\Models\AirlineModel;
use App\Models\LookupValueModel;
use App\Models\CompanyModel;
use App\Models\InvoiceTemplateModel;
use App\Models\DocketSeriesModel;
use App\Models\CustomerRateModel;
use App\Services\CustomerRateService;

class MasterController extends BaseController
{
    /**
     * Guard: admin only. Returns redirect response or null.
     * For AJAX callers (Quick Master modal etc.) returns JSON error instead of a redirect.
     */
    private function requireAdmin()
    {
        if (session()->get('role') !== 'admin') {
            if ($this->request->isAJAX()) {
                return $this->response->setJSON([
                    'status'  => 'error',
                    'message' => 'Admin access is required to create master records. Please contact your administrator.'
                ]);
            }
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
            'terms_conditions' => $this->request->getPost('terms_conditions'),
        ];

        (new CompanyModel())->update($companyId, $data);
        session()->set('selected_company_name', $data['name']);
        return redirect()->back()->with('success', 'Company settings saved!');
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
            'origin'       => (new LookupValueModel())->getByType($companyId, 'origin'),
            'destination'  => (new LookupValueModel())->getByType($companyId, 'destination'),
        ];
        
        return view('masters/customers', [
            'customers' => (new CustomerModel())->getByCompany($companyId),
            'contacts'  => (new \App\Models\ContactsMasterModel())->getByCompany($companyId),
            'customer_rates' => [],
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

        try {
            $customerId = (new CustomerRateService())->createCustomer((int) $post['company_id'], $post);
        } catch (\Throwable $e) {
            $errors = $e->getMessage();
            if ($this->request->isAJAX()) {
                return $this->response->setJSON(['status' => 'error', 'message' => $errors]);
            }
            return redirect()->back()->with('error', $errors);
        }
        
        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'status'      => 'success',
                'message'     => 'Customer created!',
                'id'          => $customerId,
                'name'        => $post['name'],
                'code'        => $post['code'] ?? '',
                'city'        => $post['city'] ?? '',
                'bill_to'     => $post['bill_to'] ?? '',
                'consignee'   => $post['consignee'] ?? '',
                'payment_type'=> $post['payment_type'] ?? '',
                'gst_number'  => $post['gst_number'] ?? '',
                'gst_state'   => $post['gst_state'] ?? '',
                'csrf_hash'   => csrf_hash(),
            ]);
        }
        return redirect()->to('/masters/customers/edit/' . $customerId)->with('success', 'Customer created!');
    }

    public function editCustomer(int $id)
    {
        if ($r = $this->requireAdmin()) return $r;
        $companyId = $this->companyId();
        $customer = (new CustomerModel())->where('id', $id)->where('company_id', $companyId)->first();
        if (!$customer) return redirect()->to('/masters/customers')->with('error', 'Customer not found!');
        
        $lookups = [
            'payment_type' => (new LookupValueModel())->getByType($companyId, 'payment_type'),
            'origin'       => (new LookupValueModel())->getByType($companyId, 'origin'),
            'destination'  => (new LookupValueModel())->getByType($companyId, 'destination'),
        ];
        
        return view('masters/customer_form', [
            'customer' => $customer,
            'customer_rates' => (new CustomerRateModel())
                ->where('company_id', $companyId)
                ->where('customer_id', (int) $customer['id'])
                ->where('is_active', 1)
                ->orderBy('origin', 'ASC')
                ->orderBy('destination', 'ASC')
                ->findAll(),
            'customer_rate_history' => (new CustomerRateModel())
                ->where('company_id', $companyId)
                ->where('customer_id', (int) $customer['id'])
                ->where('is_active', 0)
                ->orderBy('effective_from', 'DESC')
                ->orderBy('id', 'DESC')
                ->findAll(),
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

        try {
            (new CustomerRateService())->updateCustomer((int) $post['company_id'], $id, $post);
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
        return redirect()->to('/masters/customers/edit/' . $id)->with('success', 'Customer updated!');
    }

    public function deleteCustomer(int $id)
    {
        if ($r = $this->requireAdmin()) return $r;
        try {
            $deleted = (new CustomerRateService())->deleteCustomer($this->companyId(), $id);
            if (!$deleted) {
                return $this->response->setStatusCode(404)->setJSON([
                    'success' => false,
                    'message' => 'Customer not found.',
                    'csrf_hash' => csrf_hash(),
                ]);
            }
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Customer deleted successfully.',
                'csrf_hash' => csrf_hash(),
            ]);
        } catch (\Throwable $e) {
            log_message('error', '[Customer delete] ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Unable to delete the customer. Please try again.',
                'csrf_hash' => csrf_hash(),
            ]);
        }
    }

    public function lookupCustomerRate()
    {
        $companyId = $this->companyId();
        if (!$companyId) {
            return $this->response->setStatusCode(401)->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
        }

        $customerName = trim((string) $this->request->getPost('customer_name'));
        $origin = trim((string) $this->request->getPost('origin'));
        $destination = trim((string) $this->request->getPost('destination'));
        $bookingDate = substr(trim((string) $this->request->getPost('booking_date')), 0, 10) ?: date('Y-m-d');
        $category = trim((string) $this->request->getPost('material_category'));

        $rate = (new CustomerRateModel())->findRate($companyId, $customerName, $category, $bookingDate, $origin, $destination);
        if (!$rate) {
            return $this->response->setJSON([
                'status' => 'success',
                'found' => false,
                'csrf_hash' => csrf_hash(),
            ]);
        }

        return $this->response->setJSON([
            'status' => 'success',
            'found' => true,
            'id' => (int) ($rate['id'] ?? 0),
            'rate' => (float) ($rate['rate'] ?? 0),
            'origin' => $rate['origin'] ?? '',
            'destination' => $rate['destination'] ?? '',
            'material_category' => $rate['material_category'] ?? '',
            'csrf_hash' => csrf_hash(),
        ]);
    }

    public function saveCustomerRate()
    {
        $companyId = $this->companyId();
        if (!$companyId) {
            return $this->response->setStatusCode(401)->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
        }

        $customerName = trim((string) $this->request->getPost('customer_name'));
        $origin = trim((string) $this->request->getPost('origin'));
        $destination = trim((string) $this->request->getPost('destination'));
        $category = trim((string) $this->request->getPost('material_category'));
        $rate = (float) $this->request->getPost('rate');
        $rateId = (int) ($this->request->getPost('rate_id') ?? 0);

        if ($customerName === '' || $origin === '' || $destination === '' || $rate <= 0) {
            return $this->response->setStatusCode(422)->setJSON([
                'status' => 'error',
                'message' => 'Customer, origin, destination, and item rate are required.',
                'csrf_hash' => csrf_hash(),
            ]);
        }

        try {
            $saved = (new CustomerRateService())->saveRuntimeRate(
                $companyId,
                $customerName,
                $origin,
                $destination,
                $category,
                $rate,
                $rateId
            );
        } catch (\Throwable $e) {
            $statusCode = in_array($e->getCode(), [404, 409], true) ? $e->getCode() : 422;
            return $this->response->setStatusCode($statusCode)->setJSON([
                'status' => 'error',
                'message' => $e->getMessage(),
                'csrf_hash' => csrf_hash(),
            ]);
        }

        $rateId = (int) $saved['id'];

        return $this->response->setJSON([
            'status' => 'success',
            'message' => 'Customer item rate saved.',
            'id' => $rateId,
            'rate' => $rate,
            'csrf_hash' => csrf_hash(),
        ]);
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
            $errors = implode(', ', $model->errors());
            if ($this->request->isAJAX()) {
                return $this->response->setJSON(['status' => 'error', 'message' => $errors]);
            }
            return redirect()->back()->with('error', $errors);
        }
        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'status'    => 'success',
                'message'   => 'Transporter created!',
                'id'        => $model->getInsertID(),
                'name'      => $post['name'],
                'mobile'    => $post['mobile'] ?? '',
                'csrf_hash' => csrf_hash(),
            ]);
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
            $errors = implode(', ', $model->errors());
            if ($this->request->isAJAX()) {
                return $this->response->setJSON(['status' => 'error', 'message' => $errors]);
            }
            return redirect()->back()->with('error', $errors);
        }
        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'status'     => 'success',
                'message'    => 'Driver created!',
                'id'         => $model->getInsertID(),
                'name'       => $post['name'],
                'mobile'     => $post['mobile'] ?? '',
                'vehicle_no' => $post['vehicle_no'] ?? '',
                'license_no' => $post['license_no'] ?? '',
                'csrf_hash'  => csrf_hash(),
            ]);
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
            $errors = implode(', ', $model->errors());
            if ($this->request->isAJAX()) {
                return $this->response->setJSON(['status' => 'error', 'message' => $errors]);
            }
            return redirect()->back()->with('error', $errors);
        }
        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'status'    => 'success',
                'message'   => 'Airline created!',
                'id'        => $model->getInsertID(),
                'name'      => $post['name'],
                'code'      => $post['code'] ?? '',
                'csrf_hash' => csrf_hash(),
            ]);
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
    // BANK ACCOUNTS
    // ============================================================

    public function bankAccounts()
    {
        if ($r = $this->requireAdmin()) return $r;
        return view('masters/bank_accounts', [
            'user'    => session()->get(),
        ]);
    }

    public function invoiceTemplates()
    {
        if ($r = $this->requireAdmin()) return $r;
        return view('masters/invoice_templates', [
            'user' => session()->get(),
        ]);
    }

    public function createInvoiceTemplate()
    {
        if ($r = $this->requireAdmin()) return $r;
        $post = $this->request->getPost();
        $post['company_id'] = $this->companyId();
        $post['gst_type'] = ($post['gst_type'] ?? '') === 'non_gst' ? 'non_gst' : 'gst';
        $post['prefix'] = strtoupper(trim((string) ($post['prefix'] ?? '')));
        $post['is_active'] = isset($post['is_active']) ? 1 : 0;

        $model = new InvoiceTemplateModel();
        if (!$model->insert($post)) {
            return redirect()->back()->with('error', implode(', ', $model->errors()));
        }

        return redirect()->to('/masters/invoice-templates')->with('success', 'Invoice master saved!');
    }

    public function updateInvoiceTemplate(int $id)
    {
        if ($r = $this->requireAdmin()) return $r;
        $post = $this->request->getPost();
        $companyId = $this->companyId();
        $post['company_id'] = $companyId;
        $post['gst_type'] = ($post['gst_type'] ?? '') === 'non_gst' ? 'non_gst' : 'gst';
        $post['prefix'] = strtoupper(trim((string) ($post['prefix'] ?? '')));
        $post['is_active'] = isset($post['is_active']) ? 1 : 0;

        $model = new InvoiceTemplateModel();
        if (!$model->where('id', $id)->where('company_id', $companyId)->set($post)->update()) {
            return redirect()->back()->with('error', implode(', ', $model->errors()));
        }

        return redirect()->to('/masters/invoice-templates')->with('success', 'Invoice master updated!');
    }

    public function deleteInvoiceTemplate(int $id)
    {
        if ($r = $this->requireAdmin()) return $r;
        (new InvoiceTemplateModel())->where('id', $id)->where('company_id', $this->companyId())->delete();
        return $this->response->setJSON(['success' => true]);
    }

    public function docketSeries()
    {
        if ($r = $this->requireAdmin()) return $r;
        return view('masters/docket_series', [
            'user' => session()->get(),
        ]);
    }

    public function createDocketSeries()
    {
        if ($r = $this->requireAdmin()) return $r;
        $post = $this->request->getPost();
        $post['company_id'] = $this->companyId();
        $post['entry_mode'] = ($post['entry_mode'] ?? '') === 'manual' ? 'manual' : 'auto';
        $post['prefix'] = strtoupper(trim((string) ($post['prefix'] ?? '')));
        $post['current_number'] = 10000;
        $post['is_active'] = isset($post['is_active']) ? 1 : 0;

        $model = new DocketSeriesModel();
        if (!$model->insert($post)) {
            return redirect()->back()->with('error', implode(', ', $model->errors()));
        }

        return redirect()->to('/masters/docket-series')->with('success', 'Docket master saved!');
    }

    public function updateDocketSeries(int $id)
    {
        if ($r = $this->requireAdmin()) return $r;
        $post = $this->request->getPost();
        $companyId = $this->companyId();
        $post['company_id'] = $companyId;
        $post['entry_mode'] = ($post['entry_mode'] ?? '') === 'manual' ? 'manual' : 'auto';
        $post['prefix'] = strtoupper(trim((string) ($post['prefix'] ?? '')));
        $post['is_active'] = isset($post['is_active']) ? 1 : 0;
        unset($post['current_number']);

        $model = new DocketSeriesModel();
        if (!$model->where('id', $id)->where('company_id', $companyId)->set($post)->update()) {
            return redirect()->back()->with('error', implode(', ', $model->errors()));
        }

        return redirect()->to('/masters/docket-series')->with('success', 'Docket master updated!');
    }

    public function deleteDocketSeries(int $id)
    {
        if ($r = $this->requireAdmin()) return $r;
        (new DocketSeriesModel())->where('id', $id)->where('company_id', $this->companyId())->delete();
        return $this->response->setJSON(['success' => true]);
    }

    public function createBankAccount()
    {
        if ($r = $this->requireAdmin()) return $r;
        $post = $this->request->getPost();
        
        $post['company_id'] = $this->companyId();
        
        $isDefault = isset($post['is_default']) ? 1 : 0;
        $post['is_default'] = $isDefault;

        $model = new \App\Models\BankAccountModel();
        
        if ($isDefault) {
            $model->where('company_id', $post['company_id'])->set(['is_default' => 0])->update();
        }

        if (!$model->insert($post)) {
            return redirect()->back()->with('error', implode(', ', $model->errors()));
        }
        return redirect()->to('/masters/bank-accounts')->with('success', 'Bank account added!');
    }

    public function updateBankAccount(int $id)
    {
        if ($r = $this->requireAdmin()) return $r;
        $post = $this->request->getPost();
        
        $post['company_id'] = $this->companyId();
        
        $isDefault = isset($post['is_default']) ? 1 : 0;
        $post['is_default'] = $isDefault;

        $model = new \App\Models\BankAccountModel();

        // Check if bank account exists and belongs to the company
        $exists = $model->where('id', $id)->where('company_id', $post['company_id'])->first();
        if (!$exists) {
            return redirect()->back()->with('error', 'Bank account not found!');
        }

        if ($isDefault) {
            $model->where('company_id', $post['company_id'])->set(['is_default' => 0])->update();
        }

        if (!$model->where('id', $id)->where('company_id', $post['company_id'])->set($post)->update()) {
            return redirect()->back()->with('error', implode(', ', $model->errors()));
        }
        return redirect()->to('/masters/bank-accounts')->with('success', 'Bank account updated!');
    }

    public function deleteBankAccount(int $id)
    {
        if ($r = $this->requireAdmin()) return $r;
        (new \App\Models\BankAccountModel())->where('id', $id)->where('company_id', $this->companyId())->delete();
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
            if ($this->request->isAJAX()) {
                return $this->response->setJSON(['status' => 'error', 'message' => 'Invalid lookup type!']);
            }
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
            $data['value'] = $post['value'] ?? '';
        }

        $model = new LookupValueModel();
        if (!$model->insert($data)) {
            $errors = implode(', ', $model->errors());
            if ($this->request->isAJAX()) {
                return $this->response->setJSON(['status' => 'error', 'message' => $errors]);
            }
            return redirect()->back()->with('error', $errors);
        }
        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'status'    => 'success',
                'message'   => 'Value added!',
                'id'        => $model->getInsertID(),
                'value'     => $data['value'],
                'csrf_hash' => csrf_hash(),
            ]);
        }
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
                $searchFields = ['name', 'code', 'email', 'city'];
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
            case 'bank-accounts':
                $model = new \App\Models\BankAccountModel();
                $searchFields = ['account_name', 'bank_name', 'account_number', 'branch_name', 'ifsc_code'];
                break;
            case 'invoice-templates':
                $model = new InvoiceTemplateModel();
                $searchFields = ['name', 'gst_type', 'prefix'];
                break;
            case 'docket-series':
                $model = new DocketSeriesModel();
                $searchFields = ['name', 'prefix', 'entry_mode'];
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

        session_write_close(); // Prevent database session write shutdown errors overriding 200 OK status
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

    public function generateDocket()
    {
        $userId = session()->get('user_id');
        $companyId = $this->companyId();
        if (!$userId || !$companyId) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized or no active company selected.']);
        }

        $branchId = session()->get('branch_id') ?? 1; // Default to branch 1 if not set yet
        $year = date('Y');

        $excludeDockets = $this->request->getPost('exclude_dockets');
        if (!is_array($excludeDockets)) {
            $excludeDockets = [];
        }
        $excludeDockets = array_map('trim', $excludeDockets);
        $seriesId = (int) ($this->request->getPost('docket_series_id') ?? 0);

        try {
            $db = \Config\Database::connect();
            $series = null;
            if ($seriesId > 0) {
                $series = $db->table('docket_series')
                    ->where('id', $seriesId)
                    ->where('company_id', $companyId)
                    ->where('entry_mode', 'auto')
                    ->where('is_active', 1)
                    ->get()
                    ->getRowArray();
            }

            if ($series) {
                $db->transStart();
                $locked = $db->query(
                    'SELECT * FROM docket_series WHERE id = ? AND company_id = ? FOR UPDATE',
                    [$seriesId, $companyId]
                )->getRowArray();
                if (!$locked) {
                    throw new \RuntimeException('Docket series row could not be locked.');
                }

                $prefix = (string) ($locked['prefix'] ?? 'DCK-');
                $prefixLen = strlen($prefix) + 1;
                $maxMaster = (int) (($db->table('docket_master')
                    ->select("MAX(CAST(SUBSTRING(docket_no, {$prefixLen}) AS UNSIGNED)) AS max_num", false)
                    ->where('company_id', $companyId)
                    ->where('docket_no LIKE', $prefix . '%')
                    ->get()->getRowArray())['max_num'] ?? 0);
                $maxItems = (int) (($db->table('shipment_items')
                    ->select("MAX(CAST(SUBSTRING(shipment_items.docket_no, {$prefixLen}) AS UNSIGNED)) AS max_num", false)
                    ->join('bookings', 'bookings.id = shipment_items.booking_id')
                    ->where('bookings.company_id', $companyId)
                    ->where('shipment_items.docket_no LIKE', $prefix . '%')
                    ->get()->getRowArray())['max_num'] ?? 0);

                $nextVal = max((int) ($locked['current_number'] ?? 10000), $maxMaster, $maxItems) + 1;
                $docketNo = $prefix . $nextVal;

                $checkExists = function($no) use ($db, $companyId, $excludeDockets) {
                    $existsInMaster = $db->table('docket_master')->where('company_id', $companyId)->where('docket_no', $no)->get()->getRowArray();
                    if ($existsInMaster) return true;
                    $existsInItems = $db->table('shipment_items')->join('bookings', 'bookings.id = shipment_items.booking_id')->where('bookings.company_id', $companyId)->where('shipment_items.docket_no', $no)->get()->getRowArray();
                    if ($existsInItems) return true;
                    return in_array($no, $excludeDockets, true);
                };

                while ($checkExists($docketNo)) {
                    $nextVal++;
                    $docketNo = $prefix . $nextVal;
                }

                $db->table('docket_series')
                    ->where('id', $seriesId)
                    ->where('company_id', $companyId)
                    ->update(['current_number' => $nextVal, 'updated_at' => date('Y-m-d H:i:s')]);

                $db->transComplete();
                if ($db->transStatus() === false) {
                    throw new \RuntimeException('Docket series transaction failed.');
                }

                session_write_close();
                return $this->response->setJSON(['status' => 'success', 'docket_no' => $docketNo]);
            }

            $db->transStart(); // Start atomic transaction

            // Locks sequence row for exclusive update
            $sql = "SELECT current_val, prefix FROM sys_sequences WHERE company_id = ? AND branch_id = ? AND sequence_type = ? AND suffix_year = ? FOR UPDATE";
            $seq = $db->query($sql, [$companyId, $branchId, 'docket', $year])->getRowArray();

            if (!$seq) {
                // Initialize sequence row using max from DB or default
                $rowMaster = $db->table('docket_master')
                                ->select("MAX(CAST(SUBSTRING(docket_no, 5) AS UNSIGNED)) AS max_num", false)
                                ->where('company_id', $companyId)
                                ->where('docket_no LIKE', 'DCK-%')
                                ->get()->getRowArray();
                $maxMaster = (int) ($rowMaster['max_num'] ?? 0);

                $rowItems = $db->table('shipment_items')
                               ->select("MAX(CAST(SUBSTRING(shipment_items.docket_no, 5) AS UNSIGNED)) AS max_num", false)
                               ->join('bookings', 'bookings.id = shipment_items.booking_id')
                               ->where('bookings.company_id', $companyId)
                               ->where('shipment_items.docket_no LIKE', 'DCK-%')
                               ->get()->getRowArray();
                $maxItems = (int) ($rowItems['max_num'] ?? 0);

                $startVal = max($maxMaster, $maxItems, 10000);
                $nextVal = $startVal + 1;

                $db->table('sys_sequences')->insert([
                    'company_id'    => $companyId,
                    'branch_id'     => $branchId,
                    'sequence_type' => 'docket',
                    'current_val'   => $nextVal,
                    'prefix'        => 'DCK-',
                    'suffix_year'   => $year
                ]);
            } else {
                $nextVal = intval($seq['current_val']) + 1;
                
                $db->table('sys_sequences')
                   ->where('company_id', $companyId)
                   ->where('branch_id', $branchId)
                   ->where('sequence_type', 'docket')
                   ->where('suffix_year', $year)
                   ->update(['current_val' => $nextVal]);
            }

            $docketNo = 'DCK-' . $nextVal;

            // Extra collision check against exclude list or override checks
            $checkExists = function($no) use ($db, $companyId, $excludeDockets) {
                $existsInMaster = $db->table('docket_master')
                                     ->where('company_id', $companyId)
                                     ->where('docket_no', $no)
                                     ->get()->getRowArray();
                if ($existsInMaster) return true;

                $existsInItems = $db->table('shipment_items')
                                    ->join('bookings', 'bookings.id = shipment_items.booking_id')
                                    ->where('bookings.company_id', $companyId)
                                    ->where('shipment_items.docket_no', $no)
                                    ->get()->getRowArray();
                if ($existsInItems) return true;

                return in_array($no, $excludeDockets);
            };

            while ($checkExists($docketNo)) {
                $nextVal++;
                $docketNo = 'DCK-' . $nextVal;
            }

            // Sync updated value back to sequence table
            $db->table('sys_sequences')
               ->where('company_id', $companyId)
               ->where('branch_id', $branchId)
               ->where('sequence_type', 'docket')
               ->where('suffix_year', $year)
               ->update(['current_val' => $nextVal]);

            $db->transComplete();

            if ($db->transStatus() === FALSE) {
                throw new \RuntimeException("Transaction failed: Concurrency lock timeout.");
            }

            session_write_close();
            return $this->response->setJSON(['status' => 'success', 'docket_no' => $docketNo]);
        } catch (\Throwable $e) {
            log_message('error', '[Generate Docket Error] ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            session_write_close();
            return $this->response->setJSON(['status' => 'error', 'message' => 'Failed to generate docket. Please try again.']);
        }
    }

    /**
     * Preview next docket number WITHOUT consuming/incrementing the sequence.
     * Used by the "Add Item" drawer to show a faint placeholder hint to the user.
     */
    public function previewDocket()
    {
        $userId    = session()->get('user_id');
        $companyId = $this->companyId();
        if (!$userId || !$companyId) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
        }

        $branchId = session()->get('branch_id') ?? 1;
        $year     = date('Y');

        $excludeDockets = $this->request->getPost('exclude_dockets');
        if (!is_array($excludeDockets)) {
            $excludeDockets = [];
        }
        $excludeDockets = array_map('trim', $excludeDockets);
        $seriesId = (int) ($this->request->getPost('docket_series_id') ?? 0);

        try {
            $db  = \Config\Database::connect();
            if ($seriesId > 0) {
                $series = $db->table('docket_series')
                    ->where('id', $seriesId)
                    ->where('company_id', $companyId)
                    ->where('entry_mode', 'auto')
                    ->where('is_active', 1)
                    ->get()
                    ->getRowArray();

                if ($series) {
                    $prefix = (string) ($series['prefix'] ?? 'DCK-');
                    $prefixLen = strlen($prefix) + 1;
                    $maxMaster = (int) (($db->table('docket_master')
                        ->select("MAX(CAST(SUBSTRING(docket_no, {$prefixLen}) AS UNSIGNED)) AS max_num", false)
                        ->where('company_id', $companyId)
                        ->where('docket_no LIKE', $prefix . '%')
                        ->get()->getRowArray())['max_num'] ?? 0);
                    $maxItems = (int) (($db->table('shipment_items')
                        ->select("MAX(CAST(SUBSTRING(shipment_items.docket_no, {$prefixLen}) AS UNSIGNED)) AS max_num", false)
                        ->join('bookings', 'bookings.id = shipment_items.booking_id')
                        ->where('bookings.company_id', $companyId)
                        ->where('shipment_items.docket_no LIKE', $prefix . '%')
                        ->get()->getRowArray())['max_num'] ?? 0);
                    $nextVal = max((int) ($series['current_number'] ?? 10000), $maxMaster, $maxItems) + 1;
                    $docketNo = $prefix . $nextVal;

                    $checkExists = function($no) use ($db, $companyId, $excludeDockets) {
                        $existsInMaster = $db->table('docket_master')->where('company_id', $companyId)->where('docket_no', $no)->get()->getRowArray();
                        if ($existsInMaster) return true;
                        $existsInItems = $db->table('shipment_items')->join('bookings', 'bookings.id = shipment_items.booking_id')->where('bookings.company_id', $companyId)->where('shipment_items.docket_no', $no)->get()->getRowArray();
                        if ($existsInItems) return true;
                        return in_array($no, $excludeDockets, true);
                    };

                    while ($checkExists($docketNo)) {
                        $nextVal++;
                        $docketNo = $prefix . $nextVal;
                    }

                    session_write_close();
                    return $this->response->setJSON([
                        'status'    => 'success',
                        'docket_no' => $docketNo
                    ]);
                }
            }

            $seq = $db->table('sys_sequences')
                      ->where('company_id',    $companyId)
                      ->where('branch_id',     $branchId)
                      ->where('sequence_type', 'docket')
                      ->where('suffix_year',   $year)
                      ->get()->getRowArray();

            if ($seq) {
                $nextVal  = intval($seq['current_val']) + 1;
                $prefix   = $seq['prefix'] ?? 'DCK-';
            } else {
                // Sequence not initialised yet — compute from existing data (same logic as generate)
                $rowMaster = $db->table('docket_master')
                                ->select("MAX(CAST(SUBSTRING(docket_no, 5) AS UNSIGNED)) AS max_num", false)
                                ->where('company_id', $companyId)
                                ->where('docket_no LIKE', 'DCK-%')
                                ->get()->getRowArray();

                $rowItems = $db->table('shipment_items')
                               ->select("MAX(CAST(SUBSTRING(shipment_items.docket_no, 5) AS UNSIGNED)) AS max_num", false)
                               ->join('bookings', 'bookings.id = shipment_items.booking_id')
                               ->where('bookings.company_id', $companyId)
                               ->where('shipment_items.docket_no LIKE', 'DCK-%')
                               ->get()->getRowArray();

                $startVal = max((int)($rowMaster['max_num'] ?? 0), (int)($rowItems['max_num'] ?? 0), 10000);
                $nextVal  = $startVal + 1;
                $prefix   = 'DCK-';
            }

            $docketNo = $prefix . $nextVal;

            // Extra collision check against exclude list or override checks (matching generateDocket)
            $checkExists = function($no) use ($db, $companyId, $excludeDockets) {
                $existsInMaster = $db->table('docket_master')
                                     ->where('company_id', $companyId)
                                     ->where('docket_no', $no)
                                     ->get()->getRowArray();
                if ($existsInMaster) return true;

                $existsInItems = $db->table('shipment_items')
                                    ->join('bookings', 'bookings.id = shipment_items.booking_id')
                                    ->where('bookings.company_id', $companyId)
                                    ->where('shipment_items.docket_no', $no)
                                    ->get()->getRowArray();
                if ($existsInItems) return true;

                return in_array($no, $excludeDockets);
            };

            while ($checkExists($docketNo)) {
                $nextVal++;
                $docketNo = $prefix . $nextVal;
            }

            session_write_close();
            return $this->response->setJSON([
                'status'    => 'success',
                'docket_no' => $docketNo
            ]);
        } catch (\Throwable $e) {
            log_message('error', '[Preview Docket Error] ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            session_write_close();
            return $this->response->setJSON(['status' => 'error', 'message' => 'Preview failed']);
        }
    }

    public function checkDocketUnique()
    {
        $companyId = $this->companyId();
        $docketNo = trim($this->request->getPost('docket_no') ?? '');
        $bookingId = (int) ($this->request->getPost('booking_id') ?? 0);
        
        if (empty($docketNo)) {
            return $this->response->setJSON(['status' => 'success', 'unique' => true]);
        }
        
        $shipmentModel = new \App\Models\ShipmentItemModel();
        
        $existing = $shipmentModel
            ->select('shipment_items.docket_no, bookings.awb_no')
            ->join('bookings', 'bookings.id = shipment_items.booking_id')
            ->where('bookings.company_id', $companyId)
            ->where('shipment_items.docket_no', $docketNo)
            ->where('bookings.id !=', $bookingId)
            ->first();
            
        if ($existing) {
            return $this->response->setJSON([
                'status' => 'success',
                'unique' => false,
                'message' => 'This docket number is already assigned to another AWB and cannot be used here.'
            ]);
        }
        
        return $this->response->setJSON(['status' => 'success', 'unique' => true]);
    }
}

