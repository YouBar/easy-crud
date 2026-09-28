<?php

namespace Youbar\EasyCrud\Tests\Fixtures\Resources\Alternate;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Lives where no configured pattern would ever look, so only an explicit
 * $resource property can reach it.
 */
class OffConventionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->resource->id,
            'title' => $this->resource->title,
            'morphed' => (bool) $this->resource->morphed,
            'via' => 'OffConventionResource',
        ];
    }
}
