<?php

namespace Youbar\EasyCrud\Tests\Fixtures\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Youbar\EasyCrud\CrudController;
use Youbar\EasyCrud\Tests\Fixtures\Models\Post;

/**
 * Level 2: method overrides.
 */
class CustomisedPostController extends CrudController
{
    protected string $model = Post::class;

    /**
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    protected function scope(Builder $query): Builder
    {
        return $query->where('published', true);
    }

    /**
     * @return array<int, string>
     */
    protected function with(string $action): array
    {
        return $action === 'show' ? ['comments'] : [];
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(string $action): array
    {
        return $action === 'store' ? ['title' => 'required|string|min:5'] : [];
    }

    protected function perPage(): int
    {
        return 2;
    }
}
