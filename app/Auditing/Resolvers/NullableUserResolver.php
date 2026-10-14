<?php

namespace App\Auditing\Resolvers;

use OwenIt\Auditing\Contracts\UserResolver;

class NullableUserResolver implements UserResolver
{
    public static function resolve()
    {
        return auth()->check() ? auth()->user() : null;
    }
}
