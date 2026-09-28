<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed roles and permissions for tests that use the database
        if ($this->app->environment('testing')) {
            $this->seed(\Database\Seeders\PermissionSeeder::class);
            $this->seed(\Database\Seeders\RoleSeeder::class);
        }
    }
}
