<?php

namespace Youbar\EasyCrud\Tests\Fixtures\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->resource->id,
            'title' => $this->resource->title,
            'via' => 'PostResource',
        ];
    }
}
