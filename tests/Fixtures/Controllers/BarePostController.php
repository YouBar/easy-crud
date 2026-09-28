<?php

namespace Youbar\EasyCrud\Tests\Fixtures\Controllers;

use Youbar\EasyCrud\CrudController;
use Youbar\EasyCrud\Tests\Fixtures\Models\Post;

/**
 * Level 1: the whole controller. Everything else is conventions and defaults.
 */
class BarePostController extends CrudController
{
    protected string $model = Post::class;
}
