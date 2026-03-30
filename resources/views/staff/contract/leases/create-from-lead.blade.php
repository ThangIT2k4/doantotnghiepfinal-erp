@extends('layouts.agent_dashboard')

@section('title', 'Tạo hợp đồng từ Lead')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0 text-gray-800">
                        <i class="fas fa-file-contract me-2"></i>Tạo hợp đồng từ Lead
                    </h1>
                    <p class="text-muted mb-0">Chuyển đổi lead "{{ $lead->name }}" thành hợp đồng thuê</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('staff.leads.show', $lead->id) }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Quay lại Lead
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Lead Information -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-user me-2"></i>Thông tin Lead
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="info-item mb-3">
                                <label class="form-label text-muted small">Tên khách hàng</label>
                                <p class="mb-0 fw-bold">{{ $lead->name }}</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-item mb-3">
                                <label class="form-label text-muted small">Số điện thoại</label>
                                <p class="mb-0">{{ $lead->phone }}</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-item mb-3">
                                <label class="form-label text-muted small">Email</label>
                                <p class="mb-0">{{ $lead->email ?? 'N/A' }}</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-item mb-3">
                                <label class="form-label text-muted small">Ngân sách</label>
                                <p class="mb-0">
                                    @if($lead->budget_min && $lead->budget_max)
                                        {{ number_format($lead->budget_min) }}đ - {{ number_format($lead->budget_max) }}đ
                                    @elseif($lead->budget_min)
                                        Từ {{ number_format($lead->budget_min) }}đ
                                    @elseif($lead->budget_max)
                                        Đến {{ number_format($lead->budget_max) }}đ
                                    @else
                                        Chưa xác định
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Lease Form -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-file-contract me-2"></i>Thông tin hợp đồng
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('staff.leads.store-lease', $lead->id) }}" method="POST" id="leaseForm">
                        @csrf
                        
                        <div class="row">
                            <!-- Property Selection -->
                            <div class="col-md-6 mb-3">
                                <label for="property_id" class="form-label">Bất động sản <span class="text-danger">*</span></label>
                                <select class="form-select @error('property_id') is-invalid @enderror" id="property_id" name="property_id" required>
                                    <option value="">Chọn bất động sản</option>
                                    @foreach($properties as $property)
                                        <option value="{{ $property->id }}" 
                                            {{ $selectedProperty && $selectedProperty->id == $property->id ? 'selected' : '' }}>
                                            {{ $property->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('property_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Unit Selection -->
                            <div class="col-md-6 mb-3">
                                <label for="unit_id" class="form-label">Phòng <span class="text-danger">*</span></label>
                                <select class="form-select @error('unit_id') is-invalid @enderror" id="unit_id" name="unit_id" required>
                                    <option value="">Chọn phòng</option>
                                </select>
                                @error('unit_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Tenant Selection -->
                            <div class="col-md-6 mb-3">
                                <label for="tenant_id" class="form-label">Khách thuê</label>
                                <select class="form-select @error('tenant_id') is-invalid @enderror" id="tenant_id" name="tenant_id">
                                    <option value="">Chọn khách thuê (tùy chọn)</option>
                                    @foreach($tenants as $tenant)
                                        <option value="{{ $tenant->id }}">{{ $tenant->full_name }} ({{ $tenant->phone }})</option>
                                    @endforeach
                                </select>
                                <div class="form-text">
                                    <i class="fas fa-info-circle text-info"></i>
                                    Nếu không chọn, hợp đồng sẽ được tạo với thông tin từ lead: <strong>{{ $lead->name }}</strong> ({{ $lead->phone }})
                                </div>
                                @error('tenant_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Contract Number -->
                            <div class="col-md-6 mb-3">
                                <label for="contract_no" class="form-label">Mã hợp đồng</label>
                                <div class="input-group">
                                    <input type="text" class="form-control @error('contract_no') is-invalid @enderror" 
                                           id="contract_no" name="contract_no" value="{{ old('contract_no') }}">
                                    <button type="button" class="btn btn-outline-secondary" id="generateContractNo">
                                        <i class="fas fa-sync-alt"></i> Tự động
                                    </button>
                                </div>
                                @error('contract_no')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Start Date -->
                            <div class="col-md-6 mb-3">
                                <label for="start_date" class="form-label">Ngày bắt đầu <span class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('start_date') is-invalid @enderror" 
                                       id="start_date" name="start_date" value="{{ old('start_date') }}" required>
                                @error('start_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- End Date -->
                            <div class="col-md-6 mb-3">
                                <label for="end_date" class="form-label">Ngày kết thúc <span class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('end_date') is-invalid @enderror" 
                                       id="end_date" name="end_date" value="{{ old('end_date') }}" required>
                                @error('end_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Rent Amount -->
                            <div class="col-md-6 mb-3">
                                <label for="rent_amount" class="form-label">Tiền thuê/tháng <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="text" class="form-control @error('rent_amount') is-invalid @enderror" 
                                           id="rent_amount" name="rent_amount" value="{{ old('rent_amount') }}" 
                                           placeholder="Nhập số tiền" required>
                                    <span class="input-group-text">đ</span>
                                </div>
                                @error('rent_amount')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Deposit Amount -->
                            <div class="col-md-6 mb-3">
                                <label for="deposit_amount" class="form-label">Tiền cọc</label>
                                <div class="input-group">
                                    <input type="text" class="form-control @error('deposit_amount') is-invalid @enderror" 
                                           id="deposit_amount" name="deposit_amount" value="{{ old('deposit_amount') }}" 
                                           placeholder="Nhập số tiền">
                                    <span class="input-group-text">đ</span>
                                </div>
                                @error('deposit_amount')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Billing Day -->
                            <div class="col-md-6 mb-3">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>Ngày tạo hóa đơn:</strong> Sẽ được lấy từ Payment Cycle. Vui lòng cài đặt Payment Cycle để thiết lập ngày tạo hóa đơn.
                                </div>
                            </div>

                            <!-- Status -->
                            <div class="col-md-6 mb-3">
                                <label for="status" class="form-label">Trạng thái <span class="text-danger">*</span></label>
                                <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                                    <option value="draft" {{ old('status') == 'draft' ? 'selected' : '' }}>Nháp</option>
                                    <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Hoạt động</option>
                                    <option value="terminated" {{ old('status') == 'terminated' ? 'selected' : '' }}>Đã chấm dứt</option>
                                    <option value="expired" {{ old('status') == 'expired' ? 'selected' : '' }}>Hết hạn</option>
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Signed Date -->
                            <div class="col-md-6 mb-3">
                                <label for="signed_at" class="form-label">Ngày ký hợp đồng</label>
                                <input type="datetime-local" class="form-control @error('signed_at') is-invalid @enderror" 
                                       id="signed_at" name="signed_at" value="{{ old('signed_at') }}">
                                @error('signed_at')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Services Section -->
                        <hr>
                        <h6 class="mb-3">
                            <i class="fas fa-cogs me-2"></i>Dịch vụ bổ sung
                        </h6>
                        
                        <div id="services-container">
                            <div class="service-item row mb-3">
                                <div class="col-md-6">
                                    <select class="form-select" name="services[0][service_id]">
                                        <option value="">Chọn dịch vụ</option>
                                        @foreach($services as $service)
                                            <option value="{{ $service->id }}">{{ $service->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <div class="input-group">
                                        <input type="number" class="form-control" name="services[0][price]" placeholder="Giá" step="0.01" min="0">
                                        <span class="input-group-text">đ</span>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <button type="button" class="btn btn-outline-danger btn-sm remove-service">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <button type="button" class="btn btn-outline-primary btn-sm" id="add-service">
                            <i class="fas fa-plus me-1"></i>Thêm dịch vụ
                        </button>

                        <!-- Form Actions -->
                        <hr>
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('staff.leads.show', $lead->id) }}" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-1"></i>Hủy
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Tạo hợp đồng
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    let serviceIndex = 1;

    // Property change handler
    document.getElementById('property_id').addEventListener('change', function() {
        const propertyId = this.value;
        const unitSelect = document.getElementById('unit_id');
        
        unitSelect.innerHTML = '<option value="">Đang tải...</option>';
        
        if (propertyId) {
            fetch(`/agent/api/leases/units/${propertyId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    unitSelect.innerHTML = '<option value="">Chọn phòng</option>';
                    
                    // Check if data is an array
                    if (Array.isArray(data)) {
                        data.forEach(unit => {
                            if (!unit.has_active_lease) {
                                const option = document.createElement('option');
                                option.value = unit.id;
                                option.textContent = `${unit.code} - ${unit.unit_type} (${unit.area_m2}m²)`;
                                unitSelect.appendChild(option);
                            }
                        });
                        
                        if (data.length === 0) {
                            const option = document.createElement('option');
                            option.value = '';
                            option.textContent = 'Không có phòng nào';
                            unitSelect.appendChild(option);
                        }
                    } else if (data.error) {
                        // Handle error response
                        const option = document.createElement('option');
                        option.value = '';
                        option.textContent = 'Lỗi: ' + data.error;
                        unitSelect.appendChild(option);
                    }
                })
                .catch(error => {
                    console.error('Error loading units:', error);
                    unitSelect.innerHTML = '<option value="">Lỗi tải dữ liệu</option>';
                });
        } else {
            unitSelect.innerHTML = '<option value="">Chọn phòng</option>';
        }
    });

    // Generate contract number
    document.getElementById('generateContractNo').addEventListener('click', function() {
        fetch('/agent/api/leases/next-contract-number')
            .then(response => response.json())
            .then(data => {
                if (data.contract_no) {
                    document.getElementById('contract_no').value = data.contract_no;
                }
            })
            .catch(error => {
                console.error('Error generating contract number:', error);
            });
    });

    // Add service
    document.getElementById('add-service').addEventListener('click', function() {
        const container = document.getElementById('services-container');
        const newService = document.createElement('div');
        newService.className = 'service-item row mb-3';
        newService.innerHTML = `
            <div class="col-md-6">
                <select class="form-select" name="services[${serviceIndex}][service_id]">
                    <option value="">Chọn dịch vụ</option>
                    @foreach($services as $service)
                        <option value="{{ $service->id }}">{{ $service->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <div class="input-group">
                    <input type="number" class="form-control" name="services[${serviceIndex}][price]" placeholder="Giá" step="0.01" min="0">
                    <span class="input-group-text">đ</span>
                </div>
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-outline-danger btn-sm remove-service">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        `;
        container.appendChild(newService);
        serviceIndex++;
    });

    // Remove service
    document.addEventListener('click', function(e) {
        if (e.target.closest('.remove-service')) {
            e.target.closest('.service-item').remove();
        }
    });

    // Format currency inputs
    function formatCurrency(input) {
        let value = input.value.replace(/[^\d]/g, '');
        if (value) {
            value = parseInt(value).toLocaleString('vi-VN');
        }
        input.value = value;
    }

    document.getElementById('rent_amount').addEventListener('input', function() {
        formatCurrency(this);
    });

    document.getElementById('deposit_amount').addEventListener('input', function() {
        formatCurrency(this);
    });

    // Trigger property change if pre-selected
    const propertySelect = document.getElementById('property_id');
    if (propertySelect.value) {
        propertySelect.dispatchEvent(new Event('change'));
    }
});
</script>
@endpush
