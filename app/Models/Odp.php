<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Odp extends Model
{
    use HasFactory;

    protected $table = 'odps';

    protected $fillable = [
        'billing_node_id',
        'code_odp',
        'name',
        'latitude',
        'longitude',
        'total_ports',
        'used_ports',
        'status',
        'photo_path',
        'address',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'billing_node_id' => 'integer',
        'created_by'      => 'integer',
        'total_ports'     => 'integer',
        'used_ports'      => 'integer',
    ];

    protected $appends = [
        'photo_url',
        'available_ports',
        'occupancy_percentage',
    ];

    /**
     * Billing Node / Instance relationship
     */
    public function billingNode(): BelongsTo
    {
        return $this->belongsTo(BillingInstance::class, 'billing_node_id');
    }

    public function billingInstance(): BelongsTo
    {
        return $this->belongsTo(BillingInstance::class, 'billing_node_id');
    }

    /**
     * User who created or last maintained this ODP
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relationship to customers mapped by ODP code
     */
    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class, 'odp_name', 'code_odp');
    }

    /**
     * Get customers strictly scoped to this ODP's billing node to avoid cross-tenant leakage
     */
    public function getScopedCustomersAttribute()
    {
        return Customer::when($this->billing_node_id, fn($q) => $q->where('billing_node_id', $this->billing_node_id))
            ->where('odp_name', $this->code_odp)
            ->get();
    }

    /**
     * Accessor for full public photo URL
     */
    public function getPhotoUrlAttribute(): ?string
    {
        if (!empty($this->photo_path) && Storage::disk('public')->exists($this->photo_path)) {
            return asset('storage/' . $this->photo_path);
        }

        return null;
    }

    /**
     * Accessor for available port count
     */
    public function getAvailablePortsAttribute(): int
    {
        return max(0, $this->total_ports - $this->used_ports);
    }

    /**
     * Accessor for occupancy percentage
     */
    public function getOccupancyPercentageAttribute(): int
    {
        if ($this->total_ports <= 0) {
            return 0;
        }

        return min(100, (int) round(($this->used_ports / $this->total_ports) * 100));
    }

    /**
     * Scope query to ODPs with valid GPS coordinates
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
     * Scope query to ODPs without valid GPS coordinates
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
     * Scope query to search across ODP code, name, address, or notes
     */
    public function scopeSearch(Builder $query, ?string $keyword): Builder
    {
        if (empty($keyword)) {
            return $query;
        }

        $term = trim($keyword);

        return $query->where(function (Builder $q) use ($term) {
            $q->where('code_odp', 'like', "%{$term}%")
              ->orWhere('name', 'like', "%{$term}%")
              ->orWhere('address', 'like', "%{$term}%")
              ->orWhere('notes', 'like', "%{$term}%");
        });
    }
}
