<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Land extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'location',
        'latitude',
        'longitude',
        'area',
        'area_unit',
        'soil_type',
        'status',
        'description',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function crops(): HasMany
    {
        return $this->hasMany(Crop::class);
    }
}
