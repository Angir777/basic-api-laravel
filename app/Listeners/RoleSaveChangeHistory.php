<?php

namespace App\Listeners;

use App\Events\RoleChanged;
use App\Http\Resources\V1\Role\RoleResourceToHistoryJson;
use App\Models\RoleHistory;
use App\Services\History\HistoryService;

class RoleSaveChangeHistory
{
    /**
     * Handle the event.
     */
    public function handle(RoleChanged $event): void
    {
        $diff = HistoryService::diff(
            isset($event->old)
                ? (
                    new RoleResourceToHistoryJson(
                        $event->old
                    )
                )->toJson()
                : null,
            isset($event->new)
                ? (
                    new RoleResourceToHistoryJson(
                        $event->new
                    )
                )->toJson()
                : null
        );
        if ($event->old === null && $event->new === null) {
            return;
        }

        $main = $event->old;
        if ($main === null) {
            $main = $event->new;
        }

        if ($diff !== null) {
            RoleHistory::create([
                'diff' => json_encode($diff),
                'role_id' => optional($main)->id,
                'changed_by_user_id' => optional($event->changedByUser)->id,
                'changed_by_user_name' => optional($event->changedByUser)->fullName,
                'changed_by_user_email' => optional($event->changedByUser)->email,
                'action_type' => $event->actionType->value,
                'ip' => $event->ip,
            ]);
        }
    }
}
