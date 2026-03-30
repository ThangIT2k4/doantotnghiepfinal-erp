@extends('layouts.staff_dashboard')

@section('title', 'Thêm phòng mới')

@section('content')
<main class="main-content">
    <div class="container-fluid">
        {{-- 1. Page Header với nút "Quay lại" --}}
        @include('staff.components.index-page-header', [
            'title' => 'Thêm phòng mới',
            'subtitle' => 'Tạo phòng mới cho bất động sản trong tổ chức',
            'icon' => 'fas fa-plus',
            'actions' => [
                [
                    'variant' => 'secondary',
                    'label' => 'Quay lại',
                    'icon' => 'fas fa-arrow-left',
                    'url' => route('staff.units.index')
                ]
            ]
        ])

    @if($errors->any())
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                @foreach($errors->all() as $error)
                    Notify.error('{{ $error }}');
                @endforeach
            });
        </script>
    @endif

    <!-- Creation Mode Toggle -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h6 class="mb-1">Chế độ tạo phòng</h6>
                    <p class="text-muted mb-0">Chọn cách tạo phòng phù hợp với nhu cầu của bạn</p>
                </div>
                <div class="col-md-4">
                    <div class="btn-group w-100" role="group">
                        <input type="radio" class="btn-check" name="creation_mode" id="single_mode" value="single" checked>
                        <label class="btn btn-outline-primary" for="single_mode">
                            <i class="fas fa-plus me-1"></i>Tạo đơn lẻ
                        </label>
                        
                        <input type="radio" class="btn-check" name="creation_mode" id="bulk_mode" value="bulk">
                        <label class="btn btn-outline-success" for="bulk_mode">
                            <i class="fas fa-layer-group me-1"></i>Tạo hàng loạt
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('staff.units.store') }}" enctype="multipart/form-data" id="unit-form">
        @csrf
        <input type="hidden" name="creation_mode" id="creation_mode_input" value="single">
        
        <div class="row">
            <!-- Main Form -->
            <div class="col-lg-8">
                <!-- Single Unit Form -->
                <div id="single-unit-form">
                    <!-- Basic Information -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-info-circle me-2"></i>Thông tin cơ bản
                            </h5>
                        </div>
                        <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="property_id" class="form-label">
                                        Bất động sản <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select @error('property_id') is-invalid @enderror" 
                                            id="property_id" name="property_id" required>
                                        <option value="">Chọn bất động sản</option>
                                        @foreach($properties as $property)
                                            <option value="{{ $property->id }}" 
                                                    {{ (old('property_id', $propertyId ?? null) == $property->id) ? 'selected' : '' }}>
                                                {{ $property->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('property_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="code" class="form-label">
                                        Mã phòng <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" 
                                           class="form-control @error('code') is-invalid @enderror" 
                                           id="code" 
                                           name="code" 
                                           value="{{ old('code') }}" 
                                           placeholder="VD: P101, A201..."
                                           required>
                                    @error('code')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="floor" class="form-label">Tầng</label>
                                    <input type="number" 
                                           class="form-control @error('floor') is-invalid @enderror" 
                                           id="floor" 
                                           name="floor" 
                                           value="{{ old('floor') }}" 
                                           min="1" 
                                           max="100"
                                           placeholder="VD: 1, 2, 3...">
                                    @error('floor')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="area_m2" class="form-label">Diện tích (m²)</label>
                                    <input type="number" 
                                           class="form-control @error('area_m2') is-invalid @enderror" 
                                           id="area_m2" 
                                           name="area_m2" 
                                           value="{{ old('area_m2') }}" 
                                           min="0" 
                                           max="1000"
                                           step="0.1"
                                           placeholder="VD: 25.5">
                                    @error('area_m2')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="unit_type" class="form-label">
                                        Loại phòng <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select @error('unit_type') is-invalid @enderror" 
                                            id="unit_type" name="unit_type" required>
                                        <option value="">Chọn loại phòng</option>
                                        <option value="room" {{ old('unit_type') == 'room' ? 'selected' : '' }}>Phòng</option>
                                        <option value="apartment" {{ old('unit_type') == 'apartment' ? 'selected' : '' }}>Căn hộ</option>
                                        <option value="dorm" {{ old('unit_type') == 'dorm' ? 'selected' : '' }}>Ký túc xá</option>
                                        <option value="shared" {{ old('unit_type') == 'shared' ? 'selected' : '' }}>Chung</option>
                                    </select>
                                    @error('unit_type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="max_occupancy" class="form-label">
                                        Số người tối đa <span class="text-danger">*</span>
                                    </label>
                                    <input type="number" 
                                           class="form-control @error('max_occupancy') is-invalid @enderror" 
                                           id="max_occupancy" 
                                           name="max_occupancy" 
                                           value="{{ old('max_occupancy') }}" 
                                           min="1" 
                                           max="10"
                                           required>
                                    @error('max_occupancy')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                {{-- Trạng thái luôn là 'available' khi tạo mới, không hiển thị trong form --}}
                                <input type="hidden" name="status" id="status" value="available">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="note" class="form-label">Ghi chú</label>
                            <textarea class="form-control @error('note') is-invalid @enderror" 
                                      id="note" 
                                      name="note" 
                                      rows="3"
                                      placeholder="Ghi chú thêm về phòng...">{{ old('note') }}</textarea>
                            @error('note')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Pricing Information -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-dollar-sign me-2"></i>Thông tin giá cả
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="base_rent" class="form-label">
                                        Giá thuê cơ bản (đ/tháng) <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" 
                                           class="form-control money-input @error('base_rent') is-invalid @enderror" 
                                           id="base_rent" 
                                           name="base_rent" 
                                           value="{{ old('base_rent') ? number_format(old('base_rent'), 0, ',', '.') : '' }}" 
                                           placeholder="VD: 5.000.000"
                                           required>
                                    @error('base_rent')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="deposit_amount" class="form-label">Tiền cọc (đ)</label>
                                    <input type="text" 
                                           class="form-control money-input @error('deposit_amount') is-invalid @enderror" 
                                           id="deposit_amount" 
                                           name="deposit_amount" 
                                           value="{{ old('deposit_amount') ? number_format(old('deposit_amount'), 0, ',', '.') : '' }}" 
                                           placeholder="VD: 10.000.000">
                                    @error('deposit_amount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Images -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-images me-2"></i>Hình ảnh phòng
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="images" class="form-label">Chọn hình ảnh</label>
                            <div class="image-upload-area" id="imageUploadArea" style="border: 2px dashed #dee2e6; border-radius: 8px; padding: 20px; text-align: center; cursor: pointer; transition: all 0.3s ease;" ondrop="handleDrop(event, 'images')" ondragover="handleDragOver(event)" ondragleave="handleDragLeave(event)">
                                <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                                <p class="mb-2">Kéo thả ảnh vào đây hoặc click để chọn</p>
                                <input type="file" 
                                       class="form-control @error('images') is-invalid @enderror" 
                                       id="images" 
                                       name="images[]" 
                                       multiple 
                                       accept="image/*"
                                       style="display: none;">
                                <button type="button" class="btn btn-outline-primary" onclick="document.getElementById('images').click()">
                                    <i class="fas fa-folder-open me-2"></i>Chọn ảnh
                                </button>
                            </div>
                            <div class="form-text">Chọn nhiều hình ảnh cùng lúc bằng cách giữ Ctrl (Windows) hoặc Cmd (Mac) và click chọn nhiều file. Định dạng: JPEG, PNG, JPG, GIF, WebP. Tối đa 5MB mỗi file.</div>
                            @error('images')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Image Preview -->
                        <div id="image-preview" class="row g-2"></div>
                    </div>
                </div>
                </div>

                <!-- Bulk Unit Form -->
                <div id="bulk-unit-form" style="display: none;">
                    <!-- Property Selection -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-building me-2"></i>Chọn bất động sản
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="bulk_property_id" class="form-label">
                                    Bất động sản <span class="text-danger">*</span>
                                </label>
                                <select class="form-select @error('bulk_property_id') is-invalid @enderror" 
                                        id="bulk_property_id" name="bulk_property_id">
                                    <option value="">Chọn bất động sản</option>
                                    @foreach($properties as $property)
                                        <option value="{{ $property->id }}" 
                                                {{ (old('bulk_property_id', $propertyId ?? null) == $property->id) ? 'selected' : '' }}>
                                            {{ $property->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('bulk_property_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Common Information -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-cog me-2"></i>Thông tin chung cho tất cả phòng
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="bulk_unit_type" class="form-label">
                                            Loại phòng <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-select @error('bulk_unit_type') is-invalid @enderror" 
                                                id="bulk_unit_type" name="bulk_unit_type">
                                            <option value="">Chọn loại phòng</option>
                                            <option value="room" {{ old('bulk_unit_type') == 'room' ? 'selected' : '' }}>Phòng</option>
                                            <option value="apartment" {{ old('bulk_unit_type') == 'apartment' ? 'selected' : '' }}>Căn hộ</option>
                                            <option value="dorm" {{ old('bulk_unit_type') == 'dorm' ? 'selected' : '' }}>Ký túc xá</option>
                                            <option value="shared" {{ old('bulk_unit_type') == 'shared' ? 'selected' : '' }}>Chung</option>
                                        </select>
                                        @error('bulk_unit_type')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="bulk_max_occupancy" class="form-label">
                                            Số người tối đa <span class="text-danger">*</span>
                                        </label>
                                        <input type="number" 
                                               class="form-control @error('bulk_max_occupancy') is-invalid @enderror" 
                                               id="bulk_max_occupancy" 
                                               name="bulk_max_occupancy" 
                                               value="{{ old('bulk_max_occupancy') }}" 
                                               min="1" 
                                               max="10">
                                        @error('bulk_max_occupancy')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="bulk_area_m2" class="form-label">Diện tích (m²)</label>
                                        <input type="number" 
                                               class="form-control @error('bulk_area_m2') is-invalid @enderror" 
                                               id="bulk_area_m2" 
                                               name="bulk_area_m2" 
                                               value="{{ old('bulk_area_m2') }}" 
                                               min="0" 
                                               max="1000"
                                               step="0.1"
                                               placeholder="VD: 25.5">
                                        @error('bulk_area_m2')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    {{-- Trạng thái luôn là 'available' khi tạo mới, không hiển thị trong form --}}
                                    <input type="hidden" name="bulk_status" id="bulk_status" value="available">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="bulk_base_rent" class="form-label">
                                            Giá thuê cơ bản (đ/tháng) <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" 
                                               class="form-control money-input @error('bulk_base_rent') is-invalid @enderror" 
                                               id="bulk_base_rent" 
                                               name="bulk_base_rent" 
                                               value="{{ old('bulk_base_rent') ? number_format(old('bulk_base_rent'), 0, ',', '.') : '' }}" 
                                               placeholder="VD: 5.000.000">
                                        @error('bulk_base_rent')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="bulk_deposit_amount" class="form-label">Tiền cọc (đ)</label>
                                        <input type="text" 
                                               class="form-control money-input @error('bulk_deposit_amount') is-invalid @enderror" 
                                               id="bulk_deposit_amount" 
                                               name="bulk_deposit_amount" 
                                               value="{{ old('bulk_deposit_amount') ? number_format(old('bulk_deposit_amount'), 0, ',', '.') : '' }}" 
                                               placeholder="VD: 10.000.000">
                                        @error('bulk_deposit_amount')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="bulk_note" class="form-label">Ghi chú chung</label>
                                <textarea class="form-control @error('bulk_note') is-invalid @enderror" 
                                          id="bulk_note" 
                                          name="bulk_note" 
                                          rows="3"
                                          placeholder="Ghi chú chung cho tất cả phòng...">{{ old('bulk_note') }}</textarea>
                                @error('bulk_note')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Floor Configuration Mode -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-cogs me-2"></i>Chế độ cấu hình tầng
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <h6 class="mb-1">Cách cấu hình tầng</h6>
                                    <p class="text-muted mb-0">Chọn cách cấu hình phù hợp với nhu cầu của bạn</p>
                                </div>
                                <div class="col-md-4">
                                    <div class="btn-group w-100" role="group">
                                        <input type="radio" class="btn-check" name="floor_config_mode" id="simple_mode" value="simple" checked>
                                        <label class="btn btn-outline-primary" for="simple_mode">
                                            <i class="fas fa-layer-group me-1"></i>Đơn giản
                                        </label>
                                        
                                        <input type="radio" class="btn-check" name="floor_config_mode" id="advanced_mode" value="advanced">
                                        <label class="btn btn-outline-success" for="advanced_mode">
                                            <i class="fas fa-cogs me-1"></i>Chi tiết
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Simple Floor Configuration -->
                    <div id="simple-floor-config">
                        <div class="card shadow-sm mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-layer-group me-2"></i>Cấu hình tầng đơn giản
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="start_floor" class="form-label">
                                                Tầng bắt đầu <span class="text-danger">*</span>
                                            </label>
                                            <input type="number" 
                                                   class="form-control @error('start_floor') is-invalid @enderror" 
                                                   id="start_floor" 
                                                   name="start_floor" 
                                                   value="{{ old('start_floor', 1) }}" 
                                                   min="1" 
                                                   max="100">
                                            @error('start_floor')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="end_floor" class="form-label">
                                                Tầng kết thúc <span class="text-danger">*</span>
                                            </label>
                                            <input type="number" 
                                                   class="form-control @error('end_floor') is-invalid @enderror" 
                                                   id="end_floor" 
                                                   name="end_floor" 
                                                   value="{{ old('end_floor', 1) }}" 
                                                   min="1" 
                                                   max="100">
                                            @error('end_floor')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="rooms_per_floor" class="form-label">
                                                Số phòng mỗi tầng <span class="text-danger">*</span>
                                            </label>
                                            <input type="number" 
                                                   class="form-control @error('rooms_per_floor') is-invalid @enderror" 
                                                   id="rooms_per_floor" 
                                                   name="rooms_per_floor" 
                                                   value="{{ old('rooms_per_floor', 1) }}" 
                                                   min="1" 
                                                   max="50">
                                            @error('rooms_per_floor')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="room_prefix" class="form-label">
                                                Tiền tố mã phòng
                                            </label>
                                            <input type="text" 
                                                   class="form-control @error('room_prefix') is-invalid @enderror" 
                                                   id="room_prefix" 
                                                   name="room_prefix" 
                                                   value="{{ old('room_prefix', 'P') }}" 
                                                   placeholder="VD: P, A, R...">
                                            <div class="form-text">Mã phòng sẽ được tạo theo định dạng: {Tiền tố}{Tầng}{Số phòng} (VD: P101, P102...)</div>
                                            @error('room_prefix')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Advanced Floor Configuration -->
                    <div id="advanced-floor-config" style="display: none;">
                        <div class="card shadow-sm mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-cogs me-2"></i>Cấu hình chi tiết từng tầng
                                </h5>
                                <button type="button" class="btn btn-sm btn-outline-primary" id="add-floor-config">
                                    <i class="fas fa-plus me-1"></i>Thêm tầng
                                </button>
                            </div>
                            <div class="card-body">
                                <div id="floor-configs">
                                    <!-- Floor configurations will be added here dynamically -->
                                </div>
                                <div class="text-center mt-3">
                                    <button type="button" class="btn btn-outline-secondary" id="add-floor-config-bottom">
                                        <i class="fas fa-plus me-1"></i>Thêm tầng khác
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Preview -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-eye me-2"></i>Xem trước danh sách phòng
                            </h5>
                        </div>
                        <div class="card-body">
                            <div id="room-preview" class="border rounded p-3 bg-light" style="max-height: 300px; overflow-y: auto;">
                                <p class="text-muted mb-0">Nhập thông tin trên để xem trước danh sách phòng</p>
                            </div>
                        </div>
                    </div>

                    <!-- Images for Bulk Creation -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-images me-2"></i>Hình ảnh chung cho tất cả phòng
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="bulk_images" class="form-label">Chọn hình ảnh</label>
                                <div class="image-upload-area" id="bulkImageUploadArea" style="border: 2px dashed #dee2e6; border-radius: 8px; padding: 20px; text-align: center; cursor: pointer; transition: all 0.3s ease;" ondrop="handleDrop(event, 'bulk_images')" ondragover="handleDragOver(event)" ondragleave="handleDragLeave(event)">
                                    <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                                    <p class="mb-2">Kéo thả ảnh vào đây hoặc click để chọn</p>
                                    <input type="file" 
                                           class="form-control @error('bulk_images') is-invalid @enderror" 
                                           id="bulk_images" 
                                           name="bulk_images[]" 
                                           multiple 
                                           accept="image/*"
                                           style="display: none;">
                                    <button type="button" class="btn btn-outline-primary" onclick="document.getElementById('bulk_images').click()">
                                        <i class="fas fa-folder-open me-2"></i>Chọn ảnh
                                    </button>
                                </div>
                                <div class="form-text">Chọn nhiều hình ảnh cùng lúc bằng cách giữ Ctrl (Windows) hoặc Cmd (Mac) và click chọn nhiều file. Tất cả hình ảnh sẽ được gắn cho mỗi phòng được tạo. Định dạng: JPEG, PNG, JPG, GIF, WebP. Tối đa 5MB mỗi file.</div>
                                @error('bulk_images')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Bulk Image Preview -->
                            <div id="bulk-image-preview" class="row g-2"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Amenities -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-star me-2"></i>Tiện ích
                        </h5>
                    </div>
                    <div class="card-body">
                        @if($amenities->count() > 0)
                            <div class="amenities-list">
                                @foreach($amenities->groupBy('category') as $category => $categoryAmenities)
                                    <div class="amenity-category mb-3">
                                        <h6 class="text-muted small mb-2">{{ $category }}</h6>
                                        @foreach($categoryAmenities as $amenity)
                                            <div class="form-check">
                                                <input class="form-check-input" 
                                                       type="checkbox" 
                                                       id="amenity_{{ $amenity->id }}" 
                                                       name="amenities[]" 
                                                       value="{{ $amenity->id }}"
                                                       {{ in_array($amenity->id, old('amenities', [])) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="amenity_{{ $amenity->id }}">
                                                    {{ $amenity->name }}
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-muted">Chưa có tiện ích nào được định nghĩa.</p>
                        @endif
                    </div>
                </div>

                {{-- Card Thao tác (chứa action-buttons với layout dọc) --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-cogs me-2"></i>Thao tác
                        </h6>
                    </div>
                    <div class="card-body">
                        @include('staff.components.action-buttons', [
                            'layout' => 'vertical',
                            'size' => 'md',
                            'actions' => [
                                [
                                    'type' => 'submit',
                                    'variant' => 'primary',
                                    'label' => 'Tạo phòng',
                                    'icon' => 'fas fa-save',
                                    'id' => 'submit-btn',
                                    'class' => 'w-100'
                                ],
                                [
                                    'type' => 'link',
                                    'variant' => 'secondary',
                                    'label' => 'Hủy',
                                    'icon' => 'fas fa-times',
                                    'url' => route('staff.units.index'),
                                    'class' => 'w-100'
                                ]
                            ]
                        ])
                    </div>
                </div>

                {{-- Card Hướng dẫn --}}
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-question-circle me-2"></i>Hướng dẫn
                        </h6>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled small">
                            <li class="mb-2">
                                <i class="fas fa-check text-success me-2"></i>
                                Mã phòng phải duy nhất trong cùng bất động sản
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check text-success me-2"></i>
                                Giá thuê và tiền cọc tính bằng VNĐ
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check text-success me-2"></i>
                                Có thể upload nhiều hình ảnh
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check text-success me-2"></i>
                                Chọn tiện ích phù hợp với phòng
                            </li>
                            <li>
                                <i class="fas fa-info-circle text-info me-2"></i>
                                Trạng thái mặc định khi tạo mới là "Có sẵn". Bạn có thể thay đổi trạng thái sau khi tạo.
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </form>
    </div>
</main>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mode switching
    const singleMode = document.getElementById('single_mode');
    const bulkMode = document.getElementById('bulk_mode');
    const singleForm = document.getElementById('single-unit-form');
    const bulkForm = document.getElementById('bulk-unit-form');
    const creationModeInput = document.getElementById('creation_mode_input');
    const submitBtn = document.getElementById('submit-btn');
    const submitText = submitBtn ? submitBtn.querySelector('span') : null;

    function switchMode(mode) {
        if (mode === 'single') {
            singleForm.style.display = 'block';
            bulkForm.style.display = 'none';
            creationModeInput.value = 'single';
            if (submitText) submitText.textContent = 'Tạo phòng';
            
            // Enable required attributes for single mode fields
            enableRequiredForSingleMode();
            disableRequiredForBulkMode();
        } else {
            singleForm.style.display = 'none';
            bulkForm.style.display = 'block';
            creationModeInput.value = 'bulk';
            if (submitText) submitText.textContent = 'Tạo hàng loạt';
            
            // Disable required attributes for single mode fields
            disableRequiredForSingleMode();
            enableRequiredForBulkMode();
        }
    }

    function enableRequiredForSingleMode() {
        const singleRequiredFields = [
            'property_id', 'code', 'unit_type', 'max_occupancy', 'base_rent'
        ];
        singleRequiredFields.forEach(fieldName => {
            const field = document.getElementById(fieldName);
            if (field) {
                field.setAttribute('required', 'required');
            }
        });
    }

    function disableRequiredForSingleMode() {
        const singleRequiredFields = [
            'property_id', 'code', 'unit_type', 'max_occupancy', 'base_rent'
        ];
        singleRequiredFields.forEach(fieldName => {
            const field = document.getElementById(fieldName);
            if (field) {
                field.removeAttribute('required');
            }
        });
    }

    function enableRequiredForBulkMode() {
        const bulkRequiredFields = [
            'bulk_property_id', 'bulk_unit_type', 'bulk_max_occupancy', 'bulk_base_rent'
        ];
        bulkRequiredFields.forEach(fieldName => {
            const field = document.getElementById(fieldName);
            if (field) {
                field.setAttribute('required', 'required');
            }
        });
    }

    function disableRequiredForBulkMode() {
        const bulkRequiredFields = [
            'bulk_property_id', 'bulk_unit_type', 'bulk_max_occupancy', 'bulk_base_rent'
        ];
        bulkRequiredFields.forEach(fieldName => {
            const field = document.getElementById(fieldName);
            if (field) {
                field.removeAttribute('required');
            }
        });
    }

    singleMode.addEventListener('change', function() {
        if (this.checked) switchMode('single');
    });

    bulkMode.addEventListener('change', function() {
        if (this.checked) switchMode('bulk');
    });

    // Image preview for single mode
    const imageInput = document.getElementById('images');
    const imagePreview = document.getElementById('image-preview');

    if (imageInput) {
        imageInput.addEventListener('change', function(e) {
            imagePreview.innerHTML = '';
            
            if (e.target.files && e.target.files.length > 0) {
                // Show loading message
                const loadingDiv = document.createElement('div');
                loadingDiv.className = 'col-12';
                loadingDiv.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin me-2"></i>Đang tải ảnh...</div>';
                imagePreview.appendChild(loadingDiv);
                
                let loadedCount = 0;
                const totalFiles = e.target.files.length;
                
                Array.from(e.target.files).forEach((file, index) => {
                    if (file.type.startsWith('image/')) {
                        // Check file size (5MB limit)
                        if (file.size > 5 * 1024 * 1024) {
                            Notify.warning(`File "${file.name}" quá lớn (>5MB). Vui lòng chọn file nhỏ hơn.`);
                            return;
                        }
                        
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            loadedCount++;
                            
                            // Remove loading message if this is the first image
                            if (loadedCount === 1) {
                                const loadingDiv = imagePreview.querySelector('.col-12');
                                if (loadingDiv) loadingDiv.remove();
                            }
                            
                            const col = document.createElement('div');
                            col.className = 'col-md-4 mb-2';
                            col.innerHTML = `
                                <div class="image-preview-item position-relative">
                                    <img src="${e.target.result}" class="img-thumbnail" style="height: 100px; object-fit: cover; width: 100%;">
                                    <div class="position-absolute top-0 start-0 bg-dark bg-opacity-75 text-white px-1 rounded-bottom-end" style="font-size: 0.7rem;">
                                        ${file.name.length > 15 ? file.name.substring(0, 15) + '...' : file.name}
                                    </div>
                                    <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 btn-remove" 
                                            onclick="removeImagePreview(this)" title="Xóa ảnh">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            `;
                            imagePreview.appendChild(col);
                            
                            // Show completion message
                            if (loadedCount === totalFiles) {
                                Notify.success(`Đã tải ${loadedCount} ảnh thành công!`);
                            }
                        };
                        reader.readAsDataURL(file);
                    } else {
                        Notify.warning(`File "${file.name}" không phải là hình ảnh. Vui lòng chọn file hình ảnh.`);
                    }
                });
            }
        });
    }

    // Image preview for bulk mode
    const bulkImageInput = document.getElementById('bulk_images');
    const bulkImagePreview = document.getElementById('bulk-image-preview');

    if (bulkImageInput) {
        bulkImageInput.addEventListener('change', function(e) {
            bulkImagePreview.innerHTML = '';
            
            if (e.target.files && e.target.files.length > 0) {
                // Show loading message
                const loadingDiv = document.createElement('div');
                loadingDiv.className = 'col-12';
                loadingDiv.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin me-2"></i>Đang tải ảnh...</div>';
                bulkImagePreview.appendChild(loadingDiv);
                
                let loadedCount = 0;
                const totalFiles = e.target.files.length;
                
                Array.from(e.target.files).forEach((file, index) => {
                    if (file.type.startsWith('image/')) {
                        // Check file size (5MB limit)
                        if (file.size > 5 * 1024 * 1024) {
                            Notify.warning(`File "${file.name}" quá lớn (>5MB). Vui lòng chọn file nhỏ hơn.`);
                            return;
                        }
                        
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            loadedCount++;
                            
                            // Remove loading message if this is the first image
                            if (loadedCount === 1) {
                                const loadingDiv = bulkImagePreview.querySelector('.col-12');
                                if (loadingDiv) loadingDiv.remove();
                            }
                            
                            const col = document.createElement('div');
                            col.className = 'col-md-4 mb-2';
                            col.innerHTML = `
                                <div class="image-preview-item position-relative">
                                    <img src="${e.target.result}" class="img-thumbnail" style="height: 100px; object-fit: cover; width: 100%;">
                                    <div class="position-absolute top-0 start-0 bg-dark bg-opacity-75 text-white px-1 rounded-bottom-end" style="font-size: 0.7rem;">
                                        ${file.name.length > 15 ? file.name.substring(0, 15) + '...' : file.name}
                                    </div>
                                    <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 btn-remove" 
                                            onclick="removeBulkImagePreview(this)" title="Xóa ảnh">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            `;
                            bulkImagePreview.appendChild(col);
                            
                            // Show completion message
                            if (loadedCount === totalFiles) {
                                Notify.success(`Đã tải ${loadedCount} ảnh thành công!`);
                            }
                        };
                        reader.readAsDataURL(file);
                    } else {
                        Notify.warning(`File "${file.name}" không phải là hình ảnh. Vui lòng chọn file hình ảnh.`);
                    }
                });
            }
        });
    }

    // Floor configuration mode switching
    const simpleMode = document.getElementById('simple_mode');
    const advancedMode = document.getElementById('advanced_mode');
    const simpleConfig = document.getElementById('simple-floor-config');
    const advancedConfig = document.getElementById('advanced-floor-config');

    function switchFloorConfigMode(mode) {
        if (mode === 'simple') {
            simpleConfig.style.display = 'block';
            advancedConfig.style.display = 'none';
            
            // Enable required for simple mode fields
            enableRequiredForSimpleMode();
            disableRequiredForAdvancedMode();
        } else {
            simpleConfig.style.display = 'none';
            advancedConfig.style.display = 'block';
            
            // Disable required for simple mode fields
            disableRequiredForSimpleMode();
            enableRequiredForAdvancedMode();
        }
        updateRoomPreview();
    }

    function enableRequiredForSimpleMode() {
        const simpleRequiredFields = [
            'start_floor', 'end_floor', 'rooms_per_floor'
        ];
        simpleRequiredFields.forEach(fieldName => {
            const field = document.getElementById(fieldName);
            if (field) {
                field.setAttribute('required', 'required');
            }
        });
    }

    function disableRequiredForSimpleMode() {
        const simpleRequiredFields = [
            'start_floor', 'end_floor', 'rooms_per_floor'
        ];
        simpleRequiredFields.forEach(fieldName => {
            const field = document.getElementById(fieldName);
            if (field) {
                field.removeAttribute('required');
            }
        });
    }

    function enableRequiredForAdvancedMode() {
        // Advanced mode validation is handled by JavaScript
        // No HTML5 required attributes needed
    }

    function disableRequiredForAdvancedMode() {
        // Advanced mode validation is handled by JavaScript
        // No HTML5 required attributes needed
    }

    if (simpleMode) {
        simpleMode.addEventListener('change', function() {
            if (this.checked) switchFloorConfigMode('simple');
        });
    }

    if (advancedMode) {
        advancedMode.addEventListener('change', function() {
            if (this.checked) switchFloorConfigMode('advanced');
        });
    }

    // Advanced floor configuration
    let floorConfigCount = 0;
    const floorConfigs = document.getElementById('floor-configs');
    const addFloorConfigBtn = document.getElementById('add-floor-config');
    const addFloorConfigBottomBtn = document.getElementById('add-floor-config-bottom');

    function addFloorConfig() {
        floorConfigCount++;
        const floorConfigHtml = `
            <div class="floor-config-item border rounded p-3 mb-3" data-index="${floorConfigCount}">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0">Cấu hình tầng ${floorConfigCount}</h6>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeFloorConfig(${floorConfigCount})">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="row">
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label">Số tầng</label>
                            <input type="number" class="form-control floor-number" min="1" max="100" placeholder="VD: 1">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label">Số phòng</label>
                            <input type="number" class="form-control rooms-count" min="1" max="50" placeholder="VD: 4">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label">Loại phòng</label>
                            <select class="form-select room-type">
                                <option value="room">Phòng</option>
                                <option value="apartment">Căn hộ</option>
                                <option value="dorm">Ký túc xá</option>
                                <option value="shared">Chung</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label">Tiền tố mã</label>
                            <input type="text" class="form-control room-prefix" placeholder="VD: P, A">
                        </div>
                    </div>
                </div>
                
                <!-- Custom Room Numbers Section -->
                <div class="row">
                    <div class="col-12">
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="fas fa-list-ol me-1"></i>Số phòng tùy chỉnh (tùy chọn)
                            </label>
                            <div class="form-text mb-2">
                                Để trống để sử dụng số phòng tự động (1, 2, 3...). Nhập danh sách số phòng tùy chỉnh, cách nhau bằng dấu phẩy.
                            </div>
                            <input type="text" class="form-control custom-room-numbers" 
                                   placeholder="VD: 101, 102, 103, 105, 107 (bỏ qua 104, 106)">
                            <div class="form-text">
                                <small class="text-muted">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Ví dụ: "101, 102, 103" hoặc "A1, A2, A3" hoặc "1, 3, 5" (chỉ tạo phòng 1, 3, 5)
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        floorConfigs.insertAdjacentHTML('beforeend', floorConfigHtml);
        updateRoomPreview();
    }

    function removeFloorConfig(index) {
        const floorConfig = document.querySelector(`[data-index="${index}"]`);
        if (floorConfig) {
            floorConfig.remove();
            updateRoomPreview();
        }
    }

    if (addFloorConfigBtn) {
        addFloorConfigBtn.addEventListener('click', addFloorConfig);
    }

    if (addFloorConfigBottomBtn) {
        addFloorConfigBottomBtn.addEventListener('click', addFloorConfig);
    }

    // Room preview for bulk mode
    const startFloor = document.getElementById('start_floor');
    const endFloor = document.getElementById('end_floor');
    const roomsPerFloor = document.getElementById('rooms_per_floor');
    const roomPrefix = document.getElementById('room_prefix');
    const roomPreview = document.getElementById('room-preview');

    function updateRoomPreview() {
        if (!roomPreview) return;

        const isSimpleMode = simpleMode && simpleMode.checked;
        
        if (isSimpleMode) {
            updateSimpleRoomPreview();
        } else {
            updateAdvancedRoomPreview();
        }
    }

    function updateSimpleRoomPreview() {
        if (!startFloor || !endFloor || !roomsPerFloor || !roomPrefix) return;

        const start = parseInt(startFloor.value) || 1;
        const end = parseInt(endFloor.value) || 1;
        const roomsPer = parseInt(roomsPerFloor.value) || 1;
        const prefix = roomPrefix.value || 'P';

        if (start > end) {
            roomPreview.innerHTML = '<p class="text-danger mb-0">Tầng bắt đầu không được lớn hơn tầng kết thúc</p>';
            return;
        }

        const totalFloors = end - start + 1;
        const totalRooms = totalFloors * roomsPer;

        if (totalRooms > 100) {
            roomPreview.innerHTML = '<p class="text-warning mb-0">Cảnh báo: Sẽ tạo ' + totalRooms + ' phòng. Số lượng quá lớn có thể ảnh hưởng đến hiệu suất.</p>';
            return;
        }

        let previewHtml = `<div class="d-flex justify-content-between align-items-center mb-2">
            <strong>Tổng cộng: ${totalRooms} phòng (${totalFloors} tầng)</strong>
        </div>`;

        previewHtml += '<div class="row g-1">';
        
        for (let floor = start; floor <= end; floor++) {
            for (let room = 1; room <= roomsPer; room++) {
                const roomNumber = floor.toString().padStart(2, '0') + room.toString().padStart(2, '0');
                const roomCode = prefix + roomNumber;
                previewHtml += `<div class="col-3">
                    <span class="badge bg-light text-dark">${roomCode}</span>
                </div>`;
            }
        }
        
        previewHtml += '</div>';
        roomPreview.innerHTML = previewHtml;
    }

    function updateAdvancedRoomPreview() {
        const floorConfigs = document.querySelectorAll('.floor-config-item');
        
        if (floorConfigs.length === 0) {
            roomPreview.innerHTML = '<p class="text-muted mb-0">Thêm cấu hình tầng để xem trước</p>';
            return;
        }

        let totalRooms = 0;
        let previewHtml = '<div class="row g-2">';

        floorConfigs.forEach((config, index) => {
            const floorNumber = parseInt(config.querySelector('.floor-number').value) || 0;
            const roomsCount = parseInt(config.querySelector('.rooms-count').value) || 0;
            const roomType = config.querySelector('.room-type').value;
            const roomPrefix = config.querySelector('.room-prefix').value || 'P';
            const customRoomNumbers = config.querySelector('.custom-room-numbers').value.trim();

            if (floorNumber > 0 && roomsCount > 0) {
                let roomNumbers = [];
                
                if (customRoomNumbers) {
                    // Parse custom room numbers
                    roomNumbers = customRoomNumbers.split(',').map(num => num.trim()).filter(num => num);
                } else {
                    // Generate automatic room numbers
                    for (let room = 1; room <= roomsCount; room++) {
                        roomNumbers.push(room.toString());
                    }
                }
                
                totalRooms += roomNumbers.length;
                
                previewHtml += `<div class="col-12">
                    <div class="border rounded p-2 mb-2">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <strong>Tầng ${floorNumber} (${roomNumbers.length} phòng - ${getRoomTypeName(roomType)})</strong>
                            ${customRoomNumbers ? '<span class="badge bg-info">Tùy chỉnh</span>' : '<span class="badge bg-secondary">Tự động</span>'}
                        </div>
                        <div class="row g-1">`;
                
                roomNumbers.forEach(roomNum => {
                    const roomCode = roomPrefix + roomNum;
                    previewHtml += `<div class="col-2">
                        <span class="badge bg-light text-dark">${roomCode}</span>
                    </div>`;
                });
                
                previewHtml += '</div></div></div>';
            }
        });

        previewHtml += '</div>';

        if (totalRooms > 100) {
            roomPreview.innerHTML = '<p class="text-warning mb-0">Cảnh báo: Sẽ tạo ' + totalRooms + ' phòng. Số lượng quá lớn có thể ảnh hưởng đến hiệu suất.</p>';
            return;
        }

        const summaryHtml = `<div class="d-flex justify-content-between align-items-center mb-3">
            <strong>Tổng cộng: ${totalRooms} phòng (${floorConfigs.length} cấu hình tầng)</strong>
        </div>`;

        roomPreview.innerHTML = summaryHtml + previewHtml;
    }

    function getRoomTypeName(type) {
        const types = {
            'room': 'Phòng',
            'apartment': 'Căn hộ',
            'dorm': 'Ký túc xá',
            'shared': 'Chung'
        };
        return types[type] || type;
    }

    // Event listeners for simple mode
    if (startFloor) startFloor.addEventListener('input', updateRoomPreview);
    if (endFloor) endFloor.addEventListener('input', updateRoomPreview);
    if (roomsPerFloor) roomsPerFloor.addEventListener('input', updateRoomPreview);
    if (roomPrefix) roomPrefix.addEventListener('input', updateRoomPreview);

    // Event listeners for advanced mode
    document.addEventListener('input', function(e) {
        if (e.target.classList.contains('floor-number') || 
            e.target.classList.contains('rooms-count') || 
            e.target.classList.contains('room-type') || 
            e.target.classList.contains('room-prefix') ||
            e.target.classList.contains('custom-room-numbers')) {
            updateRoomPreview();
        }
    });

    // Form submission handling
    const form = document.getElementById('unit-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault(); // Prevent default form submission
            
            
            // Validate based on current mode
            const isBulkMode = bulkMode && bulkMode.checked;
            const isAdvancedMode = advancedMode && advancedMode.checked;
            
            if (isBulkMode) {
                if (isAdvancedMode) {
                    // Advanced bulk mode validation
                    const floorConfigs = [];
                    const floorConfigItems = document.querySelectorAll('.floor-config-item');
                    
                    
                    floorConfigItems.forEach((item, index) => {
                        const floorNumber = item.querySelector('.floor-number').value;
                        const roomsCount = item.querySelector('.rooms-count').value;
                        const roomType = item.querySelector('.room-type').value;
                        const roomPrefix = item.querySelector('.room-prefix').value;
                        const customRoomNumbers = item.querySelector('.custom-room-numbers').value.trim();
                        
                        
                        if (floorNumber && roomsCount && roomType) {
                            let roomNumbers = [];
                            
                            if (customRoomNumbers) {
                                // Parse custom room numbers
                                roomNumbers = customRoomNumbers.split(',').map(num => num.trim()).filter(num => num);
                            } else {
                                // Generate automatic room numbers
                                for (let room = 1; room <= parseInt(roomsCount); room++) {
                                    roomNumbers.push(room.toString());
                                }
                            }
                            
                            floorConfigs.push({
                                floor_number: parseInt(floorNumber),
                                rooms_count: roomNumbers.length,
                                room_type: roomType,
                                room_prefix: roomPrefix || 'P',
                                custom_room_numbers: customRoomNumbers || null
                            });
                        }
                    });
                    
                    
                    // Validate floor configs
                    if (floorConfigs.length === 0) {
                        Notify.warning('Vui lòng thêm ít nhất một cấu hình tầng.');
                        return false;
                    }
                    
                    // Add floor configs as hidden inputs
                    floorConfigs.forEach((config, index) => {
                        Object.keys(config).forEach(key => {
                            const input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = `floor_configs[${index}][${key}]`;
                            input.value = config[key];
                            form.appendChild(input);
                        });
                    });
                    
                } else {
                    // Simple bulk mode validation
                    const startFloor = document.getElementById('start_floor').value;
                    const endFloor = document.getElementById('end_floor').value;
                    const roomsPerFloor = document.getElementById('rooms_per_floor').value;
                    
                    if (!startFloor || !endFloor || !roomsPerFloor) {
                        Notify.warning('Vui lòng nhập đầy đủ thông tin tầng và phòng.');
                        return false;
                    }
                    
                    if (parseInt(startFloor) > parseInt(endFloor)) {
                        Notify.warning('Tầng bắt đầu không được lớn hơn tầng kết thúc.');
                        return false;
                    }
                }
            }
            
            // Submit form via AJAX
            submitForm();
        });
    }
    
    // AJAX form submission function
    function submitForm() {
        const form = document.getElementById('unit-form');
        
        // Process number formatting before submit
        if (window.NumberFormatter && window.NumberFormatter.processForm) {
            window.NumberFormatter.processForm(form);
        }
        
        // Convert formatted values to raw values for submission
        const moneyFields = ['base_rent', 'deposit_amount', 'bulk_base_rent', 'bulk_deposit_amount'];
        moneyFields.forEach(fieldId => {
            const field = document.getElementById(fieldId);
            if (field && window.NumberFormatter) {
                const rawValue = window.NumberFormatter.getValue(field);
                // Update the field's value directly to raw value for submission
                field.value = rawValue;
            }
        });
        
        const formData = new FormData(form);
        
        // Show loading
        Preloader.show();
        
        fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    throw new Error('Invalid JSON response from server');
                }
            });
        })
        .then(data => {
            Preloader.hide();
            
            if (data.success) {
                Notify.success(data.message);
                
                // Reset form
                form.reset();
                
                // Clear image previews
                const imagePreview = document.getElementById('image-preview');
                const bulkImagePreview = document.getElementById('bulk-image-preview');
                if (imagePreview) imagePreview.innerHTML = '';
                if (bulkImagePreview) bulkImagePreview.innerHTML = '';
                
                // Clear floor configs
                const floorConfigsContainer = document.getElementById('floor-configs');
                if (floorConfigsContainer) floorConfigsContainer.innerHTML = '';
                
                // Reset to single mode
                if (singleMode) singleMode.checked = true;
                if (bulkMode) bulkMode.checked = false;
                if (simpleMode) simpleMode.checked = true;
                if (advancedMode) advancedMode.checked = false;
                
                // Update form display
                if (typeof updateFormDisplay === 'function') {
                    updateFormDisplay();
                }
                
                // Redirect to units index with property filter after 2 seconds
                setTimeout(() => {
                    // Use redirect from response, fallback to index
                    const redirectUrl = data.redirect || '{{ route("staff.units.index") }}';
                    window.location.href = redirectUrl;
                }, 2000);
            } else {
                Notify.error(data.message || 'Có lỗi xảy ra khi tạo phòng.');
                
                // Show validation errors if any
                if (data.errors) {
                    Object.keys(data.errors).forEach(field => {
                        const errorElement = document.querySelector(`[name="${field}"]`);
                        if (errorElement) {
                            errorElement.classList.add('is-invalid');
                            const feedback = errorElement.nextElementSibling;
                            if (feedback && feedback.classList.contains('invalid-feedback')) {
                                feedback.textContent = data.errors[field][0];
                            }
                        }
                    });
                }
            }
        })
        .catch(error => {
            Preloader.hide();
            
            // Check if it's a JSON parsing error
            if (error.message.includes('Invalid JSON')) {
                Notify.error('Server trả về dữ liệu không hợp lệ. Vui lòng thử lại.');
            } else {
                Notify.error('Có lỗi xảy ra khi tạo phòng. Vui lòng thử lại.');
            }
        });
    }

    // Initial setup
    function initializeForm() {
        // Set initial mode based on checked radio button
        if (singleMode && singleMode.checked) {
            switchMode('single');
        } else if (bulkMode && bulkMode.checked) {
            switchMode('bulk');
        }
        
        // Set initial floor config mode
        if (simpleMode && simpleMode.checked) {
            switchFloorConfigMode('simple');
        } else if (advancedMode && advancedMode.checked) {
            switchFloorConfigMode('advanced');
        }
        
        updateRoomPreview();
    }

    // Initialize form on page load
    initializeForm();
});

function removeImagePreview(button) {
    const imageContainer = button.closest('.col-md-4');
    const imagePreview = document.getElementById('image-preview');
    
    // Add fade out animation
    imageContainer.style.transition = 'opacity 0.3s ease';
    imageContainer.style.opacity = '0';
    
    setTimeout(() => {
        imageContainer.remove();
        
        // If no images left, clear the file input
        const remainingImages = imagePreview.querySelectorAll('.col-md-4');
        if (remainingImages.length === 0) {
            const imageInput = document.getElementById('images');
            if (imageInput) {
                imageInput.value = '';
            }
        }
    }, 300);
}

function removeBulkImagePreview(button) {
    const imageContainer = button.closest('.col-md-4');
    const bulkImagePreview = document.getElementById('bulk-image-preview');
    
    // Add fade out animation
    imageContainer.style.transition = 'opacity 0.3s ease';
    imageContainer.style.opacity = '0';
    
    setTimeout(() => {
        imageContainer.remove();
        
        // If no images left, clear the file input
        const remainingImages = bulkImagePreview.querySelectorAll('.col-md-4');
        if (remainingImages.length === 0) {
            const bulkImageInput = document.getElementById('bulk_images');
            if (bulkImageInput) {
                bulkImageInput.value = '';
            }
        }
    }, 300);
}

function removeFloorConfig(index) {
    const floorConfig = document.querySelector(`[data-index="${index}"]`);
    if (floorConfig) {
        floorConfig.remove();
        // Trigger preview update
        const event = new Event('input');
        document.dispatchEvent(event);
    }
}

// Drag and drop functions
function handleDragOver(e) {
    e.preventDefault();
    e.currentTarget.style.borderColor = '#007bff';
    e.currentTarget.style.backgroundColor = '#f8f9fa';
}

function handleDragLeave(e) {
    e.preventDefault();
    e.currentTarget.style.borderColor = '#dee2e6';
    e.currentTarget.style.backgroundColor = 'transparent';
}

function handleDrop(e, inputId) {
    e.preventDefault();
    e.currentTarget.style.borderColor = '#dee2e6';
    e.currentTarget.style.backgroundColor = 'transparent';
    
    const files = e.dataTransfer.files;
    const input = document.getElementById(inputId);
    
    if (files.length > 0) {
        // Create a new FileList-like object
        const dt = new DataTransfer();
        Array.from(files).forEach(file => {
            if (file.type.startsWith('image/')) {
                dt.items.add(file);
            } else {
                Notify.warning(`File "${file.name}" không phải là hình ảnh. Vui lòng chọn file hình ảnh.`);
            }
        });
        
        if (dt.items.length > 0) {
            input.files = dt.files;
            // Trigger change event
            const event = new Event('change', { bubbles: true });
            input.dispatchEvent(event);
        }
    }
}
</script>
@endpush

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/staff/units.css') }}">
<style>
.image-upload-area {
    transition: all 0.3s ease;
}

.image-upload-area:hover {
    border-color: #007bff !important;
    background-color: #f8f9fa !important;
}

.image-upload-area.dragover {
    border-color: #007bff !important;
    background-color: #e3f2fd !important;
    transform: scale(1.02);
}

.image-preview-item {
    position: relative;
    overflow: hidden;
    border-radius: 8px;
}

.image-preview-item img {
    transition: transform 0.3s ease;
}

.image-preview-item:hover img {
    transform: scale(1.05);
}

.image-preview-item .btn-remove {
    opacity: 0;
    transition: opacity 0.3s ease;
}

.image-preview-item:hover .btn-remove {
    opacity: 1;
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-select property if property_id is in URL
    @if(isset($propertyId) && $propertyId)
    const propertySelect = document.getElementById('property_id');
    const bulkPropertySelect = document.getElementById('bulk_property_id');
    if (propertySelect) {
        propertySelect.value = '{{ $propertyId }}';
    }
    if (bulkPropertySelect) {
        bulkPropertySelect.value = '{{ $propertyId }}';
    }
    @endif
    
    // Form submission handling
    const form = document.getElementById('unit-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            // Show loading notification
            const loadingToast = Notify.info('Đang tạo phòng...');
            
            // Disable submit button to prevent double submission
            const submitBtn = document.getElementById('submit-btn');
            if (submitBtn) {
                submitBtn.disabled = true;
                const icon = submitBtn.querySelector('i');
                if (icon) {
                    icon.className = 'fas fa-spinner fa-spin me-1';
                }
                const label = submitBtn.querySelector('span') || submitBtn.childNodes[submitBtn.childNodes.length - 1];
                if (label && label.nodeType === Node.TEXT_NODE) {
                    label.textContent = 'Đang tạo...';
                } else if (label && label.textContent !== undefined) {
                    label.textContent = 'Đang tạo...';
                }
            }
        });
    }
});
</script>
@endpush
@endsection
