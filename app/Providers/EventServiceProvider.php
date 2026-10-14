<?php

namespace App\Providers;

use App\Events\RoleChanged;
use App\Events\UserChanged;
use App\Listeners\RoleSaveChangeHistory;
use App\Listeners\UserSaveChangeHistory;
use Illuminate\Support\ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        // Historia zmian na modelu 'Role'
        RoleChanged::class => [
            RoleSaveChangeHistory::class,
        ],

        // Historia zmian na modelu 'User'
        UserChanged::class => [
            UserSaveChangeHistory::class,
        ],
    ];
}
