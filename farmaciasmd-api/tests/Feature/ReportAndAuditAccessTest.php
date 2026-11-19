<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\BranchProductStock;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportAndAuditAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_puede_consultar_el_reporte_de_ventas(): void
    {
        $admin = User::create([
            'name' => 'Admin', 'email' => 'admin.rep1@farmaciasmd.test',
            'password' => bcrypt('password'), 'role' => 'admin',
        ]);
        $branch = Branch::create(['name' => 'Sucursal Reporte', 'code' => 'REP-01']);
        Sale::create(['branch_id' => $branch->id, 'user_id' => $admin->id, 'total' => 100]);

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/reports/sales');

        $response->assertStatus(200);
        $response->assertJsonPath('data.cantidad', 1);
        $response->assertJsonPath('data.total_general', 100);
    }

    public function test_reporte_de_stock_bajo_detecta_productos_por_debajo_del_minimo(): void
    {
        $admin = User::create([
            'name' => 'Admin', 'email' => 'admin.rep2@farmaciasmd.test',
            'password' => bcrypt('password'), 'role' => 'admin',
        ]);
        $branch = Branch::create(['name' => 'Sucursal Reporte 2', 'code' => 'REP-02']);
        $product = Product::create([
            'sku' => 'REP-001', 'name' => 'Amoxicilina 500mg',
            'price' => 60, 'min_stock' => 10, 'active' => true,
        ]);
        BranchProductStock::create(['branch_id' => $branch->id, 'product_id' => $product->id, 'stock' => 3]);

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/reports/low-stock');

        $response->assertStatus(200);
        $response->assertJsonFragment(['sku' => 'REP-001', 'stock_actual' => 3, 'stock_minimo' => 10]);
    }

    public function test_empleado_no_puede_consultar_la_auditoria(): void
    {
        $employee = User::create([
            'name' => 'Empleado', 'email' => 'empleado.audit@farmaciasmd.test',
            'password' => bcrypt('password'), 'role' => 'employee',
        ]);

        $response = $this->actingAs($employee, 'sanctum')->getJson('/api/audit');

        $response->assertStatus(403);
    }
}
