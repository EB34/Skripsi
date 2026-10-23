@extends('layouts.app')

@section('title', 'Tambah Barang')

@section('content')
<div class="card" style="max-width: 600px">
    <div class="card-body">
        <form method="POST" action="{{ route('barang.store') }}">
            @csrf
            @include('barang._form')

            <a href="{{ route('barang.index') }}" class="btn btn-secondary">Batal</a>
            <button type="submit" class="btn btn-primary">Simpan</button>
        </form>
    </div>
</div>
@endsection