<?php

namespace Youbar\EasyCrud\Tests\Feature;

use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Youbar\EasyCrud\Tests\Fixtures\Controllers\BarePostController;
use Youbar\EasyCrud\Tests\Fixtures\Controllers\TraitPostController;
use Youbar\EasyCrud\Tests\Fixtures\Models\Post;
use Youbar\EasyCrud\Tests\TestCase;

/**
 * Level 1: a controller declaring nothing but its model.
 */
class ZeroConfigurationTest extends TestCase
{
    protected function defineRoutes($router): void
    {
        Route::crud('posts', BarePostController::class);
        Route::crud('trait-posts', TraitPostController::class);
        Route::crud('model-posts', Post::class);
    }

    #[Test]
    public function it_lists_paginated_records(): void
    {
        Post::create(['title' => 'first']);
        Post::create(['title' => 'second']);

        $this->getJson('/posts')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.via', 'PostResource')
            ->assertJsonStructure(['data', 'links', 'meta']);
    }

    #[Test]
    public function it_shows_a_record(): void
    {
        $post = Post::create(['title' => 'hello']);

        $this->getJson("/posts/{$post->id}")
            ->assertOk()
            ->assertJsonPath('data.title', 'hello');
    }

    #[Test]
    public function it_returns_404_for_a_missing_record(): void
    {
        $this->getJson('/posts/999')->assertNotFound();
    }

    #[Test]
    public function it_stores_a_record_with_201(): void
    {
        $this->postJson('/posts', ['title' => 'created'])
            ->assertCreated()
            ->assertJsonPath('data.title', 'created');

        $this->assertDatabaseHas('posts', ['title' => 'created']);
    }

    #[Test]
    public function it_updates_a_record(): void
    {
        $post = Post::create(['title' => 'before']);

        $this->putJson("/posts/{$post->id}", ['title' => 'after'])
            ->assertOk()
            ->assertJsonPath('data.title', 'after');

        $this->assertDatabaseHas('posts', ['id' => $post->id, 'title' => 'after']);
    }

    #[Test]
    public function it_destroys_a_record_with_204(): void
    {
        $post = Post::create(['title' => 'doomed']);

        $this->deleteJson("/posts/{$post->id}")->assertNoContent();

        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    #[Test]
    public function the_trait_works_on_a_foreign_base_class(): void
    {
        Post::create(['title' => 'via trait']);

        $this->getJson('/trait-posts')
            ->assertOk()
            ->assertJsonPath('data.0.title', 'via trait');

        $this->assertSame('still here', (new TraitPostController)->somethingApplicationSpecific());
    }

    #[Test]
    public function a_model_can_be_routed_without_any_controller(): void
    {
        Post::create(['title' => 'controller-less']);

        $this->getJson('/model-posts')
            ->assertOk()
            ->assertJsonPath('data.0.title', 'controller-less');
    }
}
