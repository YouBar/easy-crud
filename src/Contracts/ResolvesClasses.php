<?php

namespace Youbar\EasyCrud\Contracts;

use Youbar\EasyCrud\Resolution\ResolutionResult;

interface ResolvesClasses
{
    /**
     * Resolve the class for a role, given a model and (optionally) an action.
     */
    public function resolve(string $role, string $model, ?string $action = null): ResolutionResult;

    /**
     * The class-name candidates that would be tried, in order, without touching the autoloader.
     *
     * @return array<int, string>
     */
    public function candidates(string $role, string $model, ?string $action = null): array;

    /**
     * Every role name the conventions table knows about.
     *
     * @return array<int, string>
     */
    public function roles(): array;
}
