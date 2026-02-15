<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SearchResult extends Model
{
    protected $fillable = [
        'search_request_id',
        'marketplace',
        'external_id',
        'title',
        'description',
        'price',
        'currency',
        'condition',
        'seller_name',
        'url',
        'image_url',
        'relevance_score',
        'user_status',
        'is_notified',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'relevance_score' => 'decimal:4',
            'is_notified' => 'boolean',
        ];
    }

    public function searchRequest(): BelongsTo
    {
        return $this->belongsTo(SearchRequest::class);
    }
}
