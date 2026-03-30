@if($depositRefunds->count() > 0)
    <div class="card shadow-sm">
        <div class="card-header">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-list me-2"></i>Danh sách yêu cầu hoàn tiền
            </h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Mã yêu cầu</th>
                            <th>Hợp đồng</th>
                            <th>Khách thuê</th>
                            <th>Agent</th>
                            <th>Tiền cọc gốc</th>
                            <th>Đã trừ</th>
                            <th>Hoàn lại</th>
                            <th>Phương thức</th>
                            <th>Trạng thái</th>
                            <th>Ngày tạo</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($depositRefunds as $refund)
                            <tr>
                                <td>
                                    <div class="fw-bold">#{{ $refund->id }}</div>
                                    <small class="text-muted">{{ $refund->refund_reference ?? 'Chưa có mã' }}</small>
                                </td>
                                <td>
                                    <div class="fw-bold">
                                        <a href="{{ route('staff.leases.show', $refund->lease_id) }}" class="text-decoration-none">
                                            {{ $refund->lease->contract_no ?? 'N/A' }}
                                        </a>
                                    </div>
                                    <small class="text-muted">{{ $refund->lease->unit->property->name ?? 'N/A' }}</small>
                                </td>
                                <td>
                                    <div class="fw-bold">{{ $refund->tenant->full_name ?? 'N/A' }}</div>
                                    <small class="text-muted">{{ $refund->tenant->email ?? 'N/A' }}</small>
                                </td>
                                <td>
                                    <div class="fw-bold">{{ $refund->agent->full_name ?? 'N/A' }}</div>
                                    <small class="text-muted">{{ $refund->agent->email ?? 'N/A' }}</small>
                                </td>
                                <td>
                                    <div class="fw-bold text-primary">{{ number_format($refund->original_deposit_amount) }}đ</div>
                                </td>
                                <td>
                                    <div class="fw-bold text-warning">{{ number_format($refund->deducted_amount) }}đ</div>
                                </td>
                                <td>
                                    <div class="fw-bold text-success">{{ number_format($refund->refund_amount) }}đ</div>
                                </td>
                                <td>
                                    <span class="badge bg-info">{{ $refund->refund_method_label }}</span>
                                </td>
                                <td>
                                    @include('staff.components.status-badge', [
                                        'status' => $refund->status,
                                        'type' => 'deposit-refund'
                                    ])
                                </td>
                                <td>
                                    <div>{{ $refund->created_at->format('d/m/Y') }}</div>
                                    <small class="text-muted">{{ $refund->created_at->format('H:i') }}</small>
                                </td>
                                <td>
                                    <div class="btn-group table-actions" role="group">
                                        <a href="{{ route('staff.deposit-refunds.show', $refund->id) }}" 
                                           class="btn btn-outline-primary btn-icon-only" 
                                           title="Xem chi tiết">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        
                                        @if($refund->status === 'pending')
                                            <a href="{{ route('staff.deposit-refunds.edit', $refund->id) }}" 
                                               class="btn btn-outline-warning btn-icon-only" 
                                               title="Chỉnh sửa">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" 
                                                    class="btn btn-outline-success btn-icon-only" 
                                                    title="Phê duyệt" 
                                                    onclick="approveRefund({{ $refund->id }})">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button type="button" 
                                                    class="btn btn-outline-danger btn-icon-only" 
                                                    title="Hủy" 
                                                    onclick="cancelRefund({{ $refund->id }})">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        @elseif($refund->status === 'approved')
                                            <button type="button" 
                                                    class="btn btn-outline-primary btn-icon-only" 
                                                    title="Đánh dấu đã thanh toán" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#markPaidModal{{ $refund->id }}">
                                                <i class="fas fa-money-bill-wave"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($depositRefunds->hasPages())
                <div class="mt-3">
                    {{ $depositRefunds->appends(request()->query())->links('vendor.pagination.custom', [
                        'tableContainerId' => 'deposit-refunds-table-container',
                        'htmxIndicator' => '#htmx-loading-index-filters-form'
                    ]) }}
                </div>
            @endif
        </div>
    </div>
@else
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="text-center py-5">
                <i class="fas fa-money-bill-wave fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">Chưa có yêu cầu hoàn tiền nào</h5>
                <p class="text-muted">Bắt đầu tạo yêu cầu hoàn tiền cọc cho tổ chức</p>
                <a href="{{ route('staff.deposit-refunds.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i>Tạo yêu cầu hoàn tiền
                </a>
            </div>
        </div>
    </div>
@endif

