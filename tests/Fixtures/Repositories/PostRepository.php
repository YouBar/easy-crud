<?php

namespace Youbar\EasyCrud\Tests\Fixtures\Repositories;

use Youbar\EasyCrud\Tests\Fixtures\Models\Post;

class PostRepository
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function store(array $data): Post
    {
        return Post::create($data + ['via_repository' => true]);
    }
}
