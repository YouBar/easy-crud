<?php

namespace Youbar\EasyCrud\Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Youbar\EasyCrud\Tests\Fixtures\Models\Post;
use Youbar\EasyCrud\Tests\TestCase;

class ConventionsCommandTest extends TestCase
{
    #[Test]
    public function it_reports_what_resolved_and_what_did_not(): void
    {
        config()->set('easy-crud.conventions.repository', ['Nothing\{Model}Repository']);

        $this->artisan('easy-crud:conventions', ['model' => Post::class])
            ->assertSuccessful();
    }

    #[Test]
    public function it_fails_clearly_for_something_that_is_not_a_model(): void
    {
        $this->artisan('easy-crud:conventions', ['model' => 'NotAThing'])
            ->expectsOutputToContain('Could not find an Eloquent model')
            ->assertFailed();
    }
}
