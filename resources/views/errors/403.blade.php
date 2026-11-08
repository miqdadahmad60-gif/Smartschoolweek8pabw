<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>403 - Akses Ditolak</title>
    <link rel="stylesheet" href="{{ asset('assets/css/smartschool.css') }}">
</head>
<body style="display:grid;place-items:center;min-height:100vh;background:#f3f8fa;font-family:system-ui,sans-serif">
    <div style="text-align:center;max-width:460px;padding:32px">
        <div style="font-size:84px;font-weight:800;color:#e85d3a;line-height:1">403</div>
        <h1 style="margin:10px 0">Akses Ditolak</h1>
        <p style="color:#5a6b70">{{ $exception->getMessage() ?: 'Anda tidak memiliki hak akses ke halaman ini.' }}</p>
        <p><a class="btn btn-teal" href="{{ auth()->check() ? '/dashboard' : '/login' }}">Kembali</a></p>
    </div>
</body>
</html>
