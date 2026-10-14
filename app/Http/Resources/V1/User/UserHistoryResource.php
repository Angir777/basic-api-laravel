<?php

namespace App\Http\Resources\V1\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserHistoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'diff' => $this->diff,
            'changedByUserId' => $this->changed_by_user_id,
            'changedByUserName' => $this->changed_by_user_name,
            'changedByUserEmail' => $this->changed_by_user_email,
            'actionType' => $this->action_type,
            'ip' => $this->ip,
            'createdAt' => $this->created_at,
        ];
    }
}
