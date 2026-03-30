@extends('layouts.staff_dashboard')

@section('title', 'Chỉnh sửa số liệu đo')

@section('content')
<main class="main-content">
    <div class="container-fluid">
        {{-- 1. Page Header với nút "Quay lại" và "Xem chi tiết" --}}
        @include('staff.components.index-page-header', [
            'title' => 'Chỉnh sửa số liệu đo',
            'subtitle' => 'Cập nhật thông tin số liệu đo ngày ' . $meterReading->reading_date->format('d/m/Y'),
            'icon' => 'fas fa-edit',
            'actions' => [
                [
                    'variant' => 'secondary',
                    'label' => 'Quay lại',
                    'icon' => 'fas fa-arrow-left',
                    'url' => route('staff.meter-readings.index')
                ],
                [
                    'variant' => 'info',
                    'label' => 'Xem chi tiết',
                    'icon' => 'fas fa-eye',
                    'url' => route('staff.meter-readings.show', $meterReading->id)
                ]
            ]
        ])

        {{-- 2. Form với Layout 2 Cột --}}
        <form id="edit-meter-reading-form" method="POST" action="{{ route('staff.meter-readings.update', $meterReading->id) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="row">
                {{-- Cột trái: Form chính (col-lg-8) --}}
                <div class="col-lg-8">
                    {{-- Card 1: Thông tin số liệu đo --}}
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-clipboard-list me-2"></i>Thông tin số liệu đo
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="meter_id" class="form-label">
                                            Công tơ đo <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-select @error('meter_id') is-invalid @enderror" 
                                                id="meter_id" name="meter_id" required>
                                            <option value="">Chọn công tơ đo</option>
                                            @foreach($meters as $meter)
                                                <option value="{{ $meter->id }}" 
                                                        {{ old('meter_id', $meterReading->meter_id) == $meter->id ? 'selected' : '' }}>
                                                    {{ $meter->serial_no }} - {{ $meter->property->name }} - {{ $meter->unit->code }} ({{ $meter->service->name }})
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('meter_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="reading_date" class="form-label">
                                            Ngày đo <span class="text-danger">*</span>
                                        </label>
                                        <input type="date" class="form-control @error('reading_date') is-invalid @enderror" 
                                               id="reading_date" name="reading_date" 
                                               value="{{ old('reading_date', $meterReading->reading_date->format('Y-m-d')) }}" required>
                                        @error('reading_date')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="value" class="form-label">
                                            Giá trị đo <span class="text-danger">*</span>
                                        </label>
                                        <input type="number" step="0.001" class="form-control @error('value') is-invalid @enderror" 
                                               id="value" name="value" 
                                               value="{{ old('value', $meterReading->value) }}" 
                                               placeholder="Nhập giá trị đo" required>
                                        @error('value')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <div class="form-text">
                                            <span id="unitLabel">{{ $meterReading->meter->service->unit_label }}</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="image" class="form-label">Hình ảnh công tơ</label>
                                        <input type="file" class="form-control @error('image') is-invalid @enderror" 
                                               id="image" name="image" accept="image/*">
                                        @error('image')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <div class="form-text">Chọn hình ảnh mới để thay thế (tùy chọn)</div>
                                        
                                        @if($meterReading->image_url)
                                            <div class="mt-2">
                                                <label class="form-label">Hình ảnh hiện tại:</label>
                                                <div>
                                                    <img src="{{ Storage::url($meterReading->image_url) }}" 
                                                         alt="Hình ảnh công tơ" 
                                                         class="img-thumbnail" 
                                                         style="max-width: 200px; max-height: 150px;">
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="note" class="form-label">Ghi chú</label>
                                <textarea class="form-control @error('note') is-invalid @enderror" 
                                          id="note" name="note" rows="3" 
                                          placeholder="Nhập ghi chú (tùy chọn)">{{ old('note', $meterReading->note) }}</textarea>
                                @error('note')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
                
                {{-- Cột phải: Sidebar (col-lg-4) --}}
                <div class="col-lg-4">
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
                                        'label' => 'Cập nhật số liệu đo',
                                        'icon' => 'fas fa-save'
                                    ],
                                    [
                                        'type' => 'link',
                                        'variant' => 'secondary',
                                        'label' => 'Hủy',
                                        'icon' => 'fas fa-times',
                                        'url' => route('staff.meter-readings.show', $meterReading->id)
                                    ]
                                ]
                            ])
                        </div>
                    </div>
                    
                    {{-- Card Thông tin hiện tại --}}
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-info-circle me-2"></i>Thông tin hiện tại
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Công tơ:</label>
                                <p class="mb-0">{{ $meterReading->meter->serial_no }}</p>
                                <small class="text-muted">{{ $meterReading->meter->property->name }} - {{ $meterReading->meter->unit->code }}</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Dịch vụ:</label>
                                <p class="mb-0">{{ $meterReading->meter->service->name }} ({{ $meterReading->meter->service->key_code }})</p>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Ngày đo:</label>
                                <p class="mb-0">{{ $meterReading->reading_date->format('d/m/Y') }}</p>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Giá trị đo:</label>
                                <p class="mb-0">{{ number_format($meterReading->value, 3) }} {{ $meterReading->meter->service->unit_label }}</p>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Người đo:</label>
                                <p class="mb-0">{{ $meterReading->takenBy->name ?? 'N/A' }}</p>
                            </div>
                        </div>
                    </div>

                    {{-- Card So sánh --}}
                    @if($previousReading || $nextReading)
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-chart-line me-2"></i>So sánh
                            </h6>
                        </div>
                        <div class="card-body">
                            @if($previousReading)
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Lần đo trước:</label>
                                    <p class="mb-0">{{ number_format($previousReading->value, 3) }} {{ $meterReading->meter->service->unit_label }}</p>
                                    <small class="text-muted">{{ $previousReading->reading_date->format('d/m/Y') }}</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Mức sử dụng:</label>
                                    <p class="mb-0">
                                        <span class="badge bg-info">
                                            {{ number_format($meterReading->value - $previousReading->value, 3) }} {{ $meterReading->meter->service->unit_label }}
                                        </span>
                                    </p>
                                </div>
                            @endif
                            
                            @if($nextReading)
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Lần đo sau:</label>
                                    <p class="mb-0">{{ number_format($nextReading->value, 3) }} {{ $meterReading->meter->service->unit_label }}</p>
                                    <small class="text-muted">{{ $nextReading->reading_date->format('d/m/Y') }}</small>
                                </div>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </form>
    </div>
</main>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Form submission with loading state
    const form = document.getElementById('edit-meter-reading-form');
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (window.Preloader) {
            window.Preloader.show();
        }
        
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Đang cập nhật...';
        submitBtn.disabled = true;
        
        const formData = new FormData(form);
        
        fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        })
        .then(async response => {
            const data = await response.json();
            
            if (!response.ok) {
                throw new Error(data.message || 'Có lỗi xảy ra');
            }
            
            if (data.success) {
                if (typeof window.Notify !== 'undefined') {
                    Notify.success(data.message || 'Đã cập nhật số liệu đo thành công!', 'Thành công!');
                } else {
                    alert('Đã cập nhật số liệu đo thành công!');
                }
                setTimeout(() => {
                    // Sử dụng redirect từ response, fallback về show page
                    window.location.href = data.redirect || '{{ route("staff.meter-readings.show", $meterReading->id) }}';
                }, 1500);
            } else {
                if (typeof window.Notify !== 'undefined') {
                    Notify.error(data.message || 'Có lỗi xảy ra', 'Lỗi!');
                } else {
                    alert('Có lỗi xảy ra: ' + (data.message || 'Lỗi không xác định'));
                }
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            if (typeof window.Notify !== 'undefined') {
                Notify.error('Có lỗi xảy ra khi cập nhật số liệu đo: ' + error.message, 'Lỗi hệ thống!');
            } else {
                alert('Có lỗi xảy ra khi cập nhật số liệu đo: ' + error.message);
            }
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        })
        .finally(() => {
            if (window.Preloader) {
                window.Preloader.hide();
            }
        });
    });
});
</script>
@endpush
