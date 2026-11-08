@php
    $isRegister = $mode === 'register';
    $err = fn (string $f) => $errors->first($f);
@endphp
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>{{ $isRegister ? 'Daftar Akun' : 'Login Portal' }} · Smart School</title>
<link rel="stylesheet" href="{{ asset('assets/css/smartschool.css') }}">
<style>
    .field-error{display:block;margin-top:5px;color:#c0262d;font-size:13px;font-weight:600}
    .auth-form input.is-invalid,.auth-form select.is-invalid{border-color:#c0262d;background:#fff6f6}
    .auth-remember{display:flex!important;align-items:center;gap:8px;flex-direction:row!important}
    .auth-remember input{width:auto}
    .role-field[hidden]{display:none}
</style>
</head>
<body class="auth-body">
<header class="public-header"><a href="/">Smart School Kupang</a></header>
<main class="auth-main"><section class="auth-card">
    <h1>{{ $isRegister ? 'Buat Akun Baru' : 'Selamat Datang' }}</h1>
    <p>{{ $isRegister ? 'Pilih peran dan lengkapi data Anda.' : 'Masuk ke portal akademik.' }}</p>

    @if(session('success'))<div class="inline-alert success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="inline-alert error">{{ session('error') }}</div>@endif
    @if($errors->any())
        <div class="inline-alert error" role="alert">
            <strong>Periksa kembali isian Anda:</strong>
            <ul style="margin:6px 0 0 18px;padding:0">@foreach($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul>
        </div>
    @endif

    <form class="auth-form" method="post" action="{{ $isRegister ? route('register') : route('login') }}" novalidate>
        @csrf
        @if($isRegister)
            <label>Daftar sebagai
                <select name="role" id="role" class="{{ $err('role') ? 'is-invalid' : '' }}">
                    @foreach(['siswa'=>'Siswa','guru'=>'Guru','ortu'=>'Orang Tua'] as $v=>$l)
                        <option value="{{ $v }}" @selected(old('role','siswa')===$v)>{{ $l }}</option>
                    @endforeach
                </select>
                @if($err('role'))<span class="field-error">{{ $err('role') }}</span>@endif
            </label>
            <label>Nama lengkap
                <input name="name" value="{{ old('name') }}" class="{{ $err('name') ? 'is-invalid' : '' }}" autocomplete="name">
                @if($err('name'))<span class="field-error">{{ $err('name') }}</span>@endif
            </label>
            <label class="role-field" data-role="siswa">Kelas
                <input name="class_name" value="{{ old('class_name') }}" placeholder="X TKJ 1" class="{{ $err('class_name') ? 'is-invalid' : '' }}">
                @if($err('class_name'))<span class="field-error">{{ $err('class_name') }}</span>@endif
            </label>
            <label class="role-field" data-role="guru">Mata pelajaran
                <input name="subject" value="{{ old('subject') }}" placeholder="Matematika Dasar" class="{{ $err('subject') ? 'is-invalid' : '' }}">
                @if($err('subject'))<span class="field-error">{{ $err('subject') }}</span>@endif
            </label>
            <label class="role-field" data-role="ortu">Kode siswa anak
                <input name="child_code" value="{{ old('child_code') }}" placeholder="SSK001" class="{{ $err('child_code') ? 'is-invalid' : '' }}">
                @if($err('child_code'))<span class="field-error">{{ $err('child_code') }}</span>@endif
            </label>
        @endif

        <label>Email
            <input type="email" name="email" value="{{ old('email') }}" class="{{ $err('email') ? 'is-invalid' : '' }}" autocomplete="email" @if(!$isRegister) autofocus @endif>
            @if($err('email'))<span class="field-error">{{ $err('email') }}</span>@endif
        </label>
        <label>Password
            <input type="password" name="password" class="{{ $err('password') ? 'is-invalid' : '' }}" autocomplete="{{ $isRegister ? 'new-password' : 'current-password' }}">
            @if($isRegister)<small>Minimal 8 karakter.</small>@endif
            @if($err('password'))<span class="field-error">{{ $err('password') }}</span>@endif
        </label>
        @if($isRegister)
            <label>Konfirmasi password
                <input type="password" name="password_confirmation" autocomplete="new-password">
            </label>
        @else
            <label class="auth-remember"><input type="checkbox" name="remember" value="1" @checked(old('remember'))> Ingat saya</label>
        @endif

        <button class="btn btn-orange auth-submit">{{ $isRegister ? 'Buat Akun' : 'Masuk ke Portal' }}</button>
    </form>

    @if(!$isRegister)
        <div class="demo-accounts"><strong>Akun demo</strong>
            <p>Siswa: siswa@smartschool.test / siswa123</p>
            <p>Guru: guru@smartschool.test / guru123</p>
            <p>Orang tua: ortu@smartschool.test / ortu123</p>
            <p>Akun seeder (Faker): siswa1…siswa3, guru1, guru2, ortu1 @smartschool.test / password123</p>
        </div>
    @endif
    <p class="auth-switch"><a href="{{ $isRegister ? route('login') : route('register') }}">{{ $isRegister ? 'Sudah punya akun? Login' : 'Belum punya akun? Daftar sekarang' }}</a></p>
</section></main>
@if($isRegister)
<script>
    // Tampilkan hanya kolom yang relevan dengan peran yang dipilih
    const role = document.getElementById('role');
    const sync = () => document.querySelectorAll('.role-field').forEach(el => el.hidden = el.dataset.role !== role.value);
    role.addEventListener('change', sync); sync();
</script>
@endif
</body>
</html>
