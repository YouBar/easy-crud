<?php

namespace Youbar\EasyCrud\Tests\Fixtures\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class PostCollection extends ResourceCollection
{
    /**
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'data' => $this->collection,
            'via' => 'PostCollection',
        ];
    }
}
