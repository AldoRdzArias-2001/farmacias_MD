<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_con_credenciales_validas_devuelve_token(): void
    {
        User::create([
            'name' => 'Admin de prueba',
            'email' => 'admin.test@farmaciasmd.test',
            'password' => bcrypt('password123'),
            'role' => 'admin',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'admin.test@farmaciasmd.test',
            'password' => 'password123',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonStructure([
            'data' => ['token', 'user' => ['id', 'name', 'email', 'role']],
        ]);
    }

    public function test_login_con_password_incorrecto_es_rechazado(): void
    {
        User::create([
            'name' => 'Admin de prueba',
            'email' => 'admin.test@farmaciasmd.test',
            'password' => bcrypt('password123'),
            'role' => 'admin',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'admin.test@farmaciasmd.test',
            'password' => 'password-incorrecta',
        ]);

        $response->assertStatus(422);
    }

    public function test_rutas_protegidas_rechazan_peticiones_sin_token(): void
    {
        $response = $this->getJson('/api/dashboard');

        $response->assertStatus(401);
    }
}
