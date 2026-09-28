<?php

namespace Youbar\EasyCrud\Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Youbar\EasyCrud\Tests\Fixtures\Controllers\BarePostController;
use Youbar\EasyCrud\Tests\Fixtures\Controllers\CommentController;
use Youbar\EasyCrud\Tests\Fixtures\Controllers\CustomisedPostController;
use Youbar\EasyCrud\Tests\Fixtures\Models\Comment;
use Youbar\EasyCrud\Tests\Fixtures\Models\Post;
use Youbar\EasyCrud\Tests\Fixtures\Resources\PostCollection;
use Youbar\EasyCrud\Tests\TestCase;

class CustomisationTest extends TestCase
{
    protected function defineRoutes($router): void
    {
        Route::crud('posts', BarePostController::class);
        Route::crud('custom', CustomisedPostController::class);
        Route::crud('comments', CommentController::class);
        Route::crud('limited', BarePostController::class)->only('index', 'show');
    }

    #[Test]
    public function an_overridden_scope_narrows_the_index(): void
    {
        Post::create(['title' => 'shown', 'published' => true]);
        Post::create(['title' => 'hidden', 'published' => false]);

        $this->getJson('/custom')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/posts')->assertOk()->assertJsonCount(2, 'data');
    }

    #[Test]
    public function an_overridden_with_eager_loads_per_action(): void
    {
        $post = Post::create(['title' => 'with comments']);
        Comment::create(['post_id' => $post->id, 'body' => 'first']);

        // CustomisedPostController eager loads comments on show but not index,
        // so the relation must already be present after the action runs.
        $this->getJson("/custom/{$post->id}")->assertOk();
        $this->assertSame(2, $this->queriesFor("/custom/{$post->id}"));

        // The bare controller loads nothing, so it costs one query.
        $this->assertSame(1, $this->queriesFor("/posts/{$post->id}"));
    }

    private function queriesFor(string $uri): int
    {
        $count = 0;
        DB::listen(function () use (&$count): void {
            $count++;
        });

        $this->getJson($uri)->assertOk();

        return $count;
    }

    #[Test]
    public function an_overridden_per_page_wins_over_config(): void
    {
        foreach (range(1, 5) as $i) {
            Post::create(['title' => "post {$i}"]);
        }

        $this->getJson('/custom')->assertOk()->assertJsonCount(2, 'data');
    }

    #[Test]
    public function per_page_comes_from_the_query_string_and_is_clamped(): void
    {
        foreach (range(1, 12) as $i) {
            Post::create(['title' => "post {$i}"]);
        }

        $this->getJson('/posts?perPage=3')->assertOk()->assertJsonCount(3, 'data');
        $this->getJson('/posts?per_page=4')->assertOk()->assertJsonCount(4, 'data');

        config()->set('easy-crud.pagination.max', 5);
        $this->getJson('/posts?perPage=1000')->assertOk()->assertJsonCount(5, 'data');
    }

    #[Test]
    public function a_missing_resource_class_falls_back_to_plain_json(): void
    {
        $post = Post::create(['title' => 'parent']);
        Comment::create(['post_id' => $post->id, 'body' => 'no resource class exists']);

        // There is no CommentResource anywhere.
        $this->getJson('/comments')
            ->assertOk()
            ->assertJsonPath('data.0.body', 'no resource class exists');
    }

    #[Test]
    public function an_explicit_collection_class_is_used_for_index_only(): void
    {
        config()->set(
            'easy-crud.conventions.collection',
            ['Youbar\EasyCrud\Tests\Fixtures\Resources\{Model}Collection'],
        );

        Post::create(['title' => 'grouped']);

        $this->getJson('/posts')->assertOk()->assertJsonPath('via', 'PostCollection');
        $this->assertTrue(class_exists(PostCollection::class));
    }

    #[Test]
    public function only_restricts_which_routes_exist(): void
    {
        $post = Post::create(['title' => 'read only']);

        $this->getJson('/limited')->assertOk();
        $this->getJson("/limited/{$post->id}")->assertOk();
        $this->postJson('/limited', ['title' => 'nope'])->assertStatus(405);
        $this->deleteJson("/limited/{$post->id}")->assertStatus(405);
    }

    #[Test]
    public function a_custom_role_resolves_a_class_but_changes_no_behaviour(): void
    {
        // Adding a row the package has no code for must be inert.
        config()->set(
            'easy-crud.conventions.repository',
            ['Youbar\EasyCrud\Tests\Fixtures\Repositories\{Model}Repository'],
        );

        $this->postJson('/posts', ['title' => 'unaffected'])->assertCreated();

        // PostRepository::store() would have set via_repository; nothing called it.
        $this->assertDatabaseHas('posts', ['title' => 'unaffected', 'via_repository' => false]);
    }

    #[Test]
    public function input_passes_through_unvalidated_when_nothing_validates(): void
    {
        // Comment has no request class and no rules(): the documented footgun.
        $post = Post::create(['title' => 'parent']);

        $this->postJson('/comments', ['post_id' => $post->id, 'body' => 'raw'])
            ->assertCreated();

        $this->assertDatabaseHas('comments', ['body' => 'raw']);
    }

    #[Test]
    public function response_status_codes_are_configurable(): void
    {
        config()->set('easy-crud.responses.store', 200);
        config()->set('easy-crud.responses.destroy', 200);
        config()->set('easy-crud.responses.destroy_returns_resource', true);

        $this->postJson('/posts', ['title' => 'two hundred'])->assertOk();

        $post = Post::create(['title' => 'goodbye']);
        $this->deleteJson("/posts/{$post->id}")
            ->assertOk()
            ->assertJsonPath('data.title', 'goodbye');
    }
}
