<?php

namespace Youbar\EasyCrud\Routing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Str;

class PendingCrudRoutes
{
    /** @var array<int, string> */
    protected array $only = [];

    /** @var array<int, string> */
    protected array $except = [];

    /** @var array<int, string> */
    protected array $middleware = [];

    protected ?string $name = null;

    protected bool $registered = false;

    /**
     * @param  class-string  $target  a controller class, or an Eloquent model
     *                                for the controller-less form
     */
    public function __construct(
        protected Router $router,
        protected string $uri,
        protected string $target,
    ) {}

    public function only(string ...$actions): self
    {
        $this->only = $actions;

        return $this;
    }

    public function except(string ...$actions): self
    {
        $this->except = $actions;

        return $this;
    }

    /**
     * @param  string|array<int, string>  $middleware
     */
    public function middleware(string|array $middleware): self
    {
        $this->middleware = array_merge($this->middleware, (array) $middleware);

        return $this;
    }

    public function names(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function register(): void
    {
        if ($this->registered) {
            return;
        }

        $this->registered = true;

        $item = $this->uri.'/{'.$this->parameter().'}';
        $name = $this->name ?? implode('.', $this->staticSegments());

        $routes = [
            ['index', 'get', $this->uri, false],
            ['store', 'post', $this->uri, false],
            ['show', 'get', $item, true],
            ['update', 'put', $item, true],
            ['destroy', 'delete', $item, true],
        ];

        $group = $this->middleware === [] ? [] : ['middleware' => $this->middleware];

        $this->router->group($group, function () use ($routes, $name): void {
            foreach ($routes as [$action, $verb, $uri, $keyed]) {
                if (! $this->wants($action)) {
                    continue;
                }

                $methods = $action === 'update' ? ['PUT', 'PATCH'] : [strtoupper($verb)];

                $this->router->match($methods, $uri, $this->handler($action, $keyed))
                    ->name($name.'.'.$action);
            }
        });
    }

    protected function handler(string $action, bool $keyed): mixed
    {
        if (! is_subclass_of($this->target, Model::class)) {
            return [$this->target, $action];
        }

        $model = $this->target;

        return $keyed
            ? fn (Request $request, mixed $key): mixed => (new ConventionController($model))->{$action}($request, $key)
            : fn (Request $request): mixed => (new ConventionController($model))->{$action}($request);
    }

    protected function wants(string $action): bool
    {
        if ($this->only !== [] && ! in_array($action, $this->only, true)) {
            return false;
        }

        return ! in_array($action, $this->except, true);
    }

    protected function parameter(): string
    {
        $segments = $this->staticSegments();
        $last = end($segments) ?: 'model';

        // Matches Laravel's resource registrar: course-locations -> course_location.
        return str_replace('-', '_', Str::singular($last));
    }

    /**
     * URI segments that are not route parameters, so a nested URI like
     * posts/{post}/comments contributes only "posts" and "comments".
     *
     * @return array<int, string>
     */
    protected function staticSegments(): array
    {
        return array_values(array_filter(
            explode('/', trim($this->uri, '/')),
            fn (string $segment): bool => $segment !== '' && ! str_contains($segment, '{'),
        ));
    }

    public function __destruct()
    {
        $this->register();
    }
}
