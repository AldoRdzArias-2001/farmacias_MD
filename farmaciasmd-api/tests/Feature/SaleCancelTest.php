<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\BranchProductStock;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaleCancelTest extends TestCase
{
    use RefreshDatabase;

    public function test_cancelar_una_venta_restaura_el_stock(): void
    {
        $admin = User::create([
            'name' => 'Admin', 'email' => 'admin.cancel1@farmaciasmd.test',
            'password' => bcrypt('password'), 'role' => 'admin',
        ]);
        $branch = Branch::create(['name' => 'Sucursal Cancel', 'code' => 'CAN-01']);
        $product = Product::create([
            'sku' => 'CAN-001', 'name' => 'Paracetamol 500mg',
            'price' => 25, 'min_stock' => 5, 'active' => true,
        ]);
        BranchProductStock::create(['branch_id' => $branch->id, 'product_id' => $product->id, 'stock' => 10]);

        $sale = Sale::create(['branch_id' => $branch->id, 'user_id' => $admin->id, 'total' => 75]);
        SaleItem::create([
            'sale_id' => $sale->id, 'product_id' => $product->id,
            'quantity' => 3, 'unit_price' => 25, 'subtotal' => 75,
        ]);
        BranchProductStock::where('branch_id', $branch->id)->where('product_id', $product->id)->decrement('stock', 3);

        $response = $this->actingAs($admin, 'sanctum')->postJson("/api/sales/{$sale->id}/cancel");

        $response->assertStatus(200);
        $this->assertDatabaseHas('sales', ['id' => $sale->id, 'status' => 'cancelled']);
        $this->assertDatabaseHas('branch_product_stocks', [
            'branch_id' => $branch->id, 'product_id' => $product->id, 'stock' => 10,
        ]);
    }

    public function test_cancelar_una_venta_ya_cancelada_es_rechazada(): void
    {
        $admin = User::create([
            'name' => 'Admin', 'email' => 'admin.cancel2@farmaciasmd.test',
            'password' => bcrypt('password'), 'role' => 'admin',
        ]);
        $branch = Branch::create(['name' => 'Sucursal Cancel 2', 'code' => 'CAN-02']);
        $sale = Sale::create([
            'branch_id' => $branch->id, 'user_id' => $admin->id,
            'total' => 50, 'status' => 'cancelled',
        ]);

        $response = $this->actingAs($admin, 'sanctum')->postJson("/api/sales/{$sale->id}/cancel");

        $response->assertStatus(422);
    }
}
