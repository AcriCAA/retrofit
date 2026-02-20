<?php

namespace App\Models;

use App\Models\Concerns\ScopedToAuthUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiUsageLog extends Model
{
    use ScopedToAuthUser;
    protected $fillable = [
        'user_id',
        'conversation_id',
        'feature',
        'model',
        'input_tokens',
        'output_tokens',
        'cost_usd',
    ];

    protected function casts(): array
    {
        return [
            'cost_usd' => 'decimal:6',
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

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public static function getTodayTokensForUser(int $userId): int
    {
        return (int) static::forUser($userId)
            ->whereDate('created_at', today())
            ->selectRaw('COALESCE(SUM(input_tokens + output_tokens), 0) as total')
            ->value('total');
    }

    public static function calculateCost(string $model, int $inputTokens, int $outputTokens): float
    {
        $costs = config('ai.costs.' . $model, ['input' => 3.00, 'output' => 15.00]);

        return ($inputTokens * $costs['input'] / 1_000_000)
             + ($outputTokens * $costs['output'] / 1_000_000);
    }
}
