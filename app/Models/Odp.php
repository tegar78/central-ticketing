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
     * Backward-compatible accessors; master ODP is unified across all billing nodes
     */
    public function getBillingNodeAttribute(): ?BillingInstance
    {
        return $this->connected_nodes->first();
    }

    public function getBillingInstanceAttribute(): ?BillingInstance
    {
        return $this->billing_node;
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
     * Get all connected billing instances whose clients occupy ports on this physical ODP
     */
    public function getConnectedNodesAttribute()
    {
        return BillingInstance::whereIn('id', function ($q) {
            $q->select('billing_node_id')
              ->from('customers')
              ->where('odp_name', $this->code_odp)
              ->whereNotNull('billing_node_id');
        })->get(['id', 'tenant_code', 'name']);
    }

    /**
     * Get customers strictly scoped to this ODP's billing node to avoid cross-tenant leakage
     */
    public function getScopedCustomersAttribute()
    {
        $cleanCode = preg_replace('/^ODP-/i', '', $this->code_odp);
        return Customer::where(function ($q) use ($cleanCode) {
                $q->where('odp_name', $this->code_odp)
                  ->orWhere('odp_name', $cleanCode);
            })
            ->get();
    }

    /**
     * Get complete port slot matrix from 1 to total_ports with connected customers across billing nodes
     */
    public function getPortMatrix(?int $billingNodeId = null): array
    {
        $cleanCode = preg_replace('/^ODP-/i', '', $this->code_odp);
        $customers = Customer::with('billingNode:id,name,tenant_code')
            ->where(function ($q) use ($cleanCode) {
                $q->where('odp_name', $this->code_odp)
                  ->orWhere('odp_name', $cleanCode);
            })
            ->when($billingNodeId, function ($q) use ($billingNodeId) {
                $q->where('billing_node_id', $billingNodeId);
            })
            ->get();

        $byPort = $customers->whereNotNull('port_number')->groupBy('port_number');
        $unassigned = $customers->whereNull('port_number')->values();

        $ports = [];
        $total = max(1, (int) $this->total_ports);

        for ($i = 1; $i <= $total; $i++) {
            $portCustomers = $byPort->get($i, collect());
            $cust = $portCustomers->first();
            $isConflict = $portCustomers->count() > 1;

            $ports[] = [
                'port_number' => $i,
                'status'      => $cust ? 'occupied' : 'available',
                'is_conflict' => $isConflict,
                'customer'    => $cust ? [
                    'id'           => $cust->id,
                    'no_services'  => $cust->no_services,
                    'name'         => $cust->name,
                    'phone'        => $cust->phone,
                    'address'      => $cust->address,
                    'status'       => $cust->status,
                    'package_name' => $cust->package_name,
                    'billing_node' => $cust->billingNode ? [
                        'id'          => $cust->billingNode->id,
                        'tenant_code' => $cust->billingNode->tenant_code,
                        'name'        => $cust->billingNode->name,
                    ] : null,
                ] : null,
                'all_customers' => $portCustomers->map(fn($c) => [
                    'id'           => $c->id,
                    'no_services'  => $c->no_services,
                    'name'         => $c->name,
                    'phone'        => $c->phone,
                    'address'      => $c->address,
                    'status'       => $c->status,
                    'package_name' => $c->package_name,
                    'billing_node' => $c->billingNode ? [
                        'id'          => $c->billingNode->id,
                        'tenant_code' => $c->billingNode->tenant_code,
                        'name'        => $c->billingNode->name,
                    ] : null,
                ])->values(),
            ];
        }

        return [
            'total_ports'      => $total,
            'used_ports_count' => $byPort->count() ?: $customers->count(),
            'ports'            => $ports,
            'unassigned'       => $unassigned->map(fn($c) => [
                'id'           => $c->id,
                'no_services'  => $c->no_services,
                'name'         => $c->name,
                'phone'        => $c->phone,
                'address'      => $c->address,
                'status'       => $c->status,
                'package_name' => $c->package_name,
                'billing_node' => $c->billingNode ? [
                    'id'          => $c->billingNode->id,
                    'tenant_code' => $c->billingNode->tenant_code,
                    'name'        => $c->billingNode->name,
                ] : null,
            ]),
        ];
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

    /**
     * Scope query to ODPs associated with a specific billing node (via connected customers)
     */
    public function scopeForBillingNode(Builder $query, ?int $billingNodeId): Builder
    {
        if (empty($billingNodeId)) {
            return $query;
        }

        return $query->whereIn('code_odp', function ($q) use ($billingNodeId) {
            $q->select('odp_name')
              ->from('customers')
              ->where('billing_node_id', $billingNodeId)
              ->whereNotNull('odp_name');
        });
    }
}
