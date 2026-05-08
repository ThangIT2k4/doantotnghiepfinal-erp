@extends('layouts.superadmin')

@section('title', 'Chi tiết Subscription')

@section('content')
<div class="container-fluid">
    <h1 class="h3 mb-4"><i class="fas fa-file-invoice me-2"></i>Subscription của {{ $organization->name }}</h1>

    @if(!$subscription)
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-circle me-2"></i>
        Tổ chức này chưa có gói dịch vụ nào.
    </div>
    <a href="{{ route('superadmin.organizations.subscription.assign', $organization->id) }}" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i> Gán gói dịch vụ
    </a>
    @else
    <div class="row">
        <div class="col-md-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Thông tin gói hiện tại</h6>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <th width="40%">Gói:</th>
                            <td><strong>{{ $subscription->plan->name }}</strong></td>
                        </tr>
                        <tr>
                            <th>Trạng thái:</th>
                            <td><span class="badge bg-{{ $subscription->getStatusColor() }}">{{ $subscription->getStatusLabel() }}</span></td>
                        </tr>
                        <tr>
                            <th>Chu kỳ:</th>
                            <td>{{ ucfirst($subscription->payment_cycle) }}</td>
                        </tr>
                        <tr>
                            <th>Bắt đầu:</th>
                            <td>{{ $subscription->current_period_start ? $subscription->current_period_start->format('d/m/Y H:i') : '-' }}</td>
                        </tr>
                        <tr>
                            <th>Kết thúc:</th>
                            <td>{{ $subscription->current_period_end ? $subscription->current_period_end->format('d/m/Y H:i') : '-' }}</td>
                        </tr>
                        <tr>
                            <th>Còn lại:</th>
                            <td>
                                @if($subscription->daysUntilExpiry() !== null)
                                    <strong>{{ $subscription->daysUntilExpiry() }} ngày</strong>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Tự động gia hạn:</th>
                            <td>
                                @if($subscription->auto_renew)
                                    <span class="badge bg-success">Có</span>
                                @else
                                    <span class="badge bg-secondary">Không</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Cổng thanh toán:</th>
                            <td>{{ ucfirst($subscription->payment_gateway ?? 'N/A') }}</td>
                        </tr>
                    </table>

                    <div class="mt-3">
                        <form action="{{ route('superadmin.organizations.subscription.cancel', $organization->id) }}" method="POST" class="d-inline">
                            @csrf
                            <input type="hidden" name="cancel_immediately" value="0">
                            <button type="submit" class="btn btn-sm btn-warning" onclick="return confirm('Hủy gói này?')">
                                <i class="fas fa-ban me-1"></i> Hủy gói
                            </button>
                        </form>
                        
                        @if($subscription->status !== 'active')
                        <form action="{{ route('superadmin.organizations.subscription.activate', $organization->id) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-success">
                                <i class="fas fa-check me-1"></i> Kích hoạt
                            </button>
                        </form>
                        @endif
                        
                        <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#extendModal">
                            <i class="fas fa-calendar-plus me-1"></i> Gia hạn
                        </button>
                        
                        <a href="{{ route('superadmin.organizations.subscription.assign', $organization->id) }}" class="btn btn-sm btn-primary">
                            <i class="fas fa-exchange-alt me-1"></i> Đổi gói
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Mức sử dụng</h6>
                </div>
                <div class="card-body">
                    @foreach($usageStats as $key => $stat)
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span>{{ ucfirst(str_replace('_', ' ', $key)) }}</span>
                            <span>
                                <strong>{{ $stat['current'] }}</strong> / 
                                {{ $stat['limit'] == -1 ? '∞' : $stat['limit'] }}
                            </span>
                        </div>
                        <div class="progress">
                            <div class="progress-bar {{ $stat['percentage'] > 80 ? 'bg-danger' : ($stat['percentage'] > 60 ? 'bg-warning' : 'bg-success') }}" 
                                 style="width: {{ min($stat['percentage'], 100) }}%">
                                {{ number_format($stat['percentage'], 1) }}%
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Lịch sử subscriptions</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Gói</th>
                            <th>Trạng thái</th>
                            <th>Bắt đầu</th>
                            <th>Kết thúc</th>
                            <th>Tạo lúc</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($allSubscriptions as $sub)
                        <tr>
                            <td>{{ $sub->plan->name }}</td>
                            <td><span class="badge bg-{{ $sub->getStatusColor() }}">{{ $sub->getStatusLabel() }}</span></td>
                            <td>{{ $sub->current_period_start ? $sub->current_period_start->format('d/m/Y') : '-' }}</td>
                            <td>{{ $sub->current_period_end ? $sub->current_period_end->format('d/m/Y') : '-' }}</td>
                            <td>{{ $sub->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
</div>

<!-- Extend Modal -->
<div class="modal fade" id="extendModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('superadmin.organizations.subscription.extend', $organization->id) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Gia hạn subscription</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Số ngày gia hạn</label>
                        <input type="number" name="extend_days" class="form-control" min="1" max="365" value="30" required>
                        <small class="text-muted">Nhập số ngày muốn gia hạn thêm (1-365)</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-primary">Gia hạn</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

