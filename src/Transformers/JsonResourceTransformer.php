<?php

namespace Youbar\EasyCrud\Transformers;

use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Support\Collection;
use Youbar\EasyCrud\Contracts\TransformsResults;

class JsonResourceTransformer implements TransformsResults
{
    public function transform(mixed $data, ?string $resource = null, ?string $collection = null): JsonResource
    {
        if ($this->isMany($data)) {
            if ($collection !== null) {
                return new $collection($data);
            }

            return ($resource ?? JsonResource::class)::collection($data);
        }

        return ($resource ?? JsonResource::class)::make($data);
    }

    protected function isMany(mixed $data): bool
    {
        return is_array($data)
            || $data instanceof EloquentCollection
            || $data instanceof Collection
            || $data instanceof Paginator
            || $data instanceof CursorPaginator;
    }
}
