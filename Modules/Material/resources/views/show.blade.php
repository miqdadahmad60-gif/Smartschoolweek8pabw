@extends('layout')
@section('content')
<main class="portal-content">
    <div class="page-heading"><div><span class="page-eyebrow">Akademik</span><h1>Detail Materi</h1></div></div>
    <section class="management-form">
        <h2>{{ $material->title }}</h2>
        <p><strong>Mata Pelajaran:</strong> {{ $material->subject }}</p>
        <p><strong>Pengajar:</strong> {{ $material->teacher_name }}</p>
        <p><strong>Deskripsi:</strong> {{ $material->description ?: '-' }}</p>
        <p><strong>Nama File:</strong> {{ $material->original_name ?: '-' }}</p>
        <p><strong>Jenis File:</strong> {{ strtoupper($material->file_type ?: '-') }}</p>
        <p><strong>Ukuran:</strong> {{ $material->file_size ? number_format($material->file_size / 1024, 1).' KB' : '-' }}</p>
        @if($material->file_name && in_array(strtolower($material->file_type), ['jpg','jpeg','png']))
            <p><strong>Pratinjau:</strong></p>
            <img src="{{ asset('storage/'.$material->file_name) }}" alt="Pratinjau file materi" style="max-width:100%;max-height:420px;border-radius:12px;">
        @elseif($material->file_name && strtolower($material->file_type) === 'pdf')
            <iframe src="{{ asset('storage/'.$material->file_name) }}" title="Pratinjau PDF" style="width:100%;height:500px;border:1px solid #ddd;border-radius:8px;"></iframe>
        @endif
        <p><a class="btn btn-teal" href="{{ route('materials.download', $material->id) }}">Unduh File</a></p>
        @if($user['role'] === 'guru' && $material->teacher_id == $user['id'])
            <a class="btn btn-outline" href="{{ route('materials.edit', $material->id) }}">Edit Materi</a>
        @endif
        <a class="btn btn-outline" href="{{ route('materials.index') }}">Kembali</a>
    </section>
</main>
@endsection
