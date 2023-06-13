<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Resource extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $guarded = [];

    // TODO: resource manager method
    // ....

    public function reservations()
    {
        return $this->belongsToMany(Reservation::class)->using(ReservationResource::class);
    }

    public function padalinys()
    {
        return $this->belongsTo(Padalinys::class);
    }
}
