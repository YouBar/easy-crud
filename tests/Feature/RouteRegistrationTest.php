<?php

namespace Youbar\EasyCrud\Tests\Feature;

use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Youbar\EasyCrud\Tests\Fixtures\Controllers\BarePostController;
use Youbar\EasyCrud\Tests\Fixtures\Models\Post;
use Youbar\EasyCrud\Tests\TestCase;

class RouteRegistrationTest extends TestCase
{
    protected function defineRoutes($router): void
    {
        Route::crud('posts', BarePostController::class);
        Route::crud('course-locations', BarePostController::class);
        Route::crud('posts/{post}/comments', BarePostController::class);
        Route::crud('readonly', BarePostController::class)->only('index');
        Route::crud('no-delete', BarePostController::class)->except('destroy');
        Route::crud('renamed', BarePostController::class)->names('articles');
        Route::crud('model-only', Post::class);
    }

    /**
     * @return array<string, string> "METHODS uri" => route name
     */
    private function routes(string $prefix): array
    {
        $found = [];

        foreach (Route::getRoutes() as $route) {
            if (! str_starts_with($route->uri(), $prefix)) {
                continue;
            }

            $methods = implode('|', array_values(array_diff($route->methods(), ['HEAD'])));
            $found[$methods.' '.$route->uri()] = $route->getName() ?? '-';
        }

        return $found;
    }

    #[Test]
    public function it_registers_the_five_actions_like_api_resource(): void
    {
        $this->assertSame([
            'GET posts' => 'posts.index',
            'POST posts' => 'posts.store',
            'GET posts/{post}' => 'posts.show',
            'PUT|PATCH posts/{post}' => 'posts.update',
            'DELETE posts/{post}' => 'posts.destroy',
        ], array_filter(
            $this->routes('posts'),
            fn (string $name): bool => ! str_contains($name, 'comments'),
        ));
    }

    #[Test]
    public function a_multi_word_uri_gets_a_snake_case_parameter(): void
    {
        $this->assertArrayHasKey(
            'GET course-locations/{course_location}',
            $this->routes('course-locations'),
        );
    }

    #[Test]
    public function a_nested_uri_names_its_parameter_after_the_last_segment(): void
    {
        $routes = $this->routes('posts/{post}/comments');

        $this->assertSame([
            'GET posts/{post}/comments' => 'posts.comments.index',
            'POST posts/{post}/comments' => 'posts.comments.store',
            'GET posts/{post}/comments/{comment}' => 'posts.comments.show',
            'PUT|PATCH posts/{post}/comments/{comment}' => 'posts.comments.update',
            'DELETE posts/{post}/comments/{comment}' => 'posts.comments.destroy',
        ], $routes);
    }

    #[Test]
    public function only_registers_just_the_named_actions(): void
    {
        $this->assertSame(
            ['GET readonly' => 'readonly.index'],
            $this->routes('readonly'),
        );
    }

    #[Test]
    public function except_drops_the_named_actions(): void
    {
        $this->assertArrayNotHasKey('DELETE no-delete/{no_delete}', $this->routes('no-delete'));
        $this->assertArrayHasKey('GET no-delete', $this->routes('no-delete'));
    }

    #[Test]
    public function names_overrides_the_route_name_prefix(): void
    {
        $this->assertSame('articles.index', $this->routes('renamed')['GET renamed']);
    }

    #[Test]
    public function a_model_can_be_routed_without_a_controller(): void
    {
        $this->assertSame([
            'GET model-only' => 'model-only.index',
            'POST model-only' => 'model-only.store',
            'GET model-only/{model_only}' => 'model-only.show',
            'PUT|PATCH model-only/{model_only}' => 'model-only.update',
            'DELETE model-only/{model_only}' => 'model-only.destroy',
        ], $this->routes('model-only'));
    }
}
