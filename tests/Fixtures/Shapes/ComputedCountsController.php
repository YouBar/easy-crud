<?php

namespace Youbar\EasyCrud\Tests\Fixtures\Shapes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Youbar\EasyCrud\CrudController;
use Youbar\EasyCrud\Tests\Fixtures\Models\Post;
use Youbar\EasyCrud\Tests\Fixtures\Resources\Alternate\CountsResource;

/**
 * Mirrors UserController: relations on show plus loadCount() with closures,
 * and the same relations re-loaded after update.
 */
class ComputedCountsController extends CrudController
{
    protected string $model = Post::class;

    /** @var class-string|null */
    protected ?string $resource = CountsResource::class;

    /**
     * @return array<int, string>
     */
    protected function with(string $action): array
    {
        return in_array($action, ['show', 'update'], true) ? ['comments'] : [];
    }

    protected function prepare(mixed $data, string $action): mixed
    {
        if ($data instanceof Model && in_array($action, ['show', 'update'], true)) {
            $data->loadCount([
                'comments as approved_count' => fn (Builder $q) => $q->where('body', 'like', 'ok%'),
                'comments as rejected_count' => fn (Builder $q) => $q->where('body', 'not like', 'ok%'),
            ]);
        }

        return $data;
    }
}
