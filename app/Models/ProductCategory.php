<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductCategory extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'default_attributes',
        'chatbot_prompt_config',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'default_attributes' => 'array',
            'chatbot_prompt_config' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function searchRequests(): HasMany
    {
        return $this->hasMany(SearchRequest::class);
    }
}
