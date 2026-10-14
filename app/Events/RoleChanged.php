<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

class RoleChanged implements ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $old;

    public $new;

    public $actionType;

    public $changedByUser;

    public $ip;

    public function __construct(
        ?Role $old,
        ?Role $new,
        $actionType
    ) {
        $this->old = $old;
        $this->new = $new;
        $this->actionType = $actionType;

        $this->changedByUser = Auth::user();
        $this->ip = request()->ip();
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
