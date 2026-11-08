@extends('layout')
@section('content')
<main class="portal-content">
    <div class="page-heading">
        <div><span class="page-eyebrow">Akademik</span><h1>Materi Pembelajaran</h1></div>
        @if($user['role'] === 'guru')
            <a class="btn btn-teal" href="{{ route('materials.create') }}">+ Tambah Materi</a>
        @endif
    </div>
    <section class="content-section">
        <h2>Daftar Materi ({{ $materials->count() }})</h2>
        <div class="material-list">
            @forelse($materials as $item)
                <article class="material-card">
                    <div class="material-type">{{ strtoupper($item->file_type ?: 'FILE') }}</div>
                    <div class="material-info">
                        <span>{{ $item->subject }}</span>
                        <h3>{{ $item->title }}</h3>
                        <p>{{ $item->description }}</p>
                        <small>File: {{ $item->original_name }} · Oleh {{ $item->teacher_name }}</small>
                    </div>
                    <div class="card-actions">
                        <a class="btn btn-outline btn-small" href="{{ route('materials.show', $item->id) }}">Detail</a>
                        <a class="btn btn-outline btn-small" href="{{ route('materials.download', $item->id) }}">Unduh</a>
                        @if($user['role'] === 'guru' && $item->teacher_id == $user['id'])
                            <a class="btn btn-teal btn-small" href="{{ route('materials.edit', $item->id) }}">Edit</a>
                            <form method="POST" action="{{ route('materials.destroy', $item->id) }}" onsubmit="return confirm('Yakin ingin menghapus materi beserta file?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-danger btn-small" type="submit">Hapus</button>
                            </form>
                        @endif
                    </div>
                </article>
            @empty
                <p>Belum ada materi yang diterbitkan.</p>
            @endforelse
        </div>
    </section>
</main>
@endsection
