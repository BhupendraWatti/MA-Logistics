<?php

namespace App\Models;

use CodeIgniter\Model;

class InvoiceDownloadModel extends Model
{
    protected $table = 'invoice_downloads';
    protected $primaryKey = 'id';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'company_id',
        'user_id',
        'customer_name',
        'bill_to',
        'invoice_no',
        'invoice_date',
        'from_date',
        'to_date',
        'layout_orientation',
        'billing_mode',
        'club_by_lr',
        'item_ids',
        'file_path',
        'file_name',
        'downloaded_at',
    ];

    public function getRecentByCompany(int $companyId, int $limit = 25): array
    {
        return $this->select('invoice_downloads.*, users.username as downloaded_by')
            ->join('users', 'users.id = invoice_downloads.user_id', 'left')
            ->where('invoice_downloads.company_id', $companyId)
            ->orderBy('invoice_downloads.downloaded_at', 'DESC')
            ->limit($limit)
            ->findAll();
    }
}
