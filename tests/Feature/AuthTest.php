<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_guest_is_redirected_from_protected_pages(): void
    {
        foreach (['/dashboard', '/academic', '/quizzes', '/grades', '/facilities'] as $url) {
            $this->get($url)->assertRedirect('/login');
        }
        $this->post('/logout')->assertRedirect('/login');
    }

    public function test_seeder_creates_at_least_5_dummy_accounts_with_hashed_passwords(): void
    {
        $dummy = User::where('email', 'like', '%@smartschool.test')->whereIn('email', [
            'siswa1@smartschool.test', 'siswa2@smartschool.test', 'siswa3@smartschool.test',
            'guru1@smartschool.test', 'guru2@smartschool.test', 'ortu1@smartschool.test',
        ])->get();

        $this->assertGreaterThanOrEqual(5, $dummy->count());
        foreach (User::all() as $u) {
            $this->assertStringStartsWith('$2y$', $u->password);          // bcrypt
            $this->assertNotEquals('password123', $u->password);
        }
    }

    public function test_login_success_for_each_role_then_logout(): void
    {
        foreach (['siswa1', 'guru1', 'ortu1'] as $name) {
            $this->post('/login', ['email' => "$name@smartschool.test", 'password' => 'password123'])
                ->assertRedirect('/dashboard');
            $this->get('/dashboard')->assertOk();
            $this->post('/logout')->assertRedirect('/login');
            $this->assertGuest();
            $this->get('/dashboard')->assertRedirect('/login');
        }
    }

    public function test_login_validation_and_wrong_credentials(): void
    {
        $this->from('/login')->post('/login', ['email' => '', 'password' => ''])
            ->assertSessionHasErrors(['email' => 'Email wajib diisi.', 'password' => 'Password wajib diisi.']);

        $this->from('/login')->post('/login', ['email' => 'bukan-email', 'password' => 'x'])
            ->assertSessionHasErrors(['email' => 'Format Email tidak valid (contoh: nama@email.com).']);

        $this->from('/login')->post('/login', ['email' => 'siswa1@smartschool.test', 'password' => 'salah-banget'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['email' => 'Email atau password yang Anda masukkan salah.']);
        $this->assertGuest();
    }

    public function test_inactive_account_cannot_login(): void
    {
        User::where('email', 'siswa1@smartschool.test')->update(['is_active' => 0]);
        $this->post('/login', ['email' => 'siswa1@smartschool.test', 'password' => 'password123'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_is_throttled_after_5_failures(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => 'guru1@smartschool.test', 'password' => 'salah']);
        }
        $this->post('/login', ['email' => 'guru1@smartschool.test', 'password' => 'password123'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_register_siswa_success_hashes_password_and_logs_in(): void
    {
        $this->post('/register', [
            'role' => 'siswa', 'name' => 'Rina Pello', 'email' => 'Rina@Example.com',
            'password' => 'rahasia123', 'password_confirmation' => 'rahasia123', 'class_name' => 'X TKJ 2',
        ])->assertRedirect('/dashboard');

        $user = User::where('email', 'rina@example.com')->firstOrFail();
        $this->assertTrue(password_verify('rahasia123', $user->password));
        $this->assertNotEquals('rahasia123', $user->password);
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('students', ['user_id' => $user->id, 'class_name' => 'X TKJ 2']);
        $this->get('/dashboard')->assertOk();
    }

    public function test_register_validation_errors(): void
    {
        $this->from('/register')->post('/register', [
            'role' => 'siswa', 'name' => '', 'email' => 'siswa1@smartschool.test',
            'password' => 'pendek', 'password_confirmation' => 'beda',
        ])->assertRedirect('/register')->assertSessionHasErrors([
            'name' => 'Nama lengkap wajib diisi.',
            'email' => 'Email ini sudah terdaftar. Gunakan email lain atau login.',
            'password' => 'Password minimal 8 karakter.',
            'class_name' => 'Kelas wajib diisi untuk peran yang dipilih.',
        ]);

        $this->from('/register')->post('/register', [
            'role' => 'siswa', 'name' => 'Budi', 'email' => 'baru@example.com',
            'password' => 'password123', 'password_confirmation' => 'password124', 'class_name' => 'X',
        ])->assertSessionHasErrors(['password' => 'Konfirmasi password tidak sama dengan password.']);

        $this->from('/register')->post('/register', [
            'role' => 'ortu', 'name' => 'Pak Ortu', 'email' => 'ortu-x@example.com',
            'password' => 'password123', 'password_confirmation' => 'password123', 'child_code' => 'ZZZ999',
        ])->assertSessionHasErrors('child_code');
        $this->assertDatabaseMissing('users', ['email' => 'ortu-x@example.com']);   // rollback
    }

    public function test_logged_in_user_cannot_open_login_or_register(): void
    {
        $this->post('/login', ['email' => 'guru1@smartschool.test', 'password' => 'password123']);
        $this->get('/login')->assertRedirect('/dashboard');
        $this->get('/register')->assertRedirect('/dashboard');
    }

    public function test_api_me_requires_login(): void
    {
        $this->getJson('/api/me')->assertStatus(401);
        $this->postJson('/api/login', ['email' => 'siswa1@smartschool.test', 'password' => 'password123'])->assertOk()->assertJson(['ok' => true]);
        $this->getJson('/api/me')->assertOk()->assertJsonPath('user.role', 'siswa');
    }
}
