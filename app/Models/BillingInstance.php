<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BillingInstance extends Model
{
    protected $fillable = [
        'tenant_code',
        'name',
        'domain_url',
        'api_key',
        'callback_url',
        'is_active',
    ];

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function customers()
    {
        return $this->hasMany(Customer::class, 'billing_node_id');
    }
}
