@extends('layouts.staff_dashboard')

@section('title', 'Tạo Kỳ Lương')

@section('content')
<main class="main-content">
    <div class="container-fluid">
        {{-- Page Header --}}
        @include('staff.components.index-page-header', [
            'title' => 'Tạo Kỳ Lương',
            'subtitle' => 'Tạo kỳ lương mới cho tổ chức',
            'icon' => 'fas fa-calendar-plus',
            'actions' => [
                [
                    'variant' => 'secondary',
                    'label' => 'Quay lại',
                    'icon' => 'fas fa-arrow-left',
                    'url' => route('staff.payroll-cycles.index')
                ]
            ]
        ])

        <form id="create-payroll-cycle-form" method="POST" action="{{ route('staff.payroll-cycles.store') }}">
            @csrf
            <div class="row">
                {{-- Cột trái: Form chính (col-lg-8) --}}
                <div class="col-lg-8">
                    {{-- Card 1: Thông tin Kỳ Lương --}}
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-info-circle me-2"></i>Thông tin Kỳ Lương
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="period_month" class="form-label">
                                        Kỳ lương <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select @error('period_month') is-invalid @enderror" 
                                            id="period_month" 
                                            name="period_month" 
                                            required>
                                        <option value="">Chọn kỳ lương</option>
                                        @foreach($availableMonths as $value => $label)
                                            <option value="{{ $value }}" {{ old('period_month', $currentMonth) == $value ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('period_month')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">Chọn tháng/năm cho kỳ lương (có thể chọn các tháng trong quá khứ)</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Trạng thái</label>
                                    <div class="form-control-plaintext">
                                        <span class="badge bg-success">
                                            <i class="fas fa-unlock me-1"></i>Mở
                                        </span>
                                        <small class="text-muted d-block mt-1">Kỳ lương mới sẽ được tạo với trạng thái "Mở"</small>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12 mb-3">
                                    <label for="note" class="form-label">Ghi chú</label>
                                    <textarea class="form-control @error('note') is-invalid @enderror" 
                                              id="note" 
                                              name="note" 
                                              rows="3" 
                                              placeholder="Ghi chú về kỳ lương...">{{ old('note') }}</textarea>
                                    @error('note')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                {{-- Cột phải: Sidebar (col-lg-4) --}}
                <div class="col-lg-4">
                    {{-- Card Thao tác (chứa action-buttons với layout dọc) --}}
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-cogs me-2"></i>Thao tác
                            </h6>
                        </div>
                        <div class="card-body">
                            @include('staff.components.action-buttons', [
                                'layout' => 'vertical',
                                'size' => 'md',
                                'actions' => [
                                    [
                                        'type' => 'submit',
                                        'variant' => 'primary',
                                        'label' => 'Tạo kỳ lương',
                                        'icon' => 'fas fa-save'
                                    ],
                                    [
                                        'type' => 'link',
                                        'variant' => 'secondary',
                                        'label' => 'Hủy',
                                        'icon' => 'fas fa-times',
                                        'url' => route('staff.payroll-cycles.index')
                                    ]
                                ]
                            ])
                        </div>
                    </div>
                    
                    {{-- Card Hướng dẫn --}}
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-question-circle me-2"></i>Hướng dẫn
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                <h6><i class="fas fa-info-circle"></i> Thông tin cần thiết</h6>
                                <ul class="mb-0 small">
                                    <li>Kỳ lương sẽ được tạo với trạng thái "Mở"</li>
                                    <li>Sau khi tạo, bạn có thể tạo phiếu lương cho nhân viên</li>
                                    <li>Khi hoàn tất, hãy khóa kỳ lương để tránh chỉnh sửa</li>
                                    <li>Mỗi tháng chỉ có thể tạo một kỳ lương</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</main>
@endsection
