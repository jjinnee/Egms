<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Helpers\SecurityHelper;

class Device extends Model
{
    use HasFactory;
    protected $table = 'devices';
    protected $fillable = ['device_id','barangay','household_name','contact_number','status','last_seen'];

    protected $casts = [
        'last_seen' => 'datetime',
    ];

    /**
     * Get display status based on last_seen timestamp
     */
    public function getDisplayStatusAttribute()
    {
        if (!$this->last_seen) return 'Inactive';
        return $this->last_seen->gt(now()->subMinutes(10)) ? 'Active' : 'Inactive';
    }

    /**
     * Get hashed device ID for secure transmission
     */
    public function getHashedDeviceIdAttribute(): string
    {
        return SecurityHelper::hashDeviceId($this->device_id ?? '');
    }

    /**
     * Get hashed contact number for secure display
     */
    public function getHashedContactAttribute(): ?string
    {
        if (empty($this->contact_number)) return null;
        return SecurityHelper::hashDeviceData($this->contact_number);
    }

    /**
     * Get device data with sensitive fields hashed
     */
    public function getSecureDataAttribute(): array
    {
        return SecurityHelper::hashDeviceFields($this->toArray());
    }

    public function household()
    {
        return $this->hasOne(Household::class, 'device_pk');
    }
}
