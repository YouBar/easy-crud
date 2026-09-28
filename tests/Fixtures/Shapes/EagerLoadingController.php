<?php

namespace Youbar\EasyCrud\Tests\Fixtures\Shapes;

use Youbar\EasyCrud\CrudController;
use Youbar\EasyCrud\Tests\Fixtures\Models\Post;

/**
 * Mirrors CourseController: eager loads on show only.
 */
class EagerLoadingController extends CrudController
{
    protected string $model = Post::class;

    /**
     * @return array<int, string>
     */
    protected function with(string $action): array
    {
        return $action === 'show' ? ['comments'] : [];
    }
}
