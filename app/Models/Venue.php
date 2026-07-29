<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Venue extends Model
{
    protected $fillable = [
        'category_id',
        'user_id',
        'name',
        'address',
        'use_type',
        'location',
        'owner_phone',
        'availability',
        'price',
        'status',
    ];

    protected $casts = [
        'location' => 'array',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return HasMany<VenueImage, $this> */
    public function images(): HasMany
    {
        return $this->hasMany(VenueImage::class);
    }

    public function slots(): HasMany
    {
        return $this->hasMany(VenueSlot::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
