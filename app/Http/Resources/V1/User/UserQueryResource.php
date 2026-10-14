<?php

namespace App\Http\Resources\V1\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserQueryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => UserResource::collection($this->items()),
            'total' => $this->total(),
            'page' => $this->currentPage(),
            'lastPage' => $this->lastPage(),
            'pageSize' => $this->perPage(),
        ];
    }
}
