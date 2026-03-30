<div class="row">
    <!-- Organization Settings -->
    <div class="col-lg-6">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h6 class="card-title mb-0">
                    <i class="fas fa-building me-2"></i>
                    Cài đặt tổ chức: {{ $organization->name }}
                </h6>
            </div>
            <div class="card-body">
                <form action="{{ route('staff.lease-service-settings.organization.update') }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    @if($defaultLeaseServiceSet)
                        <div class="alert alert-info mb-3">
                            <strong>Bộ dịch vụ mặc định hiện tại:</strong> 
                            {{ $defaultLeaseServiceSet->name }}
                            @if($defaultLeaseServiceSet->description)
                                <br><small>{{ $defaultLeaseServiceSet->description }}</small>
                            @endif
                            @if($defaultLeaseServiceSet->items)
                                <br><small>Số dịch vụ: {{ $defaultLeaseServiceSet->items->count() }}</small>
                            @endif
                        </div>
                    @else
                        <div class="alert alert-warning mb-3">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Chưa có bộ dịch vụ mặc định.</strong> Vui lòng chọn bộ dịch vụ mặc định.
                        </div>
                    @endif
                    
                    {{-- Dropdown chọn bộ dịch vụ có sẵn --}}
                    <div class="mb-3">
                        <label for="lease_service_set_id" class="form-label">Chọn bộ dịch vụ có sẵn làm mặc định</label>
                        <select class="form-select" id="lease_service_set_id" name="lease_service_set_id">
                            <option value="">-- Chọn bộ dịch vụ có sẵn --</option>
                            @if($leaseServiceSets && $leaseServiceSets->count() > 0)
                                @foreach($leaseServiceSets as $set)
                                    <option value="{{ $set->id }}" {{ ($defaultLeaseServiceSet && $defaultLeaseServiceSet->id == $set->id) ? 'selected' : '' }}>
                                        {{ $set->name }}
                                        @if($set->items)
                                            ({{ $set->items->count() }} dịch vụ)
                                        @endif
                                        @if($set->is_default)
                                            (Mặc định)
                                        @endif
                                    </option>
                                @endforeach
                            @endif
                        </select>
                        <div class="form-text">Chọn một bộ dịch vụ có sẵn để đặt làm mặc định cho tổ chức</div>
                    </div>

                    <div class="alert alert-info mb-3">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Lưu ý:</strong> Nút "Cập nhật tổ chức" chỉ cập nhật cài đặt cho tổ chức, không tự động áp dụng lên bất động sản. 
                        Để áp dụng bộ dịch vụ này cho tất cả bất động sản, vui lòng nhấn nút "Áp dụng cho tất cả BĐS".
                    </div>

                    @include('staff.components.action-buttons', [
                        'layout' => 'horizontal',
                        'size' => 'md',
                        'actions' => [
                            [
                                'type' => 'submit',
                                'variant' => 'primary',
                                'label' => 'Cập nhật tổ chức',
                                'icon' => 'fas fa-save',
                                'iconPosition' => 'left'
                            ],
                            [
                                'type' => 'button',
                                'variant' => 'success',
                                'label' => 'Áp dụng cho tất cả BĐS',
                                'icon' => 'fas fa-arrow-down',
                                'iconPosition' => 'left',
                                'onclick' => 'applyToProperties()'
                            ]
                        ]
                    ])
                </form>
            </div>
        </div>
    </div>

    <!-- Lease Service Sets Management -->
    <div class="col-lg-6">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="card-title mb-0">
                    <i class="fas fa-box me-2"></i>
                    Quản lý bộ dịch vụ
                </h6>
                <div class="btn-group">
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="toggleLeaseServiceSection(this)" title="Thu gọn/Mở rộng">
                        <i class="fas fa-chevron-up"></i>
                    </button>
                    <button type="button" class="btn btn-danger btn-sm" onclick="deleteUnusedSets()" title="Xóa các bộ dịch vụ không được sử dụng">
                        <i class="fas fa-trash-alt me-1"></i>
                        Xóa bộ dịch vụ không dùng
                    </button>
                    <button type="button" class="btn btn-primary btn-sm" onclick="showCreateSetModal()">
                        <i class="fas fa-plus me-1"></i>
                        Tạo bộ dịch vụ
                    </button>
                </div>
            </div>
            <div class="card-body" id="lease-service-management-body">
                @if($leaseServiceSets->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th style="width: 25%;">Tên bộ dịch vụ</th>
                                    <th style="width: 45%;">Dịch vụ và giá</th>
                                    <th style="width: 20%;">Sử dụng</th>
                                    <th style="width: 10%;">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($leaseServiceSets as $set)
                                    <tr>
                                        <td>
                                            <strong>{{ $set->name }}</strong>
                                            @if($set->is_default)
                                                <span class="badge bg-success ms-2">Mặc định</span>
                                            @endif
                                            @if($set->description)
                                                <br><small class="text-muted">{{ $set->description }}</small>
                                            @endif
                                            <br><span class="badge bg-info mt-1">{{ $set->items->count() }} dịch vụ</span>
                                        </td>
                                        <td>
                                            @if($set->items && $set->items->count() > 0)
                                                <div class="service-list">
                                                    @foreach($set->items->take(3) as $item)
                                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                                            <span>
                                                                <i class="fas fa-check-circle text-success me-1"></i>
                                                                <strong>{{ $item->service->name ?? 'N/A' }}</strong>
                                                                @if($item->service->key_code)
                                                                    <small class="text-muted">({{ $item->service->key_code }})</small>
                                                                @endif
                                                            </span>
                                                            <span class="text-primary fw-bold">
                                                                {{ number_format($item->price, 0, ',', '.') }} đ
                                                                @if($item->service->unit_label)
                                                                    / {{ $item->service->unit_label }}
                                                                @endif
                                                            </span>
                                                        </div>
                                                    @endforeach
                                                    @if($set->items->count() > 3)
                                                        <div class="text-muted small">
                                                            <i class="fas fa-ellipsis-h"></i>
                                                            và {{ $set->items->count() - 3 }} dịch vụ khác...
                                                        </div>
                                                    @endif
                                                </div>
                                            @else
                                                <span class="text-muted">Chưa có dịch vụ</span>
                                            @endif
                                        </td>
                                        <td>
                                            @php
                                                $propertiesCount = $set->properties_count ?? 0;
                                                $leasesCount = $set->leases_count ?? 0;
                                                $totalUsage = $propertiesCount + $leasesCount;
                                            @endphp
                                            @if($totalUsage > 0)
                                                <div class="mb-1">
                                                    <i class="fas fa-home text-info me-1"></i>
                                                    <small>{{ $propertiesCount }} BĐS</small>
                                                </div>
                                                <div>
                                                    <i class="fas fa-file-contract text-primary me-1"></i>
                                                    <small>{{ $leasesCount }} hợp đồng</small>
                                                </div>
                                            @else
                                                <span class="badge bg-secondary">Chưa sử dụng</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group table-actions" role="group">
                                                <button type="button" class="btn btn-outline-info btn-icon-only" 
                                                        onclick="viewSetDetails({{ $set->id }})" 
                                                        title="Xem chi tiết">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-warning btn-icon-only" 
                                                        onclick="editSet({{ $set->id }})" 
                                                        title="Sửa">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-danger btn-icon-only" 
                                                        onclick="deleteSet({{ $set->id }}, '{{ addslashes($set->name) }}')" 
                                                        title="Xóa">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="fas fa-box fa-3x text-muted mb-3"></i>
                        <p class="text-muted mt-2">Chưa có bộ dịch vụ nào</p>
                        <button type="button" class="btn btn-primary btn-sm" onclick="showCreateSetModal()">
                            <i class="fas fa-plus me-1"></i>
                            Tạo bộ dịch vụ đầu tiên
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row mt-3">
    <!-- Properties List -->
    <div class="col-12">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h6 class="card-title mb-0">
                    <i class="fas fa-home me-2"></i>
                    Danh sách bất động sản
                </h6>
            </div>
            <div class="card-body">
                @if($properties->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Tên BĐS</th>
                                    <th>Bộ dịch vụ</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($properties as $property)
                                    <tr>
                                        <td><strong>{{ $property->name }}</strong></td>
                                        <td>
                                            @php
                                                $effectiveSet = $property->getEffectiveLeaseServiceSet();
                                            @endphp
                                            @if($effectiveSet)
                                                <span class="badge bg-info" title="{{ $effectiveSet->description ?? '' }}">
                                                    {{ $effectiveSet->name }}
                                                    @if($effectiveSet->items)
                                                        ({{ $effectiveSet->items->count() }})
                                                    @endif
                                                </span>
                                                @if($property->lease_services_id)
                                                    <br><small class="text-muted">Từ BĐS</small>
                                                @else
                                                    <br><small class="text-muted">Từ tổ chức</small>
                                                @endif
                                            @else
                                                <span class="text-muted">Chưa cài đặt</span>
                                            @endif
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-primary btn-icon-only" 
                                                    onclick="showPropertySettings({{ $property->id }}, '{{ $property->name }}')"
                                                    title="Cài đặt">
                                                <i class="fas fa-cog"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="fas fa-home fa-3x text-muted mb-3"></i>
                        <p class="text-muted mt-2">Chưa có bất động sản nào</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@push('modals')
<!-- Property Settings Modal -->
<div class="modal fade" id="leaseServicePropertyModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cài đặt bộ dịch vụ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="leaseServicePropertySettingsContent">
                    <!-- Content will be loaded here -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create/Edit Set Modal -->
<div class="modal fade" id="setModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="setModalTitle">Tạo bộ dịch vụ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="setForm" onsubmit="return saveSet(event)">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <input type="hidden" name="set_id" id="set_id">
                    
                    <div class="mb-3">
                        <label for="set_name" class="form-label">
                            Tên bộ dịch vụ <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="set_name" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="set_description" class="form-label">Mô tả</label>
                        <textarea class="form-control" id="set_description" name="description" rows="2"></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">
                            Dịch vụ trong bộ <span class="text-danger">*</span>
                        </label>
                        <div id="servicesContainer">
                            <!-- Services will be added here -->
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="addServiceRow()">
                            <i class="fas fa-plus me-1"></i>
                            Thêm dịch vụ
                        </button>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="submit" form="setForm" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i>
                    Lưu
                </button>
            </div>
        </div>
    </div>
</div>

<!-- View Set Details Modal -->
<div class="modal fade" id="viewSetDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewSetDetailsTitle">Chi tiết bộ dịch vụ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="viewSetDetailsContent">
                    <!-- Content will be loaded here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Set Confirmation Modal -->
<div class="modal fade" id="deleteSetModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Xác nhận xóa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Bạn có chắc chắn muốn xóa bộ dịch vụ <strong id="deleteSetName"></strong>?</p>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Thao tác này không thể hoàn tác. Tất cả các bất động sản và hợp đồng đang sử dụng bộ dịch vụ này sẽ bị ảnh hưởng.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteSetBtn">
                    <i class="fas fa-trash me-1"></i>
                    Xóa
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Apply to Properties Modal -->
<div class="modal fade" id="leaseServiceApplyAllModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Xác nhận áp dụng cài đặt</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Bạn có chắc chắn muốn áp dụng bộ dịch vụ mặc định của tổ chức cho tất cả bất động sản?</p>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Thao tác này sẽ ghi đè cài đặt hiện tại của tất cả bất động sản.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <form action="{{ route('staff.lease-service-settings.apply-to-properties') }}" method="POST" style="display: inline;">
                    @csrf
                    <input type="hidden" name="apply_to_properties" value="1">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check me-1"></i>
                        Xác nhận áp dụng
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endpush

@push('scripts')
<script>
const services = @json($services);
let serviceRowIndex = 0;
let editingSetId = null;

function showCreateSetModal() {
    editingSetId = null;
    document.getElementById('setModalTitle').textContent = 'Tạo bộ dịch vụ';
    document.getElementById('setForm').reset();
    document.getElementById('set_id').value = '';
    document.getElementById('servicesContainer').innerHTML = '';
    serviceRowIndex = 0;
    addServiceRow();
    
    const modal = new bootstrap.Modal(document.getElementById('setModal'));
    modal.show();
}

function editSet(setId) {
    editingSetId = setId;
    document.getElementById('setModalTitle').textContent = 'Sửa bộ dịch vụ';
    
    fetch(`{{ route('staff.lease-service-settings.sets.show', ['id' => 'PLACEHOLDER']) }}`.replace('PLACEHOLDER', setId), {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.leaseServiceSet) {
            const set = data.leaseServiceSet;
            document.getElementById('set_id').value = set.id;
            document.getElementById('set_name').value = set.name || '';
            document.getElementById('set_description').value = set.description || '';
            
            document.getElementById('servicesContainer').innerHTML = '';
            serviceRowIndex = 0;
            
            if (set.items && set.items.length > 0) {
                set.items.forEach(item => {
                    addServiceRow(item.service_id, item.price);
                });
            } else {
                addServiceRow();
            }
            
            const modal = new bootstrap.Modal(document.getElementById('setModal'));
            modal.show();
        } else {
            if (typeof window.Notify !== 'undefined') {
                window.Notify.error('Không thể tải dữ liệu bộ dịch vụ.', 'Lỗi');
            } else {
                alert('Không thể tải dữ liệu bộ dịch vụ.');
            }
        }
    })
    .catch(error => {
        console.error('Error loading set:', error);
        if (typeof window.Notify !== 'undefined') {
            window.Notify.error('Có lỗi xảy ra khi tải dữ liệu.', 'Lỗi');
        } else {
            alert('Có lỗi xảy ra khi tải dữ liệu.');
        }
    });
}

function deleteSet(setId, setName) {
    document.getElementById('deleteSetName').textContent = setName;
    document.getElementById('confirmDeleteSetBtn').onclick = function() {
        confirmDeleteSet(setId);
    };
    
    const modal = new bootstrap.Modal(document.getElementById('deleteSetModal'));
    modal.show();
}

function confirmDeleteSet(setId) {
    const csrfToken = getCsrfToken();
    
    fetch(`{{ route('staff.lease-service-settings.sets.destroy', ['id' => 'PLACEHOLDER']) }}`.replace('PLACEHOLDER', setId), {
        method: 'DELETE',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const modal = bootstrap.Modal.getInstance(document.getElementById('deleteSetModal'));
            modal.hide();
            if (typeof window.Notify !== 'undefined') {
                window.Notify.success(data.message || 'Đã xóa bộ dịch vụ thành công!', 'Thành công');
                setTimeout(() => window.location.reload(), 1500);
            } else {
                window.location.reload();
            }
        } else {
            if (typeof window.Notify !== 'undefined') {
                window.Notify.error(data.error || 'Có lỗi xảy ra khi xóa bộ dịch vụ.', 'Lỗi');
            } else {
                alert(data.error || 'Có lỗi xảy ra khi xóa bộ dịch vụ.');
            }
        }
    })
    .catch(error => {
        console.error('Error deleting set:', error);
        if (typeof window.Notify !== 'undefined') {
            window.Notify.error('Có lỗi xảy ra khi xóa bộ dịch vụ.', 'Lỗi');
        } else {
            alert('Có lỗi xảy ra khi xóa bộ dịch vụ.');
        }
    });
}

function addServiceRow(selectedServiceId = null, price = '') {
    const container = document.getElementById('servicesContainer');
    const rowId = `service_row_${serviceRowIndex++}`;
    
    const serviceOptions = services.map(service => {
        const selected = selectedServiceId == service.id ? 'selected' : '';
        return `<option value="${service.id}" ${selected}>${service.key_code ? service.key_code + ' - ' : ''}${service.name}</option>`;
    }).join('');
    
    const row = document.createElement('div');
    row.className = 'row mb-2';
    row.id = rowId;
    row.innerHTML = `
        <div class="col-md-6">
            <select class="form-select form-select-sm" name="services[${serviceRowIndex - 1}][service_id]" required>
                <option value="">-- Chọn dịch vụ --</option>
                ${serviceOptions}
            </select>
        </div>
        <div class="col-md-4">
            <input type="number" class="form-control form-control-sm" name="services[${serviceRowIndex - 1}][price]" 
                   placeholder="Giá" step="0.01" min="0" value="${price}" required>
        </div>
        <div class="col-md-2">
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeServiceRow('${rowId}')">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    `;
    
    container.appendChild(row);
}

function removeServiceRow(rowId) {
    const row = document.getElementById(rowId);
    if (row) {
        row.remove();
    }
}

function saveSet(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    
    const services = [];
    const serviceIds = document.querySelectorAll('#servicesContainer select[name^="services["]');
    const servicePrices = document.querySelectorAll('#servicesContainer input[name^="services["]');
    
    serviceIds.forEach((select, index) => {
        const serviceId = select.value;
        const price = servicePrices[index]?.value || '';
        
        if (serviceId && price) {
            services.push({
                service_id: serviceId,
                price: price
            });
        }
    });
    
    if (services.length === 0) {
        if (typeof window.Notify !== 'undefined') {
            window.Notify.error('Vui lòng thêm ít nhất một dịch vụ.', 'Lỗi');
        } else {
            alert('Vui lòng thêm ít nhất một dịch vụ.');
        }
        return false;
    }
    
    const csrfToken = getCsrfToken();
    const setId = formData.get('set_id');
    const url = setId 
        ? `{{ route('staff.lease-service-settings.sets.update', ['id' => 'PLACEHOLDER']) }}`.replace('PLACEHOLDER', setId)
        : `{{ route('staff.lease-service-settings.sets.store') }}`;
    
    const finalFormData = new FormData();
    finalFormData.append('_token', csrfToken);
    finalFormData.append('name', formData.get('name'));
    if (formData.get('description')) {
        finalFormData.append('description', formData.get('description'));
    }
    services.forEach((service, index) => {
        finalFormData.append(`services[${index}][service_id]`, service.service_id);
        finalFormData.append(`services[${index}][price]`, service.price);
    });
    
    if (setId) {
        finalFormData.append('_method', 'PUT');
    }
    
    fetch(url, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: finalFormData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const modal = bootstrap.Modal.getInstance(document.getElementById('setModal'));
            modal.hide();
            if (typeof window.Notify !== 'undefined') {
                window.Notify.success(data.message || 'Đã lưu bộ dịch vụ thành công!', 'Thành công');
                setTimeout(() => window.location.reload(), 1500);
            } else {
                window.location.reload();
            }
        } else {
            if (typeof window.Notify !== 'undefined') {
                window.Notify.error(data.error || 'Có lỗi xảy ra khi lưu bộ dịch vụ.', 'Lỗi');
            } else {
                alert(data.error || 'Có lỗi xảy ra khi lưu bộ dịch vụ.');
            }
        }
    })
    .catch(error => {
        console.error('Error saving set:', error);
        if (typeof window.Notify !== 'undefined') {
            window.Notify.error('Có lỗi xảy ra khi lưu bộ dịch vụ.', 'Lỗi');
        } else {
            alert('Có lỗi xảy ra khi lưu bộ dịch vụ.');
        }
    });
    
    return false;
}

function showPropertySettings(propertyId, propertyName) {
    const contentDiv = document.getElementById('leaseServicePropertySettingsContent');
    if (contentDiv) {
        contentDiv.innerHTML = `
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Đang tải dữ liệu...</p>
            </div>
        `;
    }
    
    const modalTitle = document.querySelector('#leaseServicePropertyModal .modal-title');
    if (modalTitle) {
        modalTitle.textContent = `Cài đặt: ${propertyName}`;
    }
    
    const modal = document.getElementById('leaseServicePropertyModal');
    if (modal) {
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
    }
    
    const url = `{{ route('staff.lease-service-settings.property.leases', ['propertyId' => 'PLACEHOLDER']) }}`.replace('PLACEHOLDER', propertyId);
    
    fetch(url, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(response => {
        if (response.success) {
            const contentDiv = document.getElementById('leaseServicePropertySettingsContent');
            if (contentDiv) {
                const sets = @json($leaseServiceSets);
                const setsOptions = sets.map(set => {
                    const itemsCount = set.items ? set.items.length : 0;
                    const isSelected = response.property.lease_service_set && response.property.lease_service_set.id == set.id ? 'selected' : '';
                    return `<option value="${set.id}" ${isSelected}>${set.name} (${itemsCount} dịch vụ)</option>`;
                }).join('');
                
                contentDiv.innerHTML = `
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Thông tin bất động sản</h6>
                            <form id="propertyUpdateForm" onsubmit="return updatePropertyLeaseServiceSet(event, ${propertyId})" method="POST">
                                <input type="hidden" name="_token" value="${getCsrfToken()}">
                                <input type="hidden" name="_method" value="PUT">
                                
                                <div class="mb-3">
                                    <label class="form-label">Bộ dịch vụ</label>
                                    <select class="form-select" name="lease_services_id" id="prop_lease_service_set_modal">
                                        <option value="">-- Không chọn (dùng từ tổ chức) --</option>
                                        ${setsOptions}
                                    </select>
                                    <div class="form-text">
                                        Chọn bộ dịch vụ cho bất động sản này. Nếu không chọn, sẽ dùng bộ dịch vụ mặc định của tổ chức.
                                    </div>
                                </div>

                                @if($defaultLeaseServiceSet)
                                <div class="alert alert-info mb-3">
                                    <strong>Bộ dịch vụ mặc định (từ tổ chức):</strong><br>
                                    {{ $defaultLeaseServiceSet->name }}
                                    @if($defaultLeaseServiceSet->items)
                                        ({{ $defaultLeaseServiceSet->items->count() }} dịch vụ)
                                    @endif
                                </div>
                                @endif

                                <div class="mb-3">
                                    <label class="form-label">Bộ dịch vụ hiệu lực</label>
                                    <div class="alert alert-secondary mb-0">
                                        ${response.property.effective_lease_service_set ? 
                                            `<strong>${response.property.effective_lease_service_set.name}</strong><br>
                                            <small>${response.property.effective_lease_service_set.description || ''}</small><br>
                                            <small>Số dịch vụ: ${response.property.effective_lease_service_set.items_count || 0}</small>` :
                                            '<span class="text-muted">Chưa có bộ dịch vụ</span>'
                                        }
                                    </div>
                                </div>

                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary btn-sm">
                                        <i class="fas fa-save me-1"></i>
                                        Cập nhật
                                    </button>
                                </div>
                            </form>
                        </div>
                        
                        <div class="col-md-6">
                            <h6>Hợp đồng thuê (${response.leases.length})</h6>
                            <div style="max-height: 300px; overflow-y: auto;">
                                ${response.leases.length > 0 ? response.leases.map(lease => `
                                    <div class="border rounded p-2 mb-2">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <strong>${lease.contract_no}</strong><br>
                                                <small class="text-muted">${lease.unit_code} - ${lease.tenant_name}</small>
                                            </div>
                                            <div class="text-end">
                                                ${lease.lease_service_set ? 
                                                    `<span class="badge bg-info">${lease.lease_service_set.name}</span><br>
                                                    <small class="text-muted">${lease.lease_service_set.items_count || 0} dịch vụ</small>` : 
                                                    '<span class="text-muted">Chưa cài</span>'
                                                }
                                            </div>
                                        </div>
                                    </div>
                                `).join('') : '<p class="text-muted">Chưa có hợp đồng</p>'}
                            </div>
                        </div>
                    </div>
                `;
            }
        }
    })
    .catch(error => {
        console.error('Error loading property settings:', error);
        const contentDiv = document.getElementById('leaseServicePropertySettingsContent');
        if (contentDiv) {
            contentDiv.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    Có lỗi xảy ra khi tải dữ liệu.
                </div>
            `;
        }
    });
}

function viewSetDetails(setId) {
    fetch(`{{ route('staff.lease-service-settings.sets.show', ['id' => 'PLACEHOLDER']) }}`.replace('PLACEHOLDER', setId), {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.leaseServiceSet) {
            const set = data.leaseServiceSet;
            const modalTitle = document.getElementById('viewSetDetailsTitle');
            const modalContent = document.getElementById('viewSetDetailsContent');
            
            if (modalTitle) {
                modalTitle.textContent = `Chi tiết: ${set.name}`;
            }
            
            if (modalContent) {
                let itemsHtml = '';
                if (set.items && set.items.length > 0) {
                    itemsHtml = `
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>STT</th>
                                        <th>Mã dịch vụ</th>
                                        <th>Tên dịch vụ</th>
                                        <th>Đơn vị</th>
                                        <th>Giá</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${set.items.map((item, index) => `
                                        <tr>
                                            <td>${index + 1}</td>
                                            <td>
                                                ${item.service && item.service.key_code ? 
                                                    `<code>${item.service.key_code}</code>` : 
                                                    '<span class="text-muted">-</span>'
                                                }
                                            </td>
                                            <td>
                                                <strong>${item.service ? item.service.name : 'N/A'}</strong>
                                                ${item.service && item.service.description ? 
                                                    `<br><small class="text-muted">${item.service.description}</small>` : 
                                                    ''
                                                }
                                            </td>
                                            <td>${item.service && item.service.unit_label ? item.service.unit_label : 'tháng'}</td>
                                            <td>
                                                <strong class="text-primary">
                                                    ${parseFloat(item.price).toLocaleString('vi-VN')} đ
                                                </strong>
                                            </td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    `;
                } else {
                    itemsHtml = '<p class="text-muted">Chưa có dịch vụ nào trong bộ này.</p>';
                }
                
                // Get usage statistics from API response
                const propertiesCount = set.properties_count || 0;
                const leasesCount = set.leases_count || 0;
                
                modalContent.innerHTML = `
                    <div class="mb-3">
                        <h6>Thông tin bộ dịch vụ</h6>
                        <div class="card bg-light">
                            <div class="card-body">
                                <p><strong>Tên:</strong> ${set.name}</p>
                                ${set.description ? `<p><strong>Mô tả:</strong> ${set.description}</p>` : ''}
                                <p><strong>Số dịch vụ:</strong> <span class="badge bg-info">${set.items ? set.items.length : 0} dịch vụ</span></p>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <h6>Thống kê sử dụng</h6>
                        <div class="card">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="fas fa-home text-info me-2"></i>
                                            <div>
                                                <strong>Bất động sản:</strong>
                                                <span class="badge bg-info ms-2">${propertiesCount}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="fas fa-file-contract text-primary me-2"></i>
                                            <div>
                                                <strong>Hợp đồng thuê:</strong>
                                                <span class="badge bg-primary ms-2">${leasesCount}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <hr>
                                <div class="text-center">
                                    <strong>Tổng sử dụng:</strong>
                                    <span class="badge bg-success ms-2">${propertiesCount + leasesCount}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div>
                        <h6>Danh sách dịch vụ</h6>
                        ${itemsHtml}
                    </div>
                `;
            }
            
            const modal = new bootstrap.Modal(document.getElementById('viewSetDetailsModal'));
            modal.show();
        } else {
            if (typeof window.Notify !== 'undefined') {
                window.Notify.error('Không thể tải dữ liệu bộ dịch vụ.', 'Lỗi');
            } else {
                alert('Không thể tải dữ liệu bộ dịch vụ.');
            }
        }
    })
    .catch(error => {
        console.error('Error loading set details:', error);
        if (typeof window.Notify !== 'undefined') {
            window.Notify.error('Có lỗi xảy ra khi tải dữ liệu.', 'Lỗi');
        } else {
            alert('Có lỗi xảy ra khi tải dữ liệu.');
        }
    });
}

function applyToProperties() {
    const modal = document.getElementById('leaseServiceApplyAllModal');
    if (modal) {
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
    }
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

function updatePropertyLeaseServiceSet(event, propertyId) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    
    let csrfToken = form.querySelector('input[name="_token"]')?.value;
    if (!csrfToken) {
        csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    }
    if (!csrfToken) {
        csrfToken = document.querySelector('input[name="_token"]')?.value;
    }
    
    if (csrfToken) {
        formData.append('_token', csrfToken);
    }
    formData.append('_method', 'PUT');
    
    const routeUrl = `/staff/lease-service-settings/property/${propertyId}`;
    
    fetch(routeUrl, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken || ''
        },
        body: formData
    })
    .then(response => {
        if (response.redirected) {
            window.location.href = response.url;
            return null;
        }
        if (!response.ok) {
            return response.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch {
                    throw new Error('Server error: ' + response.status);
                }
            });
        }
        return response.json();
    })
    .then(data => {
        if (data === null) {
            return;
        }
        if (data && (data.success || data.message)) {
            const modal = document.getElementById('leaseServicePropertyModal');
            if (modal) {
                const bsModal = bootstrap.Modal.getInstance(modal);
                if (bsModal) {
                    bsModal.hide();
                }
            }
            if (typeof window.Notify !== 'undefined') {
                window.Notify.success(data.message || 'Cập nhật cài đặt thành công!', 'Thành công');
                setTimeout(() => window.location.reload(), 1500);
            } else {
                window.location.reload();
            }
        } else if (data && (data.error || data.errors)) {
            const errorMsg = data.error || (data.errors ? Object.values(data.errors).flat().join(', ') : 'Có lỗi xảy ra khi cập nhật');
            if (typeof window.Notify !== 'undefined') {
                window.Notify.error(errorMsg, 'Lỗi cập nhật');
            } else {
                alert(errorMsg);
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        if (typeof window.Notify !== 'undefined') {
            window.Notify.error('Có lỗi xảy ra khi cập nhật: ' + error.message, 'Lỗi hệ thống');
        } else {
            alert('Có lỗi xảy ra khi cập nhật: ' + error.message);
        }
    });
    
    return false;
}

// Toggle lease service management section
function toggleLeaseServiceSection(button) {
    const body = document.getElementById('lease-service-management-body');
    const icon = button.querySelector('i');
    
    if (body) {
        if (body.style.display === 'none') {
            body.style.display = '';
            icon.classList.remove('fa-chevron-down');
            icon.classList.add('fa-chevron-up');
        } else {
            body.style.display = 'none';
            icon.classList.remove('fa-chevron-up');
            icon.classList.add('fa-chevron-down');
        }
    }
}

function deleteUnusedSets() {
    if (typeof window.Notify !== 'undefined') {
        window.Notify.confirm({
            title: 'Xác nhận xóa bộ dịch vụ không sử dụng',
            message: 'Bạn có chắc chắn muốn xóa tất cả các bộ dịch vụ không được sử dụng?',
            details: 'Thao tác này sẽ xóa tất cả các bộ dịch vụ không được gán cho bất động sản hoặc hợp đồng thuê. Bộ dịch vụ mặc định sẽ không bị xóa.',
            type: 'warning',
            confirmText: 'Xác nhận xóa',
            cancelText: 'Hủy',
            onConfirm: () => {
                performDeleteUnusedSets();
            }
        });
    } else {
        if (confirm('Bạn có chắc chắn muốn xóa tất cả các bộ dịch vụ không được sử dụng?')) {
            performDeleteUnusedSets();
        }
    }
}

function performDeleteUnusedSets() {
    const csrfToken = getCsrfToken();
    
    if (typeof window.Notify !== 'undefined') {
        window.Notify.info('Đang xóa các bộ dịch vụ không sử dụng...', 'Đang xử lý');
    }
    
    fetch(`{{ route('staff.lease-service-settings.sets.delete-unused') }}`, {
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
                    data.message || `Đã xóa ${data.deleted_count || 0} bộ dịch vụ không sử dụng thành công!`,
                    'Thành công'
                );
                setTimeout(() => window.location.reload(), 1500);
            } else {
                alert(data.message || 'Đã xóa các bộ dịch vụ không sử dụng thành công!');
                window.location.reload();
            }
        } else {
            if (typeof window.Notify !== 'undefined') {
                window.Notify.error(data.error || 'Có lỗi xảy ra khi xóa các bộ dịch vụ không sử dụng.', 'Lỗi');
            } else {
                alert(data.error || 'Có lỗi xảy ra khi xóa các bộ dịch vụ không sử dụng.');
            }
        }
    })
    .catch(error => {
        console.error('Error deleting unused sets:', error);
        if (typeof window.Notify !== 'undefined') {
            window.Notify.error('Có lỗi xảy ra khi xóa các bộ dịch vụ không sử dụng.', 'Lỗi');
        } else {
            alert('Có lỗi xảy ra khi xóa các bộ dịch vụ không sử dụng.');
        }
    });
}
</script>
@endpush
