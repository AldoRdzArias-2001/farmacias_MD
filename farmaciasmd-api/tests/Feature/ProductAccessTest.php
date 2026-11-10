<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductAccessTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        return User::create([
            'name' => 'Admin',
            'email' => 'admin.productos@farmaciasmd.test',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);
    }

    private function employeeUser(): User
    {
        return User::create([
            'name' => 'Empleado',
            'email' => 'empleado.productos@farmaciasmd.test',
            'password' => bcrypt('password'),
            'role' => 'employee',
        ]);
    }

    public function test_admin_puede_crear_un_producto(): void
    {
        $admin = $this->adminUser();

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/products', [
            'sku' => 'TST-001',
            'name' => 'Paracetamol 500mg',
            'brand' => 'Generico',
            'price' => 25.50,
            'min_stock' => 10,
            'active' => true,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('products', ['sku' => 'TST-001']);
    }

    public function test_empleado_no_puede_crear_un_producto(): void
    {
        $employee = $this->employeeUser();

        $response = $this->actingAs($employee, 'sanctum')->postJson('/api/products', [
            'sku' => 'TST-002',
            'name' => 'Ibuprofeno 400mg',
            'price' => 30,
            'min_stock' => 5,
            'active' => true,
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('products', ['sku' => 'TST-002']);
    }

    public function test_crear_producto_sin_sku_falla_validacion(): void
    {
        $admin = $this->adminUser();

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/products', [
            'name' => 'Producto sin sku',
            'price' => 10,
            'min_stock' => 1,
            'active' => true,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['sku']);
    }

    public function test_empleado_si_puede_listar_productos(): void
    {
        $employee = $this->employeeUser();
        Product::create([
            'sku' => 'LST-001',
            'name' => 'Vitamina C',
            'price' => 15,
            'min_stock' => 5,
            'active' => true,
        ]);

        $response = $this->actingAs($employee, 'sanctum')->getJson('/api/products');

        $response->assertStatus(200);
    }
}
