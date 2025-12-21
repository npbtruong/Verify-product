<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'tag_id',
        'image_url',
        'describe',
        'uploaded_by',
        'owner_name',
        'owner_email',
    ];

    /**
     * Generate unique tag ID
     * Format: NFC-XXXXXX (NFC + 6 ký tự ngẫu nhiên uppercase)
     */
    public static function generateUniqueTagId(): string
    {
        do {
            // Generate random tag: NFC-AB12CD (prefix + 6 ký tự alphanumeric)
            $tagId = 'NFC-' . strtoupper(Str::random(6));
        } while (self::where('tag_id', $tagId)->exists());

        return $tagId;
    }

    /**
     * Get the user that uploaded the product.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
