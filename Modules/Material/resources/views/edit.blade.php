@extends('layout')
@section('content')
<main class="portal-content">
    <div class="page-heading"><h1>Edit Materi Pembelajaran</h1></div>
    <section class="management-form">
        <form class="styled-form" method="POST" action="{{ route('materials.update', $material->id) }}" enctype="multipart/form-data">
            @csrf @method('PUT')
            @include('material::_form')
            <button class="btn btn-teal" type="submit">Simpan Perubahan</button>
            <a class="btn btn-outline" href="{{ route('materials.show', $material->id) }}">Batal</a>
        </form>
    </section>
</main>
@endsection
