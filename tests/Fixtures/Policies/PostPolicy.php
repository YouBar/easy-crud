<?php

namespace Youbar\EasyCrud\Tests\Fixtures\Policies;

use Youbar\EasyCrud\Tests\Fixtures\Models\Post;

/**
 * Deliberately incomplete: viewAny and delete are missing, so the optional
 * authorizer has to skip them rather than deny.
 */
class PostPolicy
{
    public function view(mixed $user, Post $post): bool
    {
        return true;
    }

    public function create(mixed $user): bool
    {
        return true;
    }

    public function update(mixed $user, Post $post): bool
    {
        return $post->title !== 'locked';
    }
}
