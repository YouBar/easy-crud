<?php

namespace Youbar\EasyCrud\Tests\Feature;

use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Youbar\EasyCrud\Tests\Fixtures\Models\Comment;
use Youbar\EasyCrud\Tests\Fixtures\Models\Post;
use Youbar\EasyCrud\Tests\Fixtures\Shapes\ComputedCountsController;
use Youbar\EasyCrud\Tests\Fixtures\Shapes\EagerLoadingController;
use Youbar\EasyCrud\Tests\Fixtures\Shapes\NestedCommentController;
use Youbar\EasyCrud\Tests\Fixtures\Shapes\OffConventionResourceController;
use Youbar\EasyCrud\Tests\Fixtures\Shapes\ValidatedDestroyController;
use Youbar\EasyCrud\Tests\TestCase;

class ControllerShapesTest extends TestCase
{
    protected function defineRoutes($router): void
    {
        Route::crud('courses', EagerLoadingController::class);
        Route::crud('counted', ComputedCountsController::class);
        Route::crud('off-convention', OffConventionResourceController::class);
        Route::crud('validated-destroy', ValidatedDestroyController::class);
        Route::crud('posts/{post}/comments', NestedCommentController::class);
    }

    #[Test]
    public function eager_loads_apply_to_one_action_only(): void
    {
        $post = Post::create(['title' => 'course']);
        Comment::create(['post_id' => $post->id, 'body' => 'ok one']);

        $this->getJson("/courses/{$post->id}")->assertOk();
        $this->getJson('/courses')->assertOk();
    }

    #[Test]
    public function computed_counts_are_shaped_by_prepare(): void
    {
        $post = Post::create(['title' => 'user']);
        Comment::create(['post_id' => $post->id, 'body' => 'ok yes']);
        Comment::create(['post_id' => $post->id, 'body' => 'ok also']);
        Comment::create(['post_id' => $post->id, 'body' => 'no thanks']);

        $this->getJson("/counted/{$post->id}")
            ->assertOk()
            ->assertJsonPath('data.approved_count', 2)
            ->assertJsonPath('data.rejected_count', 1)
            ->assertJsonPath('data.comments_loaded', true);
    }

    #[Test]
    public function relations_reload_after_update(): void
    {
        $post = Post::create(['title' => 'before']);
        Comment::create(['post_id' => $post->id, 'body' => 'ok kept']);

        $this->putJson("/counted/{$post->id}", ['title' => 'after'])
            ->assertOk()
            ->assertJsonPath('data.title', 'after')
            ->assertJsonPath('data.comments_loaded', true)
            ->assertJsonPath('data.approved_count', 1);
    }

    #[Test]
    public function an_explicit_resource_beats_the_conventions(): void
    {
        $post = Post::create(['title' => 'paid']);

        // The conventions would find PostResource; the property wins.
        $this->getJson("/off-convention/{$post->id}")
            ->assertOk()
            ->assertJsonPath('data.via', 'OffConventionResource')
            ->assertJsonPath('data.morphed', true);
    }

    #[Test]
    public function destroy_validates_and_passes_its_data_to_delete(): void
    {
        $post = Post::create(['title' => 'doomed']);

        // The destroy request is enforced.
        $this->deleteJson("/validated-destroy/{$post->id}")
            ->assertStatus(422)
            ->assertJsonValidationErrors('reason');

        // ...and the validated data reaches delete().
        $this->deleteJson("/validated-destroy/{$post->id}", ['reason' => 'cancelled'])
            ->assertNoContent();

        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    #[Test]
    public function nested_routes_scope_to_their_parent(): void
    {
        $first = Post::create(['title' => 'first']);
        $second = Post::create(['title' => 'second']);

        Comment::create(['post_id' => $first->id, 'body' => 'belongs to first']);
        Comment::create(['post_id' => $second->id, 'body' => 'belongs to second']);

        $this->getJson("/posts/{$first->id}/comments")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.body', 'belongs to first');

        $this->postJson("/posts/{$second->id}/comments", ['body' => 'new one'])
            ->assertCreated();

        $this->assertDatabaseHas('comments', [
            'body' => 'new one',
            'post_id' => $second->id,
        ]);
    }

    #[Test]
    public function nested_item_routes_resolve_their_own_parameter(): void
    {
        $post = Post::create(['title' => 'parent']);
        $comment = Comment::create(['post_id' => $post->id, 'body' => 'mine']);

        $this->getJson("/posts/{$post->id}/comments/{$comment->id}")
            ->assertOk()
            ->assertJsonPath('data.body', 'mine');

        $this->deleteJson("/posts/{$post->id}/comments/{$comment->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    }

    #[Test]
    public function no_fixture_overrides_a_crud_action(): void
    {
        $actions = ['index', 'show', 'store', 'update', 'destroy'];

        $controllers = [
            EagerLoadingController::class,
            ComputedCountsController::class,
            OffConventionResourceController::class,
            ValidatedDestroyController::class,
            NestedCommentController::class,
        ];

        foreach ($controllers as $controller) {
            foreach ($actions as $action) {
                $method = new \ReflectionMethod($controller, $action);

                $this->assertNotSame(
                    $controller,
                    $method->getDeclaringClass()->getName(),
                    sprintf(
                        '%s overrides %s(). These shapes must stay expressible '
                        .'without overriding a CRUD action.',
                        class_basename($controller),
                        $action,
                    ),
                );
            }
        }
    }
}
