@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Selamat datang, {{ auth()->user()->nama }}</h5>
                    <p class="card-text">Role Anda: <strong>{{ ucfirst(auth()->user()->role) }}</strong></p>
                </div>
            </div>
        </div>
    </div>
@endsection