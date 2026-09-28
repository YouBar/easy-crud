<?php

namespace Youbar\EasyCrud\Pagination;

use Youbar\EasyCrud\Exceptions\CrudConfigurationException;

class Paginator
{
    public const STRATEGIES = ['length_aware', 'simple', 'cursor', 'none'];

    public function __construct(protected string $strategy = 'length_aware') {}

    public function paginate(mixed $query, int $perPage): mixed
    {
        $method = match ($this->strategy) {
            'length_aware' => 'paginate',
            'simple' => 'simplePaginate',
            'cursor' => 'cursorPaginate',
            'none' => 'get',
            default => throw CrudConfigurationException::unknownPaginationStrategy($this->strategy),
        };

        // is_callable, not method_exists: a forwarding wrapper declares nothing.
        if (! is_object($query) || ! is_callable([$query, $method])) {
            throw CrudConfigurationException::unpaginatableQuery($query, $method);
        }

        $arguments = $method === 'get' ? [] : [$perPage];

        return call_user_func_array([$query, $method], $arguments);
    }
}
