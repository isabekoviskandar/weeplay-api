<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VenueSlot extends Model
{
    protected $fillable =
        [
            'venue_id',
            'user_id',
            'date',
            'start_time',
            'end_time',
            'price',
            'status',
        ];

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }
}
