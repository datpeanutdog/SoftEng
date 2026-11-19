<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ListingPhoto extends Model
{
    protected $fillable = ['path', 'position'];

    public function listing()
    {
        return $this->belongsTo(Listing::class);
    }
}
