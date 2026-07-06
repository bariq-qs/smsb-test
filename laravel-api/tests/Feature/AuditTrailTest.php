<?php

namespace Tests\Feature;

use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuditTrailTest extends TestCase
{
    use RefreshDatabase;

    public function test_updating_a_supplier_creates_an_audit_record(): void
    {
        // The `artisan test` runner itself executes in console context, and the
        // auditing package disables auditing for console requests by default.
        config(['audit.console' => true]);

        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('Administrator');
        Sanctum::actingAs($user);

        $supplier = Supplier::create(['name' => 'Original Name', 'is_active' => true]);

        $this->putJson("/api/suppliers/{$supplier->id}", [
            'name' => 'Updated Name',
            'is_active' => true,
        ])->assertOk();

        $supplier->refresh();

        // One "created" audit from Supplier::create(), one "updated" from the PUT request.
        $this->assertCount(2, $supplier->audits);

        $audit = $supplier->audits()->where('event', 'updated')->latest()->first();
        $this->assertNotNull($audit);
        $this->assertSame($user->id, $audit->user_id);
        $this->assertSame('Original Name', $audit->old_values['name']);
        $this->assertSame('Updated Name', $audit->new_values['name']);

        $response = $this->getJson("/api/suppliers/{$supplier->id}/audits");
        $response->assertOk();
        $response->assertJsonCount(2);
    }
}
