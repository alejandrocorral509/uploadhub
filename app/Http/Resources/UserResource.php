<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'bio' => $this->bio,
            'url' => $this->profileUrl(),
            // El correo solo se devuelve a su dueño
            'email' => $this->when($request->user()?->is($this->resource), $this->email),
        ];
    }
}
