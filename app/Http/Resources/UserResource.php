<?php

namespace App\Http\Resources;

use App\Enums\UserTypeEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if ($this->type === UserTypeEnum::VETERINARIAN->value) {
            return [
                'idUser' => $this->id_user,
                'name' => $this->name,
                'email' => $this->email,
                'canSendWhatsapp' => $this->can_send_whatsapp === 1,
                'type' => $this->type,
                'crmv' => $this->veterinarian->crmv,
                'uf' => $this->veterinarian->uf,
            ];
        }

        return [
            'idUser' => $this->id_user,
            'name' => $this->name,
            'email' => $this->email,
            'canSendWhatsapp' => $this->can_send_whatsapp === 1,
            'type' => $this->type,
        ];
    }
}
