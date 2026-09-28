<?php

namespace Youbar\EasyCrud\Tests\Feature;

use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Youbar\EasyCrud\Exceptions\CrudConfigurationException;
use Youbar\EasyCrud\Tests\Fixtures\Controllers\BarePostController;
use Youbar\EasyCrud\Tests\Fixtures\Controllers\CustomisedPostController;
use Youbar\EasyCrud\Tests\Fixtures\Controllers\ExplicitRequestPostController;
use Youbar\EasyCrud\Tests\Fixtures\Models\Post;
use Youbar\EasyCrud\Tests\TestCase;

class ValidationTest extends TestCase
{
    protected function defineRoutes($router): void
    {
        Route::crud('posts', BarePostController::class);
        Route::crud('custom', CustomisedPostController::class);
        Route::crud('explicit', ExplicitRequestPostController::class);
    }

    #[Test]
    public function a_convention_request_class_is_used_when_one_resolves(): void
    {
        // StorePostRequest requires a title.
        $this->postJson('/posts', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('title');
    }

    #[Test]
    public function the_nested_request_layout_resolves_when_the_flat_one_misses(): void
    {
        $post = Post::create(['title' => 'x']);

        // Requests\Post\UpdateRequest caps the title at 255 characters.
        $this->putJson("/posts/{$post->id}", ['title' => str_repeat('a', 256)])
            ->assertStatus(422)
            ->assertJsonValidationErrors('title');
    }

    #[Test]
    public function inline_rules_beat_a_resolved_request_class(): void
    {
        // CustomisedPostController::rules() demands min:5; StorePostRequest does not.
        $this->postJson('/custom', ['title' => 'tiny'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('title');

        $this->postJson('/custom', ['title' => 'long enough'])->assertCreated();
    }

    #[Test]
    public function an_explicit_requests_entry_beats_inline_rules(): void
    {
        // rules() would allow anything; StrictStorePostRequest demands starts_with:OK.
        $this->postJson('/explicit', ['title' => 'nope'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('title');

        $this->postJson('/explicit', ['title' => 'OK fine'])->assertCreated();
    }

    #[Test]
    public function no_request_and_no_rules_means_no_validation(): void
    {
        // The zero-file default: index has no request class anywhere.
        Post::create(['title' => 'a']);

        $this->getJson('/posts')->assertOk();
    }

    #[Test]
    public function strict_requests_turns_a_missing_request_class_into_a_configuration_error(): void
    {
        config()->set('easy-crud.strict_requests', true);
        config()->set('easy-crud.conventions.request', ['Nothing\Matches\{Action}{Model}Request']);

        $this->withoutExceptionHandling();

        $this->expectException(CrudConfigurationException::class);
        $this->expectExceptionMessageMatches('/nothing to validate/');

        $this->postJson('/posts', ['title' => 'x']);
    }
}
