<?php

namespace Youbar\EasyCrud\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;
use Youbar\EasyCrud\EasyCrudServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('posts', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->boolean('published')->default(true);
            $table->boolean('via_repository')->default(false);
            $table->timestamps();
        });

        Schema::create('comments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('post_id');
            $table->string('body');
            $table->timestamps();
        });
    }

    /**
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [EasyCrudServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Fixtures live outside App\*, so point the conventions at them. This
        // doubles as proof that the package has no hardcoded namespaces.
        $app['config']->set('easy-crud.conventions', [
            'resource' => ['Youbar\EasyCrud\Tests\Fixtures\Resources\{Model}Resource'],
            'collection' => null,
            'request' => [
                'Youbar\EasyCrud\Tests\Fixtures\Requests\{Action}{Model}Request',
                'Youbar\EasyCrud\Tests\Fixtures\Requests\{Model}\{Action}Request',
            ],
        ]);
    }
}
