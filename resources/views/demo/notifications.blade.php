@extends('layouts.manager_dashboard')

@section('title', 'Demo Notification System')

@section('content')
<main class="main-content">
    <header class="header">
        <div class="header-content">
            <div class="header-info">
                <h1>Demo Notification System</h1>
                <p>Test các loại thông báo và popup xác nhận</p>
            </div>
        </div>
    </header>
    
    <div class="content">
        <div class="row">
            <!-- Toast Notifications -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-bell"></i> Toast Notifications</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button class="btn btn-success" onclick="testSuccessToast()">
                                <i class="fas fa-check"></i> Success Toast
                            </button>
                            <button class="btn btn-danger" onclick="testErrorToast()">
                                <i class="fas fa-times"></i> Error Toast
                            </button>
                            <button class="btn btn-warning" onclick="testWarningToast()">
                                <i class="fas fa-exclamation-triangle"></i> Warning Toast
                            </button>
                            <button class="btn btn-info" onclick="testInfoToast()">
                                <i class="fas fa-info-circle"></i> Info Toast
                            </button>
                            <button class="btn btn-primary" onclick="testAdvancedToast()">
                                <i class="fas fa-cog"></i> Advanced Toast
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Confirmation Popups -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-question-circle"></i> Confirmation Popups</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button class="btn btn-danger" onclick="testDeleteConfirm()">
                                <i class="fas fa-trash"></i> Delete Confirmation
                            </button>
                            <button class="btn btn-primary" onclick="testSaveConfirm()">
                                <i class="fas fa-save"></i> Save Confirmation
                            </button>
                            <button class="btn btn-warning" onclick="testCustomConfirm()">
                                <i class="fas fa-cog"></i> Custom Confirmation
                            </button>
                            <button class="btn btn-info" onclick="testMultiStepConfirm()">
                                <i class="fas fa-list"></i> Multi-step Confirmation
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Real-world Examples -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-code"></i> Real-world Examples</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <h6>Form Submit</h6>
                                <button class="btn btn-primary btn-sm" onclick="simulateFormSubmit()">
                                    Simulate Form Submit
                                </button>
                            </div>
                            <div class="col-md-4">
                                <h6>Delete with Confirmation</h6>
                                <button class="btn btn-danger btn-sm" onclick="simulateDelete()">
                                    Simulate Delete
                                </button>
                            </div>
                            <div class="col-md-4">
                                <h6>Validation Errors</h6>
                                <button class="btn btn-warning btn-sm" onclick="simulateValidationErrors()">
                                    Simulate Validation Errors
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Code Examples -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-code"></i> Code Examples</h5>
                    </div>
                    <div class="card-body">
                        <div class="accordion" id="codeExamples">
                            <!-- Toast Examples -->
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#toastExamples">
                                        Toast Notification Examples
                                    </button>
                                </h2>
                                <div id="toastExamples" class="accordion-collapse collapse show" data-bs-parent="#codeExamples">
                                    <div class="accordion-body">
                                        <pre><code>// Basic toast
Notify.success('Dữ liệu đã được lưu thành công!');

// Advanced toast with actions
Notify.toast({
    title: 'Thành công!',
    message: 'Bất động sản đã được lưu.',
    type: 'success',
    duration: 0,
    actions: [
        {
            text: 'Xem',
            icon: 'fas fa-eye',
            type: 'primary',
            action: 'view',
            handler: (toastId) => {
                window.location.href = '/manager/properties/1';
            }
        }
    ]
});</code></pre>
                                    </div>
                                </div>
                            </div>

                            <!-- Confirmation Examples -->
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#confirmExamples">
                                        Confirmation Examples
                                    </button>
                                </h2>
                                <div id="confirmExamples" class="accordion-collapse collapse" data-bs-parent="#codeExamples">
                                    <div class="accordion-body">
                                        <pre><code>// Quick delete confirmation
Notify.confirmDelete('bất động sản này', () => {
    // Delete logic here
    console.log('Confirmed delete');
});

// Custom confirmation
Notify.confirm({
    title: 'Xác nhận xóa',
    message: 'Bạn có chắc chắn muốn xóa?',
    details: 'Hành động này không thể hoàn tác.',
    type: 'danger',
    confirmText: 'Xóa',
    onConfirm: () => {
        // Delete logic here
    }
});</code></pre>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

@push('scripts')
<script>
// Toast Notification Tests
function testSuccessToast() {
    Notify.success('Dữ liệu đã được lưu thành công!', 'Thành công!');
}

function testErrorToast() {
    Notify.error('Có lỗi xảy ra khi xử lý dữ liệu!', 'Lỗi!');
}

function testWarningToast() {
    Notify.warning('Vui lòng kiểm tra lại thông tin!', 'Cảnh báo!');
}

function testInfoToast() {
    Notify.info('Hệ thống đang cập nhật dữ liệu...', 'Thông tin');
}

function testAdvancedToast() {
    Notify.toast({
        title: 'Đã lưu thành công!',
        message: 'Bất động sản đã được lưu vào hệ thống.',
        type: 'success',
        duration: 10000, // 10 giây
        showProgress: true,
        actions: [
            {
                text: 'Xem chi tiết',
                icon: 'fas fa-eye',
                type: 'primary',
                action: 'view',
                handler: (toastId) => {
                    Notify.info('Chuyển đến trang chi tiết...');
                }
            },
            {
                text: 'Đóng',
                icon: 'fas fa-times',
                type: 'secondary',
                action: 'close',
                handler: (toastId) => {
                    const toast = document.getElementById(toastId);
                    const bsToast = bootstrap.Toast.getInstance(toast);
                    bsToast.hide();
                }
            }
        ]
    });
}

// Confirmation Tests
function testDeleteConfirm() {
    Notify.confirmDelete('bất động sản này', () => {
        Notify.success('Đã xóa thành công!');
    });
}

function testSaveConfirm() {
    Notify.confirmSave(() => {
        Notify.success('Đã lưu thành công!');
    });
}

function testCustomConfirm() {
    Notify.confirm({
        title: 'Xác nhận thay đổi',
        message: 'Bạn có chắc chắn muốn thay đổi trạng thái?',
        details: 'Thay đổi này sẽ ảnh hưởng đến tất cả dữ liệu liên quan.',
        type: 'warning',
        confirmText: 'Thay đổi',
        cancelText: 'Hủy bỏ',
        onConfirm: () => {
            Notify.success('Đã thay đổi trạng thái!');
        },
        onCancel: () => {
            Notify.info('Đã hủy thay đổi.');
        }
    });
}

function testMultiStepConfirm() {
    Notify.confirm({
        title: 'Xác nhận nhiều bước',
        message: 'Bước 1: Xóa dữ liệu cũ',
        details: 'Sau khi xóa, bạn sẽ cần nhập lại dữ liệu mới.',
        type: 'danger',
        confirmText: 'Tiếp tục',
        onConfirm: () => {
            Notify.confirm({
                title: 'Bước 2: Xác nhận cuối cùng',
                message: 'Bạn có chắc chắn muốn tiếp tục?',
                details: 'Đây là bước cuối cùng và không thể hoàn tác.',
                type: 'danger',
                confirmText: 'Xác nhận',
                onConfirm: () => {
                    Notify.success('Đã hoàn thành tất cả các bước!');
                }
            });
        }
    });
}

// Real-world Examples
function simulateFormSubmit() {
    // Show preloader
    if (window.Preloader) {
        window.Preloader.show();
    }
    
    // Simulate API call
    setTimeout(() => {
        if (window.Preloader) {
            window.Preloader.hide();
        }
        
        // Random success/error
        if (Math.random() > 0.3) {
            Notify.success('Dữ liệu đã được lưu thành công!', 'Thành công!');
        } else {
            Notify.error('Có lỗi xảy ra khi lưu dữ liệu!', 'Lỗi!');
        }
    }, 2000);
}

function simulateDelete() {
    Notify.confirmDelete('bất động sản này', () => {
        if (window.Preloader) {
            window.Preloader.show();
        }
        
        setTimeout(() => {
            if (window.Preloader) {
                window.Preloader.hide();
            }
            Notify.success('Đã xóa thành công!', 'Hoàn thành!');
        }, 1500);
    });
}

function simulateValidationErrors() {
    const errors = {
        name: ['Tên không được để trống'],
        price: ['Giá phải là số dương', 'Giá không được vượt quá 1 tỷ'],
        location: ['Địa chỉ không hợp lệ']
    };
    
    const errorList = Object.values(errors).flat().join('<br>');
    
    Notify.toast({
        title: 'Lỗi xác thực',
        message: errorList,
        type: 'error',
        duration: 12000, // 12 giây cho lỗi validation
        showProgress: true
    });
}
</script>
@endpush
@endsection
