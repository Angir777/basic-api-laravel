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
        // Change history for the 'User' model.
        UserChanged::class => [
            UserSaveChangeHistory::class,
        ],

        // Change history for the 'Role' model.
        RoleChanged::class => [
            RoleSaveChangeHistory::class,
        ],
    ];
}
