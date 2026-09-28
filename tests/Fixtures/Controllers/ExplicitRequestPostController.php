<?php

namespace Youbar\EasyCrud\Tests\Fixtures\Controllers;

use Illuminate\Foundation\Http\FormRequest;
use Youbar\EasyCrud\CrudController;
use Youbar\EasyCrud\Tests\Fixtures\Models\Post;
use Youbar\EasyCrud\Tests\Fixtures\Requests\StrictStorePostRequest;

/**
 * Declares $requests, which must win over both rules() and the conventions.
 */
class ExplicitRequestPostController extends CrudController
{
    protected string $model = Post::class;

    /** @var array<string, class-string<FormRequest>> */
    protected array $requests = ['store' => StrictStorePostRequest::class];

    /**
     * @return array<string, mixed>
     */
    protected function rules(string $action): array
    {
        // Would allow anything; the $requests entry must take precedence.
        return $action === 'store' ? ['title' => 'nullable'] : [];
    }
}
