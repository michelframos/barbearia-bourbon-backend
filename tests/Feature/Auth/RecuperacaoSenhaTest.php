<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Modules\Auth\Mail\RecuperacaoSenhaMail;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class RecuperacaoSenhaTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->user = User::query()->where('email', 'admin@email.com')->firstOrFail();
    }

    public function test_forgot_password_envia_email_quando_usuario_existe(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => 'admin@email.com',
        ]);

        $response->assertOk()
            ->assertJsonPath(
                'message',
                'Se o e-mail existir em nossa base, enviaremos as instruções de recuperação.'
            );

        Mail::assertSent(RecuperacaoSenhaMail::class, function (RecuperacaoSenhaMail $mail) {
            return $mail->hasTo('admin@email.com')
                && $mail->user->is($this->user)
                && str_contains($mail->resetUrl, '/redefinir-senha?')
                && str_contains($mail->resetUrl, 'email='.urlencode('admin@email.com'));
        });
    }

    public function test_forgot_password_nao_revela_email_inexistente(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => 'naoexiste@email.com',
        ]);

        $response->assertOk();
        Mail::assertNothingSent();
    }

    public function test_reset_password_com_token_valido(): void
    {
        $token = Password::broker()->createToken($this->user);

        $response = $this->postJson('/api/auth/reset-password', [
            'email' => 'admin@email.com',
            'token' => $token,
            'password' => 'NovaSenha@123',
            'password_confirmation' => 'NovaSenha@123',
        ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Senha redefinida com sucesso. Você já pode entrar com a nova senha.');

        $this->user->refresh();
        $this->assertTrue(Hash::check('NovaSenha@123', $this->user->password));
        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => 'admin@email.com',
        ]);
    }

    public function test_reset_password_rejeita_token_invalido(): void
    {
        $response = $this->postJson('/api/auth/reset-password', [
            'email' => 'admin@email.com',
            'token' => 'token-invalido',
            'password' => 'NovaSenha@123',
            'password_confirmation' => 'NovaSenha@123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['token']);
    }

    public function test_reset_password_token_de_uso_unico(): void
    {
        $token = Password::broker()->createToken($this->user);

        $this->postJson('/api/auth/reset-password', [
            'email' => 'admin@email.com',
            'token' => $token,
            'password' => 'NovaSenha@123',
            'password_confirmation' => 'NovaSenha@123',
        ])->assertOk();

        $response = $this->postJson('/api/auth/reset-password', [
            'email' => 'admin@email.com',
            'token' => $token,
            'password' => 'OutraSenha@123',
            'password_confirmation' => 'OutraSenha@123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['token']);
    }

    public function test_reset_password_rejeita_token_expirado(): void
    {
        $token = Password::broker()->createToken($this->user);

        DB::table('password_reset_tokens')
            ->where('email', 'admin@email.com')
            ->update(['created_at' => now()->subHours(5)]);

        $response = $this->postJson('/api/auth/reset-password', [
            'email' => 'admin@email.com',
            'token' => $token,
            'password' => 'NovaSenha@123',
            'password_confirmation' => 'NovaSenha@123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['token']);
    }
}
