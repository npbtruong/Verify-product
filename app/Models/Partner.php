<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Partner extends Model
{
    protected $fillable = [
        'name',
        'domain',
        'brand',
    ];

    /**
     * Get all users belonging to this partner
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
