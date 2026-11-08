@extends('layout')
@section('content')
<main class="portal-content">
    <div class="page-heading"><h1>Tambah Materi Pembelajaran</h1></div>
    <section class="management-form">
        <form class="styled-form" method="POST" action="{{ route('materials.store') }}" enctype="multipart/form-data">
            @csrf
            @include('material::_form')
            <button class="btn btn-teal" type="submit">Simpan Materi</button>
            <a class="btn btn-outline" href="{{ route('materials.index') }}">Batal</a>
        </form>
    </section>
</main>
@endsection
