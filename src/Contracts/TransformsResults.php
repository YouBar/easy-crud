<?php

namespace Youbar\EasyCrud\Contracts;

use Illuminate\Http\Resources\Json\JsonResource;

interface TransformsResults
{
    /**
     * Turn a model, collection or paginator into a response-ready resource.
     *
     * @param  class-string|null  $resource  the single-item resource class, if one resolved
     * @param  class-string|null  $collection  the collection resource class, if one resolved
     */
    public function transform(mixed $data, ?string $resource = null, ?string $collection = null): JsonResource;
}
