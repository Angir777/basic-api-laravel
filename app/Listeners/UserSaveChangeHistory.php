<?php

namespace App\Listeners;

use App\Events\UserChanged;
use App\Http\Resources\V1\User\UserResourceToHistoryJson;
use App\Models\UserHistory;
use App\Services\History\HistoryService;

class UserSaveChangeHistory
{
    /**
     * Handle the event.
     */
    public function handle(UserChanged $event): void
    {
        $diff = HistoryService::diff(
            isset($event->old)
                ? (
                    new UserResourceToHistoryJson(
                        $event->old
                    )
                )->toJson()
                : null,
            isset($event->new)
                ? (
                    new UserResourceToHistoryJson(
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
            UserHistory::create([
                'diff' => json_encode($diff),
                'user_id' => optional($main)->id,
                'changed_by_user_id' => optional($event->changedByUser)->id,
                'changed_by_user_name' => optional($event->changedByUser)->fullName,
                'changed_by_user_email' => optional($event->changedByUser)->email,
                'action_type' => $event->actionType->value,
                'ip' => $event->ip,
            ]);
        }
    }
}
