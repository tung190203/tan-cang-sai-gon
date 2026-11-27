@extends('backend.index')

@section('title', 'Chỉnh sửa Popup: ' . $port->short_name)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tenant.tan_cang.port_map.index') }}">Quản lý bản đồ cảng</a></li>
    <li class="breadcrumb-item active">{{ $port->short_name }}</li>
@endsection

@section('content')
<div class="container-fluid">
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0 pl-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('tenant.tan_cang.port_map.ports.update', $port) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="row">
            <div class="col-lg-6">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Thông tin cơ bản ({{ $port->code }})</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="name">Tên đầy đủ (Tiếng Việt) *</label>
                            <input type="text" id="name" name="name" class="form-control" value="{{ old('name', $port->name) }}" required maxlength="255">
                        </div>

                        <div class="form-group">
                            <label for="name_en">Tên đầy đủ (English - bỏ trống sẽ dùng Tiếng Việt)</label>
                            <input type="text" id="name_en" name="name_en" class="form-control" value="{{ old('name_en', $port->name_en) }}" maxlength="255" placeholder="VD: Tan Cang - Cat Lai Terminal">
                        </div>

                        <div class="form-group">
                            <label for="short_name">Tên ngắn trên bản đồ (Tiếng Việt) *</label>
                            <input type="text" id="short_name" name="short_name" class="form-control" value="{{ old('short_name', $port->short_name) }}" required maxlength="100">
                        </div>

                        <div class="form-group">
                            <label for="short_name_en">Tên ngắn trên bản đồ (English - bỏ trống sẽ dùng Tiếng Việt)</label>
                            <input type="text" id="short_name_en" name="short_name_en" class="form-control" value="{{ old('short_name_en', $port->short_name_en) }}" maxlength="100" placeholder="VD: Cat Lai">
                        </div>

                        <div class="form-group">
                            <label for="region">Khu vực *</label>
                            <select id="region" name="region" class="form-control" required>
                                @foreach ($regions as $key => $label)
                                    <option value="{{ $key }}" {{ old('region', $port->region) === $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1" {{ old('is_active', $port->is_active) ? 'checked' : '' }}>
                                <label class="custom-control-label" for="is_active">Hiển thị trên bản đồ</label>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="has_tour" name="has_tour" value="1" {{ old('has_tour', $port->has_tour) ? 'checked' : '' }}>
                                <label class="custom-control-label" for="has_tour">Có VR Tour 360°</label>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="tour_url">Đường dẫn VR Tour (tùy chọn)</label>
                            <input type="text" id="tour_url" name="tour_url" class="form-control" value="{{ old('tour_url', $port->tour_url) }}" placeholder="https://... hoặc /vrtour/...">
                        </div>

                        <div class="form-group">
                            <label for="image">Ảnh popup (PNG, JPG, WEBP, tối đa 2MB)</label>
                            @if ($port->image_path)
                                <div class="mb-2">
                                    <img src="{{ asset('storage/' . $port->image_path) }}" alt="{{ $port->short_name }}" class="img-thumbnail" style="max-height: 120px;">
                                    <div class="custom-control custom-checkbox mt-1">
                                        <input type="checkbox" class="custom-control-input" id="remove_image" name="remove_image" value="1">
                                        <label class="custom-control-label text-danger" for="remove_image">Xóa ảnh tùy chỉnh (dùng ảnh mặc định)</label>
                                    </div>
                                </div>
                            @endif
                            <input type="file" id="image" name="image" class="form-control-file" accept=".png,.jpg,.jpeg,.webp">
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card card-info">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title mb-0">Các dòng thông tin trong Popup (Tiếng Việt)</h3>
                        <button type="button" id="add-popup-row" class="btn btn-xs btn-light text-dark">
                            <i class="fas fa-plus"></i> Thêm dòng (VI)
                        </button>
                    </div>
                    <div class="card-body">
                        <div id="popup-rows-container">
                            @php
                                $rows = old('popup_keys')
                                    ? collect(old('popup_keys'))->map(fn ($k, $i) => ['k' => $k, 'v' => old('popup_values.' . $i, '')])->all()
                                    : ($port->popup_rows ?? []);
                            @endphp
                            @forelse ($rows as $row)
                                <div class="form-row align-items-center mb-2 popup-row-item">
                                    <div class="col-4">
                                        <input type="text" name="popup_keys[]" class="form-control form-control-sm" value="{{ $row['k'] ?? '' }}" placeholder="Nhãn (VD: Vị trí)" maxlength="100">
                                    </div>
                                    <div class="col-7">
                                        <input type="text" name="popup_values[]" class="form-control form-control-sm" value="{{ $row['v'] ?? '' }}" placeholder="Giá trị thông tin" maxlength="500">
                                    </div>
                                    <div class="col-1 text-right">
                                        <button type="button" class="btn btn-xs btn-danger remove-popup-row" title="Xóa dòng">&times;</button>
                                    </div>
                                </div>
                            @empty
                                <div class="form-row align-items-center mb-2 popup-row-item">
                                    <div class="col-4">
                                        <input type="text" name="popup_keys[]" class="form-control form-control-sm" placeholder="Nhãn (VD: Vị trí)" maxlength="100">
                                    </div>
                                    <div class="col-7">
                                        <input type="text" name="popup_values[]" class="form-control form-control-sm" placeholder="Giá trị thông tin" maxlength="500">
                                    </div>
                                    <div class="col-1 text-right">
                                        <button type="button" class="btn btn-xs btn-danger remove-popup-row" title="Xóa dòng">&times;</button>
                                    </div>
                                </div>
                            @endforelse
                        </div>
                        <small class="text-muted">Các dòng này sẽ hiển thị trực tiếp trong thẻ popup khi người dùng chọn cảng trên bản đồ Frontend.</small>
                    </div>
                </div>

                <div class="card card-secondary">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title mb-0">Các dòng thông tin trong Popup (English - tùy chọn)</h3>
                        <button type="button" id="add-popup-row-en" class="btn btn-xs btn-light text-dark">
                            <i class="fas fa-plus"></i> Thêm dòng (EN)
                        </button>
                    </div>
                    <div class="card-body">
                        <div id="popup-rows-en-container">
                            @php
                                $rowsEn = old('popup_keys_en')
                                    ? collect(old('popup_keys_en'))->map(fn ($k, $i) => ['k' => $k, 'v' => old('popup_values_en.' . $i, '')])->all()
                                    : ($port->popup_rows_en ?? []);
                            @endphp
                            @forelse ($rowsEn as $row)
                                <div class="form-row align-items-center mb-2 popup-row-item">
                                    <div class="col-4">
                                        <input type="text" name="popup_keys_en[]" class="form-control form-control-sm" value="{{ $row['k'] ?? '' }}" placeholder="Label (e.g. Location)" maxlength="100">
                                    </div>
                                    <div class="col-7">
                                        <input type="text" name="popup_values_en[]" class="form-control form-control-sm" value="{{ $row['v'] ?? '' }}" placeholder="Value" maxlength="500">
                                    </div>
                                    <div class="col-1 text-right">
                                        <button type="button" class="btn btn-xs btn-danger remove-popup-row" title="Xóa dòng">&times;</button>
                                    </div>
                                </div>
                            @empty
                                <div class="form-row align-items-center mb-2 popup-row-item">
                                    <div class="col-4">
                                        <input type="text" name="popup_keys_en[]" class="form-control form-control-sm" placeholder="Label (e.g. Location)" maxlength="100">
                                    </div>
                                    <div class="col-7">
                                        <input type="text" name="popup_values_en[]" class="form-control form-control-sm" placeholder="Value" maxlength="500">
                                    </div>
                                    <div class="col-1 text-right">
                                        <button type="button" class="btn btn-xs btn-danger remove-popup-row" title="Xóa dòng">&times;</button>
                                    </div>
                                </div>
                            @endforelse
                        </div>
                        <small class="text-muted">Nếu để trống toàn bộ phần Tiếng Anh, hệ thống tự động hiển thị nội dung Tiếng Việt khi người dùng chuyển sang EN.</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="mb-4">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save mr-1"></i> Lưu thay đổi
            </button>
            <a href="{{ route('tenant.tan_cang.port_map.index') }}" class="btn btn-secondary ml-2">Quay lại</a>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const container = document.getElementById('popup-rows-container');
    const addButton = document.getElementById('add-popup-row');

    const containerEn = document.getElementById('popup-rows-en-container');
    const addButtonEn = document.getElementById('add-popup-row-en');

    const bindRowEvents = (wrap, addBtn, keyName, valName, keyPh, valPh) => {
        if (addBtn && wrap) {
            addBtn.addEventListener('click', function () {
                const row = document.createElement('div');
                row.className = 'form-row align-items-center mb-2 popup-row-item';
                row.innerHTML =
                    '<div class="col-4"><input type="text" name="' + keyName + '[]" class="form-control form-control-sm" placeholder="' + keyPh + '" maxlength="100"></div>' +
                    '<div class="col-7"><input type="text" name="' + valName + '[]" class="form-control form-control-sm" placeholder="' + valPh + '" maxlength="500"></div>' +
                    '<div class="col-1 text-right"><button type="button" class="btn btn-xs btn-danger remove-popup-row" title="Xóa dòng">&times;</button></div>';
                wrap.appendChild(row);
            });
        }
        if (wrap) {
            wrap.addEventListener('click', function (event) {
                if (event.target.classList.contains('remove-popup-row')) {
                    const row = event.target.closest('.popup-row-item');
                    if (row) {
                        row.remove();
                    }
                }
            });
        }
    };

    bindRowEvents(container, addButton, 'popup_keys', 'popup_values', 'Nhãn (VD: Diện tích)', 'Giá trị thông tin');
    bindRowEvents(containerEn, addButtonEn, 'popup_keys_en', 'popup_values_en', 'Label (e.g. Area)', 'Value');
});
</script>
@endpush
