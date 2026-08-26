<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Filters\ApiBasicAuthFilter;
use App\Models\AirlineModel;
use App\Models\BookingModel;
use App\Models\CompanyModel;
use App\Models\CustomerModel;
use App\Models\CustomerRateModel;
use App\Models\DriverModel;
use App\Models\InvoiceDownloadModel;
use App\Models\LookupValueModel;
use App\Models\ShipmentItemModel;
use App\Models\TrackingHistoryModel;
use App\Models\TransporterModel;
use App\Models\UserModel;
use App\Services\BookingService;
use App\Services\CustomerRateService;
use CodeIgniter\HTTP\ResponseInterface;

class V1Controller extends BaseController
{
    public function login(): ResponseInterface
    {
        return $this->run(function () {
            $data = $this->payload();
            $username = trim((string) ($data['username'] ?? ''));
            $password = (string) ($data['password'] ?? '');
            if (($username === '' || $password === '') && stripos($this->request->getHeaderLine('Authorization'), 'Basic ') === 0) {
                $decoded = base64_decode(substr($this->request->getHeaderLine('Authorization'), 6), true);
                if (is_string($decoded) && str_contains($decoded, ':')) {
                    [$username, $password] = explode(':', $decoded, 2);
                }
            }
            if ($username === '' || $password === '') {
                return $this->error('Username and password are required.', 422);
            }

            $user = (new UserModel())->attemptLogin(compact('username', 'password'));
            if (!$user || !(int) ($user['is_active'] ?? 0)) {
                return $this->error('Invalid username or password.', 401);
            }
            ApiBasicAuthFilter::establishSession($user);

            return $this->success([
                'user' => $this->publicUser($user),
                'companies' => array_map(fn (array $company) => $this->publicCompany($company), (new CompanyModel())->orderBy('name')->findAll()),
                'auth_type' => 'session_or_basic',
                'csrf_required' => false,
            ], 'Login successful.');
        });
    }

    public function logout(): ResponseInterface
    {
        session()->destroy();
        return $this->success(null, 'Logout successful.');
    }

    public function companies(): ResponseInterface
    {
        return $this->run(fn () => $this->success(array_map(fn (array $company) => $this->publicCompany($company), (new CompanyModel())->orderBy('name')->findAll())));
    }

    public function selectCompany(): ResponseInterface
    {
        return $this->run(function () {
            $id = (int) ($this->payload()['company_id'] ?? 0);
            $company = $id > 0 ? (new CompanyModel())->find($id) : null;
            if (!$company) {
                return $this->error('A valid company_id is required.', 422);
            }
            session()->set(['selected_company_id' => $id, 'selected_company_name' => $company['name'] ?? $company['company_name'] ?? '']);
            return $this->success(['company' => $this->publicCompany($company), 'company_id' => $id], 'Company selected.');
        });
    }

    public function company(): ResponseInterface
    {
        return $this->run(function () {
            if (session()->get('role') !== 'admin') {
                throw new \RuntimeException('Admin access required.', 403);
            }
            return $this->success((new CompanyModel())->find($this->companyId()));
        });
    }

    public function customers(): ResponseInterface
    {
        return $this->run(fn () => $this->success((new CustomerModel())->getByCompany($this->companyId())));
    }

    public function customer(int $id): ResponseInterface
    {
        return $this->run(function () use ($id) {
            $row = (new CustomerModel())->where('company_id', $this->companyId())->find($id);
            return $row ? $this->success($row) : $this->error('Customer not found.', 404);
        });
    }

    public function createCustomer(): ResponseInterface
    {
        return $this->run(function () {
            $this->requirePermission('can_create');
            $id = (new CustomerRateService())->createCustomer($this->companyId(), $this->payload());
            $row = (new CustomerModel())->find($id);
            return $this->success(['customer_id' => $id, 'customer' => $row], 'Customer created.', 201);
        });
    }

    public function deleteCustomer(int $id): ResponseInterface
    {
        return $this->run(function () use ($id) {
            $this->requirePermission('can_delete');
            if (!(new CustomerRateService())->deleteCustomer($this->companyId(), $id)) {
                return $this->error('Customer not found.', 404);
            }
            return $this->success(['customer_id' => $id], 'Customer deleted.');
        });
    }

    public function generateDocket(): ResponseInterface
    {
        return $this->run(function () {
            $this->companyId();
            $this->request->setGlobal('post', $this->payload());
            $controller = new \App\Controllers\MasterController();
            $controller->initController($this->request, $this->response, service('logger'));
            $result = $controller->generateDocket();
            $body = json_decode((string) $result->getBody(), true);
            if (is_array($body) && ($body['status'] ?? '') === 'success') {
                $body['data'] = ['docket_no' => $body['docket_no'] ?? ''];
                return $this->response->setStatusCode($result->getStatusCode())->setJSON($body);
            }
            return $result;
        });
    }

    public function lookupCustomerRate(): ResponseInterface
    {
        return $this->run(function () {
            $data = $this->payload();
            foreach (['customer_name', 'booking_date'] as $field) {
                if (trim((string) ($data[$field] ?? '')) === '') {
                    return $this->error("{$field} is required.", 422);
                }
            }
            if ((trim((string) ($data['origin'] ?? '')) === '') xor (trim((string) ($data['destination'] ?? '')) === '')) {
                return $this->error('origin and destination must be supplied together.', 422);
            }
            $rate = (new CustomerRateModel())->findRate(
                $this->companyId(),
                (string) $data['customer_name'],
                $data['material_category'] ?? null,
                substr((string) $data['booking_date'], 0, 10),
                $data['origin'] ?? null,
                $data['destination'] ?? null
            );
            return $rate ? $this->success($rate) : $this->error('No matching customer rate was found.', 404);
        });
    }

    public function transporters(): ResponseInterface
    {
        return $this->run(fn () => $this->success((new TransporterModel())->getByCompany($this->companyId())));
    }

    public function drivers(): ResponseInterface
    {
        return $this->run(fn () => $this->success((new DriverModel())->getByCompany($this->companyId())));
    }

    public function airlines(): ResponseInterface
    {
        return $this->run(fn () => $this->success((new AirlineModel())->getByCompany($this->companyId())));
    }

    public function lookups(string $type): ResponseInterface
    {
        return $this->run(function () use ($type) {
            if (!array_key_exists($type, LookupValueModel::TYPES)) {
                return $this->error('Unknown lookup type.', 404);
            }
            return $this->success((new LookupValueModel())->getByType($this->companyId(), $type));
        });
    }

    public function checkAwb(): ResponseInterface
    {
        return $this->run(function () {
            $awb = trim((string) ($this->payload()['awb_no'] ?? ''));
            if ($awb === '') {
                return $this->error('awb_no is required.', 422);
            }
            $exists = (new BookingModel())->where('company_id', $this->companyId())->where('awb_no', $awb)->first();
            return $this->success(['unique' => !$exists, 'awb_no' => $awb], $exists ? 'AWB already exists.' : 'AWB is available.');
        });
    }

    public function searchBookings(): ResponseInterface
    {
        return $this->run(function () {
            $data = $this->payload();
            $start = max(0, (int) ($data['start'] ?? $this->request->getGet('start') ?? 0));
            $length = min(100, max(1, (int) ($data['length'] ?? $this->request->getGet('length') ?? 25)));
            $draw = (int) ($data['draw'] ?? $this->request->getGet('draw') ?? 1);
            $search = trim((string) (($data['search']['value'] ?? null) ?? $data['search'] ?? $this->request->getGet('search') ?? ''));
            $db = \Config\Database::connect();
            $base = $db->table('bookings')->where('company_id', $this->companyId());
            $this->applyBranchScope($base);
            $total = $base->countAllResults(false);
            if ($search !== '') {
                $base->groupStart()->like('awb_no', $search)->orLike('origin', $search)->orLike('destination', $search)->orLike('status', $search)->groupEnd();
            }
            $filtered = $base->countAllResults(false);
            $rows = $base->orderBy('id', 'DESC')->limit($length, $start)->get()->getResultArray();
            return $this->response->setJSON(['status' => 'success', 'draw' => $draw, 'recordsTotal' => $total, 'recordsFiltered' => $filtered, 'data' => $rows]);
        });
    }

    public function createBooking(): ResponseInterface
    {
        return $this->run(function () {
            $this->requirePermission('can_create');
            $companyId = $this->companyId();
            $data = $this->normalizeBooking($this->payload(), $companyId);
            $id = (new BookingService())->createBooking($data, (int) session()->get('user_id'), $companyId);
            return $this->success(['booking_id' => (int) $id, 'booking' => $this->fullBooking((int) $id, $companyId)], 'Booking created.', 201);
        });
    }

    public function booking(int $id): ResponseInterface
    {
        return $this->run(function () use ($id) {
            $booking = $this->fullBooking($id, $this->companyId());
            return $booking ? $this->success($booking) : $this->error('Booking not found.', 404);
        });
    }

    public function updateBooking(int $id): ResponseInterface
    {
        return $this->run(function () use ($id) {
            $this->requirePermission('can_edit');
            $companyId = $this->companyId();
            $current = $this->fullBooking($id, $companyId);
            if (!$current) {
                return $this->error('Booking not found.', 404);
            }
            $incoming = $this->payload();
            $merged = array_replace($current['booking'], $incoming);
            $merged['items'] = $incoming['items'] ?? $current['items'];
            $data = $this->normalizeBooking($merged, $companyId);
            (new BookingService())->updateBooking($id, $data, (int) session()->get('user_id'), $companyId);
            return $this->success(['booking_id' => $id, 'booking' => $this->fullBooking($id, $companyId)], 'Booking updated.');
        });
    }

    public function deleteBooking(int $id): ResponseInterface
    {
        return $this->run(function () use ($id) {
            $this->requirePermission('can_delete');
            $companyId = $this->companyId();
            if (!$this->tenantBooking($id, $companyId)) {
                return $this->error('Booking not found.', 404);
            }
            $db = \Config\Database::connect();
            $db->transStart();
            $db->table('tracking_history')->where('booking_id', $id)->delete();
            if ($db->tableExists('docket_master')) {
                $db->table('docket_master')->where('booking_id', $id)->update(['booking_id' => null, 'shipment_item_id' => null]);
            }
            $db->table('sales_charges')->where('booking_id', $id)->delete();
            $db->table('shipment_items')->where('booking_id', $id)->delete();
            $db->table('audit_logs')->where('table_name', 'bookings')->where('record_id', $id)->delete();
            $db->table('bookings')->where('id', $id)->where('company_id', $companyId)->delete();
            $db->transComplete();
            if (!$db->transStatus()) {
                throw new \RuntimeException('Booking deletion transaction failed.');
            }
            return $this->success(['booking_id' => $id], 'Booking deleted.');
        });
    }

    public function docketPdf(int $bookingId): ResponseInterface
    {
        return $this->run(function () use ($bookingId) {
            $companyId = $this->companyId();
            if (!$this->tenantBooking($bookingId, $companyId)) {
                return $this->error('Booking not found.', 404);
            }
            $item = (new ShipmentItemModel())->where('booking_id', $bookingId)->first();
            if (!$item) {
                return $this->error('No shipment item exists for this booking.', 404);
            }
            $controller = new \App\Controllers\Logistics();
            $controller->initController($this->request, $this->response, service('logger'));
            return $controller->exportDocketPdf((int) $item['id']);
        });
    }

    public function trackingHistory(int $bookingId): ResponseInterface
    {
        return $this->run(function () use ($bookingId) {
            $booking = $this->tenantBooking($bookingId, $this->companyId());
            if (!$booking) {
                return $this->error('Booking not found.', 404);
            }
            $history = (new TrackingHistoryModel())->where('booking_id', $bookingId)->orderBy('event_date', 'DESC')->orderBy('event_time', 'DESC')->findAll();
            return $this->success(['booking' => $booking, 'history' => $history]);
        });
    }

    public function saveTracking(): ResponseInterface
    {
        return $this->run(function () {
            $data = $this->payload();
            $bookingId = (int) ($data['booking_id'] ?? 0);
            $booking = $this->tenantBooking($bookingId, $this->companyId());
            if (!$booking) {
                return $this->error('Booking not found.', 404);
            }
            $row = [
                'booking_id' => $bookingId,
                'awb_no' => $booking['awb_no'],
                'current_location' => trim((string) ($data['current_location'] ?? '')),
                'status' => trim((string) ($data['status'] ?? '')),
                'event_date' => $data['event_date'] ?? date('Y-m-d'),
                'event_time' => $data['event_time'] ?? date('H:i:s'),
                'remarks' => $data['remarks'] ?? '',
                'receiver_name' => $data['receiver_name'] ?? '',
                'receiver_phone' => $data['receiver_phone'] ?? '',
                'receiver_company' => $data['receiver_company'] ?? '',
            ];
            $model = new TrackingHistoryModel();
            $trackingId = (int) ($data['tracking_id'] ?? $data['id'] ?? 0);
            $isUpdate = $trackingId > 0;
            if ($trackingId > 0) {
                $existing = $model->where('booking_id', $bookingId)->find($trackingId);
                if (!$existing) {
                    return $this->error('Tracking event not found.', 404);
                }
                if (!$model->update($trackingId, $row)) {
                    return $this->error(implode(', ', $model->errors()), 422);
                }
            } else {
                if (!$model->insert($row)) {
                    return $this->error(implode(', ', $model->errors()), 422);
                }
                $trackingId = (int) $model->getInsertID();
            }
            (new BookingModel())->update($bookingId, ['status' => $row['status']]);
            return $this->success(['tracking_id' => $trackingId, 'booking_id' => $bookingId], 'Tracking saved.', $isUpdate ? 200 : 201);
        });
    }

    public function deleteTracking(int $id): ResponseInterface
    {
        return $this->run(function () use ($id) {
            $model = new TrackingHistoryModel();
            $row = $model->find($id);
            if (!$row || !$this->tenantBooking((int) $row['booking_id'], $this->companyId())) {
                return $this->error('Tracking event not found.', 404);
            }
            $bookingId = (int) $row['booking_id'];
            $model->delete($id, true);
            $latest = $model->where('booking_id', $bookingId)->orderBy('event_date', 'DESC')->orderBy('event_time', 'DESC')->first();
            (new BookingModel())->update($bookingId, ['status' => $latest['status'] ?? 'Billed']);
            return $this->success(['tracking_id' => $id], 'Tracking event deleted.');
        });
    }

    public function consolidatedInvoice(): ResponseInterface
    {
        return $this->run(function () {
            $companyId = $this->companyId();
            $data = $this->payload();
            $bookingIds = $data['booking_ids'] ?? (isset($data['booking_id']) ? [$data['booking_id']] : []);
            $bookingIds = array_values(array_filter(array_map('intval', (array) $bookingIds)));
            $itemIds = array_values(array_filter(array_map('intval', (array) ($data['item_ids'] ?? []))));
            $db = \Config\Database::connect();
            if ($bookingIds) {
                $valid = $db->table('bookings')->select('id')->where('company_id', $companyId)->whereIn('id', $bookingIds)->get()->getResultArray();
                if (count($valid) !== count(array_unique($bookingIds))) {
                    return $this->error('One or more bookings were not found.', 404);
                }
                $itemIds = array_map('intval', array_column($db->table('shipment_items')->select('id')->whereIn('booking_id', $bookingIds)->get()->getResultArray(), 'id'));
            }
            if (!$itemIds) {
                return $this->error('booking_id, booking_ids, or item_ids is required.', 422);
            }
            $itemBuilder = $db->table('shipment_items si')
                ->select('si.*')
                ->join('bookings b', 'b.id = si.booking_id')
                ->where('b.company_id', $companyId)
                ->whereIn('si.id', $itemIds);
            $this->applyBranchScope($itemBuilder, 'b.branch_id');
            $items = $itemBuilder->get()->getResultArray();
            if (!$items || count($items) !== count(array_unique($itemIds))) {
                return $this->error('One or more shipment items were not found.', 404);
            }
            $data['item_ids'] = $itemIds;
            $data['customer_name'] = trim((string) ($data['customer_name'] ?? $items[0]['customer_name'] ?? $items[0]['bill_to'] ?? ''));
            $data['export_type'] = $data['export_type'] ?? 'pdf';
            $this->request->setGlobal('post', $data);
            $this->request->setHeader('X-Requested-With', 'XMLHttpRequest');
            $controller = new \App\Controllers\Logistics();
            $controller->initController($this->request, $this->response, service('logger'));
            $result = $controller->generateConsolidatedInvoice();
            $body = json_decode((string) $result->getBody(), true);
            if (is_array($body) && ($body['status'] ?? '') === 'success') {
                $downloadId = 0;
                if (preg_match('~/downloads/(\d+)$~', (string) ($body['download_url'] ?? ''), $matches)) {
                    $downloadId = (int) $matches[1];
                }
                $body['data'] = [
                    'invoice_download_id' => $downloadId,
                    'file_name' => $body['file_name'] ?? '',
                    'download_url' => $body['download_url'] ?? '',
                ];
                return $this->response->setStatusCode($result->getStatusCode())->setJSON($body);
            }
            return $result;
        });
    }

    public function invoiceDownload(int $id): ResponseInterface
    {
        return $this->run(function () use ($id) {
            $download = (new InvoiceDownloadModel())->where('company_id', $this->companyId())->find($id);
            if (!$download) {
                return $this->error('Invoice download not found.', 404);
            }
            $path = realpath(WRITEPATH . $download['file_path']);
            $root = realpath(WRITEPATH);
            if (!$path || !$root || !str_starts_with($path, $root . DIRECTORY_SEPARATOR) || !is_file($path)) {
                return $this->error('Invoice PDF is missing from storage.', 404);
            }
            return $this->response
                ->setHeader('Content-Type', 'application/pdf')
                ->setHeader('Content-Disposition', 'inline; filename="' . basename((string) $download['file_name']) . '"')
                ->setBody(file_get_contents($path));
        });
    }

    public function deleteInvoiceDownload(int $id): ResponseInterface
    {
        return $this->run(function () use ($id) {
            $this->requirePermission('can_delete');
            $model = new InvoiceDownloadModel();
            $download = $model->where('company_id', $this->companyId())->find($id);
            if (!$download) {
                return $this->error('Invoice download not found.', 404);
            }
            $path = realpath(WRITEPATH . $download['file_path']);
            $root = realpath(WRITEPATH);
            if ($path && $root && str_starts_with($path, $root . DIRECTORY_SEPARATOR) && is_file($path)) {
                unlink($path);
            }
            $model->delete($id);
            return $this->success(['invoice_download_id' => $id], 'Invoice download deleted.');
        });
    }

    private function run(callable $callback): ResponseInterface
    {
        try {
            return $callback();
        } catch (\Throwable $e) {
            log_message('error', '[API v1] ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            $code = (int) $e->getCode();
            $messageLower = strtolower(strip_tags($e->getMessage()));
            $status = $code >= 400 && $code <= 599 ? $code : ($e instanceof \InvalidArgumentException ? 422 : 500);
            if ($status === 500 && (str_contains($messageLower, 'duplicate') || str_contains($messageLower, 'already exists'))) {
                $status = 409;
            } elseif ($status === 500 && (str_contains($messageLower, 'required') || str_contains($messageLower, 'invalid') || str_contains($messageLower, 'must be'))) {
                $status = 422;
            }
            $message = $status === 500 ? 'The request could not be completed.' : strip_tags($e->getMessage());
            return $this->error($message, $status);
        }
    }

    private function payload(): array
    {
        $data = $this->request->getPost();
        $raw = $this->request->getRawInput();
        if (is_array($raw)) {
            $data = array_replace($data, $raw);
        }
        try {
            $json = $this->request->getJSON(true);
            if (is_array($json)) {
                $data = array_replace($data, $json);
            }
        } catch (\Throwable $e) {
            // A non-JSON request body is valid for form and multipart endpoints.
        }
        return $data;
    }

    private function companyId(): int
    {
        $data = $this->payload();
        $id = (int) ($this->request->getHeaderLine('X-Company-ID') ?: $this->request->getGet('company_id') ?: ($data['company_id'] ?? session()->get('selected_company_id') ?? 0));
        if ($id <= 0 || !(new CompanyModel())->find($id)) {
            throw new \InvalidArgumentException('Select a valid company using X-Company-ID or POST /api/v1/companies/select.');
        }
        session()->set('selected_company_id', $id);
        return $id;
    }

    private function tenantBooking(int $id, int $companyId): ?array
    {
        $builder = \Config\Database::connect()->table('bookings')->where('id', $id)->where('company_id', $companyId);
        $this->applyBranchScope($builder);
        return $builder->get()->getRowArray() ?: null;
    }

    private function applyBranchScope($builder, string $column = 'branch_id'): void
    {
        if (session()->get('role') !== 'admin') {
            $builder->where($column, (int) (session()->get('branch_id') ?? 1));
        }
    }

    private function fullBooking(int $id, int $companyId): ?array
    {
        $booking = $this->tenantBooking($id, $companyId);
        if (!$booking) {
            return null;
        }
        $db = \Config\Database::connect();
        return [
            'booking' => $booking,
            'items' => $db->table('shipment_items')->where('booking_id', $id)->orderBy('id')->get()->getResultArray(),
            'sales' => $db->table('sales_charges')->where('booking_id', $id)->get()->getRowArray(),
        ];
    }

    private function normalizeBooking(array $data, int $companyId): array
    {
        $customer = null;
        if (!empty($data['customer_id'])) {
            $customer = (new CustomerModel())->where('company_id', $companyId)->find((int) $data['customer_id']);
            if (!$customer) {
                throw new \InvalidArgumentException('customer_id does not belong to the selected company.');
            }
        }
        $customerName = trim((string) ($data['customer_name'] ?? $customer['name'] ?? ''));
        $items = $data['items'] ?? [];
        if (!$items) {
            $items = [[
                'customer_name' => $customerName,
                'bill_to' => $data['bill_to'] ?? $customer['bill_to'] ?? $customerName,
                'consignee' => $data['consignee'] ?? $customer['consignee'] ?? $customerName,
                'docket_no' => $data['docket_no'] ?? '',
                'contents' => $data['contents'] ?? $data['material_details'] ?? 'Freight',
                'pieces' => $data['total_pieces'] ?? 1,
                'actual_weight' => $data['actual_weight'] ?? $data['total_weight'] ?? 0,
                'chargeable_weight' => $data['charged_weight'] ?? $data['weight'] ?? $data['actual_weight'] ?? 0,
                'calculated_chargeable_weight' => $data['charged_weight'] ?? $data['weight'] ?? $data['actual_weight'] ?? 0,
                'rate' => $data['freight_rate'] ?? $data['rate'] ?? 0,
            ]];
        }
        foreach ($items as &$item) {
            $item['customer_name'] = trim((string) ($item['customer_name'] ?? $item['customer'] ?? $customerName));
            $item['bill_to'] = trim((string) ($item['bill_to'] ?? $customer['bill_to'] ?? $item['customer_name']));
            $item['consignee'] = trim((string) ($item['consignee'] ?? $customer['consignee'] ?? $item['customer_name']));
            if ($item['bill_to'] === '') {
                $item['bill_to'] = $item['customer_name'];
            }
            if ($item['consignee'] === '') {
                $item['consignee'] = $item['customer_name'];
            }
            $item['chargeable_weight'] = $item['chargeable_weight'] ?? $item['final_chargeable_weight'] ?? $item['charged_weight'] ?? $item['actual_weight'] ?? 0;
            $item['calculated_chargeable_weight'] = $item['calculated_chargeable_weight'] ?? $item['chargeable_weight'];
            if ($item['customer_name'] === '' || $item['bill_to'] === '' || $item['consignee'] === '') {
                throw new \InvalidArgumentException('Each shipment item requires customer_name, bill_to, and consignee.');
            }
        }
        unset($item);
        $data['items'] = $items;
        $data['booking_date'] = $data['booking_date'] ?? date('Y-m-d');
        $data['status'] = $data['status'] ?? 'Draft';
        $data['total_pieces'] = $data['total_pieces'] ?? array_sum(array_map(fn ($item) => (int) ($item['pieces'] ?? 1), $items));
        $data['total_weight'] = $data['total_weight'] ?? $data['actual_weight'] ?? array_sum(array_map(fn ($item) => (float) ($item['actual_weight'] ?? 0), $items));
        $data['rate'] = $data['rate'] ?? $data['freight_rate'] ?? 0;
        $data['weight'] = $data['weight'] ?? $data['charged_weight'] ?? $data['total_weight'];
        return $data;
    }

    private function requirePermission(string $permission): void
    {
        if (session()->get('role') !== 'admin' && !(int) (session()->get('permissions')[$permission] ?? 0)) {
            throw new \RuntimeException('Permission denied.', 403);
        }
    }

    private function publicUser(array $user): array
    {
        return array_intersect_key($user, array_flip(['id', 'username', 'email', 'role', 'branch_id', 'can_create', 'can_edit', 'can_delete']));
    }

    private function publicCompany(array $company): array
    {
        return array_intersect_key($company, array_flip(['id', 'name', 'company_name']));
    }

    private function success($data = null, string $message = '', int $status = 200): ResponseInterface
    {
        return $this->response->setStatusCode($status)->setJSON(['status' => 'success', 'message' => $message, 'data' => $data]);
    }

    private function error(string $message, int $status): ResponseInterface
    {
        return $this->response->setStatusCode($status)->setJSON(['status' => 'error', 'message' => $message]);
    }
}
