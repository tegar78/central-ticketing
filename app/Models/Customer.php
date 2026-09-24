<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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

    /**
     * Get tickets strictly scoped to this customer's billing node to prevent cross-tenant leakage
     */
    public function scopedTickets()
    {
        return Ticket::where('billing_instance_id', $this->billing_node_id)
            ->where('no_services', $this->no_services)
            ->latest()
            ->get();
    }

    /**
     * Scope query to customers with valid GPS coordinates.
     */
    public function scopeHasGpsCoordinates(Builder $query): Builder
    {
        return $query->whereNotNull('latitude')
            ->where('latitude', '!=', '')
            ->whereNotNull('longitude')
            ->where('longitude', '!=', '')
            ->where('latitude', '!=', '0')
            ->where('longitude', '!=', '0');
    }

    /**
     * Scope query to customers without valid GPS coordinates.
     */
    public function scopeWithoutGpsCoordinates(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->whereNull('latitude')
              ->orWhere('latitude', '')
              ->orWhereNull('longitude')
              ->orWhere('longitude', '')
              ->orWhere('latitude', '0')
              ->orWhere('longitude', '0');
        });
    }

    /**
     * Scope query to search across standard customer identification columns.
     */
    public function scopeSearch(Builder $query, ?string $keyword): Builder
    {
        if (empty($keyword)) {
            return $query;
        }

        $term = trim($keyword);

        return $query->where(function (Builder $q) use ($term) {
            $q->where('no_services', 'like', "%{$term}%")
              ->orWhere('name', 'like', "%{$term}%")
              ->orWhere('phone', 'like', "%{$term}%")
              ->orWhere('address', 'like', "%{$term}%")
              ->orWhere('odp_name', 'like', "%{$term}%");
        });
    }
}
