<?php

namespace App\Models;

use App\Models\Concerns\ScopedToAuthUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model
{
    use ScopedToAuthUser;
    protected $fillable = [
        'user_id',
        'email_enabled',
        'email_frequency',
        'notify_new_results',
        'notify_price_drops',
    ];

    protected function casts(): array
    {
        return [
            'email_enabled' => 'boolean',
            'notify_new_results' => 'boolean',
            'notify_price_drops' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
