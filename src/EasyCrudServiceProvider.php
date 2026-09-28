<?php

namespace Youbar\EasyCrud;

use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Youbar\EasyCrud\Authorization\NullAuthorizer;
use Youbar\EasyCrud\Authorization\OptionalPolicyAuthorizer;
use Youbar\EasyCrud\Authorization\StrictPolicyAuthorizer;
use Youbar\EasyCrud\Console\ConventionsCommand;
use Youbar\EasyCrud\Console\MakeCrudControllerCommand;
use Youbar\EasyCrud\Contracts\AuthorizesActions;
use Youbar\EasyCrud\Contracts\ResolvesClasses;
use Youbar\EasyCrud\Contracts\TransformsResults;
use Youbar\EasyCrud\Resolution\PatternClassResolver;
use Youbar\EasyCrud\Routing\PendingCrudRoutes;
use Youbar\EasyCrud\Transformers\JsonResourceTransformer;

class EasyCrudServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfig();

        $this->app->singleton(ResolvesClasses::class, function ($app): ResolvesClasses {
            /** @var array<string, mixed> $conventions */
            $conventions = $app['config']->get('easy-crud.conventions', []);

            $modelNamespace = $app['config']->get('easy-crud.model_namespace');

            return new PatternClassResolver(
                $conventions,
                is_string($modelNamespace) ? $modelNamespace : null,
            );
        });

        $this->app->singleton(AuthorizesActions::class, function ($app): AuthorizesActions {
            $gate = $app->make(Gate::class);

            return match ((string) $app['config']->get('easy-crud.authorization', 'optional')) {
                'strict' => new StrictPolicyAuthorizer($gate),
                'none' => new NullAuthorizer,
                default => new OptionalPolicyAuthorizer($gate),
            };
        });

        $this->app->singleton(TransformsResults::class, JsonResourceTransformer::class);
    }

    protected function mergeConfig(): void
    {
        /** @var array<string, mixed> $package */
        $package = require __DIR__.'/../config/easy-crud.php';

        $config = $this->app['config'];

        /** @var array<string, mixed> $application */
        $application = $config->get('easy-crud', []);

        $config->set('easy-crud', $this->mergeDeep($package, $application));
    }

    /**
     * @param  array<string, mixed>  $base
     * @param  array<string, mixed>  $override
     * @return array<string, mixed>
     */
    protected function mergeDeep(array $base, array $override): array
    {
        foreach ($override as $key => $value) {
            $existing = $base[$key] ?? null;

            $mergeable = is_array($value)
                && is_array($existing)
                && ! array_is_list($value)
                && ! array_is_list($existing);

            $base[$key] = $mergeable
                ? $this->mergeDeep($existing, $value)
                : $value;
        }

        return $base;
    }

    public function boot(): void
    {
        $this->registerRouteMacro();

        if ($this->app->runningInConsole()) {
            $this->commands([
                ConventionsCommand::class,
                MakeCrudControllerCommand::class,
            ]);

            $this->publishes([
                __DIR__.'/../config/easy-crud.php' => $this->app->configPath('easy-crud.php'),
            ], 'easy-crud-config');
        }
    }

    protected function registerRouteMacro(): void
    {
        if (Route::hasMacro('crud')) {
            return;
        }

        Route::macro('crud', function (string $uri, string $target): PendingCrudRoutes {
            /** @var Router $this */
            return new PendingCrudRoutes($this, $uri, $target);
        });
    }
}
