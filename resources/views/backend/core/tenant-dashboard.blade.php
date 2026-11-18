@extends('backend.index')

@section('title', $tenant->name . ' - Dashboard')

@section('breadcrumb')
    <li class="breadcrumb-item active">Dashboard</li>
@endsection

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{ $tenant->name }}</h3>
        </div>
        <div class="card-body">
            <p class="mb-0">Chào mừng đến với không gian làm việc của <strong>{{ $tenant->name }}</strong>. Vui lòng chọn chức năng ở thanh điều hướng bên trái.</p>
        </div>
    </div>
</div>
@endsection
