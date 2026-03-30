@extends('layouts.staff_dashboard')

@section('title', 'Thêm Hợp đồng Thuê Lại')

@section('content')
@if(session('success'))
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Notify.success('{{ session('success') }}');
        });
    </script>
@endif

@if(session('error'))
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Notify.error('{{ session('error') }}');
        });
    </script>
@endif

@if(session('warning'))
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Notify.warning('{{ session('warning') }}');
        });
    </script>
@endif

@if(session('info'))
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Notify.info('{{ session('info') }}');
        });
    </script>
@endif

@if($errors->any())
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            @foreach($errors->all() as $error)
                Notify.error('{{ $error }}');
            @endforeach
        });
    </script>
@endif
<main class="main-content">
    <header class="header">
        <div class="header-content">
            <div class="header-info">
                <h1>Thêm Hợp đồng Thuê Lại</h1>
                <p>Tạo hợp đồng thuê lại mới với chủ bất động sản</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('staff.master-leases.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i>
                    Quay lại
                </a>
            </div>
        </div>
    </header>
    
    <div class="content" id="content">
        <div class="card">
            <div class="card-body">
                <form id="masterLeaseForm" method="POST" action="{{ route('staff.master-leases.store') }}">
                    @csrf
                    
                    <div class="row">
                        <!-- Left Column -->
                        <div class="col-md-6">
                            <h5 class="mb-3">Thông tin cơ bản</h5>
                            
                            <div class="mb-3">
                                <label class="form-label">Mã hợp đồng <span class="text-danger">*</span></label>
                                <input type="text" name="contract_no" class="form-control" value="{{ old('contract_no', $previewContractNo ?? '') }}" readonly style="background-color: #e9ecef; cursor: not-allowed;">
                                <small class="form-text text-muted">Mã hợp đồng được tự động tạo bởi hệ thống</small>
                                @error('contract_no')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Bất động sản <span class="text-danger">*</span></label>
                                <select name="property_id" id="propertySelect" class="form-select" required>
                                    <option value="">Chọn bất động sản</option>
                                    @if(isset($properties) && $properties->count() > 0)
                                        @foreach ($properties as $property)
                                        <option value="{{ $property->id }}" {{ old('property_id', $selectedProperty?->id) == $property->id ? 'selected' : '' }}>
                                            {{ $property->name }}
                                        </option>
                                        @endforeach
                                    @else
                                        <option value="" disabled>Không có bất động sản nào trong tổ chức</option>
                                    @endif
                                </select>
                                @error('property_id')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                                @if(isset($properties))
                                    <small class="form-text text-muted">Tìm thấy {{ $properties->count() }} bất động sản</small>
                                @endif
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Chủ nhà</label>
                                <select name="landlord_user_id" id="landlordSelect" class="form-select">
                                    <option value="">Chọn chủ nhà</option>
                                    @if(isset($landlords) && $landlords->count() > 0)
                                        @foreach ($landlords as $landlord)
                                        <option value="{{ $landlord->id }}" {{ old('landlord_user_id') == $landlord->id ? 'selected' : '' }}>
                                            {{ $landlord->full_name }} ({{ $landlord->phone }})
                                        </option>
                                        @endforeach
                                    @else
                                        <option value="" disabled>Không có chủ nhà nào trong tổ chức</option>
                                    @endif
                                </select>
                                @error('landlord_user_id')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                                @if(isset($landlords))
                                    <small class="form-text text-muted">Tìm thấy {{ $landlords->count() }} chủ nhà</small>
                                @endif
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Ngày bắt đầu <span class="text-danger">*</span></label>
                                        <input type="date" name="start_date" class="form-control" value="{{ old('start_date') }}" required>
                                        @error('start_date')
                                            <div class="text-danger small">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Ngày kết thúc <span class="text-danger">*</span></label>
                                        <input type="date" name="end_date" class="form-control" value="{{ old('end_date') }}" required>
                                        @error('end_date')
                                            <div class="text-danger small">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column -->
                        <div class="col-md-6">
                            <h5 class="mb-3">Thông tin tài chính</h5>
                            
                            <div class="mb-3">
                                <label class="form-label">Tiền thuê cơ bản <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="text" name="base_rent" id="base_rent" class="form-control money-input" value="{{ old('base_rent') ? number_format(old('base_rent'), 0, ',', '.') : '' }}" required>
                                    <select name="rent_currency" class="form-select" style="max-width: 100px;">
                                        <option value="VND" {{ old('rent_currency', 'VND') == 'VND' ? 'selected' : '' }}>VND</option>
                                        <option value="USD" {{ old('rent_currency') == 'USD' ? 'selected' : '' }}>USD</option>
                                    </select>
                                </div>
                                @error('base_rent')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Tiền cọc</label>
                                <input type="text" name="deposit_amount" id="deposit_amount" class="form-control money-input" value="{{ old('deposit_amount') ? number_format(old('deposit_amount'), 0, ',', '.') : '' }}">
                                @error('deposit_amount')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Chu kỳ thanh toán (số tháng) <span class="text-danger">*</span></label>
                                <input type="number" name="billing_cycle" class="form-control" value="{{ old('billing_cycle', 1) }}" min="1" max="120" required>
                                <small class="form-text text-muted">Nhập số tháng cho chu kỳ thanh toán (1 = hàng tháng, 3 = hàng quý, 12 = hàng năm, v.v.)</small>
                                @error('billing_cycle')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Ngày thanh toán</label>
                                        <input type="number" name="billing_day" class="form-control" value="{{ old('billing_day', 5) }}" min="1" max="31">
                                        @error('billing_day')
                                            <div class="text-danger small">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Số ngày đến hạn</label>
                                        <input type="number" name="due_in_days" class="form-control" value="{{ old('due_in_days', 5) }}" min="1" max="365">
                                        @error('due_in_days')
                                            <div class="text-danger small">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Tỷ lệ chia sẻ doanh thu (%)</label>
                                <input type="text" name="revenue_share_pct" id="revenue_share_pct" class="form-control number-input" value="{{ old('revenue_share_pct') ? number_format(old('revenue_share_pct'), 0, ',', '.') : '' }}">
                                @error('revenue_share_pct')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <h5 class="mb-3">Thông tin bổ sung</h5>
                            
                            <div class="mb-3">
                                <label class="form-label">Trạng thái</label>
                                <select name="status" class="form-select">
                                    <option value="draft" {{ old('status', 'draft') == 'draft' ? 'selected' : '' }}>Nháp</option>
                                    <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Hoạt động</option>
                                    <option value="terminated" {{ old('status') == 'terminated' ? 'selected' : '' }}>Chấm dứt</option>
                                    <option value="expired" {{ old('status') == 'expired' ? 'selected' : '' }}>Hết hạn</option>
                                </select>
                                @error('status')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Ghi chú</label>
                                <textarea name="note" class="form-control" rows="4" placeholder="Ghi chú về hợp đồng...">{{ old('note') }}</textarea>
                                @error('note')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    @include('staff.components.form-actions', [
                        'submitLabel' => 'Tạo hợp đồng',
                        'submitIcon' => 'fas fa-save',
                        'submitVariant' => 'primary',
                        'cancelLabel' => 'Hủy',
                        'cancelIcon' => 'fas fa-times',
                        'cancelUrl' => route('staff.master-leases.index')
                    ])
                </form>
            </div>
        </div>
    </div>
</main>

@endsection

@push('scripts')
<script src="{{ asset('assets/js/number-formatter.js') }}"></script>
<script>
$(document).ready(function() {
    // Handle form submission with AJAX
    const form = document.getElementById('masterLeaseForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Unformat number inputs before submission
            if (window.NumberFormatter && window.NumberFormatter.processForm) {
                window.NumberFormatter.processForm(this);
            }
            
            const formData = new FormData(this);
            const action = this.action;
            
            // Show loading - tìm button submit từ component form-actions
            const submitBtn = this.querySelector('.form-action-submit button[type="submit"]') || this.querySelector('button[type="submit"]');
            let originalText = '';
            if (submitBtn) {
                originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Đang tạo...';
                submitBtn.disabled = true;
            }
            
            fetch(action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => {
                if (response.ok) {
                    return response.text();
                }
                throw new Error('Network response was not ok');
            })
            .then(html => {
                // Parse response to check for success/error messages
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const successMessage = doc.querySelector('script')?.textContent?.match(/Notify\.success\('([^']+)'\)/);
                const errorMessage = doc.querySelector('script')?.textContent?.match(/Notify\.error\('([^']+)'\)/);
                
                if (successMessage) {
                    Notify.success(successMessage[1]);
                    // Redirect to index page after 2 seconds
                    setTimeout(() => {
                        window.location.href = '{{ route("staff.master-leases.index") }}';
                    }, 2000);
                } else if (errorMessage) {
                    Notify.error(errorMessage[1]);
                    if (submitBtn && originalText) {
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                    }
                } else {
                    // Fallback: redirect to index
                    window.location.href = '{{ route("staff.master-leases.index") }}';
                }
            })
            .catch(error => {
                Notify.error('Có lỗi xảy ra khi tạo hợp đồng.');
                if (submitBtn && originalText) {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }
            });
        });
    }
});
</script>
@endpush
