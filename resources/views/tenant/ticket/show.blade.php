@extends('layouts.app')

@section('title', 'Chi tiết ticket')

@include('tenant.components.theme-config')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/tenant/tickets.css') }}?v={{ time() }}">
<link rel="stylesheet" href="{{ asset('assets/css/tenant/form-blue-theme.css') }}?v={{ time() }}">
<style>
/* Ticket Detail Container with Blue Theme */
.ticket-detail-container-blue {
    background: linear-gradient(to bottom, #F0F4FF 0%, #ffffff 100%);
    min-height: 100vh;
    padding: 2rem 0;
}

/* Content Cards with Blue Theme */
.content-card-blue {
    background: white;
    border-radius: 16px;
    padding: 0;
    margin-bottom: 1.5rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    border: 1px solid var(--blue-border);
}

.content-card-blue .card-header {
    padding: 1.5rem;
    border-bottom: 1px solid var(--blue-border);
    background: var(--blue-bg-light);
    border-radius: 16px 16px 0 0;
}

.content-card-blue .card-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--blue-primary);
    margin: 0;
}

.content-card-blue .card-body {
    padding: 1.5rem;
}

.description-content-blue {
    color: #4b5563;
    line-height: 1.8;
    font-size: 1rem;
}

/* Sidebar Cards with Blue Theme */
.sidebar-card-blue {
    background: white;
    border-radius: 16px;
    padding: 0;
    margin-bottom: 1.5rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    border: 1px solid var(--blue-border);
}

.sidebar-card-blue .card-header {
    padding: 1.5rem;
    border-bottom: 1px solid var(--blue-border);
    background: var(--blue-bg-light);
    border-radius: 16px 16px 0 0;
}

.sidebar-title-blue {
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--blue-primary);
    margin: 0;
}

.sidebar-card-blue .card-body {
    padding: 1.5rem;
}

.sidebar-content-blue {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.info-item-blue {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.info-item-blue label {
    font-weight: 600;
    color: var(--blue-primary);
    font-size: 0.875rem;
}

.info-item-blue span {
    color: #4b5563;
    font-size: 0.95rem;
}

/* Priority and Status Badges */
.priority-badge-blue {
    padding: 0.375rem 0.75rem;
    border-radius: 8px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    display: inline-block;
}

.priority-badge-blue.priority-low {
    background: #dbeafe;
    color: #1e40af;
}

.priority-badge-blue.priority-medium {
    background: #fef3c7;
    color: #92400e;
}

.priority-badge-blue.priority-high {
    background: #fed7aa;
    color: #9a3412;
}

.priority-badge-blue.priority-urgent {
    background: #fee2e2;
    color: #991b1b;
}

.status-badge-blue {
    padding: 0.375rem 0.75rem;
    border-radius: 8px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    display: inline-block;
}

.status-badge-blue.status-open {
    background: #3b82f6;
    color: #ffffff;
}

.status-badge-blue.status-in_progress {
    background: #f59e0b;
    color: #ffffff;
}

.status-badge-blue.status-resolved {
    background: #10b981;
    color: #ffffff;
}

.status-badge-blue.status-closed {
    background: #6b7280;
    color: #ffffff;
}

.status-badge-blue.status-cancelled {
    background: #ef4444;
    color: #ffffff;
}

/* Timeline with Blue Theme */
.timeline-blue {
    position: relative;
    padding-left: 2rem;
}

.timeline-blue::before {
    content: '';
    position: absolute;
    left: 0.5rem;
    top: 0;
    bottom: 0;
    width: 2px;
    background: var(--blue-border);
}

.timeline-item-blue {
    position: relative;
    margin-bottom: 2rem;
    padding-left: 2rem;
}

.timeline-item-blue:last-child {
    margin-bottom: 0;
}

.timeline-marker-blue {
    position: absolute;
    left: -1.5rem;
    top: 0.25rem;
    width: 1rem;
    height: 1rem;
    border-radius: 50%;
    background: var(--blue-primary);
    border: 3px solid white;
    box-shadow: 0 0 0 2px var(--blue-primary);
}

.timeline-content-blue {
    background: var(--blue-bg-light);
    border-radius: 12px;
    padding: 1rem;
    border: 1px solid var(--blue-border);
}

.timeline-header-blue {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.timeline-title-blue {
    font-weight: 600;
    color: var(--blue-primary);
    font-size: 1rem;
}

.timeline-date-blue {
    font-size: 0.875rem;
    color: #6b7280;
}

.timeline-description-blue {
    color: #4b5563;
    margin-bottom: 0.5rem;
    line-height: 1.6;
}

.timeline-user-blue {
    font-size: 0.875rem;
    color: #6b7280;
}

.action-buttons-blue {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}
</style>
@endpush

@push('scripts')
<script src="{{ asset('assets/js/notifications.js') }}?v={{ time() }}"></script>
<script src="{{ asset('assets/js/tenant/tickets.js') }}?v={{ time() }}"></script>
<script>
// Page-specific initialization
document.addEventListener('DOMContentLoaded', function() {
    TicketModule.initShow({{ $ticket->id }});
});
</script>
@endpush

@section('content')
<div class="page-container-blue">
    <div class="container">
        <!-- Page Header -->
        <div class="page-header-blue">
            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb" class="mb-3" style="position: relative; z-index: 1;">
                <ol class="breadcrumb mb-0" style="background: rgba(255, 255, 255, 0.2); padding: 0.75rem 1rem; border-radius: 10px; backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px);">
                    <li class="breadcrumb-item">
                        <a href="{{ route('tenant.dashboard') }}" style="color: rgba(255, 255, 255, 0.9); text-decoration: none;">
                            <i class="fas fa-home me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="{{ route('tenant.tickets.index') }}" style="color: rgba(255, 255, 255, 0.9); text-decoration: none;">
                            <i class="fas fa-ticket-alt me-1"></i>Ticket
                        </a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page" style="color: rgba(255, 255, 255, 1);">
                        <i class="fas fa-info-circle me-1"></i>Chi tiết
                    </li>
                </ol>
            </nav>
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div class="header-content">
                    <div class="header-icon">
                        <i class="fas fa-ticket-alt"></i>
                    </div>
                    <div>
                        <h1 class="page-title">{{ $ticket->title }}</h1>
                        <p class="page-subtitle">Ticket #{{ $ticket->id }} • Tạo lúc {{ $ticket->created_at->format('d/m/Y H:i') }}</p>
                    </div>
                </div>
                <div class="header-actions">
                    @if ($ticket->status === 'open')
                        <a href="{{ route('tenant.tickets.edit', $ticket->id) }}" class="btn btn-outline-primary" style="background: white; color: var(--blue-primary); border: 2px solid var(--blue-primary); font-weight: 600; padding: 0.75rem 1.5rem; border-radius: 12px; transition: all 0.3s ease; text-decoration: none;">
                            <i class="fas fa-edit me-1"></i>Chỉnh sửa
                        </a>
                    @endif
                    <a href="{{ route('tenant.tickets.index') }}" class="btn btn-outline-secondary" style="background: rgba(255, 255, 255, 0.25); color: white; border: 1px solid rgba(255, 255, 255, 0.3); font-weight: 600; padding: 0.75rem 1.5rem; backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); text-decoration: none;">
                        <i class="fas fa-arrow-left me-1"></i>Quay lại
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Status and Priority Badges -->
        <div class="mb-4 d-flex gap-2 flex-wrap">
            <span class="priority-badge-blue priority-{{ $ticket->priority }}">
                {{ $ticket->priority_label }}
            </span>
            @php
                $statusLabels = [
                    'open' => 'Mở',
                    'in_progress' => 'Đang xử lý',
                    'resolved' => 'Đã giải quyết',
                    'closed' => 'Đã đóng',
                    'cancelled' => 'Đã hủy'
                ];
            @endphp
            <span class="status-badge-blue status-{{ $ticket->status }}">
                {{ $statusLabels[$ticket->status] ?? ucfirst($ticket->status) }}
            </span>
        </div>

        <!-- Ticket Content -->
        <div class="ticket-content-blue">
            <div class="row">
                <!-- Main Content -->
                <div class="col-md-8">
                    <!-- Description -->
                    <div class="content-card-blue">
                        <div class="card-header">
                            <h5 class="card-title">
                                <i class="fas fa-align-left me-2"></i>Mô tả chi tiết
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="description-content-blue">
                                {!! nl2br(e($ticket->description)) !!}
                            </div>
                            
                            @php
                                // Lấy tất cả images từ documents
                                // Sử dụng getRawOriginal để lấy file_url gốc, không qua accessor
                                $images = \App\Models\Document::where('owner_type', \App\Models\Ticket::class)
                                    ->where('owner_id', $ticket->id)
                                    ->where('document_type', 'image')
                                    ->whereNull('deleted_at')
                                    ->orderBy('is_primary', 'desc')
                                    ->orderBy('sort_order')
                                    ->orderBy('created_at')
                                    ->get();
                            @endphp
                            
                            @if($images && $images->count() > 0)
                                <div class="mt-4">
                                    <h6 class="text-muted mb-3">
                                        <i class="fas fa-image me-2"></i>Hình ảnh đính kèm ({{ $images->count() }})
                                    </h6>
                                    <div class="d-flex flex-wrap gap-3">
                                        @foreach($images as $image)
                                            @php
                                                // Lấy file_url gốc từ database
                                                $rawFileUrl = $image->getRawOriginal('file_url') ?? $image->file_url;
                                                
                                                // Xử lý URL
                                                if (str_starts_with($rawFileUrl, 'http://') || str_starts_with($rawFileUrl, 'https://')) {
                                                    $imageUrl = $rawFileUrl;
                                                } else {
                                                    // Loại bỏ 'storage/' nếu đã có trong path
                                                    $path = ltrim($rawFileUrl, '/');
                                                    if (str_starts_with($path, 'storage/')) {
                                                        $imageUrl = asset($path);
                                                    } else {
                                                        $imageUrl = asset('storage/' . $path);
                                                    }
                                                }
                                            @endphp
                                            <div class="position-relative" style="max-width: 200px;">
                                                <img src="{{ $imageUrl }}" 
                                                     alt="Ticket image #{{ $image->id }}" 
                                                     class="img-fluid rounded shadow-sm" 
                                                     style="max-width: 200px; max-height: 200px; object-fit: cover; cursor: pointer; border: 2px solid var(--blue-border);" 
                                                     onclick="openImageModal('{{ $imageUrl }}')"
                                                     onerror="this.onerror=null; this.src='{{ asset('assets/img/placeholder-image.png') }}';"
                                                     loading="lazy">
                                                @if($image->is_primary)
                                                    <span class="badge bg-primary position-absolute top-0 end-0 m-2" style="font-size: 0.7rem;">
                                                        <i class="fas fa-star me-1"></i>Chính
                                                    </span>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Ticket Logs (if any) -->
                    @if($ticket->logs && $ticket->logs->count() > 0)
                        <div class="content-card-blue">
                            <div class="card-header">
                                <h5 class="card-title">
                                    <i class="fas fa-history me-2"></i>Lịch sử xử lý
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="timeline-blue">
                                    @foreach($ticket->logs as $log)
                                        <div class="timeline-item-blue">
                                            <div class="timeline-marker-blue"></div>
                                            <div class="timeline-content-blue">
                                                <div class="timeline-header-blue">
                                                    <span class="timeline-title-blue">{{ $log->action }}</span>
                                                    <span class="timeline-date-blue">{{ $log->created_at ? $log->created_at->format('d/m/Y H:i') : 'N/A' }}</span>
                                                </div>
                                                @if($log->detail)
                                                    <div class="timeline-description-blue">
                                                        {{ $log->detail }}
                                                    </div>
                                                @endif
                                                @if($log->linked_invoice_id)
                                                    @php
                                                        $invoice = \App\Models\Invoice::find($log->linked_invoice_id);
                                                    @endphp
                                                    @if($invoice)
                                                        <div class="timeline-description-blue mt-2" style="background: rgba(39, 102, 236, 0.1); padding: 0.75rem; border-radius: 8px; border-left: 3px solid var(--blue-primary);">
                                                            <i class="fas fa-file-invoice me-2" style="color: var(--blue-primary);"></i>
                                                            <strong>Hóa đơn liên quan:</strong>
                                                            <a href="{{ route('tenant.invoices.show', $invoice->id) }}" style="color: var(--blue-primary); text-decoration: none; font-weight: 600;">
                                                                {{ $invoice->invoice_no ?? 'HD' . str_pad($invoice->id, 6, '0', STR_PAD_LEFT) }}
                                                            </a>
                                                            @if($log->cost_amount > 0)
                                                                <span class="ms-2" style="color: var(--blue-primary); font-weight: 600;">
                                                                    - {{ number_format($log->cost_amount) }} VNĐ
                                                                </span>
                                                            @endif
                                                        </div>
                                                    @endif
                                                @endif
                                                @if($log->cost_amount > 0 && !$log->linked_invoice_id)
                                                    <div class="timeline-description-blue mt-2" style="background: rgba(16, 185, 129, 0.1); padding: 0.75rem; border-radius: 8px; border-left: 3px solid #10b981;">
                                                        <i class="fas fa-money-bill-wave me-2" style="color: #10b981;"></i>
                                                        <strong>Chi phí:</strong> {{ number_format($log->cost_amount) }} VNĐ
                                                        @if($log->cost_note)
                                                            <br><small style="color: #666;">{{ $log->cost_note }}</small>
                                                        @endif
                                                    </div>
                                                @endif
                                                <div class="timeline-user-blue">
                                                    <i class="fas fa-user me-1"></i>
                                                    {{ $log->actor_name ?: 'Hệ thống' }}
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="content-card-blue">
                            <div class="card-header">
                                <h5 class="card-title">
                                    <i class="fas fa-history me-2"></i>Lịch sử xử lý
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-2x mb-3"></i>
                                    <p>Chưa có lịch sử xử lý nào</p>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Sidebar -->
                <div class="col-md-4">
                    <!-- Ticket Information -->
                    <div class="sidebar-card-blue">
                        <div class="card-header">
                            <h5 class="sidebar-title-blue">
                                <i class="fas fa-info-circle me-2"></i>Thông tin ticket
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="sidebar-content-blue">
                                <div class="info-item-blue">
                                    <label>Trạng thái:</label>
                                    <div>
                                        @php
                                            $statusLabels = [
                                                'open' => 'Mở',
                                                'in_progress' => 'Đang xử lý',
                                                'resolved' => 'Đã giải quyết',
                                                'closed' => 'Đã đóng',
                                                'cancelled' => 'Đã hủy'
                                            ];
                                        @endphp
                                        <span class="status-badge-blue status-{{ $ticket->status }}">
                                            {{ $statusLabels[$ticket->status] ?? ucfirst($ticket->status) }}
                                        </span>
                                    </div>
                                </div>
                                <div class="info-item-blue">
                                    <label>Độ ưu tiên:</label>
                                    <span class="priority-badge-blue priority-{{ $ticket->priority }}">
                                        {{ $ticket->priority_label }}
                                    </span>
                                </div>
                                <div class="info-item-blue">
                                    <label>Ngày tạo:</label>
                                    <span>{{ $ticket->created_at->format('d/m/Y H:i') }}</span>
                                </div>
                                <div class="info-item-blue">
                                    <label>Cập nhật cuối:</label>
                                    <span>{{ $ticket->updated_at->format('d/m/Y H:i') }}</span>
                                </div>
                                <div class="info-item-blue">
                                    <label>Người tạo:</label>
                                    <span>{{ $ticket->created_by_name ?: 'Hệ thống' }}</span>
                                </div>
                                <div class="info-item-blue">
                                    <label>Người xử lý:</label>
                                    <span>{{ $ticket->assigned_to_name ?: 'Chưa phân công' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Property Information -->
                    <div class="sidebar-card-blue">
                        <div class="card-header">
                            <h5 class="sidebar-title-blue">
                                <i class="fas fa-home me-2"></i>Thông tin phòng
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="sidebar-content-blue">
                                <div class="info-item-blue">
                                    <label>Tòa nhà:</label>
                                    <span>{{ $ticket->property_name ?: 'Chưa xác định' }}</span>
                                </div>
                                <div class="info-item-blue">
                                    <label>Địa chỉ:</label>
                                    <span>
                                        @if($ticket->location_address)
                                            {{ $ticket->location_address }}
                                            @if($ticket->location2025_address && $ticket->location2025_address != $ticket->location_address)
                                                <br><small class="text-muted">(2025: {{ $ticket->location2025_address }})</small>
                                            @endif
                                        @elseif($ticket->location2025_address)
                                            {{ $ticket->location2025_address }}
                                        @else
                                            Chưa xác định
                                        @endif
                                    </span>
                                </div>
                                <div class="info-item-blue">
                                    <label>Phòng:</label>
                                    <span>{{ $ticket->unit_name ?: 'Chưa xác định' }}</span>
                                </div>
                                <div class="info-item-blue">
                                    <label>Hợp đồng:</label>
                                    <span>{{ $ticket->lease_contract_number ?: 'Chưa liên kết' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    @if(in_array($ticket->status, ['open', 'in_progress']))
                        <div class="sidebar-card-blue">
                            <div class="card-header">
                                <h5 class="sidebar-title-blue">
                                    <i class="fas fa-tools me-2"></i>Hành động
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="action-buttons-blue">
                                    <a href="{{ route('tenant.tickets.edit', $ticket->id) }}" class="btn btn-primary btn-sm w-100 mb-2">
                                        <i class="fas fa-edit me-2"></i>Chỉnh sửa ticket
                                    </a>
                                    <form method="POST" action="{{ route('tenant.tickets.destroy', $ticket->id) }}" 
                                          class="delete-form">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-warning btn-sm w-100">
                                            <i class="fas fa-ban me-2"></i>Hủy ticket
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Status Information -->
                    <div class="sidebar-card-blue">
                        <div class="card-header">
                            <h5 class="sidebar-title-blue">
                                <i class="fas fa-info-circle me-2"></i>Trạng thái
                            </h5>
                        </div>
                        <div class="card-body">
                            @if($ticket->status === 'open')
                                <div class="alert alert-info">
                                    <i class="fas fa-clock me-2"></i>
                                    Ticket đang chờ được xử lý. Bạn có thể chỉnh sửa thông tin.
                                </div>
                            @elseif($ticket->status === 'in_progress')
                                <div class="alert alert-warning">
                                    <i class="fas fa-cog me-2"></i>
                                    Ticket đang được xử lý. Không thể chỉnh sửa.
                                </div>
                            @elseif($ticket->status === 'resolved')
                                <div class="alert alert-success">
                                    <i class="fas fa-check-circle me-2"></i>
                                    Ticket đã được giải quyết. Vui lòng kiểm tra và phản hồi.
                                </div>
                            @elseif($ticket->status === 'closed')
                                <div class="alert alert-secondary">
                                    <i class="fas fa-archive me-2"></i>
                                    Ticket đã được đóng. Không thể chỉnh sửa.
                                </div>
                            @elseif($ticket->status === 'cancelled')
                                <div class="alert alert-danger">
                                    <i class="fas fa-times-circle me-2"></i>
                                    Ticket đã bị hủy. Không thể chỉnh sửa.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Image Modal -->
<div class="modal fade" id="imageModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Hình ảnh đính kèm</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <img id="modalImage" src="" alt="Ticket image" class="img-fluid">
            </div>
        </div>
    </div>
</div>

<script>
// Image modal functions
function openImageModal(imageUrl) {
    const modal = document.getElementById('imageModal');
    const modalImage = document.getElementById('modalImage');
    modalImage.src = imageUrl;
    new bootstrap.Modal(modal).show();
}
</script>
@endsection