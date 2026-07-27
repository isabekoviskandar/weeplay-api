<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = [
        'name',
        'image',
        'status',
    ];

    protected $casts = [
        'name' => 'array',
    ];

    public function venues(): HasMany
    {
        return $this->hasMany(Venue::class);
    }
}
