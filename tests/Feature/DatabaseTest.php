<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;

class DatabaseTest extends TestCase
{

    #[Test]
    public function it_can_create_a_user_in_the_database()
    {
        $user = User::factory()->create();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => $user->email,
        ]);
    }
}
