@extends('layouts.app')

@section('title', 'Tambah Pelanggan')

@section('content')
<h1 class="page-title mb-3">Tambah Pelanggan Baru</h1>

<div class="panel-abu" style="max-width: 600px">
    <form method="POST" action="{{ route('customer.store') }}">
        @csrf
        @include('customer.form')

        <div class="d-flex gap-2">
            <a href="{{ route('customer.index') }}" class="btn-abu">Batal</a>
            <button type="submit" class="btn-abu">Simpan</button>
        </div>
    </form>
</div>
@endsection