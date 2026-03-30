@extends('layouts.staff_dashboard')

@section('title', 'Sửa thông tin lead')

@section('content')
<main class="main-content">
    <div class="container-fluid">
        <!-- Page Header -->
        @include('staff.components.index-page-header', [
            'title' => 'Sửa thông tin lead',
            'subtitle' => 'Cập nhật thông tin lead: ' . $lead->name,
            'icon' => 'fas fa-user-edit',
            'actions' => [
                [
                    'variant' => 'secondary',
                    'label' => 'Quay lại',
                    'icon' => 'fas fa-arrow-left',
                    'url' => route('staff.leads.index')
                ],
                [
                    'variant' => 'info',
                    'label' => 'Xem chi tiết',
                    'icon' => 'fas fa-eye',
                    'url' => route('staff.leads.show', $lead->id)
                ]
            ]
        ])

        <!-- Edit Form -->
        <form id="edit-lead-form" action="{{ route('staff.leads.update', $lead->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="row">
                {{-- Cột trái: Form chính (col-lg-8) --}}
                <div class="col-lg-8">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-info-circle me-2"></i>Thông tin lead
                            </h6>
                        </div>
                        <div class="card-body">
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="name" class="form-label">Tên khách hàng <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="name" name="name" 
                                               value="{{ old('name', $lead->name) }}" required>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="phone" class="form-label">Số điện thoại <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="phone" name="phone" 
                                               value="{{ old('phone', $lead->phone) }}" required>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="email" name="email" 
                                               value="{{ old('email', $lead->email) }}">
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="source" class="form-label">Nguồn <span class="text-danger">*</span></label>
                                        <select class="form-select" id="source" name="source" required>
                                            <option value="">Chọn nguồn</option>
                                            <option value="facebook" {{ old('source', $lead->source) == 'facebook' ? 'selected' : '' }}>Facebook</option>
                                            <option value="google" {{ old('source', $lead->source) == 'google' ? 'selected' : '' }}>Google</option>
                                            <option value="zalo" {{ old('source', $lead->source) == 'zalo' ? 'selected' : '' }}>Zalo</option>
                                            <option value="website" {{ old('source', $lead->source) == 'website' ? 'selected' : '' }}>Website</option>
                                            <option value="referral" {{ old('source', $lead->source) == 'referral' ? 'selected' : '' }}>Giới thiệu</option>
                                            <option value="viewing_booking" {{ old('source', $lead->source) == 'viewing_booking' ? 'selected' : '' }}>Đặt lịch xem</option>
                                            <option value="other" {{ old('source', $lead->source) == 'other' ? 'selected' : '' }}>Khác</option>
                                        </select>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="desired_city" class="form-label">Thành phố mong muốn</label>
                                        <input type="text" class="form-control" id="desired_city" name="desired_city" 
                                               value="{{ old('desired_city', $lead->desired_city) }}">
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="status" class="form-label">Trạng thái</label>
                                        <select class="form-select" id="status" name="status">
                                            <option value="new" {{ old('status', $lead->status) == 'new' ? 'selected' : '' }}>Mới</option>
                                            <option value="contacted" {{ old('status', $lead->status) == 'contacted' ? 'selected' : '' }}>Đã liên hệ</option>
                                            <option value="qualified" {{ old('status', $lead->status) == 'qualified' ? 'selected' : '' }}>Đủ điều kiện</option>
                                            <option value="proposal" {{ old('status', $lead->status) == 'proposal' ? 'selected' : '' }}>Đề xuất</option>
                                            <option value="negotiation" {{ old('status', $lead->status) == 'negotiation' ? 'selected' : '' }}>Thương lượng</option>
                                            <option value="converted" {{ old('status', $lead->status) == 'converted' ? 'selected' : '' }}>Đã chuyển đổi</option>
                                            <option value="lost" {{ old('status', $lead->status) == 'lost' ? 'selected' : '' }}>Mất</option>
                                        </select>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="budget_min" class="form-label">Ngân sách tối thiểu (VNĐ)</label>
                                        <input type="text" class="form-control money-input" id="budget_min" name="budget_min" 
                                               value="{{ old('budget_min', $lead->budget_min ? number_format($lead->budget_min, 0, ',', '.') : '') }}" 
                                               placeholder="Ví dụ: 1.000.000">
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="budget_max" class="form-label">Ngân sách tối đa (VNĐ)</label>
                                        <input type="text" class="form-control money-input" id="budget_max" name="budget_max" 
                                               value="{{ old('budget_max', $lead->budget_max ? number_format($lead->budget_max, 0, ',', '.') : '') }}" 
                                               placeholder="Ví dụ: 5.000.000">
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="note" class="form-label">Ghi chú</label>
                                <textarea class="form-control" id="note" name="note" rows="3" 
                                          placeholder="Ghi chú về lead...">{{ old('note', $lead->note) }}</textarea>
                                <div class="invalid-feedback"></div>
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
                                        'label' => 'Cập nhật',
                                        'icon' => 'fas fa-save'
                                    ],
                                    [
                                        'type' => 'link',
                                        'variant' => 'secondary',
                                        'label' => 'Hủy',
                                        'icon' => 'fas fa-times',
                                        'url' => route('staff.leads.show', $lead->id)
                                    ]
                                ]
                            ])
                        </div>
                    </div>
                    
                    {{-- Card Thông tin hiện tại --}}
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-info-circle me-2"></i>Thông tin hiện tại
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <h6 class="mb-0">{{ $lead->name }}</h6>
                                <small class="text-muted">{{ $lead->phone }}</small>
                            </div>

                            <div class="mb-3">
                                <h6>Nguồn hiện tại:</h6>
                                <span class="badge bg-info">{{ ucfirst($lead->source) }}</span>
                            </div>

                            <div class="mb-3">
                                <h6>Trạng thái:</h6>
                                @include('staff.components.status-badge', [
                                    'status' => $lead->status,
                                    'type' => 'lead'
                                ])
                            </div>

                            <div class="mb-3">
                                <h6>Thông tin khác:</h6>
                                <ul class="list-unstyled mb-0 small">
                                    <li><small class="text-muted">ID: #{{ $lead->id }}</small></li>
                                    <li><small class="text-muted">Tạo lúc: {{ $lead->created_at->format('d/m/Y H:i') }}</small></li>
                                    @if($lead->email)
                                        <li><small class="text-muted">Email: {{ $lead->email }}</small></li>
                                    @endif
                                    @if($lead->desired_city)
                                        <li><small class="text-muted">Thành phố: {{ $lead->desired_city }}</small></li>
                                    @endif
                                    @if($lead->budget_min || $lead->budget_max)
                                        <li><small class="text-muted">Ngân sách: 
                                            @if($lead->budget_min && $lead->budget_max)
                                                {{ number_format($lead->budget_min) }} - {{ number_format($lead->budget_max) }} VNĐ
                                            @elseif($lead->budget_min)
                                                Từ {{ number_format($lead->budget_min) }} VNĐ
                                            @elseif($lead->budget_max)
                                                Đến {{ number_format($lead->budget_max) }} VNĐ
                                            @endif
                                        </small></li>
                                    @endif
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</main>

@push('scripts')
<script src="{{ asset('assets/js/number-formatter.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('edit-lead-form');
    if (!form) return;
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Show preloader
        if (window.Preloader) {
            window.Preloader.show();
        }
        
        // Prepare sanitized values (do NOT mutate DOM values)
        const budgetMinInput = form.querySelector('input[name="budget_min"]');
        const budgetMaxInput = form.querySelector('input[name="budget_max"]');
        const budgetMinSanitized = (budgetMinInput && window.NumberFormatter && window.NumberFormatter.unformat)
            ? window.NumberFormatter.unformat(budgetMinInput.value) : (budgetMinInput ? budgetMinInput.value : '');
        const budgetMaxSanitized = (budgetMaxInput && window.NumberFormatter && window.NumberFormatter.unformat)
            ? window.NumberFormatter.unformat(budgetMaxInput.value) : (budgetMaxInput ? budgetMaxInput.value : '');

        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        if (!csrfToken) {
            console.error('CSRF token not found');
            Notify.error('Lỗi bảo mật: Không tìm thấy CSRF token. Vui lòng tải lại trang và thử lại.', 'Lỗi bảo mật!');
            if (window.Preloader) {
                window.Preloader.hide();
            }
            return;
        }

        // Build JSON payload with only allowed fields
        const body = {
            source: (form.querySelector('[name="source"]')?.value ?? '').toString(),
            name: (form.querySelector('[name="name"]')?.value ?? '').toString(),
            phone: (form.querySelector('[name="phone"]')?.value ?? '').toString(),
            email: (form.querySelector('[name="email"]')?.value ?? '').toString(),
            desired_city: (form.querySelector('[name="desired_city"]')?.value ?? '').toString(),
            budget_min: (budgetMinSanitized ?? '').toString(),
            budget_max: (budgetMaxSanitized ?? '').toString(),
            note: (form.querySelector('[name="note"]')?.value ?? '').toString(),
            status: (form.querySelector('[name="status"]')?.value ?? '').toString(),
        };

        // Log payload (debug)
        console.log('Payload being sent (JSON):', body);

        // Build clean URL (strip dangerous query params if present)
        const dangerousParams = ['organization_id', 'user_organization_id', 'org_id'];
        const requestUrl = new URL(this.action, window.location.origin);
        dangerousParams.forEach(param => requestUrl.searchParams.delete(param));

        fetch(requestUrl.toString(), {
            method: 'PUT',
            body: JSON.stringify(body),
            headers: {
                'X-CSRF-TOKEN': csrfToken.getAttribute('content'),
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json; charset=UTF-8'
            },
            credentials: 'same-origin'
        })
        .then(async response => {
            // Try to parse JSON response
            let data;
            try {
                const text = await response.text();
                data = text ? JSON.parse(text) : {};
            } catch (parseError) {
                console.error('JSON parse error:', parseError);
                data = { message: 'Không thể phân tích phản hồi từ server' };
            }
            
            if (!response.ok) {
                // Handle validation errors (422) or other errors
                if (response.status === 422) {
                    // Clear previous validation errors
                    form.querySelectorAll('.is-invalid').forEach(el => {
                        el.classList.remove('is-invalid');
                    });
                    form.querySelectorAll('.invalid-feedback').forEach(el => {
                        el.textContent = '';
                    });
                    
                    // Display validation errors
                    if (data.errors) {
                        Object.keys(data.errors).forEach(field => {
                            const input = form.querySelector(`[name="${field}"]`);
                            if (input) {
                                input.classList.add('is-invalid');
                                const feedback = input.parentElement.querySelector('.invalid-feedback');
                                if (feedback) {
                                    feedback.textContent = Array.isArray(data.errors[field]) 
                                        ? data.errors[field][0] 
                                        : data.errors[field];
                                }
                            }
                        });
                    }
                    
                    // Show error message
                    Notify.error(data.message || 'Dữ liệu không hợp lệ. Vui lòng kiểm tra lại các trường được đánh dấu.', 'Lỗi xác thực!');
                } else if (response.status === 403) {
                    // Permission denied
                    Notify.error(data.message || 'Bạn không có quyền thực hiện hành động này.', 'Lỗi quyền truy cập!');
                } else if (response.status === 404) {
                    // Not found
                    Notify.error(data.message || 'Không tìm thấy lead này.', 'Lỗi!');
                } else if (response.status === 500) {
                    // Server error
                    Notify.error(data.message || 'Lỗi máy chủ. Vui lòng thử lại sau.', 'Lỗi hệ thống!');
                } else {
                    // Other errors
                    Notify.error(data.message || `Có lỗi xảy ra (HTTP ${response.status}). Vui lòng thử lại sau.`, 'Lỗi hệ thống!');
                }
                return;
            }
            
            // Success response
            if (data.success) {
                Notify.success(data.message || 'Lead đã được cập nhật thành công!', 'Thành công!');
                setTimeout(() => {
                    window.location.href = data.redirect || '{{ route("staff.leads.show", $lead->id) }}';
                }, 1500);
            } else {
                Notify.error(data.message || 'Có lỗi xảy ra', 'Lỗi!');
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            Notify.error('Không thể kết nối đến server. Vui lòng kiểm tra kết nối mạng và thử lại.', 'Lỗi kết nối!');
            // Restore original values on error
            if (budgetMinInput) budgetMinInput.value = originalBudgetMin;
            if (budgetMaxInput) budgetMaxInput.value = originalBudgetMax;
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
@endsection
