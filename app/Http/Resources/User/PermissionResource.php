<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PermissionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if (!$this->resource) {
            return null;
        }

        return [
            'id' => $this->id,
            'code' => $this->code,
            'module' => $this->module,
            'action' => $this->action,
        ];
    }
}
