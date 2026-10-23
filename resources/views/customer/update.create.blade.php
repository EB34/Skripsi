@extends('layouts.app')
@section('title','Edit Customer')
@section('content')
<h1 class="page-title mb-3">Ubah Data Pelanggan</h1>

<div class="panel-abu" style="max-width: 600px">
    <form method="POST" action="{{ route('customer.update', $customer) }}">
        @csrf
        @method('PUT')
        @include('customer.form', ['customer' => $customer])

        <div class="d-flex gap-2">
            <a href="{{ route('customer.index') }}" class="btn-abu">Batal</a>
            <button type="submit" class="btn-abu">Perbarui</button>
        </div>
    </form>
</div>
@endsection