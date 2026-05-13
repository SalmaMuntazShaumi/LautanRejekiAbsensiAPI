<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TimeOff extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'start_date',
        'end_date',
        'reason',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
