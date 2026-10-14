<?php

namespace App\Http\Resources\V1\Permission;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SimplePermissionResource extends JsonResource
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
            'translatedName' => __('permissions.'.$this->group_name.'.'.$this->name),
            'groupName' => $this->group_name,
        ];
    }
}
