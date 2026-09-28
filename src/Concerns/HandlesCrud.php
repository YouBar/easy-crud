<?php

namespace Youbar\EasyCrud\Concerns;

use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Youbar\EasyCrud\Contracts\AuthorizesActions;
use Youbar\EasyCrud\Contracts\ResolvesClasses;
use Youbar\EasyCrud\Contracts\TransformsResults;
use Youbar\EasyCrud\Enums\CrudAction;
use Youbar\EasyCrud\Exceptions\CrudConfigurationException;
use Youbar\EasyCrud\Pagination\Paginator;

trait HandlesCrud
{
    /**
     * @return array<string, mixed>
     */
    protected function declarations(): array
    {
        return get_object_vars($this);
    }

    public function index(Request $request): JsonResource
    {
        $this->authorizeAction(CrudAction::Index);
        $this->validated($request, CrudAction::Index);

        $query = $this->scope($this->newQuery());

        // Applied before filter(), so eager loads land on a real Eloquent
        // builder rather than on whatever wrapper filter() may return.
        if (($with = $this->with(CrudAction::Index->value)) !== []) {
            $query->with($with);
        }

        return $this->transform(
            $this->prepare($this->paginate($this->filter($query)), CrudAction::Index->value),
        );
    }

    public function show(Request $request, mixed $key): JsonResource
    {
        $model = $this->find($key);

        $this->authorizeAction(CrudAction::Show, $model);
        $this->validated($request, CrudAction::Show);

        if (($with = $this->with(CrudAction::Show->value)) !== []) {
            $model->load($with);
        }

        return $this->transform($this->prepare($model, CrudAction::Show->value));
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeAction(CrudAction::Store);

        $model = $this->create($this->validated($request, CrudAction::Store));

        if (($with = $this->with(CrudAction::Store->value)) !== []) {
            $model->load($with);
        }

        return $this->respond(
            $this->transform($this->prepare($model, CrudAction::Store->value)),
            CrudAction::Store,
        );
    }

    public function update(Request $request, mixed $key): JsonResponse
    {
        $model = $this->find($key);

        $this->authorizeAction(CrudAction::Update, $model);

        $model = $this->applyUpdate($model, $this->validated($request, CrudAction::Update));

        if (($with = $this->with(CrudAction::Update->value)) !== []) {
            $model->load($with);
        }

        return $this->respond(
            $this->transform($this->prepare($model, CrudAction::Update->value)),
            CrudAction::Update,
        );
    }

    public function destroy(Request $request, mixed $key): JsonResponse
    {
        $model = $this->find($key);

        $this->authorizeAction(CrudAction::Destroy, $model);

        $this->delete($model, $this->validated($request, CrudAction::Destroy));

        $status = (int) $this->config('responses.destroy', 204);

        if ($this->config('responses.destroy_returns_resource', false)) {
            return $this->transform($model)->response()->setStatusCode($status);
        }

        return new JsonResponse(status: $status);
    }

    /**
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    protected function scope(Builder $query): Builder
    {
        return $query;
    }

    /**
     * @param  Builder<Model>  $query
     * @return Builder<Model>|object
     */
    protected function filter(Builder $query): mixed
    {
        return $query;
    }

    /**
     * Relations to eager load, per action.
     *
     * @return array<int|string, mixed>
     */
    protected function with(string $action): array
    {
        return [];
    }

    protected function prepare(mixed $data, string $action): mixed
    {
        return $data;
    }

    /**
     * @param  Builder<Model>|object  $query
     */
    protected function paginate(mixed $query): mixed
    {
        return $this->paginator()->paginate($query, $this->perPage());
    }

    protected function perPage(): int
    {
        /** @var array<int, string> $keys */
        $keys = $this->config('pagination.query_keys', ['perPage', 'per_page']);
        $default = (int) $this->config('pagination.default', 15);
        $max = (int) $this->config('pagination.max', 100);

        $request = $this->container()->make('request');
        $value = $default;

        foreach ($keys as $key) {
            if ($request->filled($key)) {
                $value = (int) $request->input($key);
                break;
            }
        }

        return max(1, min($value, $max));
    }

    /**
     * Resolve the model for show/update/destroy.
     *
     * @throws ModelNotFoundException
     */
    protected function find(mixed $key): Model
    {
        if ($key instanceof Model) {
            return $key;
        }

        $model = $this->newModel()->resolveRouteBinding($key);

        if ($model === null) {
            throw (new ModelNotFoundException)->setModel($this->model(), [$key]);
        }

        return $model;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function create(array $data): Model
    {
        $model = $this->newModel();
        $model->fill($data)->save();

        return $model;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function applyUpdate(Model $model, array $data): Model
    {
        $model->fill($data)->save();

        return $model;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function delete(Model $model, array $data = []): void
    {
        $model->delete();
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(string $action): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request, CrudAction $action): array
    {
        $requests = $this->declarations()['requests'] ?? [];

        if (is_array($requests) && is_string($explicit = $requests[$action->value] ?? null)) {
            return $this->runFormRequest($explicit, $request);
        }

        if (($rules = $this->rules($action->value)) !== []) {
            return $this->container()->make('validator')
                ->make($request->all(), $rules)
                ->validate();
        }

        $resolved = $this->classFor('request', $action->value);

        if ($resolved !== null) {
            return $this->runFormRequest($resolved, $request);
        }

        if ($action->writes() && $this->config('strict_requests', false)) {
            throw CrudConfigurationException::missingRequest(
                $this->model(),
                $action->value,
                $this->resolver()->candidates('request', $this->model(), $action->value),
            );
        }

        // Nothing to validate with. The input passes through untouched so that
        // a controller with no request class still works -- the model's own
        // $fillable/$guarded is the only thing standing between the payload and
        // the database. Set easy-crud.strict_requests to refuse instead.
        return $request->all();
    }

    protected function authorizeAction(CrudAction $action, ?Model $model = null): void
    {
        $this->authorizer()->authorize($action->ability(), $model ?? $this->model());
    }

    protected function transform(mixed $data): JsonResource
    {
        return $this->transformer()->transform(
            $data,
            $this->classFor('resource'),
            $this->classFor('collection'),
        );
    }

    protected function respond(JsonResource $resource, CrudAction $action): JsonResponse
    {
        $status = (int) $this->config('responses.'.$action->value, 200);

        return $resource->response()->setStatusCode($status);
    }

    /**
     * @return class-string|null
     */
    protected function classFor(string $role, ?string $action = null): ?string
    {
        $explicit = $this->explicitClassFor($role);

        if ($explicit === false) {
            return null;
        }

        if (is_string($explicit)) {
            return $explicit;
        }

        return $this->resolver()->resolve($role, $this->model(), $action)->class;
    }

    protected function resolved(string $role, ?string $action = null): ?object
    {
        $class = $this->classFor($role, $action);

        return $class === null ? null : $this->container()->make($class);
    }

    /**
     * Properties that short-circuit convention resolution.
     *
     * @return class-string|bool|null
     */
    protected function explicitClassFor(string $role): string|bool|null
    {
        $declared = $this->declarations()[$role] ?? null;

        return is_string($declared) || is_bool($declared) ? $declared : null;
    }

    /**
     * @return class-string<Model>
     */
    protected function model(): string
    {
        $model = $this->declarations()['model'] ?? null;

        if (! is_string($model) || $model === '') {
            throw CrudConfigurationException::missingModel(static::class);
        }

        if (! is_subclass_of($model, Model::class)) {
            throw CrudConfigurationException::notAModel($model);
        }

        return $model;
    }

    protected function newModel(): Model
    {
        $class = $this->model();

        return new $class;
    }

    /**
     * @return Builder<Model>
     */
    protected function newQuery(): Builder
    {
        return $this->newModel()->newQuery();
    }

    /**
     * @param  class-string<FormRequest>  $class
     * @return array<string, mixed>
     */
    protected function runFormRequest(string $class, Request $request): array
    {
        /** @var FormRequest $formRequest */
        $formRequest = $class::createFrom($request);

        $formRequest->setContainer($this->container())
            ->setRedirector($this->container()->make('redirect'));

        $formRequest->validateResolved();

        return $formRequest->validated();
    }

    protected function resolver(): ResolvesClasses
    {
        return $this->container()->make(ResolvesClasses::class);
    }

    protected function authorizer(): AuthorizesActions
    {
        return $this->container()->make(AuthorizesActions::class);
    }

    protected function transformer(): TransformsResults
    {
        return $this->container()->make(TransformsResults::class);
    }

    protected function paginator(): Paginator
    {
        return new Paginator((string) $this->config('pagination.strategy', 'length_aware'));
    }

    protected function config(string $key, mixed $default = null): mixed
    {
        return $this->container()->make('config')->get('easy-crud.'.$key, $default);
    }

    protected function container(): Container
    {
        return \Illuminate\Container\Container::getInstance();
    }
}
