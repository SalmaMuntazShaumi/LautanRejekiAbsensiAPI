<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $fillable = [

        'user_id',
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
}