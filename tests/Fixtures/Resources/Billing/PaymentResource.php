<?php

namespace Youbar\EasyCrud\Tests\Fixtures\Resources\Billing;

use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return ['id' => $this->resource->id, 'via' => 'Billing\PaymentResource'];
    }
}
