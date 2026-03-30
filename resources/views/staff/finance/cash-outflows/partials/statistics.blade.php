<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title">
                    <i class="fas fa-chart-pie"></i>
                    Thống kê theo trạng thái
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-6">
                        <div class="info-box bg-success">
                            <span class="info-box-icon"><i class="fas fa-check"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Thành công</span>
                                <span class="info-box-number">{{ $statistics['success_count'] }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="info-box bg-warning">
                            <span class="info-box-icon"><i class="fas fa-clock"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Đang chờ</span>
                                <span class="info-box-number">{{ $statistics['pending_count'] }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="info-box bg-danger">
                            <span class="info-box-icon"><i class="fas fa-times"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Thất bại</span>
                                <span class="info-box-number">{{ $statistics['failed_count'] }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="info-box bg-secondary">
                            <span class="info-box-icon"><i class="fas fa-undo"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Đã hoàn trả</span>
                                <span class="info-box-number">{{ $statistics['reversed_count'] }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title">
                    <i class="fas fa-money-bill-wave"></i>
                    Thống kê theo số tiền
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-12 mb-3">
                        <div class="info-box bg-info">
                            <span class="info-box-icon"><i class="fas fa-calculator"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Tổng tiền</span>
                                <span class="info-box-number">{{ number_format($statistics['total_amount']) }} VND</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="info-box bg-success">
                            <span class="info-box-icon"><i class="fas fa-check-circle"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Tiền thành công</span>
                                <span class="info-box-number">{{ number_format($statistics['success_amount']) }} VND</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="info-box bg-warning">
                            <span class="info-box-icon"><i class="fas fa-clock"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Tiền đang chờ</span>
                                <span class="info-box-number">{{ number_format($statistics['pending_amount']) }} VND</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-3">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title">
                    <i class="fas fa-chart-bar"></i>
                    Tỷ lệ thành công
                </h6>
            </div>
            <div class="card-body">
                @php
                    $totalCount = $statistics['success_count'] + $statistics['pending_count'] + $statistics['failed_count'] + $statistics['reversed_count'];
                    $successRate = $totalCount > 0 ? ($statistics['success_count'] / $totalCount) * 100 : 0;
                @endphp
                
                <div class="progress mb-2">
                    <div class="progress-bar bg-success" role="progressbar" style="width: {{ $successRate }}%">
                        {{ number_format($successRate, 1) }}%
                    </div>
                </div>
                <small class="text-muted">
                    Tỷ lệ thành công: {{ $statistics['success_count'] }}/{{ $totalCount }} giao dịch
                </small>
            </div>
        </div>
    </div>
</div>
