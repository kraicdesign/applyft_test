<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\Factory\UserFactory;
use App\Infrastructure\Persistence\Eloquent\Model\User;
use Tests\TestCase;

final class InfrastructureUserModelTest extends TestCase
{
    public function test_auth_and_factory_use_the_infrastructure_user_model(): void
    {
        self::assertSame(User::class, config('auth.providers.users.model'));
        self::assertInstanceOf(UserFactory::class, User::factory());
    }
}
