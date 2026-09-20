<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketTimeline extends Model
{
    protected $fillable = [
        'ticket_id',
        'user_id',
        'status',
        'remark',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
