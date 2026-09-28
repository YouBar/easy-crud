<?php

namespace Youbar\EasyCrud\Tests\Feature;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Youbar\EasyCrud\Tests\Fixtures\Controllers\BarePostController;
use Youbar\EasyCrud\Tests\Fixtures\Controllers\CommentController;
use Youbar\EasyCrud\Tests\Fixtures\Models\Comment;
use Youbar\EasyCrud\Tests\Fixtures\Models\Post;
use Youbar\EasyCrud\Tests\Fixtures\Policies\PostPolicy;
use Youbar\EasyCrud\Tests\TestCase;

class AuthorizationTest extends TestCase
{
    protected function defineRoutes($router): void
    {
        Route::crud('posts', BarePostController::class);
        Route::crud('comments', CommentController::class);
    }

    #[Test]
    public function without_a_policy_every_action_is_allowed(): void
    {
        // Comment has no policy for Laravel's auto-discovery to find.
        $post = Post::create(['title' => 'parent']);

        $this->getJson('/comments')->assertOk();
        $this->postJson('/comments', ['post_id' => $post->id, 'body' => 'hi'])->assertCreated();
    }

    #[Test]
    public function a_policy_is_picked_up_by_laravels_own_auto_discovery(): void
    {
        // No Gate::policy() call anywhere: Models\Post -> Policies\PostPolicy.
        $locked = Post::create(['title' => 'locked']);

        $this->putJson("/posts/{$locked->id}", ['title' => 'nope'])->assertForbidden();
    }

    #[Test]
    public function an_ability_the_policy_defines_is_enforced(): void
    {
        Gate::policy(Post::class, PostPolicy::class);

        $locked = Post::create(['title' => 'locked']);
        $open = Post::create(['title' => 'open enough']);

        $this->putJson("/posts/{$locked->id}", ['title' => 'nope'])->assertForbidden();
        $this->putJson("/posts/{$open->id}", ['title' => 'fine'])->assertOk();
    }

    #[Test]
    public function an_ability_the_policy_omits_is_skipped_rather_than_denied(): void
    {
        Gate::policy(Post::class, PostPolicy::class);

        Post::create(['title' => 'listed']);

        // PostPolicy defines no viewAny and no delete.
        $this->getJson('/posts')->assertOk();
    }

    #[Test]
    public function strict_mode_rejects_a_policy_that_omits_the_ability(): void
    {
        config()->set('easy-crud.authorization', 'strict');

        $this->withoutExceptionHandling();

        // PostPolicy is discovered but defines no viewAny.
        $this->expectExceptionMessageMatches('/does not define the \\[viewAny\\] ability/');

        $this->getJson('/posts');
    }

    #[Test]
    public function strict_mode_rejects_a_model_with_no_policy_at_all(): void
    {
        config()->set('easy-crud.authorization', 'strict');

        $this->withoutExceptionHandling();

        $this->expectExceptionMessageMatches('/No policy registered/');

        $this->getJson('/comments');
    }

    #[Test]
    public function none_mode_skips_a_policy_that_would_otherwise_deny(): void
    {
        config()->set('easy-crud.authorization', 'none');
        Gate::policy(Post::class, PostPolicy::class);

        $locked = Post::create(['title' => 'locked']);

        $this->putJson("/posts/{$locked->id}", ['title' => 'allowed now'])->assertOk();
    }
}
