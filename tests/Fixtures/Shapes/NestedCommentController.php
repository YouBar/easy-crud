<?php

namespace Youbar\EasyCrud\Tests\Fixtures\Shapes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Youbar\EasyCrud\CrudController;
use Youbar\EasyCrud\Tests\Fixtures\Models\Comment;

/**
 * Mirrors the nested courses/{course}/users route: the parent is a route
 * parameter, so scope() constrains the index and create() supplies the key.
 */
class NestedCommentController extends CrudController
{
    protected string $model = Comment::class;

    /**
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    protected function scope(Builder $query): Builder
    {
        return $query->where('post_id', $this->parentKey());
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function create(array $data): Model
    {
        return parent::create($data + ['post_id' => $this->parentKey()]);
    }

    protected function parentKey(): mixed
    {
        return request()->route('post');
    }
}
