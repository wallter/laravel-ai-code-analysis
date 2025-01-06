<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_has_fillable_attributes()
    {
        $user = User::factory()->make();

        $this->assertEquals([
            'name',
            'email',
            'password',
        ], $user->getFillable());
    }

    /** @test */
    public function it_hides_sensitive_attributes()
    {
        $user = User::factory()->make();

        $this->assertContains('password', $user->getHidden());
        $this->assertContains('remember_token', $user->getHidden());
    }

    /** @test */
    public function it_casts_attributes_correctly()
    {
        $user = User::factory()->make();

        $this->assertIsString($user->password);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $user->email_verified_at);
    }
    /** @test */
    public function it_creates_unverified_users()
    {
        $user = User::factory()->unverified()->create();

        $this->assertNull($user->email_verified_at);
    }

    /** @test */
    public function it_creates_admin_users()
    {
        // Ensure the 'is_admin' attribute exists in the users table and User model

        $user = User::factory()->admin()->create();

        $this->assertTrue($user->is_admin);
    }
}
