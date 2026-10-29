<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $fillable = [
        'guest_id',
        'host_id',
        'listing_id',
        'listing_title',
        'guest_name',
        'price',
        'code',
        'status',
        'go_enabled',
    ];
}
