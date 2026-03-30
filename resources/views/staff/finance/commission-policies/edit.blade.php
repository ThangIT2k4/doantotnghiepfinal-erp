@extends('layouts.staff_dashboard')

@section('title', 'Chỉnh sửa Chính sách Hoa hồng')

@section('content')
<main class="main-content">
    <div class="container-fluid">
        <!-- Page Header -->
        @include('staff.components.index-page-header', [
            'title' => 'Chỉnh sửa Chính sách Hoa hồng',
            'subtitle' => $commissionPolicy->title,
            'icon' => 'fas fa-edit',
            'actions' => [
                [
                    'variant' => 'secondary',
                    'label' => 'Quay lại',
                    'icon' => 'fas fa-arrow-left',
                    'url' => route('staff.commission-policies.index')
                ],
                [
                    'variant' => 'info',
                    'label' => 'Xem chi tiết',
                    'icon' => 'fas fa-eye',
                    'url' => route('staff.commission-policies.show', $commissionPolicy->id)
                ]
            ]
        ])

        <form id="edit-commission-policy-form" method="POST" action="{{ route('staff.commission-policies.update', $commissionPolicy->id) }}">
            @csrf
            @method('PUT')
            <div class="row">
                <!-- Basic Information -->
                <div class="col-lg-8">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-info-circle me-2"></i>Thông tin cơ bản
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="code" class="form-label">
                                        Mã chính sách <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" 
                                           class="form-control @error('code') is-invalid @enderror" 
                                           id="code" 
                                           name="code" 
                                           value="{{ old('code', $commissionPolicy->code) }}" 
                                           required>
                                    @error('code')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="title" class="form-label">
                                        Tên chính sách <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" 
                                           class="form-control @error('title') is-invalid @enderror" 
                                           id="title" 
                                           name="title" 
                                           value="{{ old('title', $commissionPolicy->title) }}" 
                                           required>
                                    @error('title')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="trigger_event" class="form-label">
                                        Sự kiện kích hoạt <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select @error('trigger_event') is-invalid @enderror" 
                                            id="trigger_event" 
                                            name="trigger_event" 
                                            required>
                                        <option value="">Chọn sự kiện kích hoạt</option>
                                        @foreach($triggerEvents as $key => $label)
                                            <option value="{{ $key }}" {{ old('trigger_event', $commissionPolicy->trigger_event) == $key ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('trigger_event')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="basis" class="form-label">
                                        Cơ sở tính toán <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select @error('basis') is-invalid @enderror" 
                                            id="basis" 
                                            name="basis" 
                                            required>
                                        <option value="">Chọn cơ sở tính toán</option>
                                        @foreach($basisTypes as $key => $label)
                                            <option value="{{ $key }}" {{ old('basis', $commissionPolicy->basis) == $key ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('basis')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="calc_type" class="form-label">
                                        Loại tính toán <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select @error('calc_type') is-invalid @enderror" 
                                            id="calc_type" 
                                            name="calc_type" 
                                            required>
                                        <option value="">Chọn loại tính toán</option>
                                        @foreach($calcTypes as $key => $label)
                                            <option value="{{ $key }}" {{ old('calc_type', $commissionPolicy->calc_type) == $key ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('calc_type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Trạng thái</label>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="active" value="1" 
                                               id="active" {{ old('active', $commissionPolicy->active) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="active">Hoạt động</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Calculation Settings -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-calculator me-2"></i>Cài đặt tính toán
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3" id="percent_value_field" style="display: none;">
                                    <label for="percent_value" class="form-label">Phần trăm (%)</label>
                                    <input type="number" 
                                           class="form-control @error('percent_value') is-invalid @enderror" 
                                           id="percent_value" 
                                           name="percent_value" 
                                           value="{{ old('percent_value', $commissionPolicy->percent_value) }}" 
                                           min="0" max="100" step="0.01">
                                    @error('percent_value')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6 mb-3" id="flat_amount_field" style="display: none;">
                                    <label for="flat_amount" class="form-label">Số tiền cố định (VND)</label>
                                    <input type="text" 
                                           class="form-control money-input @error('flat_amount') is-invalid @enderror" 
                                           id="flat_amount" 
                                           name="flat_amount" 
                                           value="{{ old('flat_amount', $commissionPolicy->flat_amount ? number_format($commissionPolicy->flat_amount, 0, ',', '.') : '') }}" 
                                           placeholder="VD: 1.000.000" min="0">
                                    @error('flat_amount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="apply_limit_months" class="form-label">Số tháng áp dụng</label>
                                    <input type="number" 
                                           class="form-control @error('apply_limit_months') is-invalid @enderror" 
                                           id="apply_limit_months" 
                                           name="apply_limit_months" 
                                           value="{{ old('apply_limit_months', $commissionPolicy->apply_limit_months) }}" 
                                           min="1" max="12">
                                    @error('apply_limit_months')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="min_amount" class="form-label">Số tiền tối thiểu (VND)</label>
                                    <input type="text" 
                                           class="form-control money-input @error('min_amount') is-invalid @enderror" 
                                           id="min_amount" 
                                           name="min_amount" 
                                           value="{{ old('min_amount', $commissionPolicy->min_amount ? number_format($commissionPolicy->min_amount, 0, ',', '.') : '') }}" 
                                           placeholder="VD: 100.000" min="0">
                                    @error('min_amount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="cap_amount" class="form-label">Số tiền tối đa (VND)</label>
                                    <input type="text" 
                                           class="form-control money-input @error('cap_amount') is-invalid @enderror" 
                                           id="cap_amount" 
                                           name="cap_amount" 
                                           value="{{ old('cap_amount', $commissionPolicy->cap_amount ? number_format($commissionPolicy->cap_amount, 0, ',', '.') : '') }}" 
                                           placeholder="VD: 10.000.000" min="0">
                                    @error('cap_amount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="col-lg-4">
                    <!-- Actions -->
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
                                        'label' => 'Cập nhật',
                                        'icon' => 'fas fa-save',
                                        'iconPosition' => 'left',
                                        'class' => 'w-100'
                                    ],
                                    [
                                        'type' => 'link',
                                        'variant' => 'secondary',
                                        'label' => 'Hủy',
                                        'icon' => 'fas fa-times',
                                        'iconPosition' => 'left',
                                        'url' => route('staff.commission-policies.show', $commissionPolicy->id),
                                        'class' => 'w-100'
                                    ]
                                ]
                            ])
                        </div>
                    </div>
                    
                    <!-- Current Information -->
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-info-circle me-2"></i>Thông tin hiện tại
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label fw-bold small text-muted mb-1">Ngày tạo:</label>
                                <div class="p-2 bg-light rounded">
                                    <i class="fas fa-calendar-plus me-1 text-muted"></i>
                                    {{ $commissionPolicy->created_at->format('d/m/Y H:i:s') }}
                                </div>
                            </div>
                            <div class="mb-0">
                                <label class="form-label fw-bold small text-muted mb-1">Cập nhật lần cuối:</label>
                                <div class="p-2 bg-light rounded">
                                    <i class="fas fa-calendar-edit me-1 text-muted"></i>
                                    {{ $commissionPolicy->updated_at->format('d/m/Y H:i:s') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</main>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/number-formatter.js') }}"></script>
<script>
// Show/hide calculation fields based on calc_type
document.getElementById('calc_type').addEventListener('change', function() {
    const calcType = this.value;
    const percentField = document.getElementById('percent_value_field');
    const flatField = document.getElementById('flat_amount_field');
    
    // Hide all fields first
    percentField.style.display = 'none';
    flatField.style.display = 'none';
    
    // Show relevant field
    if (calcType === 'percent') {
        percentField.style.display = 'block';
    } else if (calcType === 'flat') {
        flatField.style.display = 'block';
    }
});

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    const calcType = document.getElementById('calc_type').value;
    if (calcType === 'percent') {
        document.getElementById('percent_value_field').style.display = 'block';
    } else if (calcType === 'flat') {
        document.getElementById('flat_amount_field').style.display = 'block';
    }
    
    // Unformat money inputs before form submission
    const form = document.getElementById('edit-commission-policy-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            // Unformat all money inputs
            if (window.NumberFormatter && window.NumberFormatter.processForm) {
                window.NumberFormatter.processForm(form);
            } else {
                // Fallback: manually unformat
                const moneyInputs = form.querySelectorAll('.money-input');
                moneyInputs.forEach(input => {
                    if (input.value && !input.readOnly) {
                        input.value = input.value.replace(/\./g, '');
                    }
                });
            }
        });
        
        // Handle form submission with AJAX
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Show loading
            if (window.Preloader) {
                window.Preloader.show();
            }
            
            const formData = new FormData(form);
            
            fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(async response => {
                const data = await response.json();
                
                if (window.Preloader) {
                    window.Preloader.hide();
                }
                
                if (!response.ok) {
                    throw new Error(data.message || 'Có lỗi xảy ra');
                }
                
                if (data.success) {
                    Notify.success(data.message || 'Đã cập nhật chính sách hoa hồng thành công!', 'Thành công!');
                    setTimeout(() => {
                        // Redirect to show page
                        window.location.href = data.redirect || '{{ route("staff.commission-policies.show", $commissionPolicy->id) }}';
                    }, 1500);
                } else {
                    Notify.error(data.message || 'Có lỗi xảy ra', 'Lỗi!');
                }
            })
            .catch(error => {
                if (window.Preloader) {
                    window.Preloader.hide();
                }
                console.error('Error:', error);
                Notify.error('Không thể cập nhật chính sách hoa hồng: ' + error.message, 'Lỗi hệ thống!');
            });
        });
    }
});
</script>
@endpush
