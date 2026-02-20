<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait ScopedToAuthUser
{
    protected static function bootScopedToAuthUser(): void
    {
        static::addGlobalScope('scoped_to_auth_user', function (Builder $builder) {
            if (auth()->check()) {
                $builder->where(
                    (new static)->getTable() . '.user_id',
                    auth()->id()
                );
            }
        });
    }
}
