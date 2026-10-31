<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TeacherAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_register_and_password_is_hashed(): void
    {
        $this->post(route('register'), [
            'name' => 'Profesora',
            'email' => 'profe@example.test',
            'password' => 'clave-segura-123',
            'password_confirmation' => 'clave-segura-123',
        ])->assertRedirect(route('teacher.sessions.index'));

        $teacher = User::sole();
        $this->assertAuthenticatedAs($teacher);
        $this->assertTrue(Hash::check('clave-segura-123', $teacher->password));
    }

    public function test_wrong_credentials_do_not_authenticate(): void
    {
        User::factory()->create(['email' => 'profe@example.test']);

        $this->post(route('login'), ['email' => 'profe@example.test', 'password' => 'incorrecta'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_teacher_can_login_and_logout(): void
    {
        $teacher = User::factory()->create(['password' => 'clave-segura-123']);

        $this->post(route('login'), ['email' => $teacher->email, 'password' => 'clave-segura-123'])
            ->assertRedirect(route('teacher.sessions.index'));
        $this->assertAuthenticatedAs($teacher);

        $this->post(route('logout'))->assertRedirect(route('home'));
        $this->assertGuest();
    }
}
