<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Conversation extends Model
{
    protected $fillable = [
        'uuid',
        'user_id',
        'product_category_id',
        'image_path',
        'status',
    ];

    protected static function booted(): void
    {
        static::creating(function (Conversation $conversation) {
            if (empty($conversation->uuid)) {
                $conversation->uuid = (string) Str::uuid();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function productCategory(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }

    public function searchRequests(): HasMany
    {
        return $this->hasMany(SearchRequest::class);
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
