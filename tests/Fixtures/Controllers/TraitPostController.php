<?php

namespace Youbar\EasyCrud\Tests\Fixtures\Controllers;

use Youbar\EasyCrud\Concerns\HandlesCrud;
use Youbar\EasyCrud\Tests\Fixtures\Models\Post;

/**
 * A controller that does NOT extend anything from the package. Proves the
 * trait carries no inheritance requirement.
 */
class TraitPostController extends ForeignBaseController
{
    use HandlesCrud;

    protected string $model = Post::class;
}
