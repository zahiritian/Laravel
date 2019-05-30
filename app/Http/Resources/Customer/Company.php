<?php

namespace App\Http\Resources\API\V1\Customer;

use Illuminate\Http\Resources\Json\JsonResource;

class Company extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */

    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'image' => ($this->picture) ? route('upload.url', $this->picture) : '',
            'addresses' => new CompanyAddressCollection($this->addresses),
            'shifts' => new CompanyShiftCollection($this->shifts)
        ];
    }
}
