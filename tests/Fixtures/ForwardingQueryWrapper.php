<?php

namespace Youbar\EasyCrud\Tests\Fixtures;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Traits\ForwardsCalls;

/**
 * @mixin Builder<Model>
 */
class ForwardingQueryWrapper
{
    use ForwardsCalls;

    /**
     * @param  Builder<Model>  $query
     */
    public function __construct(protected Builder $query) {}

    /**
     * @param  array<int, mixed>  $parameters
     */
    public function __call(string $method, array $parameters): mixed
    {
        return $this->forwardCallTo($this->query, $method, $parameters);
    }
}
