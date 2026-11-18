@extends('backend.index')

@section('title', 'Quản lý Bản đồ hệ thống cảng')

@section('breadcrumb')
    <li class="breadcrumb-item active">Quản lý bản đồ cảng</li>
@endsection

@section('content')
<div class="container-fluid">
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0 pl-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @php
        $activeTab = request('tab', 'ports');
        $regionLabels = [
            'bac' => 'Miền Bắc',
            'trung' => 'Miền Trung',
            'nam' => 'Miền Nam',
        ];
    @endphp

    <div class="card card-primary card-outline card-tabs">
        <div class="card-header p-0 pt-1 border-bottom-0 d-flex justify-content-between align-items-center pr-3">
            <ul class="nav nav-tabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link {{ $activeTab === 'ports' ? 'active' : '' }}" href="{{ route('tenant.tan_cang.port_map.index', ['tab' => 'ports']) }}">
                        <i class="fas fa-map-pin mr-1"></i> Popup &amp; Thông tin cảng
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $activeTab === 'layers' ? 'active' : '' }}" href="{{ route('tenant.tan_cang.port_map.index', ['tab' => 'layers']) }}">
                        <i class="fas fa-layer-group mr-1"></i> Các lớp bản đồ
                    </a>
                </li>
            </ul>
            <a href="{{ route('home') }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-primary my-1">
                <i class="fas fa-external-link-alt mr-1"></i> Xem Frontend
            </a>
        </div>

        <div class="card-body">
            @if ($activeTab === 'layers')
                <form method="POST" action="{{ route('tenant.tan_cang.port_map.layers.update') }}">
                    @csrf
                    @method('PUT')
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th style="width: 120px;">Mã lớp</th>
                                    <th>Tên hiển thị</th>
                                    <th style="width: 140px;" class="text-center">Bật mặc định</th>
                                    <th style="width: 140px;" class="text-center">Hiển thị trên FE</th>
                                    <th style="width: 120px;">Thứ tự</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($layers as $layer)
                                    <tr>
                                        <td><code>{{ $layer->layer_key }}</code></td>
                                        <td>
                                            <input
                                                type="text"
                                                name="layers[{{ $layer->id }}][name]"
                                                value="{{ old("layers.{$layer->id}.name", $layer->name) }}"
                                                class="form-control form-control-sm"
                                                maxlength="120"
                                                required
                                                {{ $canUpdate ? '' : 'disabled' }}
                                            >
                                        </td>
                                        <td class="text-center align-middle">
                                            <input
                                                type="checkbox"
                                                name="layers[{{ $layer->id }}][default_enabled]"
                                                value="1"
                                                {{ old("layers.{$layer->id}.default_enabled", $layer->default_enabled) ? 'checked' : '' }}
                                                {{ $canUpdate ? '' : 'disabled' }}
                                            >
                                        </td>
                                        <td class="text-center align-middle">
                                            <input
                                                type="checkbox"
                                                name="layers[{{ $layer->id }}][is_visible]"
                                                value="1"
                                                {{ old("layers.{$layer->id}.is_visible", $layer->is_visible) ? 'checked' : '' }}
                                                {{ $canUpdate ? '' : 'disabled' }}
                                            >
                                        </td>
                                        <td>
                                            <input
                                                type="number"
                                                name="layers[{{ $layer->id }}][sort_order]"
                                                value="{{ old("layers.{$layer->id}.sort_order", $layer->sort_order) }}"
                                                class="form-control form-control-sm"
                                                min="0"
                                                max="9999"
                                                required
                                                {{ $canUpdate ? '' : 'disabled' }}
                                            >
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if ($canUpdate)
                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save mr-1"></i> Lưu cấu hình lớp bản đồ
                            </button>
                        </div>
                    @endif
                </form>
            @else
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th style="width: 110px;">Mã</th>
                                <th>Tên cơ sở / Cảng</th>
                                <th style="width: 140px;">Tên ngắn</th>
                                <th style="width: 110px;">Khu vực</th>
                                <th style="width: 110px;" class="text-center">VR Tour</th>
                                <th style="width: 120px;" class="text-center">Số dòng popup</th>
                                <th style="width: 110px;" class="text-center">Trạng thái</th>
                                <th style="width: 160px;" class="text-right">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($ports as $port)
                                <tr>
                                    <td><code>{{ $port->code }}</code></td>
                                    <td>
                                        <strong>{{ $port->name }}</strong>
                                        @if ($port->is_hq)
                                            <span class="badge badge-warning ml-1">Trụ sở chính</span>
                                        @endif
                                    </td>
                                    <td>{{ $port->short_name }}</td>
                                    <td>{{ $regionLabels[$port->region] ?? $port->region }}</td>
                                    <td class="text-center">
                                        @if ($port->has_tour)
                                            <span class="badge badge-info">Có</span>
                                        @else
                                            <span class="badge badge-secondary">Không</span>
                                        @endif
                                    </td>
                                    <td class="text-center">{{ is_array($port->popup_rows) ? count($port->popup_rows) : 0 }}</td>
                                    <td class="text-center">
                                        @if ($port->is_active)
                                            <span class="badge badge-success">Đang bật</span>
                                        @else
                                            <span class="badge badge-danger">Đang ẩn</span>
                                        @endif
                                    </td>
                                    <td class="text-right">
                                        @if ($canUpdate)
                                            <a href="{{ route('tenant.tan_cang.port_map.ports.edit', $port) }}" class="btn btn-xs btn-primary">
                                                <i class="fas fa-edit"></i> Sửa popup
                                            </a>
                                            <form method="POST" action="{{ route('tenant.tan_cang.port_map.ports.toggle', $port) }}" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-xs btn-{{ $port->is_active ? 'warning' : 'success' }}">
                                                    {{ $port->is_active ? 'Ẩn' : 'Bật' }}
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
