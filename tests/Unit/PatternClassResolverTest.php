<?php

namespace Youbar\EasyCrud\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Youbar\EasyCrud\Exceptions\CrudConfigurationException;
use Youbar\EasyCrud\Resolution\PatternClassResolver;
use Youbar\EasyCrud\Tests\Fixtures\Models\Billing\Payment as BillingPayment;
use Youbar\EasyCrud\Tests\Fixtures\Models\Post;
use Youbar\EasyCrud\Tests\Fixtures\Requests\Post\UpdateRequest;
use Youbar\EasyCrud\Tests\Fixtures\Requests\StorePostRequest;
use Youbar\EasyCrud\Tests\Fixtures\Resources\Billing\PaymentResource as BillingPaymentResource;
use Youbar\EasyCrud\Tests\Fixtures\Resources\PostResource;

class PatternClassResolverTest extends TestCase
{
    private const FIXTURES = 'Youbar\EasyCrud\Tests\Fixtures';

    #[Test]
    public function it_resolves_a_single_pattern(): void
    {
        $result = $this->resolver([
            'resource' => self::FIXTURES.'\Resources\{Model}Resource',
        ])->resolve('resource', Post::class);

        $this->assertTrue($result->resolved());
        $this->assertSame(PostResource::class, $result->class);
    }

    #[Test]
    public function it_tries_candidates_in_order_and_reports_which_one_matched(): void
    {
        $resolver = $this->resolver([
            'request' => [
                self::FIXTURES.'\Requests\{Action}{Model}Request',
                self::FIXTURES.'\Requests\{Model}\{Action}Request',
            ],
        ]);

        // Only the flat class exists for store.
        $store = $resolver->resolve('request', Post::class, 'store');
        $this->assertSame(StorePostRequest::class, $store->class);
        $this->assertSame(0, $store->matched);

        // Only the nested class exists for update, so the first pattern misses.
        $update = $resolver->resolve('request', Post::class, 'update');
        $this->assertSame(UpdateRequest::class, $update->class);
        $this->assertSame(1, $update->matched);
        $this->assertSame('resolved (pattern 2 of 2)', $update->explain());
    }

    #[Test]
    public function it_reports_a_miss_without_throwing(): void
    {
        $result = $this->resolver([
            'request' => [self::FIXTURES.'\Requests\{Action}{Model}Request'],
        ])->resolve('request', Post::class, 'index');

        $this->assertFalse($result->resolved());
        $this->assertNull($result->class);
        $this->assertSame([self::FIXTURES.'\Requests\IndexPostRequest'], $result->candidates);
        $this->assertSame('not found', $result->explain());
    }

    #[Test]
    public function a_null_row_is_disabled_rather_than_missing(): void
    {
        $result = $this->resolver(['repository' => null])->resolve('repository', Post::class);

        $this->assertFalse($result->resolved());
        $this->assertTrue($result->disabled);
        $this->assertStringContainsString('disabled', $result->explain());
    }

    #[Test]
    public function a_closure_row_computes_the_class_itself(): void
    {
        $result = $this->resolver([
            'resource' => fn (string $model, ?string $action): string => PostResource::class,
        ])->resolve('resource', Post::class);

        $this->assertSame(PostResource::class, $result->class);
    }

    #[Test]
    public function a_closure_returning_a_nonexistent_class_is_a_miss(): void
    {
        $result = $this->resolver([
            'resource' => fn (): string => 'Nope\Missing',
        ])->resolve('resource', Post::class);

        $this->assertFalse($result->resolved());
    }

    #[Test]
    public function domain_placeholder_supports_modular_layouts(): void
    {
        // Post lives in ...\Fixtures\Models, so {Domain} is ...\Fixtures and one
        // pattern reaches a sibling namespace without any string replacement.
        $result = $this->resolver([
            'resource' => '{Domain}\Resources\{Model}Resource',
        ])->resolve('resource', Post::class);

        $this->assertSame(PostResource::class, $result->class);
    }

    #[Test]
    public function it_expands_every_placeholder(): void
    {
        $candidates = $this->resolver([
            'probe' => '{FQCN}|{Namespace}|{Domain}|{Models}|{Model}|{models}|{model}|{Action}|{action}',
        ])->candidates('probe', Post::class, 'store');

        $this->assertSame(
            Post::class
            .'|'.self::FIXTURES.'\Models'
            .'|'.self::FIXTURES
            .'|Posts|Post|posts|post|Store|store',
            $candidates[0],
        );
    }

    #[Test]
    public function sub_namespace_mirrors_a_nested_model_into_a_sibling_tree(): void
    {
        $resolver = $this->resolver(
            ['resource' => self::FIXTURES.'\Resources\{SubNamespace}\{Model}Resource'],
            self::FIXTURES.'\Models',
        );

        // App\Models\Billing\Payment -> App\Http\Resources\Billing\PaymentResource
        $this->assertSame(
            BillingPaymentResource::class,
            $resolver->resolve('resource', BillingPayment::class)->class,
        );

        // The same pattern still works for a model sitting at the root, because
        // the empty segment collapses.
        $this->assertSame(
            PostResource::class,
            $resolver->resolve('resource', Post::class)->class,
        );
    }

    #[Test]
    public function sub_namespace_without_a_model_namespace_is_a_configuration_error(): void
    {
        $this->expectException(CrudConfigurationException::class);
        $this->expectExceptionMessageMatches('/easy-crud.model_namespace/');

        $this->resolver(['resource' => 'X\{SubNamespace}\{Model}Resource'])
            ->resolve('resource', Post::class);
    }

    #[Test]
    public function a_model_outside_the_configured_root_has_no_sub_namespace(): void
    {
        $candidates = $this->resolver(
            ['probe' => 'X\{SubNamespace}\{Model}'],
            'Some\Other\Root',
        )->candidates('probe', BillingPayment::class);

        $this->assertSame(['X\Payment'], $candidates);
    }

    #[Test]
    public function an_unknown_role_resolves_like_any_other(): void
    {
        // Proving the point that conventions only answer "which class" -- the
        // package has no code that knows what a "repository" is.
        $result = $this->resolver([
            'repository' => self::FIXTURES.'\Resources\{Model}Resource',
        ])->resolve('repository', Post::class);

        $this->assertSame(PostResource::class, $result->class);
        $this->assertContains('repository', $this->resolver(['repository' => 'x'])->roles());
    }

    #[Test]
    public function results_are_memoised_including_misses(): void
    {
        $calls = 0;
        $resolver = $this->resolver([
            'resource' => function () use (&$calls): ?string {
                $calls++;

                return null;
            },
        ]);

        $resolver->resolve('resource', Post::class);
        $resolver->resolve('resource', Post::class);

        $this->assertSame(1, $calls);
    }

    /**
     * @param  array<string, mixed>  $conventions
     */
    private function resolver(array $conventions, ?string $modelNamespace = null): PatternClassResolver
    {
        return new PatternClassResolver($conventions, $modelNamespace);
    }
}
