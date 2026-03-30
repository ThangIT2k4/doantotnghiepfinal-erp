<div class="row">
    <div class="col-12">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="card-title mb-0">
                    <i class="fas fa-list me-2"></i>
                    Danh sách dịch vụ
                </h6>
                <div class="btn-group">
                    <button type="button" class="btn btn-danger btn-sm" onclick="deleteUnusedServices()" title="Xóa các dịch vụ không được sử dụng">
                        <i class="fas fa-trash-alt me-1"></i>
                        Xóa dịch vụ không dùng
                    </button>
                    <a href="{{ route('staff.services.create') }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus me-1"></i>
                        Thêm dịch vụ
                    </a>
                </div>
            </div>
            <div class="card-body">
                @if($services->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Mã dịch vụ</th>
                                    <th>Tên dịch vụ</th>
                                    <th>Phạm vi</th>
                                    <th>Loại giá</th>
                                    <th>Đơn vị</th>
                                    <th>Mô tả</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($services as $service)
                                <tr>
                                    <td>
                                        @if($service->key_code)
                                            <code>{{ $service->key_code }}</code>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td><strong>{{ $service->name }}</strong></td>
                                    <td>
                                        @if($service->organization_id)
                                            @if($service->organization)
                                                <span class="badge bg-primary" title="Dịch vụ riêng của tổ chức: {{ $service->organization->name }}">
                                                    <i class="fas fa-building me-1"></i>
                                                    {{ Str::limit($service->organization->name, 20) }}
                                                </span>
                                            @else
                                                <span class="badge bg-primary" title="Dịch vụ riêng của tổ chức">
                                                    <i class="fas fa-building me-1"></i>
                                                    Tổ chức
                                                </span>
                                            @endif
                                        @else
                                            <span class="badge bg-success" title="Dịch vụ dùng chung cho tất cả tổ chức">
                                                <i class="fas fa-globe me-1"></i>
                                                Toàn hệ thống
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $pricingTypes = [
                                                'fixed' => 'Cố định',
                                                'per_unit' => 'Theo đơn vị',
                                                'per_area' => 'Theo diện tích',
                                            ];
                                        @endphp
                                        <span class="badge bg-info">
                                            {{ $pricingTypes[$service->pricing_type] ?? $service->pricing_type ?? 'Cố định' }}
                                        </span>
                                    </td>
                                    <td>{{ $service->unit_label ?? 'tháng' }}</td>
                                    <td>
                                        @if($service->description)
                                            <small class="text-muted">{{ Str::limit($service->description, 50) }}</small>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group table-actions" role="group">
                                            @php
                                                $user = Auth::user();
                                                $currentOrgId = $user->getCurrentOrganizationId();
                                                // User can edit if service belongs to their organization or if it's a global service they created
                                                $canEdit = $service->organization_id === $currentOrgId || 
                                                          ($service->organization_id === null && !$currentOrgId);
                                            @endphp
                                            
                                            @if($canEdit)
                                                <a href="{{ route('staff.services.edit', $service->id) }}" 
                                                   class="btn btn-outline-warning btn-icon-only" 
                                                   title="Sửa">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button type="button" 
                                                        class="btn btn-outline-danger btn-icon-only" 
                                                        onclick="deleteService({{ $service->id }}, '{{ addslashes($service->name) }}')" 
                                                        title="Xóa">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            @else
                                                <span class="badge bg-secondary" title="Dịch vụ hệ thống - Chỉ xem">
                                                    <i class="fas fa-eye"></i> Chỉ xem
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-3">
                        {{ $services->appends(request()->query())->links('vendor.pagination.custom', [
                            'contentTypeOverride' => 'dịch vụ',
                            'contentIconOverride' => 'fas fa-list',
                            'tableContainerId' => 'services-table-container',
                            'htmxIndicator' => '#htmx-loading-index-filters-form'
                        ]) }}
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-list fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Chưa có dịch vụ nào</p>
                        <a href="{{ route('staff.services.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i>
                            Thêm dịch vụ đầu tiên
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function deleteService(id, name) {
    Notify.confirm({
        title: 'Xác nhận xóa',
        message: `Bạn có chắc muốn xóa dịch vụ "${name}"?`,
        type: 'danger',
        confirmText: 'Xóa',
        cancelText: 'Hủy',
        onConfirm: function() {
            fetch(`{{ url('staff/services') }}/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Notify.success(data.message, 'Thành công!');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    Notify.error(data.message, 'Lỗi!');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Notify.error('Có lỗi xảy ra. Vui lòng thử lại.', 'Lỗi hệ thống');
            });
        }
    });
}

function getCsrfToken() {
    let token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (token) return token;
    
    token = document.querySelector('input[name="_token"]')?.value;
    if (token) return token;
    
    const form = document.querySelector('form');
    if (form) {
        const input = form.querySelector('input[name="_token"]');
        if (input) return input.value;
    }
    
    return '';
}

function deleteUnusedServices() {
    if (typeof window.Notify !== 'undefined') {
        window.Notify.confirm({
            title: 'Xác nhận xóa',
            message: 'Bạn có chắc muốn xóa tất cả các dịch vụ không được sử dụng?',
            type: 'danger',
            confirmText: 'Xóa',
            cancelText: 'Hủy',
            onConfirm: function() {
                performDeleteUnusedServices();
            }
        });
    } else {
        if (confirm('Bạn có chắc muốn xóa tất cả các dịch vụ không được sử dụng?')) {
            performDeleteUnusedServices();
        }
    }
}

function performDeleteUnusedServices() {
    const csrfToken = getCsrfToken();
    
    if (typeof window.Notify !== 'undefined') {
        window.Notify.info('Đang xóa các dịch vụ không sử dụng...', 'Đang xử lý');
    }
    
    fetch(`{{ route('staff.services.delete-unused') }}`, {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (typeof window.Notify !== 'undefined') {
                window.Notify.success(
                    data.message || `Đã xóa ${data.deleted_count || 0} dịch vụ không sử dụng thành công!`,
                    'Thành công'
                );
                setTimeout(() => window.location.reload(), 1500);
            } else {
                alert(data.message || 'Đã xóa các dịch vụ không sử dụng thành công!');
                window.location.reload();
            }
        } else {
            if (typeof window.Notify !== 'undefined') {
                window.Notify.error(data.error || 'Có lỗi xảy ra khi xóa các dịch vụ không sử dụng.', 'Lỗi');
            } else {
                alert(data.error || 'Có lỗi xảy ra khi xóa các dịch vụ không sử dụng.');
            }
        }
    })
    .catch(error => {
        console.error('Error deleting unused services:', error);
        if (typeof window.Notify !== 'undefined') {
            window.Notify.error('Có lỗi xảy ra khi xóa các dịch vụ không sử dụng.', 'Lỗi');
        } else {
            alert('Có lỗi xảy ra khi xóa các dịch vụ không sử dụng.');
        }
    });
}
</script>
@endpush

