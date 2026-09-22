<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    protected $fillable = [
        'ticket_number',
        'billing_instance_id',
        'remote_ticket_id',
        'no_services',
        'customer_name',
        'customer_phone',
        'customer_address',
        'latitude',
        'longitude',
        'category_name',
        'problem_description',
        'picture',
        'status',
        'assigned_technician_id',
        'created_by_name',
        'created_by_role',
    ];

    public function billingInstance()
    {
        return $this->belongsTo(BillingInstance::class);
    }

    public function assignedTechnician()
    {
        return $this->belongsTo(User::class, 'assigned_technician_id');
    }

    public function timelines()
    {
        return $this->hasMany(TicketTimeline::class);
    }

    /**
     * Accessor for closed_at timestamp based on closing timeline or updated_at
     */
    public function getClosedAtAttribute()
    {
        if ($this->status !== 'close') {
            return null;
        }
        $closeTimeline = $this->timelines->where('status', 'close')->sortByDesc('created_at')->first();
        return $closeTimeline?->created_at ?? $this->updated_at;
    }

    /**
     * Accessor for technician action / remark
     */
    public function getActionRemarkAttribute()
    {
        $closeTimeline = $this->timelines->where('status', 'close')->sortByDesc('created_at')->first();
        if ($closeTimeline && !empty($closeTimeline->remark)) {
            return $closeTimeline->remark;
        }
        $processTimeline = $this->timelines->where('status', 'process')->sortByDesc('created_at')->first();
        if ($processTimeline && !empty($processTimeline->remark)) {
            return $processTimeline->remark;
        }
        return $this->timelines->whereNotNull('remark')->sortByDesc('created_at')->first()?->remark ?? '-';
    }

    /**
     * Accessor for combined report description
     */
    public function getKeteranganLaporanAttribute()
    {
        if ($this->category_name && $this->problem_description && $this->category_name !== $this->problem_description) {
            return "[{$this->category_name}] {$this->problem_description}";
        }
        return $this->problem_description ?: ($this->category_name ?: '-');
    }
}
