@extends('backend.index')

@section('title', 'Tân Cảng Sài Gòn - Dashboard')

@section('breadcrumb')
    <li class="breadcrumb-item active">Dashboard</li>
@endsection

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Tổng quan hệ thống Tân Cảng Sài Gòn</h3>
        </div>
        <div class="card-body">
            <p class="mb-3">Hệ thống đã sẵn sàng trên nền tảng core HMG360 với cấu hình cô lập tenant.</p>
            @if (Route::has('home'))
                <a href="{{ route('home') }}" target="_blank" rel="noopener noreferrer" class="btn btn-primary">
                    <i class="fas fa-map-marked-alt mr-1"></i> Xem Bản đồ cảng (Frontend)
                </a>
            @endif
        </div>
    </div>
</div>
@endsection
