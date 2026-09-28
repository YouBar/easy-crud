<?php

namespace Youbar\EasyCrud\Tests\Unit;

use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Youbar\EasyCrud\EasyCrudServiceProvider;

class ConfigMergeTest extends TestCase
{
    /**
     * @param  array<string, mixed>  $published
     * @return array<string, mixed>
     */
    private function merged(array $published): array
    {
        $container = new Container;
        $container->instance('config', new Repository(['easy-crud' => $published]));

        (new EasyCrudServiceProvider($container))->register();

        /** @var array<string, mixed> $merged */
        $merged = $container['config']->get('easy-crud');

        return $merged;
    }

    #[Test]
    public function published_conventions_are_the_only_conventions(): void
    {
        $config = $this->merged([
            'conventions' => [
                'repository' => ['App\Repositories\{Model}Repository'],
            ],
        ]);

        $this->assertSame(['repository'], array_keys($config['conventions']));
    }

    #[Test]
    public function untouched_top_level_keys_survive(): void
    {
        $config = $this->merged([
            'conventions' => ['repository' => ['App\Repositories\{Model}Repository']],
        ]);

        $this->assertSame(15, $config['pagination']['default']);
        $this->assertSame(['perPage', 'per_page'], $config['pagination']['query_keys']);
        $this->assertSame(201, $config['responses']['store']);
        $this->assertSame('optional', $config['authorization']);
        $this->assertFalse($config['strict_requests']);
    }

    #[Test]
    public function a_pattern_list_replaces_rather_than_appends(): void
    {
        $config = $this->merged([
            'conventions' => ['request' => ['Only\{Action}{Model}Request']],
            'pagination' => ['default' => 25],
        ]);

        $this->assertSame(['Only\{Action}{Model}Request'], $config['conventions']['request']);
        // Sibling keys inside pagination survive the partial override.
        $this->assertSame(25, $config['pagination']['default']);
        $this->assertSame(100, $config['pagination']['max']);
    }

    #[Test]
    public function a_row_can_be_switched_off_explicitly(): void
    {
        $config = $this->merged([
            'conventions' => ['resource' => null],
        ]);

        $this->assertNull($config['conventions']['resource']);
    }

    #[Test]
    public function the_package_ships_no_conventions(): void
    {
        $this->assertSame([], $this->merged([])['conventions']);
    }
}
