@extends('layouts.app')

@section('title', 'Ubah Barang')

@section('content')
<div class="card" style="max-width: 600px">
    <div class="card-body">
        <form method="POST" action="{{ route('barang.update', $barang) }}">
            @csrf
            @method('PUT')
            @include('barang._form', ['barang' => $barang])

            <a href="{{ route('barang.index') }}" class="btn btn-secondary">Batal</a>
            <button type="submit" class="btn btn-primary">Perbarui</button>
        </form>
    </div>
</div>
@endsection