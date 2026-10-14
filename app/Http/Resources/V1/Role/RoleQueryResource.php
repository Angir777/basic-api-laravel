<?php

namespace App\Http\Resources\V1\Role;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleQueryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => RoleResource::collection($this->items()),
            'total' => $this->total(),
            'page' => $this->currentPage(),
            'lastPage' => $this->lastPage(),
            'pageSize' => $this->perPage(),
        ];
    }
}
