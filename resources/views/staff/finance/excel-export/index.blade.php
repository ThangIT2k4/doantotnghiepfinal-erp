@extends('layouts.staff_dashboard')

@section('title', 'Xuất Excel')

@section('content')
<div class="container-fluid">
    <h1 class="h3 mb-4"><i class="fas fa-file-excel"></i> Xuất Excel</h1>
    
    <div class="row">
        <!-- Form chọn loại export và filter -->
        <div class="col-12 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-filter"></i> Chọn loại xuất và bộ lọc</h5>
                </div>
                <div class="card-body">
                    <form id="exportFilterForm">
                        @csrf
                        <input type="hidden" name="limit" value="10">
                        
                        <!-- Chọn loại export -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                                <label class="form-label fw-bold">Chọn loại xuất Excel:</label>
                                <select name="export_type" id="exportTypeSelect" class="form-select form-select-lg" required>
                                    <option value="">-- Chọn loại xuất --</option>
                                    @foreach($exportTypes as $key => $type)
                                        <option value="{{ $key }}" data-icon="{{ $type['icon'] }}" data-color="{{ $type['color'] }}">
                                            {{ $type['name'] }}
                                        </option>
                                            @endforeach
                                </select>
                            </div>
                        </div>
                        
                        <!-- Filter động sẽ được load ở đây -->
                        <div id="filter-container">
                            <p class="text-muted">Vui lòng chọn loại xuất để hiển thị bộ lọc</p>
                        </div>
                        
                        <!-- Preview Button -->
                        <div class="row mt-3" id="preview-button-container" style="display: none;">
                            <div class="col-md-12">
                                <button type="button" 
                                        class="btn btn-info"
                                        id="previewButton">
                                    <i class="fas fa-eye"></i> Xem trước dữ liệu
                                </button>
                    </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Preview Container -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-table"></i> Xem trước dữ liệu</h5>
                    <div id="preview-loading" class="htmx-indicator">
                        <span class="spinner-border spinner-border-sm" role="status"></span>
                        <span class="ms-2">Đang tải...</span>
    </div>
                </div>
                <div class="card-body">
                    <div id="preview-container">
                        <p class="text-muted text-center py-4">
                            <i class="fas fa-info-circle"></i> Chọn loại xuất và bộ lọc để xem trước dữ liệu
                        </p>
                        </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Export Button -->
    <div class="row mt-4" id="export-button-container" style="display: none;">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <button type="button" id="exportButton" class="btn btn-success btn-lg">
                        <i class="fas fa-download"></i> Xuất Excel
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const exportTypeSelect = document.getElementById('exportTypeSelect');
    const filterContainer = document.getElementById('filter-container');
    const previewButtonContainer = document.getElementById('preview-button-container');
    const exportButtonContainer = document.getElementById('export-button-container');
    const exportButton = document.getElementById('exportButton');
    
    // Filter configs cho từng loại export
    const filterConfigs = {
        'properties': {
            filters: [
                { name: 'status', label: 'Trạng thái', type: 'select', options: [
                    { value: '', text: 'Tất cả' },
                    { value: '1', text: 'Hoạt động' },
                    { value: '0', text: 'Không hoạt động' }
                ]},
                { name: 'date_from', label: 'Từ ngày', type: 'date' },
                { name: 'date_to', label: 'Đến ngày', type: 'date' }
            ],
            route: '{{ route("staff.excel-export.properties") }}'
        },
        'units': {
            filters: [
                { name: 'status', label: 'Trạng thái', type: 'select', options: [
                    { value: '', text: 'Tất cả' },
                    { value: 'available', text: 'Có sẵn' },
                    { value: 'reserved', text: 'Đã đặt' },
                    { value: 'occupied', text: 'Đã thuê' },
                    { value: 'maintenance', text: 'Bảo trì' }
                ]},
                { name: 'date_from', label: 'Từ ngày', type: 'date' },
                { name: 'date_to', label: 'Đến ngày', type: 'date' }
            ],
            route: '{{ route("staff.excel-export.units") }}'
        },
        'invoices': {
            filters: [
                { name: 'status', label: 'Trạng thái', type: 'select', options: [
                    { value: '', text: 'Tất cả' },
                    { value: 'draft', text: 'Nháp' },
                    { value: 'issued', text: 'Đã phát hành' },
                    { value: 'paid', text: 'Đã thanh toán' },
                    { value: 'overdue', text: 'Quá hạn' },
                    { value: 'cancelled', text: 'Đã hủy' }
                ]},
                { name: 'date_from', label: 'Từ ngày', type: 'date' },
                { name: 'date_to', label: 'Đến ngày', type: 'date' }
            ],
            route: '{{ route("staff.excel-export.invoices") }}'
        },
        'payments': {
            filters: [
                { name: 'status', label: 'Trạng thái', type: 'select', options: [
                    { value: '', text: 'Tất cả' },
                    { value: 'pending', text: 'Chờ xử lý' },
                    { value: 'success', text: 'Thành công' },
                    { value: 'failed', text: 'Thất bại' },
                    { value: 'refunded', text: 'Đã hoàn tiền' }
                ]},
                { name: 'method_id', label: 'Phương thức thanh toán', type: 'select', options: [
                    { value: '', text: 'Tất cả' },
                    @foreach($paymentMethods as $method)
                    { value: '{{ $method->id }}', text: '{{ $method->name }}' },
                    @endforeach
                ]},
                { name: 'date_from', label: 'Từ ngày', type: 'date' },
                { name: 'date_to', label: 'Đến ngày', type: 'date' }
            ],
            route: '{{ route("staff.excel-export.payments") }}'
        },
        'leases': {
            filters: [
                { name: 'status', label: 'Trạng thái', type: 'select', options: [
                    { value: '', text: 'Tất cả' },
                    { value: 'draft', text: 'Nháp' },
                    { value: 'active', text: 'Đang hoạt động' },
                    { value: 'terminated', text: 'Đã chấm dứt' },
                    { value: 'expired', text: 'Hết hạn' }
                ]},
                { name: 'date_from', label: 'Từ ngày', type: 'date' },
                { name: 'date_to', label: 'Đến ngày', type: 'date' }
            ],
            route: '{{ route("staff.excel-export.leases") }}'
        },
        'payroll': {
            filters: [
                { name: 'status', label: 'Trạng thái', type: 'select', options: [
                    { value: '', text: 'Tất cả' },
                    { value: 'pending', text: 'Chờ thanh toán' },
                    { value: 'paid', text: 'Đã thanh toán' }
                ]},
                { name: 'date_from', label: 'Từ ngày', type: 'date' },
                { name: 'date_to', label: 'Đến ngày', type: 'date' }
            ],
            route: '{{ route("staff.excel-export.payroll") }}'
        },
        'company-invoices': {
            filters: [
                { name: 'status', label: 'Trạng thái', type: 'select', options: [
                    { value: '', text: 'Tất cả' },
                    { value: 'draft', text: 'Nháp' },
                    { value: 'pending', text: 'Chờ xử lý' },
                    { value: 'approved', text: 'Đã phê duyệt' },
                    { value: 'paid', text: 'Đã thanh toán' },
                    { value: 'overdue', text: 'Quá hạn' },
                    { value: 'cancelled', text: 'Đã hủy' }
                ]},
                { name: 'invoice_type', label: 'Loại hóa đơn', type: 'select', options: [
                    { value: '', text: 'Tất cả' },
                    { value: 'master_lease', text: 'Hợp đồng tổng' },
                    { value: 'ticket_cost', text: 'Chi phí ticket' },
                    { value: 'deposit_refund', text: 'Hoàn cọc' },
                    { value: 'payroll_payslip', text: 'Phiếu lương' },
                    { value: 'landlord_payout', text: 'Chi trả chủ nhà' },
                    { value: 'user_payout', text: 'Chi trả người dùng' },
                    { value: 'utility', text: 'Tiện ích' },
                    { value: 'maintenance', text: 'Bảo trì' },
                    { value: 'service', text: 'Dịch vụ' },
                    { value: 'supply', text: 'Cung cấp' },
                    { value: 'other', text: 'Khác' }
                ]},
                { name: 'date_from', label: 'Từ ngày', type: 'date' },
                { name: 'date_to', label: 'Đến ngày', type: 'date' }
            ],
            route: '{{ route("staff.excel-export.company-invoices") }}'
        },
        'cash-outflows': {
            filters: [
                { name: 'status', label: 'Trạng thái', type: 'select', options: [
                    { value: '', text: 'Tất cả' },
                    { value: 'pending', text: 'Chờ xử lý' },
                    { value: 'success', text: 'Thành công' },
                    { value: 'failed', text: 'Thất bại' },
                    { value: 'reversed', text: 'Đã hoàn' }
                ]},
                { name: 'method_id', label: 'Phương thức thanh toán', type: 'select', options: [
                    { value: '', text: 'Tất cả' },
                    @foreach($paymentMethods as $method)
                    { value: '{{ $method->id }}', text: '{{ $method->name }}' },
                    @endforeach
                ]},
                { name: 'date_from', label: 'Từ ngày', type: 'date' },
                { name: 'date_to', label: 'Đến ngày', type: 'date' }
            ],
            route: '{{ route("staff.excel-export.cash-outflows") }}'
        }
    };
    
    // Xử lý khi chọn loại export
    exportTypeSelect.addEventListener('change', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const exportType = this.value;
        
        if (!exportType) {
            filterContainer.innerHTML = '<p class="text-muted">Vui lòng chọn loại xuất để hiển thị bộ lọc</p>';
            previewButtonContainer.style.display = 'none';
            exportButtonContainer.style.display = 'none';
            document.getElementById('preview-container').innerHTML = '<p class="text-muted text-center py-4"><i class="fas fa-info-circle"></i> Chọn loại xuất và bộ lọc để xem trước dữ liệu</p>';
            return;
        }
        
        const config = filterConfigs[exportType];
        if (!config) {
            filterContainer.innerHTML = '<p class="text-danger">Loại xuất không hợp lệ</p>';
            return;
        }
        
        // Render filters
        let filterHtml = '<div class="row g-3">';
        config.filters.forEach(filter => {
            filterHtml += '<div class="col-md-3">';
            filterHtml += `<label class="form-label">${filter.label}:</label>`;
            
            if (filter.type === 'select') {
                filterHtml += `<select name="${filter.name}" class="form-select">`;
                filter.options.forEach(opt => {
                    filterHtml += `<option value="${opt.value}">${opt.text}</option>`;
                });
                filterHtml += '</select>';
            } else if (filter.type === 'date') {
                filterHtml += `<input type="date" name="${filter.name}" class="form-control">`;
            }
            
            filterHtml += '</div>';
        });
        filterHtml += '</div>';
        
        filterContainer.innerHTML = filterHtml;
        previewButtonContainer.style.display = 'block';
        exportButtonContainer.style.display = 'block';
        
        // Lưu route export
        exportButton.setAttribute('data-export-route', config.route);
        
        // Thêm event listener cho các filter mới được tạo
        setTimeout(() => {
            const filterSelects = filterContainer.querySelectorAll('select');
            const filterInputs = filterContainer.querySelectorAll('input[type="date"]');
            
            // Debounce function
            let debounceTimer;
            const loadPreview = () => {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => {
                    const form = document.getElementById('exportFilterForm');
                    const formData = new FormData(form);
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                    const previewContainer = document.getElementById('preview-container');
                    const loadingIndicator = document.getElementById('preview-loading');
                    
                    // Hiển thị loading
                    if (loadingIndicator) {
                        loadingIndicator.style.display = 'block';
                    }
                    
                    fetch('{{ route("staff.excel-export.preview") }}', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken || '',
                            'Accept': 'text/html'
                        }
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.text();
                    })
                    .then(html => {
                        if (previewContainer) {
                            previewContainer.innerHTML = html;
                        }
                    })
                    .catch(error => {
                        console.error('Error loading preview:', error);
                        if (previewContainer) {
                            previewContainer.innerHTML = '<div class="alert alert-danger">Lỗi khi tải dữ liệu preview. Vui lòng thử lại.</div>';
                        }
                    })
                    .finally(() => {
                        if (loadingIndicator) {
                            loadingIndicator.style.display = 'none';
                        }
                    });
                }, 500);
            };
            
            // Thêm event listener cho các filter
            filterSelects.forEach(select => {
                select.addEventListener('change', loadPreview);
            });
            
            filterInputs.forEach(input => {
                input.addEventListener('change', loadPreview);
            });
            
            // Tự động load preview lần đầu
            loadPreview();
        }, 100);
    });
    
    // Xử lý preview button
    const previewButton = document.getElementById('previewButton');
    if (previewButton) {
        previewButton.addEventListener('click', function() {
            const form = document.getElementById('exportFilterForm');
            const formData = new FormData(form);
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const previewContainer = document.getElementById('preview-container');
            const loadingIndicator = document.getElementById('preview-loading');
            
            // Hiển thị loading
            if (loadingIndicator) {
                loadingIndicator.style.display = 'block';
            }
            
            fetch('{{ route("staff.excel-export.preview") }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken || '',
                    'Accept': 'text/html'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.text();
            })
            .then(html => {
                if (previewContainer) {
                    previewContainer.innerHTML = html;
                }
            })
            .catch(error => {
                console.error('Error loading preview:', error);
                if (previewContainer) {
                    previewContainer.innerHTML = '<div class="alert alert-danger">Lỗi khi tải dữ liệu preview. Vui lòng thử lại.</div>';
                }
            })
            .finally(() => {
                if (loadingIndicator) {
                    loadingIndicator.style.display = 'none';
                }
            });
        });
    }
    
    // Xử lý export button
    exportButton.addEventListener('click', function() {
        const exportType = exportTypeSelect.value;
        if (!exportType) {
            alert('Vui lòng chọn loại xuất');
            return;
        }
        
        const route = this.getAttribute('data-export-route');
        if (!route) {
            alert('Không tìm thấy route export');
        return;
    }
    
        // Lấy form data
        const formData = new FormData(document.getElementById('exportFilterForm'));
        const params = new URLSearchParams();
        
        // Chỉ lấy filter params, không lấy export_type và limit
        for (const [key, value] of formData.entries()) {
            if (key !== 'export_type' && key !== 'limit') {
                params.append(key, value);
            }
        }
        
        // Mở trong tab mới để download
        window.open(route + '?' + params.toString(), '_blank');
    });
});
</script>
@endsection
