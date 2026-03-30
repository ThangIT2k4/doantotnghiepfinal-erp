@extends('layouts.staff_dashboard')

@section('title', 'Thêm lead mới')

@section('content')
<main class="main-content">
    <div class="container-fluid">
        <!-- Page Header -->
        @include('staff.components.index-page-header', [
            'title' => 'Thêm lead mới',
            'subtitle' => 'Tạo lead mới trong hệ thống',
            'icon' => 'fas fa-user-plus',
            'actions' => [
                [
                    'variant' => 'secondary',
                    'label' => 'Quay lại',
                    'icon' => 'fas fa-arrow-left',
                    'url' => route('staff.leads.index')
                ]
            ]
        ])

        <!-- Create Form -->
        <form id="create-lead-form" action="{{ route('staff.leads.store') }}" method="POST">
            @csrf
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
                                               value="{{ old('name') }}" required>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="phone" class="form-label">Số điện thoại <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="phone" name="phone" 
                                               value="{{ old('phone') }}" required>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="email" class="form-label"> Email <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control" id="email" name="email" 
                                               value="{{ old('email') }}" required>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="source" class="form-label">Nguồn <span class="text-danger">*</span></label>
                                        <select class="form-select" id="source" name="source" required>
                                            <option value="">Chọn nguồn</option>
                                            <option value="facebook" {{ old('source') == 'facebook' ? 'selected' : '' }}>Facebook</option>
                                            <option value="google" {{ old('source') == 'google' ? 'selected' : '' }}>Google</option>
                                            <option value="zalo" {{ old('source') == 'zalo' ? 'selected' : '' }}>Zalo</option>
                                            <option value="website" {{ old('source') == 'website' ? 'selected' : '' }}>Website</option>
                                            <option value="referral" {{ old('source') == 'referral' ? 'selected' : '' }}>Giới thiệu</option>
                                            <option value="viewing_booking" {{ old('source') == 'viewing_booking' ? 'selected' : '' }}>Đặt lịch xem</option>
                                            <option value="other" {{ old('source') == 'other' ? 'selected' : '' }}>Khác</option>
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
                                               value="{{ old('desired_city') }}">
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    {{-- Trạng thái luôn là 'new' khi tạo mới, không hiển thị trong form --}}
                                    <input type="hidden" name="status" value="new">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="budget_min" class="form-label">Ngân sách tối thiểu (VNĐ)</label>
                                        <input type="text" class="form-control money-input" id="budget_min" name="budget_min" 
                                               value="{{ old('budget_min') }}" placeholder="Ví dụ: 1.000.000">
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="budget_max" class="form-label">Ngân sách tối đa (VNĐ)</label>
                                        <input type="text" class="form-control money-input" id="budget_max" name="budget_max" 
                                               value="{{ old('budget_max') }}" placeholder="Ví dụ: 5.000.000">
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="note" class="form-label">Ghi chú</label>
                                <textarea class="form-control" id="note" name="note" rows="3" 
                                          placeholder="Ghi chú về lead...">{{ old('note') }}</textarea>
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
                                        'label' => 'Lưu',
                                        'icon' => 'fas fa-save'
                                    ],
                                    [
                                        'type' => 'link',
                                        'variant' => 'secondary',
                                        'label' => 'Hủy',
                                        'icon' => 'fas fa-times',
                                        'url' => route('staff.leads.index')
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
                            <div class="alert alert-info">
                                <h6><i class="fas fa-info-circle"></i> Thông tin cần thiết</h6>
                                <ul class="mb-0 small">
                                    <li>Tên khách hàng và số điện thoại là bắt buộc</li>
                                    <li>Số điện thoại phải là duy nhất (nếu có)</li>
                                    <li>Email phải là duy nhất (nếu có)</li>
                                    <li>Ngân sách nhập theo định dạng số</li>
                                </ul>
                            </div>

                            <div class="mt-3">
                                <h6>Trạng thái lead:</h6>
                                <ul class="list-unstyled small">
                                    <li class="mb-1">
                                        <span class="badge bg-primary me-2">Mới</span>
                                        <small class="text-muted">Lead mới được tạo (mặc định)</small>
                                    </li>
                                    <li class="mb-1">
                                        <span class="badge bg-info me-2">Đã liên hệ</span>
                                        <small class="text-muted">Đã gọi điện hoặc nhắn tin</small>
                                    </li>
                                    <li class="mb-1">
                                        <span class="badge bg-warning me-2">Đủ điều kiện</span>
                                        <small class="text-muted">Lead có tiềm năng</small>
                                    </li>
                                    <li class="mb-1">
                                        <span class="badge bg-success me-2">Đã chuyển đổi</span>
                                        <small class="text-muted">Đã mua sản phẩm</small>
                                    </li>
                                </ul>
                                <div class="alert alert-info mt-2 mb-0 small">
                                    <i class="fas fa-info-circle"></i> Trạng thái mặc định khi tạo mới là "Mới". Bạn có thể thay đổi trạng thái sau khi tạo.
                                </div>
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
    const form = document.getElementById('create-lead-form');
    if (!form) return;
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Unformat number inputs before submission
        if (window.NumberFormatter && window.NumberFormatter.processForm) {
            window.NumberFormatter.processForm(form);
        }
        
        // Show preloader
        if (window.Preloader) {
            window.Preloader.show();
        }
        
        const formData = new FormData(this);
        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        if (!csrfToken) {
            console.error('CSRF token not found');
            Notify.error('Lỗi bảo mật: Không tìm thấy CSRF token. Vui lòng tải lại trang và thử lại.', 'Lỗi bảo mật!');
            if (window.Preloader) {
                window.Preloader.hide();
            }
            return;
        }

        fetch(this.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': csrfToken.getAttribute('content'),
                'Accept': 'application/json'
            }
        })
        .then(async response => {
            const data = await response.json();
            
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
                        // Laravel validation errors
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
                } else {
                    // Other errors (403, 500, etc.)
                    Notify.error(data.message || 'Có lỗi xảy ra. Vui lòng thử lại sau.', 'Lỗi hệ thống!');
                }
                return;
            }
            
            // Success response
            if (data.success) {
                Notify.success(data.message || 'Lead đã được tạo thành công!', 'Thành công!');
                setTimeout(() => {
                    if (data.redirect) {
                        window.location.href = data.redirect;
                    } else if (data.lead && data.lead.id) {
                        window.location.href = '{{ route("staff.leads.show", ":id") }}'.replace(':id', data.lead.id);
                    } else {
                        window.location.href = '{{ route("staff.leads.index") }}';
                    }
                }, 1500);
            } else {
                Notify.error(data.message || 'Có lỗi xảy ra', 'Lỗi!');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Notify.error('Không thể tạo lead: ' + error.message + '. Vui lòng thử lại sau hoặc liên hệ Admin để được hỗ trợ.', 'Lỗi hệ thống!');
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
