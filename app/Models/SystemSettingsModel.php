<?php

namespace App\Models;

use CodeIgniter\Model;

class SystemSettingsModel extends Model
{
    protected $table      = 'system_settings';
    protected $primaryKey = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = ['company_id', 'setting_key', 'setting_value'];

    public function getSetting(int $companyId, string $key, $default = null)
    {
        $setting = $this->where('company_id', $companyId)
                        ->where('setting_key', $key)
                        ->first();
        
        return $setting ? $setting['setting_value'] : $default;
    }

    public function setSetting(int $companyId, string $key, string $value)
    {
        $existing = $this->where('company_id', $companyId)
                         ->where('setting_key', $key)
                         ->first();

        if ($existing) {
            return $this->update($existing['id'], ['setting_value' => $value]);
        } else {
            return $this->insert([
                'company_id'    => $companyId,
                'setting_key'   => $key,
                'setting_value' => $value,
            ]);
        }
    }
}
