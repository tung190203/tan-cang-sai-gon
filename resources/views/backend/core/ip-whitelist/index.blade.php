@extends('backend.index')

@section('title', 'IP Whitelist')

@section('breadcrumb')
    <li class="breadcrumb-item active">IP Whitelist</li>
@endsection

@section('content')
<div class="container-fluid">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <div class="alert {{ $isEnabled ? 'alert-info' : 'alert-warning' }} py-2">
        <i class="fas fa-shield-alt mr-1"></i>
        Trạng thái IP Whitelist:
        <strong>{{ $isEnabled ? 'Đang bật (chỉ IP trong danh sách mới truy cập được site)' : 'Đang tắt (IP_WHITELIST_ENABLED=false)' }}</strong>.
        IP hiện tại của bạn: <code>{{ $currentIp }}</code>.
    </div>

    <div class="card mb-3">
        <div class="card-header">
            <h3 class="card-title">Thêm IP vào Whitelist</h3>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('backend_core_ip_whitelist_store') }}" class="row align-items-end">
                @csrf
                <div class="col-md-4 form-group mb-md-0">
                    <label for="ip_address">Địa chỉ IP / CIDR <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input
                            type="text"
                            id="ip_address"
                            name="ip_address"
                            class="form-control @error('ip_address') is-invalid @enderror"
                            value="{{ old('ip_address') }}"
                            placeholder="VD: 192.168.1.57 hoặc 10.0.0.0/24"
                            required
                        >
                        <div class="input-group-append">
                            <button
                                type="button"
                                class="btn btn-outline-secondary"
                                onclick="document.getElementById('ip_address').value='{{ $currentIp }}'"
                                title="Điền IP hiện tại"
                            >
                                IP hiện tại
                            </button>
                        </div>
                        @error('ip_address')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="col-md-5 form-group mb-md-0">
                    <label for="label">Ghi chú</label>
                    <input
                        type="text"
                        id="label"
                        name="label"
                        class="form-control @error('label') is-invalid @enderror"
                        value="{{ old('label') }}"
                        placeholder="VD: Máy văn phòng, VPN công ty..."
                    >
                    @error('label')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-plus mr-1"></i>Thêm IP
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Danh sách IP được phép ({{ $entries->count() }})</h3>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>IP / CIDR</th>
                        <th>Ghi chú</th>
                        <th>Trạng thái</th>
                        <th>Người tạo</th>
                        <th>Ngày tạo</th>
                        <th class="text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($entries as $entry)
                        <tr>
                            <td>
                                <code>{{ $entry->ip_address }}</code>
                                @if($entry->ip_address === $currentIp)
                                    <span class="badge badge-info ml-1">IP hiện tại</span>
                                @endif
                            </td>
                            <td>{{ $entry->label ?: '—' }}</td>
                            <td>
                                @if($entry->is_active)
                                    <span class="badge badge-success">Đang bật</span>
                                @else
                                    <span class="badge badge-secondary">Đang tắt</span>
                                @endif
                            </td>
                            <td>{{ $entry->creator?->name ?? 'System' }}</td>
                            <td>{{ $entry->created_at?->format('d/m/Y H:i') }}</td>
                            <td class="text-right text-nowrap">
                                <form method="POST" action="{{ route('backend_core_ip_whitelist_toggle', $entry) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-secondary btn-sm" title="{{ $entry->is_active ? 'Tắt' : 'Bật' }}">
                                        <i class="fas fa-power-off {{ $entry->is_active ? 'text-success' : 'text-danger' }}"></i>
                                    </button>
                                </form>

                                <form method="POST" action="{{ route('backend_core_ip_whitelist_destroy', $entry) }}" class="d-inline" onsubmit="return confirm('Xóa IP {{ $entry->ip_address }} khỏi whitelist?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm" title="Xóa">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Chưa có IP nào trong whitelist.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
