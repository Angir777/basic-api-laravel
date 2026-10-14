<?php

namespace App\Http\Resources\V1\Permission;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PermissionQueryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => PermissionResource::collection($this->items()),
            'total' => $this->total(),
            'page' => $this->currentPage(),
            'lastPage' => $this->lastPage(),
            'pageSize' => $this->perPage(),
        ];
    }
}
