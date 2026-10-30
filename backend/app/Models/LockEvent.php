<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LockEvent extends Model
{
    protected $fillable = ['listing_id', 'event', 'result', 'created_at'];
}
