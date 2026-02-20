<?php

namespace App\Models;

use App\Models\Concerns\ScopedToAuthUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Conversation extends Model
{
    use ScopedToAuthUser;
    protected $fillable = [
        'uuid',
        'user_id',
        'product_category_id',
        'image_path',
        'status',
        'type',
        'search_request_id',
        'search_result_id',
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

    public function searchRequest(): BelongsTo
    {
        return $this->belongsTo(SearchRequest::class);
    }

    public function searchResult(): BelongsTo
    {
        return $this->belongsTo(SearchResult::class);
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
