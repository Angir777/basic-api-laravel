<?php

namespace App\Http\Resources\V1\Role;

use App\Http\Resources\V1\Permission\PermissionResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
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
            'translatedName' => __('roles.'.$this->name),
            'guardName' => $this->guard_name,
            'permissions' => $this->getAllPermissions() != null ? PermissionResource::collection($this->getAllPermissions()) : null,
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];
    }
}
