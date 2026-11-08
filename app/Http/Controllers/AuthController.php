<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Authentication manual (tanpa Laravel Breeze) memakai guard `web` + Eloquent User.
 * Fitur: register, login, logout (+ endpoint JSON /api/login dan /api/me).
 */
class AuthController extends Controller
{
    private const MAX_ATTEMPTS = 5;

    /* ---------------------------------------------------------------- Form */

    public function loginForm()
    {
        return view('auth', ['mode' => 'login']);
    }

    public function registerForm()
    {
        return view('auth', ['mode' => 'register']);
    }

    /* ------------------------------------------------------------ Register */

    public function register(Request $request)
    {
        $data = $request->validate([
            'role'       => ['required', 'in:siswa,guru,ortu'],
            'name'       => ['required', 'string', 'min:3', 'max:100'],
            'email'      => ['required', 'email:rfc', 'max:120', 'unique:users,email'],
            'password'   => ['required', 'string', 'min:8', 'confirmed'],
            'class_name' => ['nullable', 'required_if:role,siswa', 'string', 'max:50'],
            'subject'    => ['nullable', 'required_if:role,guru', 'string', 'max:100'],
            'child_code' => ['nullable', 'required_if:role,ortu', 'string', 'max:20'],
        ], $this->messages(), $this->attributes());

        $user = DB::transaction(function () use ($data) {
            $user = User::create([
                'name'      => $data['name'],
                'email'     => Str::lower($data['email']),
                'password'  => $data['password'],   // di-hash otomatis oleh cast 'hashed' pada model User
                'role'      => $data['role'],
                'is_active' => true,
            ]);

            match ($data['role']) {
                'siswa' => DB::table('students')->insert([
                    'user_id'      => $user->id,
                    'student_code' => 'SSK' . str_pad((string) (DB::table('students')->max('id') + 1), 3, '0', STR_PAD_LEFT),
                    'class_name'   => $data['class_name'],
                    'major'        => 'Teknik Komputer dan Jaringan',
                    'school_year'  => '2026/2027',
                ]),
                'guru' => DB::table('teachers')->insert([
                    'user_id'      => $user->id,
                    'teacher_code' => 'GRU' . str_pad((string) (DB::table('teachers')->max('id') + 1), 3, '0', STR_PAD_LEFT),
                    'main_subject' => $data['subject'],
                ]),
                'ortu' => $this->createParent($user, $data['child_code']),
            };

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('success', 'Akun berhasil dibuat. Selamat datang, ' . $user->name . '!');
    }

    private function createParent(User $user, string $childCode): void
    {
        $student = DB::table('students')->where('student_code', Str::upper($childCode))->first();
        if (! $student) {
            // melempar error validasi -> transaksi di-rollback, user tidak jadi dibuat
            throw ValidationException::withMessages(['child_code' => 'Kode siswa "' . Str::upper($childCode) . '" tidak ditemukan. Periksa kembali kode anak Anda.']);
        }
        $parentId = DB::table('parents')->insertGetId([
            'user_id'      => $user->id,
            'parent_code'  => 'ORT' . str_pad((string) (DB::table('parents')->max('id') + 1), 3, '0', STR_PAD_LEFT),
            'relationship' => 'wali',
        ]);
        DB::table('parent_students')->insert(['parent_id' => $parentId, 'student_id' => $student->id]);
    }

    /* --------------------------------------------------------------- Login */

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ], $this->messages(), $this->attributes());

        $this->attempt($request, $credentials, $request->boolean('remember'));

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'))
            ->with('success', 'Selamat datang kembali, ' . Auth::user()->name . '!');
    }

    /** Dipakai oleh form web dan API; melempar ValidationException jika gagal. */
    private function attempt(Request $request, array $credentials, bool $remember = false): void
    {
        $key = Str::lower($credentials['email']) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($key);
            throw ValidationException::withMessages([
                'email' => "Terlalu banyak percobaan login. Coba lagi dalam {$seconds} detik.",
            ]);
        }

        $ok = Auth::attempt([
            'email'     => Str::lower($credentials['email']),
            'password'  => $credentials['password'],
            'is_active' => 1,           // akun nonaktif tidak boleh login
        ], $remember);

        if (! $ok) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages([
                'email' => 'Email atau password yang Anda masukkan salah.',
            ]);
        }

        RateLimiter::clear($key);
    }

    /* -------------------------------------------------------------- Logout */

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda telah keluar dari portal.');
    }

    /* ----------------------------------------------------------------- API */

    public function apiLogin(Request $request)
    {
        if ($request->isMethod('OPTIONS')) {
            return response()->json(['ok' => true]);
        }

        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ], $this->messages(), $this->attributes());

        try {
            $this->attempt($request, $credentials);
        } catch (ValidationException $e) {
            return response()->json(['ok' => false, 'message' => $e->validator->errors()->first()], 401);
        }

        $request->session()->regenerate();

        return response()->json([
            'ok'       => true,
            'message'  => 'Login berhasil.',
            'redirect' => url('/dashboard'),
            'user'     => Auth::user()->profile(),
        ]);
    }

    public function apiMe()
    {
        return Auth::check()
            ? response()->json(['ok' => true, 'user' => Auth::user()->profile()])
            : response()->json(['ok' => false, 'message' => 'Belum login.'], 401);
    }

    /* ------------------------------------------------------ Pesan validasi */

    private function messages(): array
    {
        return [
            'required'            => ':attribute wajib diisi.',
            'required_if'         => ':attribute wajib diisi untuk peran yang dipilih.',
            'email'               => 'Format :attribute tidak valid (contoh: nama@email.com).',
            'string'              => ':attribute harus berupa teks.',
            'in'                  => ':attribute yang dipilih tidak valid.',
            'min.string'          => ':attribute minimal :min karakter.',
            'max.string'          => ':attribute maksimal :max karakter.',
            'confirmed'           => 'Konfirmasi password tidak sama dengan password.',
            'email.unique'        => 'Email ini sudah terdaftar. Gunakan email lain atau login.',
        ];
    }

    private function attributes(): array
    {
        return [
            'role'       => 'Peran',
            'name'       => 'Nama lengkap',
            'email'      => 'Email',
            'password'   => 'Password',
            'class_name' => 'Kelas',
            'subject'    => 'Mata pelajaran',
            'child_code' => 'Kode siswa anak',
        ];
    }
}
