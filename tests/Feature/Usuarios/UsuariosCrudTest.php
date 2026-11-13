<?php

namespace Tests\Feature\Usuarios;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UsuariosCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->user = User::query()->where('email', 'admin@email.com')->firstOrFail();
        Sanctum::actingAs($this->user);
    }

    public function test_lista_usuarios(): void
    {
        $response = $this->getJson('/api/usuarios');

        $response->assertOk()
            ->assertJsonPath('data.0.email', 'admin@email.com')
            ->assertJsonPath('data.0.name', 'Administrador');
    }

    public function test_cria_usuario(): void
    {
        $response = $this->postJson('/api/usuarios', [
            'name' => 'Operador',
            'email' => 'operador@email.com',
            'password' => 'Mudar@123',
            'password_confirmation' => 'Mudar@123',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Operador')
            ->assertJsonPath('data.email', 'operador@email.com')
            ->assertJsonPath('data.perfil', 'admin')
            ->assertJsonMissingPath('data.password');

        $this->assertDatabaseHas('users', [
            'email' => 'operador@email.com',
            'perfil' => 'admin',
        ]);

        $created = User::query()->where('email', 'operador@email.com')->firstOrFail();
        $this->assertTrue(Hash::check('Mudar@123', $created->password));
    }

    public function test_atualiza_usuario_sem_alterar_senha(): void
    {
        $outro = User::query()->create([
            'id_empresa' => $this->user->id_empresa,
            'name' => 'Auxiliar',
            'email' => 'auxiliar@email.com',
            'password' => 'Senha@123',
            'perfil' => 'admin',
        ]);

        $hashAntes = $outro->password;

        $response = $this->putJson("/api/usuarios/{$outro->id}", [
            'name' => 'Auxiliar Atualizado',
            'email' => 'auxiliar@email.com',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Auxiliar Atualizado');

        $outro->refresh();
        $this->assertSame($hashAntes, $outro->password);
    }

    public function test_atualiza_usuario_com_nova_senha(): void
    {
        $outro = User::query()->create([
            'id_empresa' => $this->user->id_empresa,
            'name' => 'Auxiliar',
            'email' => 'auxiliar2@email.com',
            'password' => 'Senha@123',
            'perfil' => 'admin',
        ]);

        $response = $this->putJson("/api/usuarios/{$outro->id}", [
            'name' => 'Auxiliar',
            'email' => 'auxiliar2@email.com',
            'password' => 'NovaSenha@123',
            'password_confirmation' => 'NovaSenha@123',
        ]);

        $response->assertOk();

        $outro->refresh();
        $this->assertTrue(Hash::check('NovaSenha@123', $outro->password));
    }

    public function test_exclui_usuario_com_soft_delete(): void
    {
        $outro = User::query()->create([
            'id_empresa' => $this->user->id_empresa,
            'name' => 'Temporario',
            'email' => 'temp@email.com',
            'password' => 'Mudar@123',
            'perfil' => 'admin',
        ]);

        $response = $this->deleteJson("/api/usuarios/{$outro->id}");

        $response->assertOk()
            ->assertJsonPath('message', 'Usuário removido com sucesso.');

        $this->assertSoftDeleted('users', ['id' => $outro->id]);
    }

    public function test_permite_recriar_email_apos_soft_delete(): void
    {
        $outro = User::query()->create([
            'id_empresa' => $this->user->id_empresa,
            'name' => 'Temporario',
            'email' => 'recriar@email.com',
            'password' => 'Mudar@123',
            'perfil' => 'admin',
        ]);

        $this->deleteJson("/api/usuarios/{$outro->id}")->assertOk();
        $this->assertSoftDeleted('users', ['id' => $outro->id]);

        $response = $this->postJson('/api/usuarios', [
            'name' => 'Temporario Novo',
            'email' => 'recriar@email.com',
            'password' => 'Mudar@123',
            'password_confirmation' => 'Mudar@123',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.email', 'recriar@email.com');

        $this->assertDatabaseHas('users', [
            'email' => 'recriar@email.com',
            'name' => 'Temporario Novo',
            'deleted_at' => null,
        ]);
    }

    public function test_restaura_usuario(): void
    {
        $outro = User::query()->create([
            'id_empresa' => $this->user->id_empresa,
            'name' => 'Restauravel',
            'email' => 'restaurar@email.com',
            'password' => 'Mudar@123',
            'perfil' => 'admin',
        ]);

        $this->deleteJson("/api/usuarios/{$outro->id}")->assertOk();

        $response = $this->postJson("/api/usuarios/{$outro->id}/restore");

        $response->assertOk()
            ->assertJsonPath('data.email', 'restaurar@email.com')
            ->assertJsonPath('message', 'Usuário restaurado com sucesso.');

        $this->assertDatabaseHas('users', [
            'id' => $outro->id,
            'deleted_at' => null,
        ]);
    }

    public function test_nao_restaura_se_email_ja_esta_em_uso(): void
    {
        $outro = User::query()->create([
            'id_empresa' => $this->user->id_empresa,
            'name' => 'Restauravel',
            'email' => 'conflito@email.com',
            'password' => 'Mudar@123',
            'perfil' => 'admin',
        ]);

        $this->deleteJson("/api/usuarios/{$outro->id}")->assertOk();

        $this->postJson('/api/usuarios', [
            'name' => 'Outro',
            'email' => 'conflito@email.com',
            'password' => 'Mudar@123',
            'password_confirmation' => 'Mudar@123',
        ])->assertCreated();

        $response = $this->postJson("/api/usuarios/{$outro->id}/restore");

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_nao_permite_excluir_proprio_usuario(): void
    {
        User::query()->create([
            'id_empresa' => $this->user->id_empresa,
            'name' => 'Outro',
            'email' => 'outro@email.com',
            'password' => 'Mudar@123',
            'perfil' => 'admin',
        ]);

        $response = $this->deleteJson("/api/usuarios/{$this->user->id}");

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['id']);
    }

    public function test_nao_permite_excluir_ultimo_usuario(): void
    {
        User::query()->where('id', '!=', $this->user->id)->delete();

        $response = $this->deleteJson("/api/usuarios/{$this->user->id}");

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['id']);
    }
}
