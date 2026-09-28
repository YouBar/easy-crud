<?php

namespace Youbar\EasyCrud\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Database\Eloquent\Model;
use Youbar\EasyCrud\Contracts\ResolvesClasses;
use Youbar\EasyCrud\Enums\CrudAction;

class ConventionsCommand extends Command
{
    protected $signature = 'easy-crud:conventions {model : The model class, fully qualified or short}';

    protected $description = 'Show which classes easy-crud resolves for a model';

    public function handle(ResolvesClasses $resolver, Gate $gate): int
    {
        $model = $this->qualify((string) $this->argument('model'));

        if ($model === null) {
            return self::FAILURE;
        }

        $this->newLine();
        $this->row('Model', $model);

        $root = config('easy-crud.model_namespace');

        if (is_string($root)) {
            $this->row('Namespace', $root, 'model_namespace, used by {SubNamespace}');
        }

        $this->role($resolver, 'resource', $model);
        $this->role($resolver, 'collection', $model, 'fallback: '.class_basename((string) ($resolver->resolve('resource', $model)->class ?? 'JsonResource')).'::collection()');

        $this->line('  <fg=gray>Request</>');
        foreach (CrudAction::cases() as $action) {
            $result = $resolver->resolve('request', $model, $action->value);
            $this->row(
                '    '.$action->value,
                $result->class ?? ($result->candidates[0] ?? '—'),
                $result->resolved() ? $result->explain() : $result->explain().' → no validation',
                $result->resolved(),
            );
        }

        $this->policy($gate, $model);
        $this->custom($resolver, $model);
        $this->pagination();

        $this->newLine();

        return self::SUCCESS;
    }

    protected function role(ResolvesClasses $resolver, string $role, string $model, ?string $fallback = null): void
    {
        $result = $resolver->resolve($role, $model);

        // Only name a class that was actually found. Printing the candidate on
        // a miss reads as though it resolved.
        $this->row(
            ucfirst($role),
            $result->class ?? '—',
            $result->resolved() ? $result->explain() : ($fallback ?? $result->explain()),
            $result->resolved() ? true : ($fallback !== null ? null : false),
        );
    }

    protected function policy(Gate $gate, string $model): void
    {
        $policy = $gate->getPolicyFor($model);

        if ($policy === null) {
            $this->row('Policy', '—', 'none found → authorization skipped', false);

            return;
        }

        $defined = [];
        $skipped = [];

        foreach (CrudAction::cases() as $action) {
            method_exists($policy, $action->ability())
                ? $defined[] = $action->ability()
                : $skipped[] = $action->ability();
        }

        $this->row('Policy', $policy::class, implode(', ', $defined) ?: 'no crud abilities', true);

        if ($skipped !== []) {
            $this->line('    <fg=gray>skipped</>  '.implode(', ', $skipped));
        }
    }

    protected function custom(ResolvesClasses $resolver, string $model): void
    {
        $known = ['resource', 'collection', 'request'];
        $custom = array_diff($resolver->roles(), $known);

        if ($custom === []) {
            return;
        }

        $this->newLine();
        $this->line('  <fg=gray>Custom roles</> <fg=gray>(resolved only — easy-crud has no behaviour for these)</>');

        foreach ($custom as $role) {
            $result = $resolver->resolve($role, $model);
            $this->row('    '.$role, $result->class ?? ($result->candidates[0] ?? '—'), $result->explain(), $result->resolved());
        }
    }

    protected function pagination(): void
    {
        $this->newLine();

        /** @var array<string, mixed> $config */
        $config = (array) config('easy-crud.pagination', []);

        /** @var array<int, string> $keys */
        $keys = (array) ($config['query_keys'] ?? []);

        $this->row('Pagination', (string) ($config['strategy'] ?? 'length_aware'), sprintf(
            '%s, default %s, max %s',
            implode('|', $keys),
            (string) ($config['default'] ?? 15),
            (string) ($config['max'] ?? 100),
        ), true);
    }

    protected function row(string $label, string $value, ?string $note = null, ?bool $ok = null): void
    {
        $mark = match ($ok) {
            true => ' <fg=green>✓</>',
            false => ' <fg=yellow>✗</>',
            null => '',
        };

        $indent = strlen($label) - strlen(ltrim($label));

        $this->line(sprintf(
            '  %s<options=bold>%s</> %s%s',
            str_repeat(' ', $indent),
            str_pad(ltrim($label), 12 - $indent),
            $value,
            $note === null ? '' : sprintf('  <fg=gray>%s</>%s', $note, $mark),
        ));
    }

    /**
     * @return class-string<Model>|null
     */
    protected function qualify(string $name): ?string
    {
        $candidates = str_contains($name, '\\')
            ? [$name]
            : [$name, 'App\\Models\\'.$name, 'App\\'.$name];

        foreach ($candidates as $candidate) {
            if (class_exists($candidate) && is_subclass_of($candidate, Model::class)) {
                return $candidate;
            }
        }

        $this->components->error(sprintf(
            'Could not find an Eloquent model for [%s]. Tried: %s',
            $name,
            implode(', ', $candidates),
        ));

        return null;
    }
}
