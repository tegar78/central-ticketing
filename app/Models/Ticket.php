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
}
