<?php

declare(strict_types=1);

namespace Modules\Auth\Tests\Feature;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ClosedRegistrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The registration routes are registered conditionally at boot, so
     * config()->set() inside a test is too late — the environment variable
     * must be in place before the application is created.
     */
    public function createApplication(): Application
    {
        putenv('TROVE_REGISTRATION_MODE=closed');
        $_ENV['TROVE_REGISTRATION_MODE'] = 'closed';
        $_SERVER['TROVE_REGISTRATION_MODE'] = 'closed';

        return parent::createApplication();
    }

    protected function tearDown(): void
    {
        putenv('TROVE_REGISTRATION_MODE');
        unset($_ENV['TROVE_REGISTRATION_MODE'], $_SERVER['TROVE_REGISTRATION_MODE']);

        parent::tearDown();
    }

    public function test_the_registration_routes_do_not_exist(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register', [])->assertNotFound();
    }

    public function test_signing_in_still_works(): void
    {
        $this->get('/login')->assertOk();
    }
}
