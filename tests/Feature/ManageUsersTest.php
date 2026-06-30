<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManageUsersTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(array $attributes = []): User
    {
        return User::create(array_merge([
            'name' => 'Test User '.uniqid(),
            'email' => uniqid('user', true).'@example.com',
            'password' => 'password',
            'role' => 'user',
        ], $attributes));
    }

    public function test_superadmin_can_access_manage_users_page(): void
    {
        $superAdmin = $this->createUser(['role' => 'superadmin']);

        $response = $this->actingAs($superAdmin)->get('/admin/manage-users');

        $response->assertOk();
    }

    public function test_chat_dashboard_orders_users_by_role_priority(): void
    {
        $currentUser = $this->createUser(['name' => 'Current User']);
        $superAdminAlpha = $this->createUser(['name' => 'Amy Super', 'email' => 'amy.super@example.com', 'role' => 'superadmin']);
        $superAdminBeta = $this->createUser(['name' => 'Zoe Super', 'email' => 'zoe.super@example.com', 'role' => 'superadmin']);
        $adminAlpha = $this->createUser(['name' => 'Adam Admin', 'email' => 'adam.admin@example.com', 'role' => 'admin']);
        $adminBeta = $this->createUser(['name' => 'Zed Admin', 'email' => 'zed.admin@example.com', 'role' => 'admin']);
        $userAlpha = $this->createUser(['name' => 'Bella User']);
        $userBeta = $this->createUser(['name' => 'Mike User']);

        $response = $this->actingAs($currentUser)->get(route('chat.index'));

        $response->assertOk();
        $response->assertSeeInOrder([
            $superAdminAlpha->name,
            $superAdminBeta->name,
            $adminAlpha->name,
            $adminBeta->name,
            $userAlpha->name,
            $userBeta->name,
        ]);
    }

    public function test_admin_cannot_access_manage_users_page(): void
    {
        $admin = $this->createUser(['role' => 'admin']);

        $response = $this->actingAs($admin)->get('/admin/manage-users');

        $response->assertForbidden();
    }

    public function test_manage_users_page_orders_users_by_role_priority(): void
    {
        $superAdmin = $this->createUser(['role' => 'superadmin', 'name' => 'Super Admin']);
        $superAdminAlpha = $this->createUser(['name' => 'Amy Super', 'email' => 'amy.super@example.com', 'role' => 'superadmin']);
        $adminAlpha = $this->createUser(['name' => 'Adam Admin', 'email' => 'adam.admin@example.com', 'role' => 'admin']);
        $adminBeta = $this->createUser(['name' => 'Zed Admin', 'email' => 'zed.admin@example.com', 'role' => 'admin']);
        $userAlpha = $this->createUser(['name' => 'Bella User']);
        $userBeta = $this->createUser(['name' => 'Mike User']);

        $response = $this->actingAs($superAdmin)->get(route('admin.users.index'));

        $response->assertOk();
        $response->assertSeeInOrder([
            $superAdminAlpha->name,
            $adminAlpha->name,
            $adminBeta->name,
            $userAlpha->name,
            $userBeta->name,
        ]);
    }

    public function test_restricted_user_can_only_open_chat_with_superadmin(): void
    {
        $restrictedUser = $this->createUser(['is_chat_denied' => true]);
        $superAdmin = $this->createUser(['role' => 'superadmin']);
        $regularUser = $this->createUser();

        $this->actingAs($restrictedUser)
            ->get(route('chat.show', $superAdmin))
            ->assertOk();

        $this->actingAs($restrictedUser)
            ->get(route('chat.show', $regularUser))
            ->assertRedirect(route('chat.index'));
    }

    public function test_restricted_user_can_open_chat_with_legacy_superadmin_account(): void
    {
        $restrictedUser = $this->createUser(['is_chat_denied' => true]);
        $legacySuperAdmin = $this->createUser([
            'name' => 'Super Admin',
            'email' => 'superadmin@bsu.com',
            'role' => 'admin',
        ]);

        $this->actingAs($restrictedUser)
            ->get(route('chat.show', $legacySuperAdmin))
            ->assertOk();
    }
}