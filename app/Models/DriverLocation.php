<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DriverLocation extends Model
{
    protected $fillable = [
        'user_id',
        'company_id',
        'start_latitude',
        'start_longitude',
        'latitude', 
        'longitude',
        'status',
        'started_at',
        'arrived_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'arrived_at' => 'datetime',
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
