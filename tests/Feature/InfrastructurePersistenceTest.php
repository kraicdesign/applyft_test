<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class InfrastructurePersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_infrastructure_migrations_and_default_seeder_are_registered(): void
    {
        $this->seed();

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
        ]);
    }
}
