<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchAccessTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        return User::create([
            'name' => 'Admin',
            'email' => 'admin.sucursales@farmaciasmd.test',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);
    }

    private function employeeUser(): User
    {
        return User::create([
            'name' => 'Empleado',
            'email' => 'empleado.sucursales@farmaciasmd.test',
            'password' => bcrypt('password'),
            'role' => 'employee',
        ]);
    }

    public function test_admin_puede_crear_una_sucursal(): void
    {
        $admin = $this->adminUser();

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/branches', [
            'name' => 'Sucursal Sur',
            'code' => 'SUR-01',
            'active' => true,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('branches', ['code' => 'SUR-01']);
    }

    public function test_empleado_no_puede_crear_una_sucursal(): void
    {
        $employee = $this->employeeUser();

        $response = $this->actingAs($employee, 'sanctum')->postJson('/api/branches', [
            'name' => 'Sucursal Poniente',
            'code' => 'PON-01',
            'active' => true,
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('branches', ['code' => 'PON-01']);
    }

    public function test_crear_sucursal_con_codigo_duplicado_falla_validacion(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin, 'sanctum')->postJson('/api/branches', [
            'name' => 'Sucursal Oriente',
            'code' => 'ORI-01',
            'active' => true,
        ])->assertStatus(201);

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/branches', [
            'name' => 'Sucursal Oriente 2',
            'code' => 'ORI-01',
            'active' => true,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['code']);
    }
}
