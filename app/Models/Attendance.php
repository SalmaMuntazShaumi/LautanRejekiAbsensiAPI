<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $fillable = [

        'user_id',
        'company_id',
        'date',

        'clock_in',
        'clock_out',

        'clock_in_lat',
        'clock_in_long',

        'clock_out_lat',
        'clock_out_long',

        'status',

        'clock_in_photo',
        'clock_out_photo',

        'early_out_reason',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
