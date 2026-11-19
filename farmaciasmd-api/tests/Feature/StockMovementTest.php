<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\BranchProductStock;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockMovementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_registra_una_entrada_de_stock_y_el_stock_aumenta(): void
    {
        $admin = User::create([
            'name' => 'Admin', 'email' => 'admin.mov1@farmaciasmd.test',
            'password' => bcrypt('password'), 'role' => 'admin',
        ]);
        $branch = Branch::create(['name' => 'Sucursal Mov', 'code' => 'MOV-01']);
        $product = Product::create([
            'sku' => 'MOV-001', 'name' => 'Ibuprofeno 400mg',
            'price' => 40, 'min_stock' => 5, 'active' => true,
        ]);

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/stock-movements', [
            'branch_id' => $branch->id,
            'product_id' => $product->id,
            'type' => 'entry',
            'quantity' => 30,
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.current_stock', 30);
        $this->assertDatabaseHas('branch_product_stocks', [
            'branch_id' => $branch->id, 'product_id' => $product->id, 'stock' => 30,
        ]);
    }

    public function test_una_salida_que_excede_el_stock_disponible_es_rechazada(): void
    {
        $admin = User::create([
            'name' => 'Admin', 'email' => 'admin.mov2@farmaciasmd.test',
            'password' => bcrypt('password'), 'role' => 'admin',
        ]);
        $branch = Branch::create(['name' => 'Sucursal Mov 2', 'code' => 'MOV-02']);
        $product = Product::create([
            'sku' => 'MOV-002', 'name' => 'Loratadina 10mg',
            'price' => 35, 'min_stock' => 5, 'active' => true,
        ]);
        BranchProductStock::create(['branch_id' => $branch->id, 'product_id' => $product->id, 'stock' => 4]);

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/stock-movements', [
            'branch_id' => $branch->id,
            'product_id' => $product->id,
            'type' => 'exit',
            'quantity' => 10,
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseHas('branch_product_stocks', [
            'branch_id' => $branch->id, 'product_id' => $product->id, 'stock' => 4,
        ]);
    }

    public function test_empleado_no_puede_registrar_movimientos_de_stock(): void
    {
        $employee = User::create([
            'name' => 'Empleado', 'email' => 'empleado.mov@farmaciasmd.test',
            'password' => bcrypt('password'), 'role' => 'employee',
        ]);
        $branch = Branch::create(['name' => 'Sucursal Mov 3', 'code' => 'MOV-03']);
        $product = Product::create([
            'sku' => 'MOV-003', 'name' => 'Vitamina C',
            'price' => 20, 'min_stock' => 5, 'active' => true,
        ]);

        $response = $this->actingAs($employee, 'sanctum')->postJson('/api/stock-movements', [
            'branch_id' => $branch->id,
            'product_id' => $product->id,
            'type' => 'entry',
            'quantity' => 10,
        ]);

        $response->assertStatus(403);
    }
}
