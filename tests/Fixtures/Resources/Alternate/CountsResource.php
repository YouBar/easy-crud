<?php

namespace Youbar\EasyCrud\Tests\Fixtures\Resources\Alternate;

use Illuminate\Http\Resources\Json\JsonResource;

class CountsResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->resource->id,
            'title' => $this->resource->title,
            'approved_count' => $this->resource->approved_count,
            'rejected_count' => $this->resource->rejected_count,
            'comments_loaded' => $this->resource->relationLoaded('comments'),
        ];
    }
}
