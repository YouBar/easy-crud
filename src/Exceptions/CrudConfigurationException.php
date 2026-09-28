<?php

namespace Youbar\EasyCrud\Exceptions;

use RuntimeException;

class CrudConfigurationException extends RuntimeException
{
    public static function missingModel(string $controller): self
    {
        return new self(sprintf(
            '%s must define a $model property or override model(). '
            .'Example: protected string $model = \App\Models\Post::class;',
            $controller,
        ));
    }

    public static function notAModel(string $class): self
    {
        return new self(sprintf(
            '[%s] is not an Eloquent model. easy-crud needs a class extending '
            .'Illuminate\Database\Eloquent\Model.',
            $class,
        ));
    }

    /**
     * @param  array<int, string>  $candidates
     */
    public static function unresolvedRole(string $role, string $model, array $candidates): self
    {
        return new self(sprintf(
            'easy-crud could not resolve the [%s] class for [%s]. Tried: %s. '
            .'Set it explicitly on the controller, or adjust easy-crud.conventions.%s.',
            $role,
            $model,
            $candidates === [] ? '(no patterns configured)' : implode(', ', $candidates),
            $role,
        ));
    }

    /**
     * @param  array<int, string>  $candidates
     */
    public static function missingRequest(string $model, string $action, array $candidates): self
    {
        return new self(sprintf(
            'easy-crud has nothing to validate [%s::%s] with and strict_requests is enabled. '
            .'Tried: %s. Create one of those request classes, declare rules() on the '
            .'controller, or set easy-crud.strict_requests to false.',
            $model,
            $action,
            $candidates === [] ? '(no patterns configured)' : implode(', ', $candidates),
        ));
    }

    public static function missingModelNamespace(string $pattern): self
    {
        return new self(sprintf(
            'The pattern [%s] uses {SubNamespace}, which needs to know where your models '
            .'start. Set easy-crud.model_namespace to their root, for example "App\\Models".',
            $pattern,
        ));
    }

    public static function unpaginatableQuery(mixed $query, string $method): self
    {
        return new self(sprintf(
            'The index query is a [%s], which cannot answer [%s()]. scope() and filter() may '
            .'return an Eloquent builder, or any object that forwards to one (such as '
            .'spatie/laravel-query-builder). Check what your filter() override returns.',
            get_debug_type($query),
            $method,
        ));
    }

    public static function unknownPaginationStrategy(string $strategy): self
    {
        return new self(sprintf(
            'Unknown pagination strategy [%s]. Expected one of: length_aware, simple, cursor, none.',
            $strategy,
        ));
    }
}
