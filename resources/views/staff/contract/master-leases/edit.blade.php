@extends('layouts.staff_dashboard')

@section('title', 'Chỉnh sửa Hợp đồng Thuê Lại')

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
                <h1>Chỉnh sửa Hợp đồng Thuê Lại</h1>
                <p>Cập nhật thông tin hợp đồng thuê lại</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('staff.master-leases.show', $masterLease->id) }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i>
                    Quay lại
                </a>
            </div>
        </div>
    </header>
    
    <div class="content" id="content">
        <div class="card">
            <div class="card-body">
                <form id="masterLeaseForm" method="POST" action="{{ route('staff.master-leases.update', $masterLease->id) }}">
                    @csrf
                    @method('PUT')
                    
                    <div class="row">
                        <!-- Left Column -->
                        <div class="col-md-6">
                            <h5 class="mb-3">Thông tin cơ bản</h5>
                            
                            <div class="mb-3">
                                <label class="form-label">Bất động sản <span class="text-danger">*</span></label>
                                <select name="property_id" id="propertySelect" class="form-select" required>
                                    <option value="">Chọn bất động sản</option>
                                    @foreach ($properties as $property)
                                    <option value="{{ $property->id }}" {{ old('property_id', $masterLease->property_id) == $property->id ? 'selected' : '' }}>
                                        {{ $property->name }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('property_id')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Chủ nhà</label>
                                <select name="landlord_user_id" id="landlordSelect" class="form-select">
                                    <option value="">Chọn chủ nhà</option>
                                    @foreach ($landlords as $landlord)
                                    <option value="{{ $landlord->id }}" {{ old('landlord_user_id', $masterLease->landlord_user_id) == $landlord->id ? 'selected' : '' }}>
                                        {{ $landlord->full_name }} ({{ $landlord->phone }})
                                    </option>
                                    @endforeach
                                </select>
                                @error('landlord_user_id')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Số hợp đồng</label>
                                <input type="text" name="contract_no" class="form-control" value="{{ old('contract_no', $masterLease->contract_no) }}" placeholder="Để trống để tự động tạo">
                                @error('contract_no')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Ngày bắt đầu <span class="text-danger">*</span></label>
                                        <input type="date" name="start_date" class="form-control" value="{{ old('start_date', $masterLease->start_date->format('Y-m-d')) }}" required>
                                        @error('start_date')
                                            <div class="text-danger small">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Ngày kết thúc <span class="text-danger">*</span></label>
                                        <input type="date" name="end_date" class="form-control" value="{{ old('end_date', $masterLease->end_date->format('Y-m-d')) }}" required>
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
                                    <input type="text" name="base_rent" id="base_rent" class="form-control money-input" value="{{ old('base_rent', number_format($masterLease->base_rent, 0, ',', '.')) }}" required>
                                    <select name="rent_currency" class="form-select" style="max-width: 100px;">
                                        <option value="VND" {{ old('rent_currency', $masterLease->rent_currency) == 'VND' ? 'selected' : '' }}>VND</option>
                                        <option value="USD" {{ old('rent_currency', $masterLease->rent_currency) == 'USD' ? 'selected' : '' }}>USD</option>
                                    </select>
                                </div>
                                @error('base_rent')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Tiền cọc</label>
                                <input type="text" name="deposit_amount" id="deposit_amount" class="form-control money-input" value="{{ old('deposit_amount', number_format($masterLease->deposit_amount ?? 0, 0, ',', '.')) }}">
                                @error('deposit_amount')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Chu kỳ thanh toán (số tháng) <span class="text-danger">*</span></label>
                                <input type="number" name="billing_cycle" class="form-control" value="{{ old('billing_cycle', $masterLease->billing_cycle) }}" min="1" max="120" required>
                                <small class="form-text text-muted">Nhập số tháng cho chu kỳ thanh toán (1 = hàng tháng, 3 = hàng quý, 12 = hàng năm, v.v.)</small>
                                @error('billing_cycle')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Ngày thanh toán</label>
                                        <input type="number" name="billing_day" class="form-control" value="{{ old('billing_day', $masterLease->billing_day) }}" min="1" max="31">
                                        @error('billing_day')
                                            <div class="text-danger small">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Số ngày đến hạn</label>
                                        <input type="number" name="due_in_days" class="form-control" value="{{ old('due_in_days', $masterLease->due_in_days) }}" min="1" max="365">
                                        @error('due_in_days')
                                            <div class="text-danger small">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Tỷ lệ chia sẻ doanh thu (%)</label>
                                <input type="text" name="revenue_share_pct" id="revenue_share_pct" class="form-control number-input" value="{{ old('revenue_share_pct', $masterLease->revenue_share_pct ? number_format($masterLease->revenue_share_pct, 0, ',', '.') : '') }}">
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
                                    <option value="draft" {{ old('status', $masterLease->status) == 'draft' ? 'selected' : '' }}>Nháp</option>
                                    <option value="active" {{ old('status', $masterLease->status) == 'active' ? 'selected' : '' }}>Hoạt động</option>
                                    <option value="terminated" {{ old('status', $masterLease->status) == 'terminated' ? 'selected' : '' }}>Chấm dứt</option>
                                    <option value="expired" {{ old('status', $masterLease->status) == 'expired' ? 'selected' : '' }}>Hết hạn</option>
                                </select>
                                @error('status')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Ghi chú</label>
                                <textarea name="note" class="form-control" rows="4" placeholder="Ghi chú về hợp đồng...">{{ old('note', $masterLease->note) }}</textarea>
                                @error('note')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('staff.master-leases.show', $masterLease->id) }}" class="btn btn-outline-secondary">
                            <i class="fas fa-times"></i>
                            Hủy
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            Cập nhật hợp đồng
                        </button>
                    </div>
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
            
            // Show loading
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang cập nhật...';
            submitBtn.disabled = true;
            
            fetch(action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8'
                }
            })
            .then(response => {
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    return response.json();
                } else {
                    return response.text();
                }
            })
            .then(data => {
                if (typeof data === 'object') {
                    // JSON response
                    if (data.success) {
                        Notify.success(data.message);
                        if (data.redirect) {
                            setTimeout(() => {
                                window.location.href = data.redirect;
                            }, 2000);
                        } else {
                            setTimeout(() => {
                                window.location.href = '{{ route("staff.master-leases.show", $masterLease->id) }}';
                            }, 2000);
                        }
                    } else {
                        Notify.error(data.message);
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                    }
                } else {
                    // HTML response - parse for success/error messages
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(data, 'text/html');
                    const successMessage = doc.querySelector('script')?.textContent?.match(/Notify\.success\('([^']+)'\)/);
                    const errorMessage = doc.querySelector('script')?.textContent?.match(/Notify\.error\('([^']+)'\)/);
                    
                    if (successMessage) {
                        Notify.success(successMessage[1]);
                        setTimeout(() => {
                            window.location.href = '{{ route("staff.master-leases.show", $masterLease->id) }}';
                        }, 2000);
                    } else if (errorMessage) {
                        Notify.error(errorMessage[1]);
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                    } else {
                        // Fallback: redirect to show page
                        window.location.href = '{{ route("staff.master-leases.show", $masterLease->id) }}';
                    }
                }
            })
            .catch(error => {
                Notify.error('Có lỗi xảy ra khi cập nhật hợp đồng.');
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        });
    }
});
</script>
@endpush
