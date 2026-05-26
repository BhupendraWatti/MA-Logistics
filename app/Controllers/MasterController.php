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

        $sig = $this->request->getFile('signature');
        if ($sig && $sig->isValid() && !$sig->hasMoved()) {
            if (!in_array($sig->getMimeType(), ['image/png', 'image/jpeg', 'image/gif'])) {
                return redirect()->back()->with('error', 'Signature must be PNG, JPG, or GIF.');
            }
            $fileName = 'sig_' . $companyId . '_' . time() . '.' . $sig->getExtension();
            $sig->move(WRITEPATH . 'uploads/signatures/', $fileName);
            $data['signature_path'] = 'uploads/signatures/' . $fileName;
        }

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
        return view('masters/customers', [
            'customers' => (new CustomerModel())->getByCompany($this->companyId()),
            'user'      => session()->get(),
        ]);
    }

    public function createCustomer()
    {
        if ($r = $this->requireAdmin()) return $r;
        $post = $this->request->getPost();
        $post['company_id'] = $this->companyId();
        (new CustomerModel())->insert($post);
        return redirect()->to('/masters/customers')->with('success', 'Customer created!');
    }

    public function editCustomer(int $id)
    {
        if ($r = $this->requireAdmin()) return $r;
        $customer = (new CustomerModel())->where('id', $id)->where('company_id', $this->companyId())->first();
        if (!$customer) return redirect()->to('/masters/customers')->with('error', 'Customer not found!');
        return view('masters/customer_form', ['customer' => $customer, 'user' => session()->get()]);
    }

    public function updateCustomer(int $id)
    {
        if ($r = $this->requireAdmin()) return $r;
        (new CustomerModel())->where('id', $id)->where('company_id', $this->companyId())
            ->set($this->request->getPost())->update();
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
        $post['company_id'] = $this->companyId();
        (new TransporterModel())->insert($post);
        return redirect()->to('/masters/transporters')->with('success', 'Transporter created!');
    }

    public function updateTransporter(int $id)
    {
        if ($r = $this->requireAdmin()) return $r;
        (new TransporterModel())->where('id', $id)->where('company_id', $this->companyId())
            ->set($this->request->getPost())->update();
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
        $post['company_id'] = $this->companyId();
        (new DriverModel())->insert($post);
        return redirect()->to('/masters/drivers')->with('success', 'Driver created!');
    }

    public function updateDriver(int $id)
    {
        if ($r = $this->requireAdmin()) return $r;
        (new DriverModel())->where('id', $id)->where('company_id', $this->companyId())
            ->set($this->request->getPost())->update();
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
        $post['company_id'] = $this->companyId();
        (new AirlineModel())->insert($post);
        return redirect()->to('/masters/airlines')->with('success', 'Airline created!');
    }

    public function updateAirline(int $id)
    {
        if ($r = $this->requireAdmin()) return $r;
        (new AirlineModel())->where('id', $id)->where('company_id', $this->companyId())
            ->set($this->request->getPost())->update();
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
        (new LookupValueModel())->insert([
            'company_id' => $this->companyId(),
            'type'       => $type,
            'value'      => $this->request->getPost('value'),
            'sort_order' => (int) $this->request->getPost('sort_order'),
        ]);
        return redirect()->to('/masters/lookups/' . $type)->with('success', 'Value added!');
    }

    public function deleteLookup(int $id)
    {
        if ($r = $this->requireAdmin()) return $r;
        (new LookupValueModel())->where('id', $id)->where('company_id', $this->companyId())->delete();
        return $this->response->setJSON(['success' => true]);
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
