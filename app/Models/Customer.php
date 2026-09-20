<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'billing_node_id',
        'remote_customer_id',
        'no_services',
        'name',
        'phone',
        'address',
        'odp_name',
        'latitude',
        'longitude',
        'package_name',
        'monthly_fee',
        'status',
    ];

    protected $casts = [
        'remote_customer_id' => 'integer',
        'billing_node_id' => 'integer',
        'monthly_fee' => 'decimal:2',
    ];

    /**
     * Billing Node / Billing Instance relationship
     */
    public function billingNode(): BelongsTo
    {
        return $this->belongsTo(BillingInstance::class, 'billing_node_id');
    }

    /**
     * Alias for billingNode for backwards compatibility
     */
    public function billingInstance(): BelongsTo
    {
        return $this->belongsTo(BillingInstance::class, 'billing_node_id');
    }

    /**
     * Relationship to tickets by no_services
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'no_services', 'no_services');
    }
}
