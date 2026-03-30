@extends('layouts.staff_dashboard')

@section('title', 'Lịch tổng quan')

@section('content')
<main class="main-content">
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="h3 mb-0 text-gray-800">
                            <i class="fas fa-calendar me-2"></i>Lịch tổng quan
                        </h1>
                        <p class="text-muted mb-0">Xem lịch hẹn theo dạng lịch tháng</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('staff.viewings.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i>Tạo lịch hẹn mới
                        </a>
                        <a href="{{ route('staff.viewings.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-list me-1"></i>Danh sách
                        </a>
                        <a href="{{ route('staff.viewings.statistics') }}" class="btn btn-outline-success">
                            <i class="fas fa-chart-bar me-1"></i>Thống kê
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Calendar -->
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div id="calendar"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Legend -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h6 class="mb-3">
                            <i class="fas fa-info-circle me-2"></i>Chú thích:
                        </h6>
                        <div class="d-flex flex-wrap gap-4">
                            <div class="d-flex align-items-center">
                                <div class="calendar-viewing lead me-2" style="width: 24px; height: 24px; border-radius: 4px;"></div>
                                <span class="fw-semibold">Lead</span>
                            </div>
                            <div class="d-flex align-items-center">
                                <div class="calendar-viewing tenant me-2" style="width: 24px; height: 24px; border-radius: 4px;"></div>
                                <span class="fw-semibold">Khách thuê</span>
                            </div>
                            <div class="d-flex align-items-center">
                                <span class="badge bg-warning me-2">
                                    <i class="fas fa-clock me-1"></i>Chờ xác nhận
                                </span>
                                <span>Chờ xác nhận</span>
                            </div>
                            <div class="d-flex align-items-center">
                                <span class="badge bg-info me-2">
                                    <i class="fas fa-check-circle me-1"></i>Đã xác nhận
                                </span>
                                <span>Đã xác nhận</span>
                            </div>
                            <div class="d-flex align-items-center">
                                <span class="badge bg-success me-2">
                                    <i class="fas fa-check me-1"></i>Hoàn thành
                                </span>
                                <span>Hoàn thành</span>
                            </div>
                            <div class="d-flex align-items-center">
                                <span class="badge bg-danger me-2">
                                    <i class="fas fa-user-times me-1"></i>Không đến
                                </span>
                                <span>Không đến</span>
                            </div>
                            <div class="d-flex align-items-center">
                                <span class="badge bg-secondary me-2">
                                    <i class="fas fa-times me-1"></i>Đã hủy
                                </span>
                                <span>Đã hủy</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Viewing Detail Modal -->
<div class="modal fade" id="viewingDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewingModalTitle">Chi tiết lịch hẹn</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="viewingDetailContent">
                <!-- Content will be populated by JavaScript -->
            </div>
            <div class="modal-footer" id="viewingDetailFooter">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                <button type="button" class="btn btn-warning" id="editViewingBtn">
                    <i class="fas fa-edit me-1"></i>Chỉnh sửa
                </button>
                <a href="#" class="btn btn-primary" id="viewingDetailLink">Xem chi tiết</a>
            </div>
        </div>
    </div>
</div>

<!-- Create Viewing Modal (Drag & Drop) -->
<div class="modal fade" id="createViewingModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-calendar-plus me-2"></i>Tạo lịch hẹn mới
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="createViewingForm" action="{{ route('staff.viewings.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Thời gian đã chọn:</strong> <span id="selectedTimeRange"></span>
                    </div>
                    
                    <!-- Customer Type Selection -->
                    <div class="mb-3">
                        <label class="form-label">Loại khách hàng <span class="text-danger">*</span></label>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="customer_type" id="customerTypeLead" value="lead" checked>
                                    <label class="form-check-label" for="customerTypeLead">
                                        <i class="fas fa-user-plus me-1"></i>Lead
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="customer_type" id="customerTypeTenant" value="tenant">
                                    <label class="form-check-label" for="customerTypeTenant">
                                        <i class="fas fa-user me-1"></i>Khách thuê
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Lead/Tenant Selection -->
                    <div class="mb-3" id="leadSelection">
                        <label for="lead_id" class="form-label">Lead</label>
                        <select class="form-select" id="lead_id" name="lead_id">
                            <option value="">Chọn lead hoặc nhập thông tin mới bên dưới</option>
                        </select>
                        <small class="form-text text-muted">Hoặc nhập thông tin lead mới bên dưới</small>
                    </div>
                    
                    <div class="mb-3" id="tenantSelection" style="display: none;">
                        <label for="tenant_id" class="form-label">Khách thuê <span class="text-danger">*</span></label>
                        <select class="form-select" id="tenant_id" name="tenant_id">
                            <option value="">Chọn khách thuê</option>
                        </select>
                    </div>
                    
                    <!-- New Lead Info (if creating new lead) -->
                    <div class="mb-3" id="newLeadInfo">
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <label for="lead_name" class="form-label">Tên lead</label>
                                <input type="text" class="form-control" id="lead_name" name="lead_name" placeholder="Nhập tên lead">
                            </div>
                            <div class="col-md-6 mb-2">
                                <label for="lead_phone" class="form-label">Số điện thoại</label>
                                <input type="text" class="form-control" id="lead_phone" name="lead_phone" placeholder="Nhập số điện thoại">
                            </div>
                            <div class="col-md-6 mb-2">
                                <label for="lead_email" class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="lead_email" name="lead_email" placeholder="Nhập email" required>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Schedule Time -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="schedule_at" class="form-label">Thời gian hẹn <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control" id="schedule_at" name="schedule_at" required>
                        </div>
                        <div class="col-md-6">
                            <label for="duration" class="form-label">Thời lượng (phút)</label>
                            <input type="number" class="form-control" id="duration" name="duration" value="60" min="15" step="15">
                        </div>
                    </div>
                    
                    <!-- Property Selection -->
                    <div class="mb-3">
                        <label for="property_id" class="form-label">Bất động sản <span class="text-danger">*</span></label>
                        <select class="form-select" id="property_id" name="property_id" required>
                            <option value="">Chọn bất động sản</option>
                        </select>
                    </div>
                    
                    <!-- Unit Selection -->
                    <div class="mb-3">
                        <label for="unit_id" class="form-label">Phòng</label>
                        <select class="form-select" id="unit_id" name="unit_id" disabled>
                            <option value="">Chọn bất động sản trước</option>
                        </select>
                    </div>
                    
                    <!-- Status Selection -->
                    <div class="mb-3">
                        <label for="status" class="form-label">Trạng thái <span class="text-danger">*</span></label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="requested" selected>Chờ xác nhận</option>
                            <option value="confirmed">Đã xác nhận</option>
                        </select>
                    </div>
                    
                    <!-- Agent Selection -->
                    <div class="mb-3">
                        <label for="agent_id" class="form-label">Người phụ trách</label>
                        <select class="form-select" id="agent_id" name="agent_id">
                            <option value="">Chọn người phụ trách</option>
                        </select>
                        <input type="hidden" id="current_user_id" value="{{ auth()->id() }}">
                    </div>
                    
                    <!-- Note -->
                    <div class="mb-3">
                        <label for="note" class="form-label">Ghi chú</label>
                        <textarea class="form-control" id="note" name="note" rows="3" placeholder="Nhập ghi chú (nếu có)"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Tạo lịch hẹn
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('styles')
<!-- FullCalendar CSS -->
<link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css' rel='stylesheet' />
<style>
    #calendar {
        max-width: 100%;
        margin: 0 auto;
        min-height: 600px;
        padding: 20px;
    }
    
    .fc-toolbar-title {
        font-size: 1.5rem;
        font-weight: 600;
    }
    
    .fc-event {
        cursor: move;
        border-radius: 4px;
        padding: 4px 6px;
        white-space: normal;
        word-wrap: break-word;
        overflow: visible;
        min-height: 24px;
        transition: all 0.2s ease;
    }
    
    .fc-event-title {
        white-space: normal;
        word-wrap: break-word;
        overflow: visible;
        line-height: 1.3;
    }
    
    .fc-event:hover {
        opacity: 0.9;
        transform: scale(1.02);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        z-index: 1000;
    }
    
    .fc-event-dragging {
        opacity: 0.6;
        cursor: move;
    }
    
    .fc-event-resizing {
        opacity: 0.6;
        cursor: ns-resize;
    }
    
    .fc-event-tenant {
        border-left: 4px solid #2e7d32;
        background-color: rgba(46, 125, 50, 0.1) !important;
    }
    
    .fc-event-lead {
        border-left: 4px solid #f57c00;
        background-color: rgba(245, 124, 0, 0.1) !important;
    }
    
    .fc-event-title {
        display: flex;
        align-items: center;
        gap: 4px;
        flex-wrap: wrap;
    }
    
    .fc-event-title i {
        font-size: 0.9em;
    }
    
    .fc-event-tenant .fc-event-title i.fa-user {
        color: #2e7d32;
    }
    
    .fc-event-lead .fc-event-title i.fa-user-plus {
        color: #f57c00;
    }
    
    .fc-event-title .badge {
        font-size: 0.65em;
        padding: 0.15em 0.4em;
        line-height: 1.2;
        white-space: nowrap;
    }
    
    .fc-event-title .badge i {
        font-size: 0.85em;
    }
    
    /* Legend styles */
    .calendar-viewing {
        width: 20px;
        height: 20px;
        border-radius: 4px;
        display: inline-block;
        margin-right: 8px;
    }
    
    .calendar-viewing.lead {
        background: linear-gradient(135deg, #fff3e0 0%, #fce4ec 100%);
        border: 2px solid #f57c00;
    }
    
    .calendar-viewing.tenant {
        background: linear-gradient(135deg, #e8f5e8 0%, #f1f8e9 100%);
        border: 2px solid #2e7d32;
    }
    
    /* FullCalendar custom styles */
    .fc-day-today {
        background-color: #e3f2fd !important;
    }
    
    .fc-button-primary {
        background-color: #0d6efd;
        border-color: #0d6efd;
    }
    
    .fc-button-primary:hover {
        background-color: #0b5ed7;
        border-color: #0a58ca;
    }
    
    .fc-button-primary:disabled {
        background-color: #6c757d;
        border-color: #6c757d;
    }
    
    /* Select mirror styles */
    .fc-highlight {
        background-color: rgba(13, 110, 253, 0.1);
        border: 2px dashed #0d6efd;
    }
    
    .fc-select-mirror {
        background-color: rgba(13, 110, 253, 0.2);
        border: 2px solid #0d6efd;
        opacity: 0.5;
    }
    
    /* Modal improvements */
    .modal-body .row {
        margin-bottom: 0.5rem;
    }
    
    .modal-body strong {
        color: #495057;
        font-weight: 600;
    }
    
    .customer-type-badge {
        display: inline-block;
        padding: 0.2rem 0.4rem;
        border-radius: 0.25rem;
        font-size: 0.65rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .customer-type-badge.tenant {
        background-color: #e8f5e8;
        color: #2e7d32;
        border: 1px solid #4caf50;
    }
    
    .customer-type-badge.lead {
        background-color: #fff3e0;
        color: #e65100;
        border: 1px solid #ff9800;
    }
</style>
@endpush

@push('scripts')
<!-- FullCalendar -->
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/locales/vi.global.min.js'></script>
<script>
(function() {
    'use strict';
    
    // Global variables
    let calendarEl;
    let calendar;
    let viewingsData = @json($viewings ?? []);
    let currentViewing = null;
    
    // Helper function to safely get customer name
    function getCustomerName(viewing) {
        if (viewing.tenant_id || (viewing.tenant && viewing.tenant.id)) {
            if (viewing.tenant) {
                if (viewing.tenant.userProfile && viewing.tenant.userProfile.full_name) {
                    return viewing.tenant.userProfile.full_name;
                }
                if (viewing.tenant.full_name) {
                    return viewing.tenant.full_name;
                }
                if (viewing.tenant.name) {
                    return viewing.tenant.name;
                }
            }
            return 'Khách thuê';
        }
        
        if (viewing.lead && viewing.lead.name) {
            return viewing.lead.name;
        }
        if (viewing.lead_name) {
            return viewing.lead_name;
        }
        return 'Khách hàng';
    }
    
    // Helper function to safely get agent name
    function getAgentName(viewing) {
        if (!viewing.agent_id && (!viewing.agent || !viewing.agent.id)) {
            return 'Chưa phân công';
        }
        
        if (viewing.agent) {
            if (viewing.agent.userProfile && viewing.agent.userProfile.full_name) {
                return viewing.agent.userProfile.full_name;
            }
            if (viewing.agent.full_name) {
                return viewing.agent.full_name;
            }
            if (viewing.agent.name) {
                return viewing.agent.name;
            }
        }
        return 'Chưa có tên';
    }
    
    // Helper functions for status display
    function getStatusText(status) {
        const statusTexts = {
            'requested': 'Chờ xác nhận',
            'confirmed': 'Đã xác nhận',
            'done': 'Hoàn thành',
            'completed': 'Hoàn thành',
            'no_show': 'Không đến',
            'cancelled': 'Đã hủy'
        };
        return statusTexts[status] || 'Không xác định';
    }
    
    function getStatusIcon(status) {
        const statusIcons = {
            'requested': 'fa-clock',
            'confirmed': 'fa-check-circle',
            'done': 'fa-check',
            'completed': 'fa-check',
            'no_show': 'fa-user-times',
            'cancelled': 'fa-times'
        };
        return statusIcons[status] || 'fa-circle';
    }
    
    function getStatusBadgeClass(status) {
        const statusClasses = {
            'requested': 'bg-warning',
            'confirmed': 'bg-info',
            'done': 'bg-success',
            'completed': 'bg-success',
            'no_show': 'bg-danger',
            'cancelled': 'bg-secondary'
        };
        return statusClasses[status] || 'bg-secondary';
    }
    
    function getCustomerTypeIcon(customerType) {
        return customerType === 'tenant' ? 'fa-user' : 'fa-user-plus';
    }
    
    function getCustomerTypeText(customerType, viewing) {
        if (customerType === 'tenant') {
            return 'Khách thuê';
        }
        if (viewing.lead_id && viewing.lead_email) {
            return 'Lead (có tài khoản)';
        }
        return 'Lead';
    }
    
    // Convert viewings data to FullCalendar format
    function convertViewingsToEvents() {
        if (!viewingsData || !Array.isArray(viewingsData)) {
            console.warn('viewingsData is not an array:', viewingsData);
            return [];
        }
        
        return viewingsData.map(viewing => {
            const customerName = getCustomerName(viewing);
            const customerType = (viewing.tenant_id || (viewing.tenant && viewing.tenant.id)) ? 'tenant' : 'lead';
            
            const statusColors = {
                'requested': '#ffc107',
                'confirmed': '#0dcaf0',
                'done': '#198754',
                'completed': '#198754',
                'no_show': '#dc3545',
                'cancelled': '#6c757d'
            };
            
            // Get unit name
            let unitName = '';
            if (viewing.unit) {
                if (viewing.unit.code) {
                    unitName = viewing.unit.code;
                    if (viewing.unit.floor) {
                        unitName += ' - Tầng ' + viewing.unit.floor;
                    }
                    if (viewing.unit.area_m2) {
                        unitName += ', ' + viewing.unit.area_m2 + 'm²';
                    }
                } else if (viewing.unit.floor) {
                    unitName = 'Tầng ' + viewing.unit.floor + (viewing.unit.area_m2 ? ', ' + viewing.unit.area_m2 + 'm²' : '');
                }
            }
            
            // Get address
            let address = '';
            if (viewing.property) {
                if (viewing.property.location2025 && viewing.property.location2025.address) {
                    address = viewing.property.location2025.address;
                } else if (viewing.property.location && viewing.property.location.address) {
                    address = viewing.property.location.address;
                }
            }
            
            // Get status info
            const status = viewing.status || 'requested';
            const statusText = getStatusText(status);
            const statusIcon = getStatusIcon(status);
            const statusBadgeClass = getStatusBadgeClass(status);
            
            // Build title with customer type prefix (HTML will be added in eventDidMount)
            let title = '';
            if (customerType === 'tenant') {
                title = 'Khách thuê: ' + customerName;
            } else {
                title = 'Lead: ' + customerName;
            }
            
            if (unitName) {
                title += ' - ' + unitName;
            }
            if (viewing.property && viewing.property.name) {
                title += ' - ' + viewing.property.name;
            }
            
            return {
                id: viewing.id,
                title: title,
                start: viewing.schedule_at,
                allDay: false,
                color: statusColors[viewing.status] || '#6c757d',
                extendedProps: viewing,
                classNames: customerType === 'tenant' ? ['fc-event-tenant'] : ['fc-event-lead']
            };
        });
    }
    
    // Show viewing detail modal
    function showViewingDetail(viewing) {
        try {
            const modalElement = document.getElementById('viewingDetailModal');
            if (!modalElement) {
                console.error('Modal element not found');
                return;
            }
            
            currentViewing = viewing;
            
            const modal = new bootstrap.Modal(modalElement);
            const content = document.getElementById('viewingDetailContent');
            const modalTitle = document.getElementById('viewingModalTitle');
            
            if (!viewing || !content) {
                console.error('Viewing data or content element not found');
                return;
            }
            
            // Reset to view mode
            modalTitle.textContent = 'Chi tiết lịch hẹn';
            
            // Restore footer to view mode
            const footer = document.getElementById('viewingDetailFooter');
            const footerHTML = document.createElement('div');
            footerHTML.innerHTML = '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button><button type="button" class="btn btn-warning" id="editViewingBtn"><i class="fas fa-edit me-1"></i>Chỉnh sửa</button><a href="#" class="btn btn-primary" id="viewingDetailLink">Xem chi tiết</a>';
            footer.innerHTML = '';
            while (footerHTML.firstChild) {
                footer.appendChild(footerHTML.firstChild);
            }
            
            // Determine customer info
            const customerName = getCustomerName(viewing);
            const customerType = (viewing.tenant_id || (viewing.tenant && viewing.tenant.id)) ? 'tenant' : 'lead';
            const agentName = getAgentName(viewing);
            
            // Build unit display
            let unitDisplay = 'Chưa chọn';
            if (viewing.unit) {
                if (viewing.unit.code) {
                    unitDisplay = viewing.unit.code;
                    if (viewing.unit.floor) {
                        unitDisplay += ' - Tầng ' + viewing.unit.floor;
                    }
                    if (viewing.unit.area_m2) {
                        unitDisplay += ', ' + viewing.unit.area_m2 + 'm²';
                    }
                } else if (viewing.unit.floor) {
                    unitDisplay = 'Tầng ' + viewing.unit.floor + (viewing.unit.area_m2 ? ', ' + viewing.unit.area_m2 + 'm²' : '');
                }
            }
            
            // Build content HTML safely
            const contentDiv = document.createElement('div');
            contentDiv.className = 'row';
            
            contentDiv.innerHTML = '<div class="col-md-6"><strong>Khách hàng:</strong><br>' + customerName + '<span class="customer-type-badge ' + customerType + ' ms-2"><i class="fas ' + getCustomerTypeIcon(customerType) + '"></i> ' + getCustomerTypeText(customerType, viewing) + '</span></div><div class="col-md-6"><strong>Thời gian:</strong><br>' + (viewing.schedule_at ? new Date(viewing.schedule_at).toLocaleString('vi-VN') : 'N/A') + '</div><div class="col-md-6 mt-2"><strong>Bất động sản:</strong><br><span class="text-primary fw-semibold"><i class="fas fa-building me-1"></i>' + (viewing.property ? viewing.property.name : 'N/A') + '</span></div><div class="col-md-6 mt-2"><strong>Phòng:</strong><br><span class="text-info fw-semibold"><i class="fas fa-door-open me-1"></i>' + unitDisplay + '</span></div><div class="col-md-6 mt-2"><strong>Người phụ trách:</strong><br><span class="text-primary fw-semibold"><i class="fas fa-user-tie me-1"></i>' + agentName + '</span></div><div class="col-md-6 mt-2"><strong>Địa chỉ:</strong><br><div class="text-muted"><div class="mb-1"><i class="fas fa-map-marker-alt me-1 text-danger"></i><strong>Địa chỉ cũ:</strong><br><small>' + (viewing.property && viewing.property.location ? viewing.property.location.address : 'Chưa có thông tin') + '</small></div><div><i class="fas fa-map-pin me-1 text-warning"></i><strong>Địa chỉ mới 2025:</strong><br><small>' + (viewing.property && viewing.property.location2025 ? viewing.property.location2025.address : 'Chưa có thông tin') + '</small></div></div></div><div class="col-12 mt-2"><strong>Trạng thái:</strong><br><span class="badge ' + getStatusBadgeClass(viewing.status) + '">' + getStatusText(viewing.status) + '</span></div>';
            
            if (viewing.note) {
                const noteDiv = document.createElement('div');
                noteDiv.className = 'col-12 mt-2';
                noteDiv.innerHTML = '<strong>Ghi chú:</strong><br><div class="bg-light p-2 rounded"></div>';
                noteDiv.querySelector('.bg-light').textContent = viewing.note;
                contentDiv.appendChild(noteDiv);
            }
            
            content.innerHTML = '';
            content.appendChild(contentDiv);
            
            // Set link
            const link = document.getElementById('viewingDetailLink');
            if (link && viewing.id) {
                link.href = '/staff/viewings/' + viewing.id;
            }
            
            // Add edit button event listener
            const editBtn = document.getElementById('editViewingBtn');
            if (editBtn) {
                editBtn.onclick = function() {
                    showEditViewingForm(viewing);
                };
            }
            
            modal.show();
        } catch (error) {
            console.error('Error showing viewing detail:', error);
        }
    }
    
    // Show edit viewing form
    window.showViewingDetail = showViewingDetail;
    window.showEditViewingForm = showEditViewingForm;
    
    function showEditViewingForm(viewing) {
        if (!viewing || !viewing.id) {
            console.error('Viewing data not found');
            return;
        }
        
        const content = document.getElementById('viewingDetailContent');
        const modalTitle = document.getElementById('viewingModalTitle');
        const footer = document.getElementById('viewingDetailFooter');
        
        if (!content) {
            console.error('Modal content not found');
            return;
        }
        
        // Change to edit mode
        modalTitle.textContent = 'Chỉnh sửa lịch hẹn';
        
        // Update footer for edit mode
        const footerHTML = document.createElement('div');
        footerHTML.innerHTML = '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button><button type="button" class="btn btn-warning" id="saveEditViewingBtn"><i class="fas fa-save me-1"></i>Lưu thay đổi</button>';
        footer.innerHTML = '';
        while (footerHTML.firstChild) {
            footer.appendChild(footerHTML.firstChild);
        }
        
        // Format schedule_at for datetime-local input
        const scheduleAt = viewing.schedule_at ? new Date(viewing.schedule_at) : new Date();
        const year = scheduleAt.getFullYear();
        const month = String(scheduleAt.getMonth() + 1).padStart(2, '0');
        const day = String(scheduleAt.getDate()).padStart(2, '0');
        const hours = String(scheduleAt.getHours()).padStart(2, '0');
        const minutes = String(scheduleAt.getMinutes()).padStart(2, '0');
        const datetimeLocal = year + '-' + month + '-' + day + 'T' + hours + ':' + minutes;
        
        // Determine customer type
        const customerType = (viewing.tenant_id || (viewing.tenant && viewing.tenant.id)) ? 'tenant' : 'lead';
        
        // Get CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        const csrfTokenValue = csrfToken ? csrfToken.getAttribute('content') : '';
        
        // Build form HTML safely
        const formHTML = document.createElement('form');
        formHTML.id = 'editViewingForm';
        formHTML.action = '/staff/viewings/' + viewing.id;
        formHTML.method = 'POST';
        
        formHTML.innerHTML = '<input type="hidden" name="_method" value="PUT"><input type="hidden" name="_token" value="' + csrfTokenValue + '"><div class="alert alert-info"><i class="fas fa-info-circle me-2"></i><strong>Đang chỉnh sửa lịch hẹn #' + viewing.id + '</strong></div><div class="mb-3"><label class="form-label">Loại khách hàng <span class="text-danger">*</span></label><div class="row"><div class="col-md-6"><div class="form-check"><input class="form-check-input" type="radio" name="customer_type" id="editCustomerTypeLead" value="lead" ' + (customerType === 'lead' ? 'checked' : '') + '><label class="form-check-label" for="editCustomerTypeLead"><i class="fas fa-user-plus me-1"></i>Lead</label></div></div><div class="col-md-6"><div class="form-check"><input class="form-check-input" type="radio" name="customer_type" id="editCustomerTypeTenant" value="tenant" ' + (customerType === 'tenant' ? 'checked' : '') + '><label class="form-check-label" for="editCustomerTypeTenant"><i class="fas fa-user me-1"></i>Khách thuê</label></div></div></div></div><div class="mb-3" id="editLeadSelection" style="display: ' + (customerType === 'lead' ? 'block' : 'none') + ';"><label for="edit_lead_id" class="form-label">Lead</label><select class="form-select" id="edit_lead_id" name="lead_id"><option value="">Chọn lead hoặc nhập thông tin mới bên dưới</option></select><small class="form-text text-muted">Hoặc nhập thông tin lead mới bên dưới</small></div><div class="mb-3" id="editTenantSelection" style="display: ' + (customerType === 'tenant' ? 'block' : 'none') + ';"><label for="edit_tenant_id" class="form-label">Khách thuê <span class="text-danger">*</span></label><select class="form-select" id="edit_tenant_id" name="tenant_id" ' + (customerType === 'tenant' ? 'required' : '') + '><option value="">Chọn khách thuê</option></select></div><div class="mb-3" id="editNewLeadInfo" style="display: ' + (customerType === 'lead' ? 'block' : 'none') + ';"><div class="row"><div class="col-md-6 mb-2"><label for="edit_lead_name" class="form-label">Tên lead</label><input type="text" class="form-control" id="edit_lead_name" name="lead_name" placeholder="Nhập tên lead"></div><div class="col-md-6 mb-2"><label for="edit_lead_phone" class="form-label">Số điện thoại</label><input type="text" class="form-control" id="edit_lead_phone" name="lead_phone" placeholder="Nhập số điện thoại"></div><div class="col-md-6 mb-2"><label for="edit_lead_email" class="form-label">Email <span class="text-danger">*</span></label><input type="email" class="form-control" id="edit_lead_email" name="lead_email" placeholder="Nhập email" required></div></div></div><div class="row mb-3"><div class="col-md-6"><label for="edit_schedule_at" class="form-label">Thời gian hẹn <span class="text-danger">*</span></label><input type="datetime-local" class="form-control" id="edit_schedule_at" name="schedule_at" value="' + datetimeLocal + '" required></div><div class="col-md-6"><label for="edit_status" class="form-label">Trạng thái <span class="text-danger">*</span></label><select class="form-select" id="edit_status" name="status" required><option value="requested" ' + (viewing.status === 'requested' ? 'selected' : '') + '>Chờ xác nhận</option><option value="confirmed" ' + (viewing.status === 'confirmed' ? 'selected' : '') + '>Đã xác nhận</option><option value="done" ' + ((viewing.status === 'done' || viewing.status === 'completed') ? 'selected' : '') + '>Hoàn thành</option><option value="no_show" ' + (viewing.status === 'no_show' ? 'selected' : '') + '>Không đến</option><option value="cancelled" ' + (viewing.status === 'cancelled' ? 'selected' : '') + '>Đã hủy</option></select></div></div><div class="mb-3"><label for="edit_property_id" class="form-label">Bất động sản <span class="text-danger">*</span></label><select class="form-select" id="edit_property_id" name="property_id" required><option value="">Chọn bất động sản</option></select></div><div class="mb-3"><label for="edit_unit_id" class="form-label">Phòng <span class="text-danger">*</span></label><select class="form-select" id="edit_unit_id" name="unit_id" required disabled><option value="">Chọn bất động sản trước</option></select></div><div class="mb-3"><label for="edit_agent_id" class="form-label">Người phụ trách</label><select class="form-select" id="edit_agent_id" name="agent_id"><option value="">Chọn người phụ trách</option></select></div><div class="mb-3"><label for="edit_note" class="form-label">Ghi chú</label><textarea class="form-control" id="edit_note" name="note" rows="3" placeholder="Nhập ghi chú (nếu có)"></textarea></div>';
        
        content.innerHTML = '';
        content.appendChild(formHTML);
        
        // Set values after form is in DOM
        if (viewing.lead_name) {
            const leadNameInput = document.getElementById('edit_lead_name');
            if (leadNameInput) leadNameInput.value = viewing.lead_name || '';
        }
        if (viewing.lead_phone) {
            const leadPhoneInput = document.getElementById('edit_lead_phone');
            if (leadPhoneInput) leadPhoneInput.value = viewing.lead_phone || '';
        }
        if (viewing.lead_email) {
            const leadEmailInput = document.getElementById('edit_lead_email');
            if (leadEmailInput) leadEmailInput.value = viewing.lead_email || '';
        }
        if (viewing.note) {
            const noteTextarea = document.getElementById('edit_note');
            if (noteTextarea) noteTextarea.textContent = viewing.note || '';
        }
        
        // Load dropdown data
        loadModalData().then(function() {
            populateEditForm(viewing);
        }).catch(function(error) {
            console.error('Error loading modal data:', error);
            populateEditForm(viewing);
        });
        
        // Handle customer type change
        const customerTypeRadios = content.querySelectorAll('input[name="customer_type"]');
        customerTypeRadios.forEach(function(radio) {
            radio.addEventListener('change', function() {
                const leadSelection = document.getElementById('editLeadSelection');
                const tenantSelection = document.getElementById('editTenantSelection');
                const newLeadInfo = document.getElementById('editNewLeadInfo');
                const leadId = document.getElementById('edit_lead_id');
                const tenantId = document.getElementById('edit_tenant_id');
                
                if (this.value === 'lead') {
                    leadSelection.style.display = 'block';
                    tenantSelection.style.display = 'none';
                    newLeadInfo.style.display = 'block';
                    leadId.required = false;
                    tenantId.required = false;
                    tenantId.value = '';
                } else {
                    leadSelection.style.display = 'none';
                    tenantSelection.style.display = 'block';
                    newLeadInfo.style.display = 'none';
                    leadId.required = false;
                    tenantId.required = true;
                    leadId.value = '';
                }
            });
        });
        
        // Handle property change
        const propertySelect = document.getElementById('edit_property_id');
        const unitSelect = document.getElementById('edit_unit_id');
        
        if (propertySelect) {
            propertySelect.addEventListener('change', function() {
                const propertyId = this.value;
                if (propertyId) {
                    unitSelect.disabled = false;
                    fetch('/api/properties/' + propertyId + '/units')
                        .then(function(response) { return response.json(); })
                        .then(function(data) {
                            unitSelect.innerHTML = '<option value="">Chọn phòng</option>';
                            if (data && Array.isArray(data)) {
                                data.forEach(function(unit) {
                                    const option = document.createElement('option');
                                    option.value = unit.id;
                                    const unitInfo = unit.code ? (unit.code + ' - Tầng ' + (unit.floor || 'N/A') + ', ' + (unit.area_m2 || 'N/A') + 'm²') : ('Tầng ' + (unit.floor || 'N/A') + ', ' + (unit.area_m2 || 'N/A') + 'm²');
                                    option.textContent = unitInfo;
                                    unitSelect.appendChild(option);
                                });
                            }
                        })
                        .catch(function(error) {
                            console.error('Error loading units:', error);
                            unitSelect.innerHTML = '<option value="">Lỗi tải danh sách phòng</option>';
                        });
                } else {
                    unitSelect.disabled = true;
                    unitSelect.innerHTML = '<option value="">Chọn bất động sản trước</option>';
                }
            });
        }
        
        // Handle form submission
        const saveBtn = document.getElementById('saveEditViewingBtn');
        if (saveBtn) {
            saveBtn.addEventListener('click', function() {
                const form = document.getElementById('editViewingForm');
                if (form) {
                    submitEditViewingForm(form, viewing.id);
                }
            });
        }
    }
    
    // Populate edit form
    function populateEditForm(viewing) {
        setTimeout(function() {
            // Set property
            const propertySelect = document.getElementById('edit_property_id');
            if (propertySelect && viewing.property && viewing.property.id) {
                propertySelect.value = viewing.property.id;
                propertySelect.dispatchEvent(new Event('change'));
                
                // Set unit after units load
                setTimeout(function() {
                    const unitSelect = document.getElementById('edit_unit_id');
                    if (unitSelect && viewing.unit && viewing.unit.id) {
                        setTimeout(function() {
                            unitSelect.value = viewing.unit.id;
                        }, 500);
                    }
                }, 300);
            }
            
            // Set agent
            setTimeout(function() {
                const agentSelect = document.getElementById('edit_agent_id');
                if (agentSelect && viewing.agent && viewing.agent.id) {
                    agentSelect.value = viewing.agent.id;
                }
            }, 400);
            
            // Set lead/tenant
            setTimeout(function() {
                if (viewing.tenant && viewing.tenant.id) {
                    const tenantSelect = document.getElementById('edit_tenant_id');
                    if (tenantSelect) {
                        tenantSelect.value = viewing.tenant.id;
                    }
                } else if (viewing.lead && viewing.lead.id) {
                    const leadSelect = document.getElementById('edit_lead_id');
                    if (leadSelect) {
                        leadSelect.value = viewing.lead.id;
                    }
                }
            }, 400);
        }, 600);
    }
    
    // Submit edit viewing form
    function submitEditViewingForm(form, viewingId) {
        const formData = new FormData(form);
        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        
        if (!csrfToken) {
            console.error('CSRF token not found');
            alert('Lỗi bảo mật: Không tìm thấy CSRF token. Vui lòng tải lại trang.');
            return;
        }
        
        const submitBtn = document.getElementById('saveEditViewingBtn');
        let originalText = '';
        if (submitBtn) {
            originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Đang cập nhật...';
        }
        
        fetch(form.action, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken.getAttribute('content'),
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(function(response) {
            if (response.ok) {
                return response.json();
            }
            return response.json().then(function(err) { return Promise.reject(err); });
        })
        .then(function(data) {
            const modal = bootstrap.Modal.getInstance(document.getElementById('viewingDetailModal'));
            if (modal) {
                modal.hide();
            }
            
            if (typeof Notify !== 'undefined') {
                Notify.success('Đã cập nhật lịch hẹn thành công!');
            } else {
                alert('Đã cập nhật lịch hẹn thành công!');
            }
            
            window.location.reload();
        })
        .catch(function(error) {
            console.error('Error updating viewing:', error);
            let errorMessage = 'Có lỗi xảy ra khi cập nhật lịch hẹn.';
            
            if (error.errors) {
                const errors = Object.values(error.errors).flat();
                errorMessage = errors.join('\n');
            } else if (error.message) {
                errorMessage = error.message;
            }
            
            if (typeof Notify !== 'undefined') {
                Notify.error(errorMessage);
            } else {
                alert(errorMessage);
            }
            
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        });
    }
    
    // Load data for modal (properties, leads, tenants, agents)
    function loadModalData() {
        return fetch('{{ route("staff.viewings.create") }}', {
            headers: {
                'Accept': 'text/html',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(function(response) { return response.text(); })
        .then(function(html) {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            
            // Load properties for edit form
            const editPropertySelect = document.getElementById('edit_property_id');
            if (editPropertySelect) {
                const propertyOptions = doc.querySelectorAll('#property_id option');
                editPropertySelect.innerHTML = '<option value="">Chọn bất động sản</option>';
                propertyOptions.forEach(function(option) {
                    if (option.value) {
                        const newOption = document.createElement('option');
                        newOption.value = option.value;
                        newOption.textContent = option.textContent;
                        editPropertySelect.appendChild(newOption);
                    }
                });
            }
            
            // Load leads for edit form
            const editLeadSelect = document.getElementById('edit_lead_id');
            if (editLeadSelect) {
                const leadOptions = doc.querySelectorAll('#lead_id option');
                editLeadSelect.innerHTML = '<option value="">Chọn lead</option>';
                leadOptions.forEach(function(option) {
                    if (option.value) {
                        const newOption = document.createElement('option');
                        newOption.value = option.value;
                        newOption.textContent = option.textContent;
                        editLeadSelect.appendChild(newOption);
                    }
                });
            }
            
            // Load tenants for edit form
            const editTenantSelect = document.getElementById('edit_tenant_id');
            if (editTenantSelect) {
                const tenantOptions = doc.querySelectorAll('#tenant_id option');
                editTenantSelect.innerHTML = '<option value="">Chọn khách thuê</option>';
                tenantOptions.forEach(function(option) {
                    if (option.value) {
                        const newOption = document.createElement('option');
                        newOption.value = option.value;
                        newOption.textContent = option.textContent;
                        editTenantSelect.appendChild(newOption);
                    }
                });
            }
            
            // Load agents for edit form
            const editAgentSelect = document.getElementById('edit_agent_id');
            if (editAgentSelect) {
                const agentOptions = doc.querySelectorAll('#agent_id option');
                editAgentSelect.innerHTML = '<option value="">Chọn người phụ trách</option>';
                agentOptions.forEach(function(option) {
                    if (option.value) {
                        const newOption = document.createElement('option');
                        newOption.value = option.value;
                        newOption.textContent = option.textContent;
                        editAgentSelect.appendChild(newOption);
                    }
                });
            }
        })
        .catch(function(error) {
            console.error('Error loading modal data:', error);
            alert('Không thể tải dữ liệu. Vui lòng tải lại trang.');
            throw error;
        });
    }
    
    // Update viewing time when dragged or resized
    function updateViewingTime(event) {
        const viewing = event.extendedProps;
        if (!viewing || !viewing.id) {
            console.error('Viewing data not found');
            event.revert();
            return;
        }
        
        const newStart = event.start;
        const newEnd = event.end || new Date(newStart.getTime() + 60 * 60 * 1000);
        
        // Format datetime for API
        const year = newStart.getFullYear();
        const month = String(newStart.getMonth() + 1).padStart(2, '0');
        const day = String(newStart.getDate()).padStart(2, '0');
        const hours = String(newStart.getHours()).padStart(2, '0');
        const minutes = String(newStart.getMinutes()).padStart(2, '0');
        const seconds = String(newStart.getSeconds()).padStart(2, '0');
        const scheduleAt = year + '-' + month + '-' + day + ' ' + hours + ':' + minutes + ':' + seconds;
        
        const originalTitle = event.title;
        event.setProp('title', originalTitle + ' (Đang cập nhật...)');
        
        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        if (!csrfToken) {
            console.error('CSRF token not found');
            event.revert();
            return;
        }
        
        const formData = new FormData();
        formData.append('_method', 'PUT');
        formData.append('schedule_at', scheduleAt);
        formData.append('customer_type', viewing.tenant ? 'tenant' : 'lead');
        formData.append('property_id', viewing.property && viewing.property.id ? viewing.property.id : '');
        formData.append('unit_id', viewing.unit && viewing.unit.id ? viewing.unit.id : '');
        formData.append('status', viewing.status || 'requested');
        formData.append('note', viewing.note || '');
        formData.append('agent_id', viewing.agent && viewing.agent.id ? viewing.agent.id : '');
        
        if (viewing.tenant) {
            formData.append('tenant_id', viewing.tenant.id);
        } else if (viewing.lead) {
            formData.append('lead_id', viewing.lead.id);
            formData.append('lead_name', viewing.lead.name || viewing.lead_name || '');
            formData.append('lead_phone', viewing.lead.phone || viewing.lead_phone || '');
            formData.append('lead_email', viewing.lead.email || viewing.lead_email || '');
        } else {
            formData.append('lead_name', viewing.lead_name || '');
            formData.append('lead_phone', viewing.lead_phone || '');
            formData.append('lead_email', viewing.lead_email || '');
        }
        
        fetch('/staff/viewings/' + viewing.id, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken.getAttribute('content'),
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(function(response) {
            if (response.ok) {
                return response.json();
            }
            return response.json().then(function(err) { return Promise.reject(err); });
        })
        .then(function(data) {
            event.setStart(newStart);
            if (newEnd) {
                event.setEnd(newEnd);
            }
            event.setProp('title', originalTitle);
            
            if (typeof Notify !== 'undefined') {
                Notify.success('Đã cập nhật thời gian lịch hẹn thành công!');
            }
        })
        .catch(function(error) {
            console.error('Error updating viewing time:', error);
            event.revert();
            
            let errorMessage = 'Có lỗi xảy ra khi cập nhật thời gian.';
            if (error.message) {
                errorMessage = error.message;
            } else if (error.errors) {
                const errors = Object.values(error.errors).flat();
                errorMessage = errors.join('\n');
            }
            
            if (typeof Notify !== 'undefined') {
                Notify.error(errorMessage);
            } else {
                alert(errorMessage);
            }
        });
    }
    
    // Open create viewing modal with selected time
    function openCreateViewingModal(start, end) {
        const modalElement = document.getElementById('createViewingModal');
        if (!modalElement) {
            console.error('Create viewing modal not found');
            return;
        }
        
        const modal = new bootstrap.Modal(modalElement);
        const form = document.getElementById('createViewingForm');
        const scheduleAtInput = document.getElementById('schedule_at');
        const selectedTimeRange = document.getElementById('selectedTimeRange');
        
        // Format datetime for input
        const startDate = new Date(start);
        const endDate = end ? new Date(end) : new Date(start.getTime() + 60 * 60 * 1000);
        
        // Format for display
        const startStr = startDate.toLocaleString('vi-VN', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        });
        const endStr = endDate.toLocaleString('vi-VN', {
            hour: '2-digit',
            minute: '2-digit'
        });
        
        selectedTimeRange.textContent = startStr + ' - ' + endStr;
        
        // Format for datetime-local input
        const year = startDate.getFullYear();
        const month = String(startDate.getMonth() + 1).padStart(2, '0');
        const day = String(startDate.getDate()).padStart(2, '0');
        const hours = String(startDate.getHours()).padStart(2, '0');
        const minutes = String(startDate.getMinutes()).padStart(2, '0');
        const datetimeLocal = year + '-' + month + '-' + day + 'T' + hours + ':' + minutes;
        
        scheduleAtInput.value = datetimeLocal;
        
        // Calculate duration in minutes
        const duration = Math.round((endDate - startDate) / (1000 * 60));
        if (duration > 0) {
            const durationInput = document.getElementById('duration');
            if (durationInput) {
                durationInput.value = duration;
            }
        }
        
        // Reset form
        form.reset();
        scheduleAtInput.value = datetimeLocal;
        document.getElementById('customerTypeLead').checked = true;
        document.getElementById('leadSelection').style.display = 'block';
        document.getElementById('tenantSelection').style.display = 'none';
        document.getElementById('lead_id').required = false;
        document.getElementById('tenant_id').required = false;
        document.getElementById('status').value = 'requested';
        
        // Load data and auto-fill agent
        loadCreateModalData().then(function() {
            const currentUserId = document.getElementById('current_user_id');
            const agentSelect = document.getElementById('agent_id');
            if (currentUserId && agentSelect && currentUserId.value) {
                const userOption = Array.from(agentSelect.options).find(function(option) {
                    return option.value === currentUserId.value;
                });
                if (userOption) {
                    agentSelect.value = currentUserId.value;
                }
            }
        });
        
        modal.show();
    }
    
    // Load data for create modal
    function loadCreateModalData() {
        return fetch('{{ route("staff.viewings.create") }}', {
            headers: {
                'Accept': 'text/html',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(function(response) { return response.text(); })
        .then(function(html) {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            
            // Load properties
            const propertySelect = document.getElementById('property_id');
            if (propertySelect) {
                const propertyOptions = doc.querySelectorAll('#property_id option');
                propertySelect.innerHTML = '<option value="">Chọn bất động sản</option>';
                propertyOptions.forEach(function(option) {
                    if (option.value) {
                        const newOption = document.createElement('option');
                        newOption.value = option.value;
                        newOption.textContent = option.textContent;
                        propertySelect.appendChild(newOption);
                    }
                });
            }
            
            // Load leads
            const leadSelect = document.getElementById('lead_id');
            if (leadSelect) {
                const leadOptions = doc.querySelectorAll('#lead_id option');
                leadSelect.innerHTML = '<option value="">Chọn lead hoặc nhập thông tin mới</option>';
                leadOptions.forEach(function(option) {
                    if (option.value) {
                        const newOption = document.createElement('option');
                        newOption.value = option.value;
                        newOption.textContent = option.textContent;
                        leadSelect.appendChild(newOption);
                    }
                });
            }
            
            // Load tenants
            const tenantSelect = document.getElementById('tenant_id');
            if (tenantSelect) {
                const tenantOptions = doc.querySelectorAll('#tenant_id option');
                tenantSelect.innerHTML = '<option value="">Chọn khách thuê</option>';
                tenantOptions.forEach(function(option) {
                    if (option.value) {
                        const newOption = document.createElement('option');
                        newOption.value = option.value;
                        newOption.textContent = option.textContent;
                        tenantSelect.appendChild(newOption);
                    }
                });
            }
            
            // Load agents
            const agentSelect = document.getElementById('agent_id');
            if (agentSelect) {
                const agentOptions = doc.querySelectorAll('#agent_id option');
                agentSelect.innerHTML = '<option value="">Chọn người phụ trách</option>';
                agentOptions.forEach(function(option) {
                    if (option.value) {
                        const newOption = document.createElement('option');
                        newOption.value = option.value;
                        newOption.textContent = option.textContent;
                        agentSelect.appendChild(newOption);
                    }
                });
            }
        })
        .catch(function(error) {
            console.error('Error loading create modal data:', error);
        });
    }
    
    // Initialize FullCalendar
    document.addEventListener('DOMContentLoaded', function() {
        try {
            if (typeof FullCalendar === 'undefined') {
                console.error('FullCalendar is not loaded');
                return;
            }
            
            calendarEl = document.getElementById('calendar');
            if (!calendarEl) {
                console.error('Calendar element not found');
                return;
            }
            
            calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                locale: 'vi',
                firstDay: 1,
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
                },
                buttonText: {
                    today: 'Hôm nay',
                    month: 'Tháng',
                    week: 'Tuần',
                    day: 'Ngày',
                    list: 'Danh sách'
                },
                events: convertViewingsToEvents(),
                eventClick: function(info) {
                    info.jsEvent.preventDefault();
                    showViewingDetail(info.event.extendedProps);
                },
                eventMouseEnter: function(info) {
                    info.el.style.cursor = 'move';
                },
                eventDrop: function(info) {
                    updateViewingTime(info.event);
                },
                eventResize: function(info) {
                    updateViewingTime(info.event);
                },
                select: function(info) {
                    openCreateViewingModal(info.start, info.end);
                },
                height: 'auto',
                contentHeight: 'auto',
                navLinks: true,
                editable: true,
                eventStartEditable: true,
                eventDurationEditable: true,
                selectable: true,
                selectMirror: true,
                dayMaxEvents: true,
                moreLinkClick: 'popover',
                slotMinTime: '06:00:00',
                slotMaxTime: '23:00:00',
                eventTimeFormat: {
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: false
                },
                slotLabelFormat: {
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: false
                },
                eventDidMount: function(info) {
                    const viewing = info.event.extendedProps;
                    if (viewing) {
                        const scheduleTime = info.event.start.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });
                        const customerName = getCustomerName(viewing);
                        const customerType = (viewing.tenant_id || (viewing.tenant && viewing.tenant.id)) ? 'tenant' : 'lead';
                        const customerTypeText = customerType === 'tenant' ? 'Khách thuê' : 'Lead';
                        
                        let unitName = 'Chưa chọn';
                        if (viewing.unit) {
                            if (viewing.unit.code) {
                                unitName = viewing.unit.code;
                                if (viewing.unit.floor) {
                                    unitName += ' - Tầng ' + viewing.unit.floor;
                                }
                                if (viewing.unit.area_m2) {
                                    unitName += ', ' + viewing.unit.area_m2 + 'm²';
                                }
                            } else if (viewing.unit.floor) {
                                unitName = 'Tầng ' + viewing.unit.floor + (viewing.unit.area_m2 ? ', ' + viewing.unit.area_m2 + 'm²' : '');
                            }
                        }
                        
                        const propertyName = viewing.property && viewing.property.name ? viewing.property.name : 'N/A';
                        const agentName = getAgentName(viewing);
                        
                        let address = '';
                        if (viewing.property) {
                            if (viewing.property.location2025 && viewing.property.location2025.address) {
                                address = viewing.property.location2025.address;
                            } else if (viewing.property.location && viewing.property.location.address) {
                                address = viewing.property.location.address;
                            }
                        }
                        
                        const status = viewing.status || 'requested';
                        const statusText = getStatusText(status);
                        const statusIcon = getStatusIcon(status);
                        const statusBadgeClass = getStatusBadgeClass(status);
                        
                        let tooltipText = scheduleTime + '\n';
                        tooltipText += 'Loại: ' + customerTypeText + '\n';
                        tooltipText += 'Trạng thái: ' + statusText + '\n';
                        tooltipText += 'Khách hàng: ' + customerName + '\n';
                        tooltipText += 'Phòng: ' + unitName + '\n';
                        tooltipText += 'BĐS: ' + propertyName + '\n';
                        tooltipText += 'Người phụ trách: ' + agentName;
                        if (address) {
                            tooltipText += '\nĐịa chỉ: ' + address;
                        }
                        
                        info.el.title = tooltipText;
                        info.el.setAttribute('data-bs-toggle', 'tooltip');
                        info.el.setAttribute('data-bs-placement', 'top');
                        
                        // Replace title with HTML content
                        const titleEl = info.el.querySelector('.fc-event-title');
                        if (titleEl) {
                            let titleHtml = '';
                            if (customerType === 'tenant') {
                                titleHtml = '<i class="fas fa-user me-1"></i>Khách thuê: ' + customerName;
                            } else {
                                titleHtml = '<i class="fas fa-user-plus me-1"></i>Lead: ' + customerName;
                            }
                            titleHtml += ' <span class="badge ' + statusBadgeClass + ' ms-1" style="font-size: 0.7em;"><i class="fas ' + statusIcon + ' me-1"></i>' + statusText + '</span>';
                            if (unitName && unitName !== 'Chưa chọn') {
                                titleHtml += ' - ' + unitName;
                            }
                            if (propertyName && propertyName !== 'N/A') {
                                titleHtml += ' - ' + propertyName;
                            }
                            titleEl.innerHTML = titleHtml;
                        }
                        
                        // Apply customer type and status styling
                        const statusColors = {
                            'requested': { bg: 'rgba(255, 193, 7, 0.25)', border: '#ffc107' },
                            'confirmed': { bg: 'rgba(13, 202, 240, 0.25)', border: '#0dcaf0' },
                            'done': { bg: 'rgba(25, 135, 84, 0.25)', border: '#198754' },
                            'completed': { bg: 'rgba(25, 135, 84, 0.25)', border: '#198754' },
                            'no_show': { bg: 'rgba(220, 53, 69, 0.25)', border: '#dc3545' },
                            'cancelled': { bg: 'rgba(108, 117, 125, 0.25)', border: '#6c757d' }
                        };
                        
                        const statusColor = statusColors[status] || statusColors['requested'];
                        
                        if (customerType === 'tenant') {
                            info.el.classList.add('fc-event-tenant');
                            info.el.style.borderLeftColor = '#2e7d32';
                            info.el.style.borderLeftWidth = '4px';
                            info.el.style.backgroundColor = statusColor.bg;
                            // Add subtle border for status
                            info.el.style.borderTopColor = statusColor.border;
                            info.el.style.borderTopWidth = '2px';
                        } else {
                            info.el.classList.add('fc-event-lead');
                            info.el.style.borderLeftColor = '#f57c00';
                            info.el.style.borderLeftWidth = '4px';
                            info.el.style.backgroundColor = statusColor.bg;
                            // Add subtle border for status
                            info.el.style.borderTopColor = statusColor.border;
                            info.el.style.borderTopWidth = '2px';
                        }
                    }
                },
                eventClassNames: function(info) {
                    const viewing = info.event.extendedProps;
                    const classes = [];
                    if (viewing && (viewing.tenant_id || (viewing.tenant && viewing.tenant.id))) {
                        classes.push('fc-event-tenant');
                    } else {
                        classes.push('fc-event-lead');
                    }
                    return classes;
                }
            });
            
            calendar.render();
            console.log('FullCalendar initialized successfully');
        } catch (error) {
            console.error('Error initializing FullCalendar:', error);
            const calendarEl = document.getElementById('calendar');
            if (calendarEl) {
                const errorDiv = document.createElement('div');
                errorDiv.className = 'alert alert-danger';
                const icon = document.createElement('i');
                icon.className = 'fas fa-exclamation-triangle me-2';
                errorDiv.appendChild(icon);
                const text = document.createTextNode('Có lỗi xảy ra khi khởi tạo lịch. Vui lòng tải lại trang.');
                errorDiv.appendChild(text);
                calendarEl.appendChild(errorDiv);
            }
        }
        
        // Handle customer type change in create modal
        const customerTypeRadios = document.querySelectorAll('input[name="customer_type"]');
        const leadSelection = document.getElementById('leadSelection');
        const tenantSelection = document.getElementById('tenantSelection');
        const leadId = document.getElementById('lead_id');
        const tenantId = document.getElementById('tenant_id');
        
        customerTypeRadios.forEach(function(radio) {
            radio.addEventListener('change', function() {
                if (this.value === 'lead') {
                    leadSelection.style.display = 'block';
                    tenantSelection.style.display = 'none';
                    leadId.required = false;
                    tenantId.required = false;
                    tenantId.value = '';
                } else {
                    leadSelection.style.display = 'none';
                    tenantSelection.style.display = 'block';
                    leadId.required = false;
                    tenantId.required = true;
                    leadId.value = '';
                }
            });
        });
        
        // Handle property change to load units in create modal
        const propertySelect = document.getElementById('property_id');
        const unitSelect = document.getElementById('unit_id');
        
        if (propertySelect) {
            propertySelect.addEventListener('change', function() {
                const propertyId = this.value;
                if (propertyId) {
                    unitSelect.disabled = false;
                    fetch('/api/properties/' + propertyId + '/units')
                        .then(function(response) { return response.json(); })
                        .then(function(data) {
                            unitSelect.innerHTML = '<option value="">Chọn phòng</option>';
                            if (data && Array.isArray(data)) {
                                data.forEach(function(unit) {
                                    const option = document.createElement('option');
                                    option.value = unit.id;
                                    const unitInfo = unit.code ? (unit.code + ' - Tầng ' + (unit.floor || 'N/A') + ', ' + (unit.area_m2 || 'N/A') + 'm²') : ('Tầng ' + (unit.floor || 'N/A') + ', ' + (unit.area_m2 || 'N/A') + 'm²');
                                    option.textContent = unitInfo;
                                    unitSelect.appendChild(option);
                                });
                            } else if (data && data.units) {
                                data.units.forEach(function(unit) {
                                    const option = document.createElement('option');
                                    option.value = unit.id;
                                    const unitInfo = unit.code ? (unit.code + ' - Tầng ' + (unit.floor || 'N/A') + ', ' + (unit.area_m2 || 'N/A') + 'm²') : ('Tầng ' + (unit.floor || 'N/A') + ', ' + (unit.area_m2 || 'N/A') + 'm²');
                                    option.textContent = unitInfo;
                                    unitSelect.appendChild(option);
                                });
                            }
                        })
                        .catch(function(error) {
                            console.error('Error loading units:', error);
                            unitSelect.innerHTML = '<option value="">Lỗi tải danh sách phòng</option>';
                        });
                } else {
                    unitSelect.disabled = true;
                    unitSelect.innerHTML = '<option value="">Chọn bất động sản trước</option>';
                }
            });
        }
        
        // Handle create form submission
        const createForm = document.getElementById('createViewingForm');
        if (createForm) {
            createForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                const csrfToken = document.querySelector('meta[name="csrf-token"]');
                
                if (!csrfToken) {
                    console.error('CSRF token not found');
                    alert('Lỗi bảo mật: Không tìm thấy CSRF token. Vui lòng tải lại trang.');
                    return;
                }
                
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Đang tạo...';
                
                fetch(this.action, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken.getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: formData
                })
                .then(function(response) {
                    if (response.ok) {
                        return response.json();
                    }
                    return response.json().then(function(err) { return Promise.reject(err); });
                })
                .then(function(data) {
                    const modal = bootstrap.Modal.getInstance(document.getElementById('createViewingModal'));
                    if (modal) {
                        modal.hide();
                    }
                    
                    if (typeof Notify !== 'undefined') {
                        Notify.success('Tạo lịch hẹn thành công!');
                    } else {
                        alert('Tạo lịch hẹn thành công!');
                    }
                    
                    window.location.reload();
                })
                .catch(function(error) {
                    console.error('Error creating viewing:', error);
                    let errorMessage = 'Có lỗi xảy ra khi tạo lịch hẹn.';
                    
                    if (error.errors) {
                        const errors = Object.values(error.errors).flat();
                        errorMessage = errors.join('\n');
                    } else if (error.message) {
                        errorMessage = error.message;
                    }
                    
                    if (typeof Notify !== 'undefined') {
                        Notify.error(errorMessage);
                    } else {
                        alert(errorMessage);
                    }
                    
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                });
            });
        }
    });
})();
</script>
@endpush
