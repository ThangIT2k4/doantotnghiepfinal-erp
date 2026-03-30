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
                <form action="{{ route('staff.payment-cycle-settings.organization.update') }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    @if($defaultPaymentCycle)
                        <div class="alert alert-info mb-3">
                            <strong>Chu kỳ thanh toán mặc định hiện tại:</strong> 
                            {{ $defaultPaymentCycle->cycle_type_name }}
                            @if($defaultPaymentCycle->billing_day)
                                <br><strong>Ngày tạo hóa đơn:</strong> Ngày {{ $defaultPaymentCycle->billing_day }}
                            @endif
                            @if($defaultPaymentCycle->invoice_timing)
                                <br><strong>Tính tiền hóa đơn:</strong> 
                                @if($defaultPaymentCycle->invoice_timing === 'start_of_cycle')
                                    Đầu chu kỳ (cộng vào hóa đơn tạo hợp đồng)
                                @else
                                    Cuối chu kỳ (không cộng vào hóa đơn tạo hợp đồng)
                                @endif
                            @endif
                            @php
                                $invoicePaymentDays = $defaultPaymentCycle->invoice_payment_days ?? 30;
                            @endphp
                            <br><strong>Số ngày thanh toán hóa đơn:</strong> {{ $invoicePaymentDays }} ngày
                            @php
                                $paymentDueMinutes = $defaultPaymentCycle->payment_due_hours ?? 4320;
                                $dueHours = floor($paymentDueMinutes / 60);
                                $dueMinutes = $paymentDueMinutes % 60;
                                $dueInHours = number_format($paymentDueMinutes / 60, 2);
                                $dueInDays = number_format($paymentDueMinutes / 60 / 24, 2);
                            @endphp
                            <br><strong>Thời gian chờ thanh toán (Booking Deposit):</strong> {{ $dueHours }} giờ {{ $dueMinutes }} phút ({{ $dueInHours }} giờ = {{ $dueInDays }} ngày)
                            @if($defaultPaymentCycle->notes)
                                <br><strong>Ghi chú:</strong> {{ $defaultPaymentCycle->notes }}
                            @endif
                        </div>
                    @else
                        <div class="alert alert-warning mb-3">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Chưa có chu kỳ thanh toán mặc định.</strong> Vui lòng chọn chu kỳ mặc định hoặc tạo mới trong phần "Quản lý chu kỳ thanh toán".
                        </div>
                    @endif

                    {{-- Dropdown chọn chu kỳ có sẵn --}}
                    <div class="mb-3">
                        <label for="payment_cycle_id" class="form-label">Chọn chu kỳ có sẵn làm mặc định</label>
                        <select class="form-select" id="payment_cycle_id" name="payment_cycle_id" onchange="showCyclePreview(this.value)">
                            <option value="">-- Chọn chu kỳ có sẵn --</option>
                            @if($availableCycles && $availableCycles->count() > 0)
                                @foreach($availableCycles as $cycle)
                                    <option value="{{ $cycle->id }}" 
                                            data-cycle-type="{{ $cycle->cycle_type_name }}"
                                            data-billing-day="{{ $cycle->billing_day }}"
                                            data-invoice-timing="{{ $cycle->invoice_timing ?? 'end_of_cycle' }}"
                                            data-invoice-payment-days="{{ $cycle->invoice_payment_days ?? 30 }}"
                                            data-payment-due-hours="{{ $cycle->payment_due_hours ?? 4320 }}"
                                            data-notes="{{ $cycle->notes }}"
                                            {{ ($defaultPaymentCycle && $defaultPaymentCycle->id == $cycle->id) ? 'selected' : '' }}>
                                        {{ $cycle->name ?? $cycle->cycle_type_name }}
                                        @if($cycle->is_default)
                                            (Mặc định)
                                        @endif
                                    </option>
                                @endforeach
                            @endif
                        </select>
                        <div class="form-text">Chọn một chu kỳ có sẵn để đặt làm mặc định cho tổ chức</div>
                    </div>

                    {{-- Preview thông tin chu kỳ được chọn --}}
                    <div id="cycle-preview" class="mb-3" style="display: none;">
                        <div class="card border-primary">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">
                                    <i class="fas fa-info-circle text-primary me-2"></i>
                                    Thông tin chu kỳ được chọn
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Chu kỳ thanh toán</label>
                                        <div>
                                            <span id="preview-cycle-type" class="badge bg-info fs-6"></span>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Ngày tạo hóa đơn</label>
                                        <div>
                                            <span id="preview-billing-day" class="badge bg-primary fs-6"></span>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label fw-bold">Tính tiền hóa đơn</label>
                                        <div>
                                            <span id="preview-invoice-timing" class="badge fs-6"></span>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Số ngày thanh toán hóa đơn</label>
                                        <div>
                                            <span id="preview-invoice-payment-days" class="badge bg-success fs-6"></span>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Thời gian chờ thanh toán (Booking Deposit)</label>
                                        <div>
                                            <span id="preview-payment-due-hours" class="badge bg-warning text-dark fs-6"></span>
                                        </div>
                                    </div>
                                    <div class="col-md-12" id="preview-notes-container" style="display: none;">
                                        <label class="form-label fw-bold">Ghi chú</label>
                                        <div>
                                            <span id="preview-notes" class="text-muted"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info mb-3">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Lưu ý:</strong> Nút "Cập nhật tổ chức" chỉ cập nhật cài đặt cho tổ chức, không tự động áp dụng lên bất động sản. 
                        Để áp dụng cài đặt này cho tất cả bất động sản, vui lòng nhấn nút "Áp dụng cho tất cả BĐS".
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

    <!-- Payment Cycles Management -->
    <div class="col-lg-6">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="card-title mb-0">
                    <i class="fas fa-calendar-check me-2"></i>
                    Quản lý chu kỳ thanh toán
                </h6>
                <div class="btn-group">
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="togglePaymentCycleSection(this)" title="Thu gọn/Mở rộng">
                        <i class="fas fa-chevron-up"></i>
                    </button>
                    <button type="button" class="btn btn-danger btn-sm" onclick="deleteUnusedCycles()" title="Xóa các chu kỳ không được sử dụng">
                        <i class="fas fa-trash-alt me-1"></i>
                        Xóa chu kỳ không dùng
                    </button>
                    <button type="button" class="btn btn-primary btn-sm" onclick="showCreateCycleModal()">
                        <i class="fas fa-plus me-1"></i>
                        Tạo chu kỳ
                    </button>
                </div>
            </div>
            <div class="card-body" id="payment-cycle-management-body">
                @if(isset($paymentCycles) && $paymentCycles->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th style="width: 30%;">Chu kỳ</th>
                                    <th style="width: 40%;">Thông tin</th>
                                    <th style="width: 20%;">Sử dụng</th>
                                    <th style="width: 10%;">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($paymentCycles as $cycle)
                                    <tr>
                                        <td>
                                            <strong>{{ $cycle->cycle_type_name }}</strong>
                                            @if($cycle->is_default)
                                                <span class="badge bg-success ms-2">Mặc định</span>
                                            @endif
                                            @if($cycle->custom_months)
                                                <br><small class="text-muted">{{ $cycle->custom_months }} tháng</small>
                                            @endif
                                        </td>
                                        <td>
                                            @if($cycle->billing_day)
                                                <div class="mb-1">
                                                    <i class="fas fa-calendar-day text-primary me-1"></i>
                                                    <small>Ngày {{ $cycle->billing_day }}</small>
                                                </div>
                                            @endif
                                            @if($cycle->notes)
                                                <div>
                                                    <small class="text-muted">{{ Str::limit($cycle->notes, 30) }}</small>
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            @php
                                                $propertiesCount = $cycle->properties_count ?? 0;
                                                $leasesCount = $cycle->leases_count ?? 0;
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
                                                        onclick="viewCycleDetails({{ $cycle->id }})" 
                                                        title="Xem chi tiết">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-warning btn-icon-only" 
                                                        onclick="editCycle({{ $cycle->id }})" 
                                                        title="Sửa">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-danger btn-icon-only" 
                                                        onclick="deleteCycle({{ $cycle->id }}, '{{ addslashes($cycle->cycle_type_name) }}')" 
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
                        <i class="fas fa-calendar-check fa-3x text-muted mb-3"></i>
                        <p class="text-muted mt-2">Chưa có chu kỳ thanh toán nào</p>
                        <button type="button" class="btn btn-primary btn-sm" onclick="showCreateCycleModal()">
                            <i class="fas fa-plus me-1"></i>
                            Tạo chu kỳ đầu tiên
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
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="card-title mb-0">
                    <i class="fas fa-home me-2"></i>
                    Danh sách bất động sản
                </h6>
                <div class="d-flex align-items-center gap-2">
                    <label for="cycle_filter" class="form-label mb-0 me-2">
                        <i class="fas fa-filter me-1"></i>
                        Lọc theo chu kỳ:
                    </label>
                    <select class="form-select form-select-sm" 
                            id="cycle_filter" 
                            name="payment_cycle_id"
                            style="width: auto; min-width: 250px;"
                            hx-get="{{ route('staff.system-settings.filter-properties') }}"
                            hx-target="#properties-list-container"
                            hx-trigger="change"
                            hx-indicator="#properties-loading">
                        <option value="">-- Tất cả chu kỳ --</option>
                        @if(isset($paymentCyclesForFilter) && $paymentCyclesForFilter->count() > 0)
                            @foreach($paymentCyclesForFilter as $cycle)
                                <option value="{{ $cycle->id }}" {{ (isset($selectedCycleId) && $selectedCycleId == $cycle->id) ? 'selected' : '' }}>
                                    {{ $cycle->name ?? $cycle->cycle_type_name }}
                                    @if($cycle->is_default)
                                        (Mặc định)
                                    @endif
                                </option>
                            @endforeach
                        @endif
                    </select>
                    <div id="properties-loading" class="htmx-indicator">
                        <span class="spinner-border spinner-border-sm text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </span>
                    </div>
                </div>
            </div>
            <div class="card-body" id="properties-list-container">
                @include('staff.settings.system-settings.tabs.partials.properties-list', [
                    'properties' => $properties,
                    'paymentCyclesForFilter' => $paymentCyclesForFilter ?? collect([]),
                    'selectedCycleId' => $selectedCycleId ?? null
                ])
            </div>
        </div>
    </div>
</div>

@push('modals')
<!-- Property Settings Modal -->
<div class="modal fade" id="propertySettingsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cài đặt chu kỳ thanh toán</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="propertySettingsContent">
                    <!-- Content will be loaded here -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Apply to Properties Modal -->
<div class="modal fade" id="applyToPropertiesModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Xác nhận áp dụng cài đặt</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Bạn có chắc chắn muốn áp dụng cài đặt chu kỳ thanh toán của tổ chức cho tất cả bất động sản?</p>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Thao tác này sẽ ghi đè cài đặt hiện tại của tất cả bất động sản.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <form action="{{ route('staff.payment-cycle-settings.apply-to-properties') }}" method="POST" style="display: inline;">
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

<!-- Apply to Leases Modal -->
<div class="modal fade" id="applyToLeasesModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Xác nhận áp dụng cài đặt</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p id="applyToLeasesMessage"></p>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Cảnh báo:</strong> Thao tác này sẽ áp dụng cài đặt chu kỳ thanh toán hiện tại của bất động sản cho <strong>TẤT CẢ</strong> hợp đồng thuê của bất động sản này.
                    <br><br>
                    <strong>Lưu ý:</strong>
                    <ul class="mb-0 mt-2">
                        <li>Tất cả hợp đồng sẽ được cập nhật với chu kỳ thanh toán mới</li>
                        <li>Ngày tạo hóa đơn sẽ được cập nhật theo payment cycle</li>
                        <li>Thao tác này không thể hoàn tác</li>
                    </ul>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <form id="applyToLeasesForm" method="POST" style="display: inline;">
                    @csrf
                    <input type="hidden" name="property_id" id="applyToLeasesPropertyId">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check me-1"></i>
                        Xác nhận áp dụng
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Create/Edit Cycle Modal -->
<div class="modal fade" id="cycleModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cycleModalTitle">Tạo chu kỳ thanh toán</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="cycleForm" onsubmit="return saveCycle(event)">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <input type="hidden" name="cycle_id" id="cycle_id">
                    
                    <div class="mb-3">
                        <label for="cycle_cycle_type" class="form-label">
                            Loại chu kỳ <span class="text-danger">*</span>
                        </label>
                        <select class="form-select" id="cycle_cycle_type" name="cycle_type" required>
                            <option value="">-- Chọn loại chu kỳ --</option>
                            @foreach($paymentCycleOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="mb-3" id="cycle_custom_months_field" style="display: none;">
                        <label for="cycle_custom_months" class="form-label">Số tháng tùy chỉnh</label>
                        <input type="number" class="form-control" id="cycle_custom_months" name="custom_months" 
                               min="1" max="60" placeholder="Nhập số tháng (1-60)">
                        <div class="form-text">Số tháng cho chu kỳ thanh toán tùy chỉnh (1-60)</div>
                    </div>

                    <div class="mb-3">
                        <label for="cycle_billing_day" class="form-label">Ngày tạo hóa đơn</label>
                        <input type="number" class="form-control" id="cycle_billing_day" name="billing_day" 
                               min="1" max="28" placeholder="Nhập ngày (1-28)">
                        <div class="form-text">Ngày trong tháng để tạo hóa đơn (1-28)</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="cycle_invoice_timing" class="form-label">Tính tiền hóa đơn</label>
                        <select class="form-select" id="cycle_invoice_timing" name="invoice_timing">
                            <option value="end_of_cycle">Cuối chu kỳ (không cộng vào hóa đơn tạo hợp đồng)</option>
                            <option value="start_of_cycle">Đầu chu kỳ (cộng vào hóa đơn tạo hợp đồng)</option>
                        </select>
                        <div class="form-text">
                            <strong>Đầu chu kỳ:</strong> Tự động tạo hóa đơn với tiền thuê chu kỳ đầu khi tạo hợp đồng.<br>
                            <strong>Cuối chu kỳ:</strong> Không tự động tạo hóa đơn chu kỳ đầu khi tạo hợp đồng.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="cycle_invoice_payment_days" class="form-label">Số ngày thanh toán hóa đơn</label>
                        <input type="number" class="form-control" id="cycle_invoice_payment_days" name="invoice_payment_days" 
                               min="1" max="365" placeholder="Nhập số ngày (1-365)">
                        <div class="form-text">Số ngày thanh toán sau khi tạo hóa đơn (mặc định: 30 ngày)</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-clock me-1"></i>
                            Thời gian chờ thanh toán (Booking Deposit)
                        </label>
                        <div class="row">
                            <div class="col-md-6">
                                <input type="number" class="form-control" id="cycle_payment_due_hours" name="payment_due_hours" 
                                       min="0" max="720" placeholder="Giờ">
                                <div class="form-text">Số giờ (0-720)</div>
                            </div>
                            <div class="col-md-6">
                                <input type="number" class="form-control" id="cycle_payment_due_minutes" 
                                       min="0" max="59" placeholder="Phút">
                                <div class="form-text">Số phút (0-59)</div>
                            </div>
                        </div>
                        <div class="form-text mt-2">
                            <i class="fas fa-info-circle me-1"></i>
                            Thời gian khách hàng có để thanh toán sau khi booking deposit được phê duyệt
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="cycle_notes" class="form-label">Ghi chú</label>
                        <textarea class="form-control" id="cycle_notes" name="notes" rows="3"></textarea>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="cycle_is_default" name="is_default" value="1">
                            <label class="form-check-label" for="cycle_is_default">
                                Đặt làm mặc định
                            </label>
                        </div>
                        <div class="form-text">Nếu chọn, chu kỳ này sẽ được đặt làm mặc định và tất cả chu kỳ mặc định khác sẽ bị bỏ chọn</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="submit" form="cycleForm" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i>
                    Lưu
                </button>
            </div>
        </div>
    </div>
</div>

<!-- View Cycle Details Modal -->
<div class="modal fade" id="viewCycleDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewCycleDetailsTitle">Chi tiết chu kỳ thanh toán</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="viewCycleDetailsContent">
                    <!-- Content will be loaded here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Cycle Confirmation Modal -->
<div class="modal fade" id="deleteCycleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Xác nhận xóa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Bạn có chắc chắn muốn xóa chu kỳ thanh toán <strong id="deleteCycleName"></strong>?</p>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Thao tác này không thể hoàn tác. Nếu chu kỳ đang được sử dụng bởi bất động sản hoặc hợp đồng thuê, bạn sẽ không thể xóa.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteCycleBtn">
                    <i class="fas fa-trash me-1"></i>
                    Xóa
                </button>
            </div>
        </div>
    </div>
</div>
@endpush

@push('scripts')
<script>
// Show cycle preview when selecting from dropdown
function showCyclePreview(cycleId) {
    const select = document.getElementById('payment_cycle_id');
    const previewDiv = document.getElementById('cycle-preview');
    
    if (!cycleId || !select || !previewDiv) {
        if (previewDiv) {
            previewDiv.style.display = 'none';
        }
        return;
    }
    
    const selectedOption = select.querySelector(`option[value="${cycleId}"]`);
    if (!selectedOption) {
        previewDiv.style.display = 'none';
        return;
    }
    
    // Get data from selected option
    const cycleType = selectedOption.getAttribute('data-cycle-type') || 'N/A';
    const billingDay = selectedOption.getAttribute('data-billing-day') || 'Không có';
    const invoiceTiming = selectedOption.getAttribute('data-invoice-timing') || 'end_of_cycle';
    const invoicePaymentDays = selectedOption.getAttribute('data-invoice-payment-days') || '30';
    const paymentDueMinutes = parseInt(selectedOption.getAttribute('data-payment-due-hours')) || 4320;
    const notes = selectedOption.getAttribute('data-notes') || '';
    
    // Calculate hours and minutes
    const dueHours = Math.floor(paymentDueMinutes / 60);
    const dueMinutes = paymentDueMinutes % 60;
    const dueInHours = (paymentDueMinutes / 60).toFixed(2);
    const dueInDays = (paymentDueMinutes / 60 / 24).toFixed(2);
    
    // Update preview content
    document.getElementById('preview-cycle-type').textContent = cycleType;
    document.getElementById('preview-billing-day').textContent = billingDay !== 'Không có' ? `Ngày ${billingDay}` : 'Không có';
    
    // Invoice timing with color
    const invoiceTimingBadge = document.getElementById('preview-invoice-timing');
    if (invoiceTiming === 'start_of_cycle') {
        invoiceTimingBadge.textContent = 'Đầu chu kỳ (cộng vào hóa đơn tạo hợp đồng)';
        invoiceTimingBadge.className = 'badge bg-primary fs-6';
    } else {
        invoiceTimingBadge.textContent = 'Cuối chu kỳ (không cộng vào hóa đơn tạo hợp đồng)';
        invoiceTimingBadge.className = 'badge bg-secondary fs-6';
    }
    
    document.getElementById('preview-invoice-payment-days').textContent = `${invoicePaymentDays} ngày`;
    document.getElementById('preview-payment-due-hours').textContent = `${dueHours} giờ ${dueMinutes} phút (${dueInHours} giờ = ${dueInDays} ngày)`;
    
    // Show/hide notes
    const notesContainer = document.getElementById('preview-notes-container');
    const notesSpan = document.getElementById('preview-notes');
    if (notes && notes.trim() !== '') {
        notesContainer.style.display = 'block';
        notesSpan.textContent = notes;
    } else {
        notesContainer.style.display = 'none';
    }
    
    // Show preview
    previewDiv.style.display = 'block';
}

// Initialize preview on page load
document.addEventListener('DOMContentLoaded', function() {
    // Show preview for initially selected cycle
    const initialSelect = document.getElementById('payment_cycle_id');
    if (initialSelect && initialSelect.value) {
        showCyclePreview(initialSelect.value);
    }
});

function showPropertySettings(propertyId, propertyName) {
    const contentDiv = document.getElementById('propertySettingsContent');
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
    
    const modalTitle = document.querySelector('#propertySettingsModal .modal-title');
    if (modalTitle) {
        modalTitle.textContent = `Cài đặt: ${propertyName}`;
    }
    
    const modal = document.getElementById('propertySettingsModal');
    if (modal) {
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
    }
    
    const url = `{{ route('staff.payment-cycle-settings.property.leases', ['propertyId' => 'PLACEHOLDER']) }}`.replace('PLACEHOLDER', propertyId);
    
    fetch(url, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        }
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(err => {
                throw new Error(err.error || err.message || `HTTP error! status: ${response.status}`);
            });
        }
        return response.json();
    })
    .then(response => {
        if (response.success) {
            const contentDiv = document.getElementById('propertySettingsContent');
            if (contentDiv) {
                contentDiv.innerHTML = `
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Thông tin bất động sản</h6>
                            <form id="propertyUpdateForm" onsubmit="return updatePropertyPaymentCycle(event, ${propertyId})" method="POST">
                                <input type="hidden" name="_token" value="${getCsrfToken()}">
                                <input type="hidden" name="_method" value="PUT">
                                
                                <div class="mb-3">
                                    <label class="form-label">Chu kỳ thanh toán</label>
                                    <select class="form-select" name="cycle_type" id="prop_payment_cycle_modal">
                                        <option value="">-- Chọn chu kỳ --</option>
                                        <option value="monthly" ${response.property.effective_payment_cycle && response.property.effective_payment_cycle.cycle_type == 'monthly' ? 'selected' : ''}>Hàng tháng</option>
                                        <option value="quarterly" ${response.property.effective_payment_cycle && response.property.effective_payment_cycle.cycle_type == 'quarterly' ? 'selected' : ''}>Hàng quý</option>
                                        <option value="yearly" ${response.property.effective_payment_cycle && response.property.effective_payment_cycle.cycle_type == 'yearly' ? 'selected' : ''}>Hàng năm</option>
                                        <option value="custom" ${response.property.effective_payment_cycle && response.property.effective_payment_cycle.cycle_type == 'custom' ? 'selected' : ''}>Tùy chỉnh (nhập số tháng)</option>
                                    </select>
                                </div>

                                <div class="mb-3" id="prop_custom_months_field_modal" style="display: ${response.property.effective_payment_cycle && response.property.effective_payment_cycle.cycle_type == 'custom' ? 'block' : 'none'};">
                                    <label class="form-label">Số tháng tùy chỉnh</label>
                                    <input type="number" class="form-control" name="custom_months" 
                                           value="${response.property.effective_payment_cycle && response.property.effective_payment_cycle.custom_months ? response.property.effective_payment_cycle.custom_months : ''}" min="1" max="60" 
                                           placeholder="Nhập số tháng (1-60)">
                                    <div class="form-text">Số tháng cho chu kỳ thanh toán tùy chỉnh (1-60)</div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Ngày tạo hóa đơn</label>
                                    <input type="number" class="form-control" name="billing_day" 
                                           value="${response.property.effective_payment_cycle && response.property.effective_payment_cycle.billing_day ? response.property.effective_payment_cycle.billing_day : ''}" min="1" max="28">
                                    <div class="form-text">Ngày trong tháng để tạo hóa đơn (1-28). Hợp đồng sẽ sử dụng ngày này từ payment cycle.</div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Ghi chú</label>
                                    <textarea class="form-control" name="notes" rows="2">${response.property.effective_payment_cycle && response.property.effective_payment_cycle.notes ? response.property.effective_payment_cycle.notes : ''}</textarea>
                                </div>

                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary btn-sm">
                                        <i class="fas fa-save me-1"></i>
                                        Cập nhật
                                    </button>
                                    <button type="button" class="btn btn-success btn-sm" onclick="showApplyToLeasesModal(${propertyId}, '${response.property.name}', ${response.leases.length})">
                                        <i class="fas fa-arrow-down me-1"></i>
                                        Áp dụng cho tất cả hợp đồng
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
                                                ${lease.payment_cycle ? 
                                                    `<span class="badge bg-info">${
                                                        lease.payment_cycle.cycle_type === 'custom' && lease.payment_cycle.custom_months ? 
                                                        lease.payment_cycle.custom_months + ' tháng' : 
                                                        (lease.payment_cycle.cycle_type_name || getPaymentCycleLabel(lease.payment_cycle.cycle_type))
                                                    }</span>` : 
                                                    '<span class="text-muted">Chưa cài</span>'
                                                }
                                                ${lease.payment_cycle && lease.payment_cycle.billing_day ? `<br><span class="badge bg-primary">Tạo HĐ: ${lease.payment_cycle.billing_day}</span>` : ''}
                                            </div>
                                        </div>
                                    </div>
                                `).join('') : '<p class="text-muted">Chưa có hợp đồng</p>'}
                            </div>
                        </div>
                    </div>
                `;
            }
            
            const selectElement = document.getElementById('prop_payment_cycle_modal');
            if (selectElement) {
                selectElement.dispatchEvent(new Event('change'));
            }
        } else {
            // Response không có success hoặc success = false
            const contentDiv = document.getElementById('propertySettingsContent');
            if (contentDiv) {
                contentDiv.innerHTML = `
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        ${response.error || response.message || 'Không thể tải dữ liệu. Vui lòng thử lại.'}
                    </div>
                `;
            }
        }
    })
    .catch(error => {
        console.error('Error loading property settings:', error);
        const contentDiv = document.getElementById('propertySettingsContent');
        if (contentDiv) {
            contentDiv.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <strong>Lỗi:</strong> ${error.message || 'Có lỗi xảy ra khi tải dữ liệu. Vui lòng thử lại.'}
                    <br><small>Nếu lỗi vẫn tiếp tục, vui lòng liên hệ quản trị viên.</small>
                </div>
            `;
        }
    });
}

function applyToProperties() {
    const modal = document.getElementById('applyToPropertiesModal');
    if (modal) {
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
    }
}

function getPaymentCycleLabel(cycle) {
    const labels = {
        'monthly': 'Hàng tháng',
        'quarterly': 'Hàng quý',
        'yearly': 'Hàng năm',
        'custom': 'Tùy chỉnh'
    };
    return labels[cycle] || cycle;
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

function updatePropertyPaymentCycle(event, propertyId) {
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
    
    const routeUrl = `/staff/payment-cycle-settings/property/${propertyId}`;
    
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
            const modal = document.getElementById('propertySettingsModal');
            if (modal) {
                const bsModal = bootstrap.Modal.getInstance(modal);
                if (bsModal) {
                    bsModal.hide();
                }
            }
            if (typeof window.Notify !== 'undefined') {
                window.Notify.success(
                    data.message || 'Cập nhật cài đặt chu kỳ thanh toán thành công!',
                    'Thành công'
                );
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
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

function showApplyToLeasesModal(propertyId, propertyName, leaseCount) {
    if (typeof window.Notify !== 'undefined') {
        window.Notify.confirm({
            title: 'Xác nhận áp dụng cài đặt',
            message: `Bạn có chắc chắn muốn áp dụng cài đặt chu kỳ thanh toán hiện tại của bất động sản "${propertyName}" cho tất cả ${leaseCount} hợp đồng thuê?`,
            details: `Thao tác này sẽ cập nhật payment cycle cho ${leaseCount} hợp đồng thuê của bất động sản này. Ngày tạo hóa đơn sẽ được cập nhật theo payment cycle. Thao tác này không thể hoàn tác.`,
            type: 'warning',
            confirmText: 'Xác nhận áp dụng',
            cancelText: 'Hủy',
            onConfirm: () => {
                applyPaymentCycleToLeases(propertyId);
            }
        });
    } else {
        const modal = document.getElementById('applyToLeasesModal');
        const messageDiv = document.getElementById('applyToLeasesMessage');
        const form = document.getElementById('applyToLeasesForm');
        const propertyIdInput = document.getElementById('applyToLeasesPropertyId');
        
        if (messageDiv) {
            messageDiv.textContent = `Bạn có chắc chắn muốn áp dụng cài đặt chu kỳ thanh toán hiện tại của bất động sản "${propertyName}" cho tất cả ${leaseCount} hợp đồng thuê?`;
        }
        
        if (propertyIdInput) {
            propertyIdInput.value = propertyId;
        }
        
        if (form) {
            form.action = `/staff/payment-cycle-settings/property/${propertyId}/apply-to-leases`;
        }
        
        if (modal) {
            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();
        }
    }
}

function applyPaymentCycleToLeases(propertyId) {
    const formData = new FormData();
    const csrfToken = getCsrfToken();
    if (csrfToken) {
        formData.append('_token', csrfToken);
    }
    formData.append('property_id', propertyId);
    
    const url = `/staff/payment-cycle-settings/property/${propertyId}/apply-to-leases`;
    
    if (typeof window.Notify !== 'undefined') {
        window.Notify.info('Đang áp dụng cài đặt cho tất cả hợp đồng...', 'Đang xử lý');
    }
    
    fetch(url, {
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
            const propertyModal = document.getElementById('propertySettingsModal');
            if (propertyModal) {
                const bsModal = bootstrap.Modal.getInstance(propertyModal);
                if (bsModal) {
                    bsModal.hide();
                }
            }
            
            if (typeof window.Notify !== 'undefined') {
                window.Notify.success(
                    data.message || `Đã áp dụng cài đặt cho ${data.updated_count || 0} hợp đồng thuê thành công!`,
                    'Thành công'
                );
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            } else {
                alert(data.message || 'Áp dụng cài đặt cho tất cả hợp đồng thành công!');
                window.location.reload();
            }
        } else if (data && (data.error || data.errors)) {
            const errorMsg = data.error || (data.errors ? Object.values(data.errors).flat().join(', ') : 'Có lỗi xảy ra khi áp dụng cài đặt');
            if (typeof window.Notify !== 'undefined') {
                window.Notify.error(errorMsg, 'Lỗi áp dụng cài đặt');
            } else {
                alert(errorMsg);
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        if (typeof window.Notify !== 'undefined') {
            window.Notify.error('Có lỗi xảy ra khi áp dụng cài đặt: ' + error.message, 'Lỗi hệ thống');
        } else {
            alert('Có lỗi xảy ra khi áp dụng cài đặt: ' + error.message);
        }
    });
}

// Payment Cycle Management Functions
const paymentCycleOptions = @json($paymentCycleOptions);
let editingCycleId = null;

// Delete unused cycles function - must be defined before DOMContentLoaded
function deleteUnusedCycles() {
    if (typeof window.Notify !== 'undefined') {
        window.Notify.confirm({
            title: 'Xác nhận xóa chu kỳ không sử dụng',
            message: 'Bạn có chắc chắn muốn xóa tất cả các chu kỳ thanh toán không được sử dụng?',
            details: 'Thao tác này sẽ xóa tất cả các chu kỳ không được gán cho bất động sản hoặc hợp đồng thuê. Chu kỳ mặc định sẽ không bị xóa.',
            type: 'warning',
            confirmText: 'Xác nhận xóa',
            cancelText: 'Hủy',
            onConfirm: () => {
                performDeleteUnusedCycles();
            }
        });
    } else {
        if (confirm('Bạn có chắc chắn muốn xóa tất cả các chu kỳ thanh toán không được sử dụng?')) {
            performDeleteUnusedCycles();
        }
    }
}

function performDeleteUnusedCycles() {
    const csrfToken = getCsrfToken();
    
    if (typeof window.Notify !== 'undefined') {
        window.Notify.info('Đang xóa các chu kỳ không sử dụng...', 'Đang xử lý');
    }
    
    fetch(`{{ route('staff.payment-cycle-settings.cycles.delete-unused') }}`, {
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
                    data.message || `Đã xóa ${data.deleted_count || 0} chu kỳ không sử dụng thành công!`,
                    'Thành công'
                );
                setTimeout(() => window.location.reload(), 1500);
            } else {
                alert(data.message || 'Đã xóa các chu kỳ không sử dụng thành công!');
                window.location.reload();
            }
        } else {
            if (typeof window.Notify !== 'undefined') {
                window.Notify.error(data.error || 'Có lỗi xảy ra khi xóa các chu kỳ không sử dụng.', 'Lỗi');
            } else {
                alert(data.error || 'Có lỗi xảy ra khi xóa các chu kỳ không sử dụng.');
            }
        }
    })
    .catch(error => {
        console.error('Error deleting unused cycles:', error);
        if (typeof window.Notify !== 'undefined') {
            window.Notify.error('Có lỗi xảy ra khi xóa các chu kỳ không sử dụng.', 'Lỗi');
        } else {
            alert('Có lỗi xảy ra khi xóa các chu kỳ không sử dụng.');
        }
    });
}

function showCreateCycleModal() {
    editingCycleId = null;
    document.getElementById('cycleModalTitle').textContent = 'Tạo chu kỳ thanh toán';
    document.getElementById('cycleForm').reset();
    document.getElementById('cycle_id').value = '';
    document.getElementById('cycle_custom_months_field').style.display = 'none';
    
    const modal = new bootstrap.Modal(document.getElementById('cycleModal'));
    modal.show();
}

function editCycle(cycleId) {
    editingCycleId = cycleId;
    document.getElementById('cycleModalTitle').textContent = 'Sửa chu kỳ thanh toán';
    
    fetch(`{{ route('staff.payment-cycle-settings.cycles.show', ['id' => 'PLACEHOLDER']) }}`.replace('PLACEHOLDER', cycleId), {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.paymentCycle) {
            const cycle = data.paymentCycle;
            document.getElementById('cycle_id').value = cycle.id;
            document.getElementById('cycle_cycle_type').value = cycle.cycle_type || '';
            document.getElementById('cycle_billing_day').value = cycle.billing_day || '';
            document.getElementById('cycle_custom_months').value = cycle.custom_months || '';
            document.getElementById('cycle_notes').value = cycle.notes || '';
            document.getElementById('cycle_is_default').checked = cycle.is_default || false;
            
            // Set invoice timing
            if (cycle.invoice_timing) {
                document.getElementById('cycle_invoice_timing').value = cycle.invoice_timing;
            }
            
            // Set invoice payment days
            if (cycle.invoice_payment_days) {
                document.getElementById('cycle_invoice_payment_days').value = cycle.invoice_payment_days;
            }
            
            // Set payment due hours (stored as minutes)
            if (cycle.payment_due_hours) {
                const hours = Math.floor(cycle.payment_due_hours / 60);
                const minutes = cycle.payment_due_hours % 60;
                document.getElementById('cycle_payment_due_hours').value = hours;
                document.getElementById('cycle_payment_due_minutes').value = minutes;
            }
            
            // Show/hide custom months field
            if (cycle.cycle_type === 'custom') {
                document.getElementById('cycle_custom_months_field').style.display = 'block';
            } else {
                document.getElementById('cycle_custom_months_field').style.display = 'none';
            }
            
            const modal = new bootstrap.Modal(document.getElementById('cycleModal'));
            modal.show();
        } else {
            if (typeof window.Notify !== 'undefined') {
                window.Notify.error('Không thể tải dữ liệu chu kỳ thanh toán.', 'Lỗi');
            } else {
                alert('Không thể tải dữ liệu chu kỳ thanh toán.');
            }
        }
    })
    .catch(error => {
        console.error('Error loading cycle:', error);
        if (typeof window.Notify !== 'undefined') {
            window.Notify.error('Có lỗi xảy ra khi tải dữ liệu.', 'Lỗi');
        } else {
            alert('Có lỗi xảy ra khi tải dữ liệu.');
        }
    });
}

function deleteCycle(cycleId, cycleName) {
    document.getElementById('deleteCycleName').textContent = cycleName;
    document.getElementById('confirmDeleteCycleBtn').onclick = function() {
        confirmDeleteCycle(cycleId);
    };
    
    const modal = new bootstrap.Modal(document.getElementById('deleteCycleModal'));
    modal.show();
}

function confirmDeleteCycle(cycleId) {
    const csrfToken = getCsrfToken();
    
    fetch(`{{ route('staff.payment-cycle-settings.cycles.destroy', ['id' => 'PLACEHOLDER']) }}`.replace('PLACEHOLDER', cycleId), {
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
            const modal = bootstrap.Modal.getInstance(document.getElementById('deleteCycleModal'));
            modal.hide();
            if (typeof window.Notify !== 'undefined') {
                window.Notify.success(data.message || 'Đã xóa chu kỳ thanh toán thành công!', 'Thành công');
                setTimeout(() => window.location.reload(), 1500);
            } else {
                window.location.reload();
            }
        } else {
            if (typeof window.Notify !== 'undefined') {
                window.Notify.error(data.error || 'Có lỗi xảy ra khi xóa chu kỳ thanh toán.', 'Lỗi');
            } else {
                alert(data.error || 'Có lỗi xảy ra khi xóa chu kỳ thanh toán.');
            }
        }
    })
    .catch(error => {
        console.error('Error deleting cycle:', error);
        if (typeof window.Notify !== 'undefined') {
            window.Notify.error('Có lỗi xảy ra khi xóa chu kỳ thanh toán.', 'Lỗi');
        } else {
            alert('Có lỗi xảy ra khi xóa chu kỳ thanh toán.');
        }
    });
}

function saveCycle(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    
    const cycleId = formData.get('cycle_id');
    const cycleType = formData.get('cycle_type');
    
    if (!cycleType) {
        if (typeof window.Notify !== 'undefined') {
            window.Notify.error('Vui lòng chọn loại chu kỳ.', 'Lỗi');
        } else {
            alert('Vui lòng chọn loại chu kỳ.');
        }
        return false;
    }
    
    if (cycleType === 'custom' && !formData.get('custom_months')) {
        if (typeof window.Notify !== 'undefined') {
            window.Notify.error('Vui lòng nhập số tháng tùy chỉnh.', 'Lỗi');
        } else {
            alert('Vui lòng nhập số tháng tùy chỉnh.');
        }
        return false;
    }
    
    const csrfToken = getCsrfToken();
    const url = cycleId 
        ? `{{ route('staff.payment-cycle-settings.cycles.update', ['id' => 'PLACEHOLDER']) }}`.replace('PLACEHOLDER', cycleId)
        : `{{ route('staff.payment-cycle-settings.cycles.store') }}`;
    
    const finalFormData = new FormData();
    finalFormData.append('_token', csrfToken);
    finalFormData.append('cycle_type', cycleType);
    
    if (formData.get('billing_day')) {
        finalFormData.append('billing_day', formData.get('billing_day'));
    }
    if (formData.get('custom_months')) {
        finalFormData.append('custom_months', formData.get('custom_months'));
    }
    if (formData.get('notes')) {
        finalFormData.append('notes', formData.get('notes'));
    }
    
    // Luôn gửi invoice_timing (select có mặc định end_of_cycle)
    finalFormData.append('invoice_timing', formData.get('invoice_timing') || 'end_of_cycle');
    
    // Add invoice payment days
    if (formData.get('invoice_payment_days')) {
        finalFormData.append('invoice_payment_days', formData.get('invoice_payment_days'));
    }
    
    // Add payment due hours (convert hours + minutes to total minutes)
    const dueHours = parseInt(formData.get('payment_due_hours')) || 0;
    const dueMinutes = parseInt(document.getElementById('cycle_payment_due_minutes')?.value) || 0;
    const totalDueMinutes = (dueHours * 60) + dueMinutes;
    if (totalDueMinutes > 0) {
        finalFormData.append('payment_due_hours', totalDueMinutes);
    }
    
    if (cycleId) {
        finalFormData.append('_method', 'PUT');
        if (formData.get('is_default')) {
            finalFormData.append('is_default', '1');
        }
    } else {
        // For new cycle, include is_default if checked
        if (formData.get('is_default')) {
            finalFormData.append('is_default', '1');
        }
    }
    
    fetch(url, {
        method: cycleId ? 'POST' : 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: finalFormData
    })
    .then(async response => {
        let data = {};
        try {
            data = await response.json();
        } catch (e) {
            data = {};
        }

        // Laravel 422: có message + errors — không được coi là thành công (tránh nhầm với nhánh success).
        if (data.errors && typeof data.errors === 'object' && Object.keys(data.errors).length) {
            const errorMsg = Object.values(data.errors).flat().join(', ');
            if (typeof window.Notify !== 'undefined') {
                window.Notify.error(errorMsg, 'Lỗi');
            } else {
                alert(errorMsg);
            }
            return;
        }

        if (!response.ok) {
            const errorMsg = data.error || data.message || 'Có lỗi xảy ra khi lưu chu kỳ thanh toán.';
            if (typeof window.Notify !== 'undefined') {
                window.Notify.error(errorMsg, 'Lỗi');
            } else {
                alert(errorMsg);
            }
            return;
        }

        if (data.success) {
            const modal = bootstrap.Modal.getInstance(document.getElementById('cycleModal'));
            modal.hide();
            if (typeof window.Notify !== 'undefined') {
                window.Notify.success(data.message || 'Đã lưu chu kỳ thanh toán thành công!', 'Thành công');
                setTimeout(() => window.location.reload(), 1500);
            } else {
                window.location.reload();
            }
        } else {
            const errorMsg = data.error || 'Có lỗi xảy ra khi lưu chu kỳ thanh toán.';
            if (typeof window.Notify !== 'undefined') {
                window.Notify.error(errorMsg, 'Lỗi');
            } else {
                alert(errorMsg);
            }
        }
    })
    .catch(error => {
        console.error('Error saving cycle:', error);
        if (typeof window.Notify !== 'undefined') {
            window.Notify.error('Có lỗi xảy ra khi lưu chu kỳ thanh toán.', 'Lỗi');
        } else {
            alert('Có lỗi xảy ra khi lưu chu kỳ thanh toán.');
        }
    });
    
    return false;
}

function viewCycleDetails(cycleId) {
    fetch(`{{ route('staff.payment-cycle-settings.cycles.show', ['id' => 'PLACEHOLDER']) }}`.replace('PLACEHOLDER', cycleId), {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.paymentCycle) {
            const cycle = data.paymentCycle;
            const modalTitle = document.getElementById('viewCycleDetailsTitle');
            const modalContent = document.getElementById('viewCycleDetailsContent');
            
            if (modalTitle) {
                modalTitle.textContent = `Chi tiết: ${cycle.cycle_type_name || 'Chu kỳ thanh toán'}`;
            }
            
            // Get usage statistics from API response
            const propertiesCount = cycle.properties_count || 0;
            const leasesCount = cycle.leases_count || 0;
            
            if (modalContent) {
                modalContent.innerHTML = `
                    <div class="mb-3">
                        <h6>Thông tin chu kỳ thanh toán</h6>
                            <div class="card bg-light">
                            <div class="card-body">
                                <p><strong>Loại chu kỳ:</strong> ${cycle.cycle_type_name || 'N/A'}</p>
                                ${cycle.custom_months ? `<p><strong>Số tháng tùy chỉnh:</strong> ${cycle.custom_months} tháng</p>` : ''}
                                ${cycle.billing_day ? `<p><strong>Ngày tạo hóa đơn:</strong> Ngày ${cycle.billing_day}</p>` : ''}
                                ${cycle.invoice_timing ? `<p><strong>Tính tiền hóa đơn:</strong> ${cycle.invoice_timing === 'start_of_cycle' ? 'Đầu chu kỳ' : 'Cuối chu kỳ'}</p>` : ''}
                                ${cycle.invoice_payment_days ? `<p><strong>Số ngày thanh toán:</strong> ${cycle.invoice_payment_days} ngày</p>` : ''}
                                ${cycle.payment_due_hours ? `<p><strong>Thời gian chờ thanh toán (Booking):</strong> ${Math.floor(cycle.payment_due_hours / 60)} giờ ${cycle.payment_due_hours % 60} phút</p>` : ''}
                                <p><strong>Mặc định:</strong> ${cycle.is_default ? '<span class="badge bg-success">Có</span>' : '<span class="badge bg-secondary">Không</span>'}</p>
                                ${cycle.notes ? `<p><strong>Ghi chú:</strong> ${cycle.notes}</p>` : ''}
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
                `;
            }
            
            const modal = new bootstrap.Modal(document.getElementById('viewCycleDetailsModal'));
            modal.show();
        } else {
            if (typeof window.Notify !== 'undefined') {
                window.Notify.error('Không thể tải dữ liệu chu kỳ thanh toán.', 'Lỗi');
            } else {
                alert('Không thể tải dữ liệu chu kỳ thanh toán.');
            }
        }
    })
    .catch(error => {
        console.error('Error loading cycle details:', error);
        if (typeof window.Notify !== 'undefined') {
            window.Notify.error('Có lỗi xảy ra khi tải dữ liệu.', 'Lỗi');
        } else {
            alert('Có lỗi xảy ra khi tải dữ liệu.');
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    const applyToLeasesForm = document.getElementById('applyToLeasesForm');
    if (applyToLeasesForm) {
        applyToLeasesForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const propertyId = document.getElementById('applyToLeasesPropertyId')?.value;
            if (propertyId) {
                applyPaymentCycleToLeases(propertyId);
            }
        });
    }
    
    
    document.addEventListener('change', function(e) {
        if (e.target && e.target.id === 'prop_payment_cycle_modal') {
            const propCustomField = document.getElementById('prop_custom_months_field_modal');
            if (propCustomField) {
                if (e.target.value === 'custom') {
                    propCustomField.style.display = 'block';
                } else {
                    propCustomField.style.display = 'none';
                }
            }
        }
        
        // Handle cycle type change in cycle modal
        if (e.target && e.target.id === 'cycle_cycle_type') {
            const cycleCustomField = document.getElementById('cycle_custom_months_field');
            if (cycleCustomField) {
                if (e.target.value === 'custom') {
                    cycleCustomField.style.display = 'block';
                } else {
                    cycleCustomField.style.display = 'none';
                }
            }
        }
    });
});

// Toggle payment cycle management section
function togglePaymentCycleSection(button) {
    const body = document.getElementById('payment-cycle-management-body');
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
</script>
@endpush

