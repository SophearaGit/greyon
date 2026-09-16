<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * camelCase shape for a developer account. Developers bypass the
 * permission matrix entirely (see AccessService), so there's no
 * `role`/`featureKeys`/`locationIds`/`hotelIds` here the way there is
 * on AdminResource — a developer just... can.
 *
 * @mixin \App\Models\Developer
 */
class DeveloperResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'status' => $this->status,
        ];
    }
}
