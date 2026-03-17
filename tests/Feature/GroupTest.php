<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Group;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class GroupTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_user_can_manage_groups()
    {
        $user = User::factory()->create();

        // Test Store (Create)
        $response = $this->actingAs($user)->postJson('/api/groups', [
            'name' => 'Monthly Bills',
            'icon_key' => 'wallet'
        ]);
        $response->assertStatus(201);

        // Test Index (Read)
        $response = $this->actingAs($user)->getJson('/api/groups');
        $response->assertStatus(200)->assertJsonCount(2);

        // Test Update
        $group = Group::first();
        $this->actingAs($user)->patchJson("/api/groups/{$group->id}", [
            'name' => 'Updated Name'
        ])->assertStatus(200);

        // Test Destroy (Delete)
        $this->actingAs($user)->deleteJson("/api/groups/{$group->id}")
            ->assertStatus(200);
    }
}
