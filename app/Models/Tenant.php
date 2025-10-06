<?php

namespace App\Models;

use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains;

    protected $fillable = [
        'id',
        'name',
        'email',
        'plan',
        'status',
        'settings',
        'white_label_settings',
    ];

    protected $casts = [
        'settings' => 'array',
        'white_label_settings' => 'array',
    ];

    public static function getCustomColumns(): array
    {
        return [
            'id',
            'name',
            'email',
            'plan',
            'status',
            'settings',
            'white_label_settings',
        ];
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isPaused(): bool
    {
        return $this->status === 'paused';
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    public function getWhiteLabelSetting(string $key, $default = null)
    {
        return data_get($this->white_label_settings, $key, $default);
    }

    public function setWhiteLabelSetting(string $key, $value): void
    {
        $settings = $this->white_label_settings ?? [];
        data_set($settings, $key, $value);
        $this->white_label_settings = $settings;
        $this->save();
    }
}