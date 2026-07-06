<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RoleGatingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_administrator_can_list_users(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrator');

        Sanctum::actingAs($admin);

        $this->getJson('/api/users')->assertOk();
    }

    public function test_staff_cannot_list_users(): void
    {
        $staff = User::factory()->create();
        $staff->assignRole('Staff');

        Sanctum::actingAs($staff);

        $this->getJson('/api/users')->assertForbidden();
    }

    public function test_staff_can_access_roles(): void
    {
        $staff = User::factory()->create();
        $staff->assignRole('Staff');

        Sanctum::actingAs($staff);

        $this->getJson('/api/roles')->assertOk();
    }

    public function test_guest_is_unauthenticated(): void
    {
        $this->getJson('/api/user')->assertUnauthorized();
    }
}
