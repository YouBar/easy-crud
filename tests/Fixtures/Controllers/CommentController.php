<?php

namespace Youbar\EasyCrud\Tests\Fixtures\Controllers;

use Youbar\EasyCrud\CrudController;
use Youbar\EasyCrud\Tests\Fixtures\Models\Comment;

/**
 * Comment has no policy and no resource class, so it exercises every fallback
 * at once. Laravel's own policy auto-discovery finds nothing for it.
 */
class CommentController extends CrudController
{
    protected string $model = Comment::class;
}
