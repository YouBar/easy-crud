<?php

namespace Youbar\EasyCrud\Resolution;

use Closure;
use Illuminate\Support\Str;
use Youbar\EasyCrud\Contracts\ResolvesClasses;
use Youbar\EasyCrud\Exceptions\CrudConfigurationException;

class PatternClassResolver implements ResolvesClasses
{
    /**
     * @var array<string, ResolutionResult>
     */
    protected array $memo = [];

    /**
     * @param  array<string, mixed>  $conventions
     */
    public function __construct(
        protected array $conventions = [],
        protected ?string $modelNamespace = null,
    ) {}

    public function resolve(string $role, string $model, ?string $action = null): ResolutionResult
    {
        return $this->memo[$role.'|'.$model.'|'.((string) $action)]
            ??= $this->lookup($role, $model, $action);
    }

    public function candidates(string $role, string $model, ?string $action = null): array
    {
        $value = $this->conventions[$role] ?? null;

        if ($value === null || $value instanceof Closure) {
            return [];
        }

        $patterns = array_filter(is_array($value) ? $value : [$value], 'is_string');

        return array_values(array_map(
            fn (string $pattern): string => $this->expand($pattern, $model, $action),
            $patterns,
        ));
    }

    public function roles(): array
    {
        return array_keys($this->conventions);
    }

    /**
     * Replace the whole conventions table. Used by the cache command.
     *
     * @param  array<string, mixed>  $conventions
     */
    public function setConventions(array $conventions): void
    {
        $this->conventions = $conventions;
        $this->memo = [];
    }

    public function setModelNamespace(?string $namespace): void
    {
        $this->modelNamespace = $namespace;
        $this->memo = [];
    }

    protected function lookup(string $role, string $model, ?string $action): ResolutionResult
    {
        $value = $this->conventions[$role] ?? null;

        if ($value === null) {
            return ResolutionResult::disabled($role, $model, $action);
        }

        if ($value instanceof Closure) {
            $class = $value($model, $action);

            return is_string($class) && class_exists($class)
                ? ResolutionResult::found($role, $model, $action, $class)
                : ResolutionResult::missing($role, $model, $action);
        }

        $candidates = $this->candidates($role, $model, $action);

        foreach ($candidates as $index => $candidate) {
            if (class_exists($candidate)) {
                return ResolutionResult::found($role, $model, $action, $candidate, $candidates, $index);
            }
        }

        return ResolutionResult::missing($role, $model, $action, $candidates);
    }

    protected function expand(string $pattern, string $model, ?string $action): string
    {
        if (str_contains($pattern, '{SubNamespace}') && $this->modelNamespace === null) {
            throw CrudConfigurationException::missingModelNamespace($pattern);
        }

        return $this->normalize(strtr($pattern, $this->replacements($model, $action)));
    }

    /**
     * @return array<string, string>
     */
    protected function replacements(string $model, ?string $action): array
    {
        $model = ltrim($model, '\\');
        $base = class_basename($model);
        $namespace = $this->namespaceOf($model);

        return [
            '{FQCN}' => $model,
            '{Namespace}' => $namespace,
            '{SubNamespace}' => $this->subNamespace($namespace),
            '{Domain}' => $this->namespaceOf($namespace),
            '{Models}' => Str::plural($base),
            '{Model}' => $base,
            '{models}' => Str::snake(Str::plural($base)),
            '{model}' => Str::snake($base),
            '{Action}' => $action === null ? '' : Str::studly($action),
            '{action}' => $action === null ? '' : Str::snake($action),
        ];
    }

    /**
     * The part of a model's namespace below the configured model root:
     * App\Models\Billing\Payment gives "Billing", App\Models\Post gives "".
     */
    protected function subNamespace(string $namespace): string
    {
        $root = trim((string) $this->modelNamespace, '\\');

        if ($root === '' || $namespace === $root) {
            return '';
        }

        return str_starts_with($namespace, $root.'\\')
            ? substr($namespace, strlen($root) + 1)
            : '';
    }

    protected function namespaceOf(string $class): string
    {
        $position = strrpos($class, '\\');

        return $position === false ? '' : substr($class, 0, $position);
    }

    protected function normalize(string $class): string
    {
        return trim((string) preg_replace('/\\\\{2,}/', '\\', $class), '\\');
    }
}
