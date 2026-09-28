<?php

namespace Youbar\EasyCrud\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Youbar\EasyCrud\Contracts\ResolvesClasses;
use Youbar\EasyCrud\Enums\CrudAction;

class MakeCrudControllerCommand extends Command
{
    protected $signature = 'make:crud-controller
        {name : The controller class name, e.g. PostController}
        {--model= : The model class (inferred from the controller name when omitted)}
        {--resource : Also create the resource class}
        {--requests : Also create the store and update request classes}
        {--all : Shorthand for --resource --requests}
        {--force : Overwrite files that already exist}';

    protected $description = 'Create an easy-crud controller, and optionally its resource and requests';

    public function handle(Filesystem $files, ResolvesClasses $resolver): int
    {
        $name = Str::studly(str_replace(['/', '\\'], '\\', (string) $this->argument('name')));
        $model = $this->model($name);

        if (! class_exists($model)) {
            $this->components->warn(sprintf(
                'Model [%s] does not exist yet. Generating anyway — create it, or pass --model.',
                $model,
            ));
        }

        $controller = $this->controllerClass($name);

        $written = [];
        $skipped = [];

        $this->write(
            $files,
            $controller,
            $this->render('controller', [
                'namespace' => $this->namespaceOf($controller),
                'class' => class_basename($controller),
                'model' => class_basename($model),
                'modelNamespace' => ltrim($model, '\\'),
            ]),
            $written,
            $skipped,
        );

        $all = (bool) $this->option('all');

        if ($all || $this->option('resource')) {
            $this->companion($files, $resolver, 'resource', $model, null, 'resource', $written, $skipped);
        }

        if ($all || $this->option('requests')) {
            foreach ([CrudAction::Store, CrudAction::Update] as $action) {
                $this->companion($files, $resolver, 'request', $model, $action->value, 'request', $written, $skipped);
            }
        }

        foreach ($written as $path) {
            $this->components->info('Created '.$path);
        }

        foreach ($skipped as $path) {
            $this->components->warn('Exists, skipped '.$path.' (use --force to overwrite)');
        }

        $this->newLine();
        $this->components->bulletList([
            'Route it: Route::crud(\''.Str::kebab(Str::plural(class_basename($model))).'\', '.class_basename($controller).'::class);',
            'Inspect it: php artisan easy-crud:conventions '.class_basename($model),
        ]);

        return self::SUCCESS;
    }

    /**
     * Generate a class into the first slot the conventions table would look in.
     *
     * @param  array<int, string>  $written
     * @param  array<int, string>  $skipped
     */
    protected function companion(
        Filesystem $files,
        ResolvesClasses $resolver,
        string $role,
        string $model,
        ?string $action,
        string $stub,
        array &$written,
        array &$skipped,
    ): void {
        $candidates = $resolver->candidates($role, $model, $action);

        if ($candidates === []) {
            $this->components->warn(sprintf(
                'No pattern configured for the [%s] role, so there is nowhere to put it. '
                .'Set easy-crud.conventions.%s first.',
                $role,
                $role,
            ));

            return;
        }

        $class = $candidates[0];

        $this->write($files, $class, $this->render($stub, [
            'namespace' => $this->namespaceOf($class),
            'class' => class_basename($class),
        ]), $written, $skipped);
    }

    /**
     * @param  array<int, string>  $written
     * @param  array<int, string>  $skipped
     */
    protected function write(Filesystem $files, string $class, string $contents, array &$written, array &$skipped): void
    {
        $path = $this->pathFor($class);

        if ($files->exists($path) && ! $this->option('force')) {
            $skipped[] = $path;

            return;
        }

        $files->ensureDirectoryExists(dirname($path));
        $files->put($path, $contents);

        $written[] = $path;
    }

    /**
     * @param  array<string, string>  $replacements
     */
    protected function render(string $stub, array $replacements): string
    {
        $contents = (string) file_get_contents(__DIR__.'/stubs/'.$stub.'.stub');

        foreach ($replacements as $key => $value) {
            $contents = str_replace('{{ '.$key.' }}', $value, $contents);
        }

        return $contents;
    }

    /**
     * Map a class name onto a file, using the application's PSR-4 roots.
     */
    protected function pathFor(string $class): string
    {
        $class = ltrim($class, '\\');
        $appNamespace = trim($this->laravel->getNamespace(), '\\');

        if (str_starts_with($class, $appNamespace.'\\')) {
            $relative = substr($class, strlen($appNamespace) + 1);

            return $this->laravel->path(str_replace('\\', '/', $relative).'.php');
        }

        // Outside the application namespace (a modular layout, say): fall back to
        // the repository root, mirroring the namespace as directories.
        return $this->laravel->basePath(str_replace('\\', '/', $class).'.php');
    }

    protected function controllerClass(string $name): string
    {
        if (str_contains($name, '\\') && str_starts_with($name, trim($this->laravel->getNamespace(), '\\'))) {
            return $name;
        }

        return trim($this->laravel->getNamespace(), '\\').'\\Http\\Controllers\\'.$name;
    }

    protected function model(string $name): string
    {
        $option = $this->option('model');

        if (is_string($option) && $option !== '') {
            return str_contains($option, '\\')
                ? ltrim($option, '\\')
                : trim($this->laravel->getNamespace(), '\\').'\\Models\\'.Str::studly($option);
        }

        $guess = Str::replaceEnd('Controller', '', class_basename($name));

        return trim($this->laravel->getNamespace(), '\\').'\\Models\\'.$guess;
    }

    protected function namespaceOf(string $class): string
    {
        $position = strrpos($class, '\\');

        return $position === false ? '' : substr($class, 0, $position);
    }
}
