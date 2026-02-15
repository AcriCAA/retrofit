<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SearchRequestAttribute extends Model
{
    protected $fillable = [
        'search_request_id',
        'key',
        'value',
    ];

    public function searchRequest(): BelongsTo
    {
        return $this->belongsTo(SearchRequest::class);
    }
}
