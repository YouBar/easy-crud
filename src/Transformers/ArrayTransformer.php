<?php

namespace Youbar\EasyCrud\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use Youbar\EasyCrud\Contracts\TransformsResults;

class ArrayTransformer implements TransformsResults
{
    public function transform(mixed $data, ?string $resource = null, ?string $collection = null): JsonResource
    {
        return new JsonResource($data);
    }
}
