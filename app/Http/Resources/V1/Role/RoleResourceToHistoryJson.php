<?php

namespace App\Http\Resources\V1\Role;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleResourceToHistoryJson extends JsonResource
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
            'name' => $this->name,
            'guardName' => $this->guard_name,
            'permissions' => $this->getAllPermissions() ? $this->getAllPermissions()->pluck('name')->implode(', ') : null,
        ];
    }
}
