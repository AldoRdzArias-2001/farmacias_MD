<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\BranchProductStock;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_una_venta_valida_descuenta_el_stock_correctamente(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'venta.admin@farmaciasmd.test',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);
        $branch = Branch::create(['name' => 'Sucursal Centro', 'code' => 'CEN-01']);
        $product = Product::create([
            'sku' => 'VEN-001',
            'name' => 'Amoxicilina 500mg',
            'price' => 80,
            'min_stock' => 5,
            'active' => true,
        ]);
        BranchProductStock::create([
            'branch_id' => $branch->id,
            'product_id' => $product->id,
            'stock' => 20,
        ]);

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/sales', [
            'branch_id' => $branch->id,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 3],
            ],
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.total', '240.00');
        $this->assertDatabaseHas('branch_product_stocks', [
            'branch_id' => $branch->id,
            'product_id' => $product->id,
            'stock' => 17,
        ]);
    }

    public function test_una_venta_con_stock_insuficiente_es_rechazada(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'venta.admin2@farmaciasmd.test',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);
        $branch = Branch::create(['name' => 'Sucursal Norte', 'code' => 'NOR-01']);
        $product = Product::create([
            'sku' => 'VEN-002',
            'name' => 'Loratadina 10mg',
            'price' => 45,
            'min_stock' => 5,
            'active' => true,
        ]);
        BranchProductStock::create([
            'branch_id' => $branch->id,
            'product_id' => $product->id,
            'stock' => 2,
        ]);

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/sales', [
            'branch_id' => $branch->id,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 5],
            ],
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseHas('branch_product_stocks', [
            'branch_id' => $branch->id,
            'product_id' => $product->id,
            'stock' => 2,
        ]);
    }
}
