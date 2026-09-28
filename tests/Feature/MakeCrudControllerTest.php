<?php

namespace Youbar\EasyCrud\Tests\Feature;

use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Youbar\EasyCrud\Tests\TestCase;

class MakeCrudControllerTest extends TestCase
{
    protected function tearDown(): void
    {
        File::deleteDirectory($this->app->path('Http'));
        File::deleteDirectory($this->app->path('Generated'));

        parent::tearDown();
    }

    #[Test]
    public function it_generates_a_controller_inferring_the_model(): void
    {
        $this->artisan('make:crud-controller', ['name' => 'PostController'])
            ->assertSuccessful();

        $path = $this->app->path('Http/Controllers/PostController.php');

        $this->assertFileExists($path);

        $contents = (string) file_get_contents($path);

        $this->assertStringContainsString('class PostController extends CrudController', $contents);
        $this->assertStringContainsString('protected string $model = Post::class;', $contents);
        $this->assertStringContainsString('use Youbar\EasyCrud\CrudController;', $contents);
    }

    #[Test]
    public function it_generates_companions_where_the_conventions_will_find_them(): void
    {
        // Point the conventions somewhere unusual: the generated files must
        // follow the config, not a hardcoded guess.
        config()->set('easy-crud.conventions.resource', ['App\Generated\Resources\{Model}Resource']);
        config()->set('easy-crud.conventions.request', ['App\Generated\Requests\{Action}{Model}Request']);

        $this->artisan('make:crud-controller', ['name' => 'PostController', '--all' => true])
            ->assertSuccessful();

        $this->assertFileExists($this->app->path('Generated/Resources/PostResource.php'));
        $this->assertFileExists($this->app->path('Generated/Requests/StorePostRequest.php'));
        $this->assertFileExists($this->app->path('Generated/Requests/UpdatePostRequest.php'));

        $resource = (string) file_get_contents($this->app->path('Generated/Resources/PostResource.php'));
        $this->assertStringContainsString('namespace App\Generated\Resources;', $resource);
    }

    #[Test]
    public function it_refuses_to_clobber_without_force(): void
    {
        $this->artisan('make:crud-controller', ['name' => 'PostController'])->assertSuccessful();

        $path = $this->app->path('Http/Controllers/PostController.php');
        file_put_contents($path, '<?php // mine');

        $this->artisan('make:crud-controller', ['name' => 'PostController'])
            ->expectsOutputToContain('Exists, skipped')
            ->assertSuccessful();

        $this->assertSame('<?php // mine', (string) file_get_contents($path));

        $this->artisan('make:crud-controller', ['name' => 'PostController', '--force' => true])
            ->assertSuccessful();

        $this->assertStringContainsString('CrudController', (string) file_get_contents($path));
    }

    #[Test]
    public function an_explicit_model_option_wins_over_the_inferred_name(): void
    {
        $this->artisan('make:crud-controller', [
            'name' => 'ArticlesController',
            '--model' => 'Post',
        ])->assertSuccessful();

        $contents = (string) file_get_contents($this->app->path('Http/Controllers/ArticlesController.php'));

        $this->assertStringContainsString('protected string $model = Post::class;', $contents);
    }
}
