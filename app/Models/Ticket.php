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
     * Check if the ticket is already closed and immutable
     */
    public function isClosed(): bool
    {
        return $this->status === 'close';
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
     * Accessor for technician action / remark (cleaned without client device audit trail)
     */
    public function getActionRemarkAttribute()
    {
        $closeTimeline = $this->timelines->where('status', 'close')->sortByDesc('created_at')->first();
        if ($closeTimeline && !empty($closeTimeline->remark)) {
            return $this->cleanActionRemark($closeTimeline->remark);
        }
        $processTimeline = $this->timelines->where('status', 'process')->sortByDesc('created_at')->first();
        if ($processTimeline && !empty($processTimeline->remark)) {
            return $this->cleanActionRemark($processTimeline->remark);
        }
        $otherTimeline = $this->timelines->whereNotNull('remark')->sortByDesc('created_at')->first();
        return $otherTimeline ? $this->cleanActionRemark($otherTimeline->remark) : '-';
    }

    /**
     * Clean technician action / remark for display and export:
     * - Strips client device info suffix (e.g. " dari Windows 10 127.0.0.1 Chrome 153.0.0.0")
     * - Strips status change prefix (e.g. "Ubah status ke Selesai (Close): ")
     */
    public function cleanActionRemark(?string $remark): string
    {
        if (empty($remark)) {
            return '-';
        }

        // 1. Strip device/browser audit log suffix
        $cleaned = preg_replace('/\s+dari\s+(?:Windows|Unknown|Android|iOS|MacOS|Macintosh|Linux|Chrome|Edge|Firefox|Safari|Opera|\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}|::1).*$/i', '', $remark);
        $cleaned = trim($cleaned);

        // 2. Strip "Ubah status ke [Status] ([Close]):" prefix
        $cleaned = preg_replace('/^ubah\s+status\s+(?:ke\s+)?[^:]*:\s*/i', '', $cleaned);

        // Also handle case where there was no colon e.g. "Ubah status ke Selesai (Close)"
        $cleaned = preg_replace('/^ubah\s+status\s+(?:ke\s+)?[^(:]*(?:\([^)]*\))?\s*$/i', '', $cleaned);

        $cleaned = trim($cleaned);
        $cleaned = preg_replace('/^:\s*/', '', $cleaned);
        $cleaned = preg_replace('/:\s*$/', '', $cleaned);

        return $cleaned !== '' ? $cleaned : '-';
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
