<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SearchRequest extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'conversation_id',
        'product_category_id',
        'title',
        'description',
        'image_path',
        'status',
        'search_frequency_minutes',
        'min_price',
        'max_price',
        'last_searched_at',
    ];

    protected function casts(): array
    {
        return [
            'min_price' => 'decimal:2',
            'max_price' => 'decimal:2',
            'last_searched_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function productCategory(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class);
    }

    public function attributes(): HasMany
    {
        return $this->hasMany(SearchRequestAttribute::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(SearchResult::class);
    }

    public function scopeDueForSearch(Builder $query): Builder
    {
        return $query->where('status', 'active')
            ->where(function (Builder $q) {
                $q->whereNull('last_searched_at')
                    ->orWhereRaw(
                        'last_searched_at <= datetime("now", "-" || search_frequency_minutes || " minutes")'
                    );
            });
    }

    public function getSearchAttribute(string $key): ?string
    {
        return $this->attributes()->where('key', $key)->value('value');
    }

    public function newResultsCount(): int
    {
        return $this->results()->where('user_status', 'new')->count();
    }
}
