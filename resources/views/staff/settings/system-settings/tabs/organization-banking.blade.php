<div class="row">
    <div class="col-12">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="card-title mb-0">
                    <i class="fas fa-university me-2"></i>
                    Tài khoản ngân hàng
                </h6>
                <button type="button" class="btn btn-primary btn-sm" onclick="showCreateBankingModal()">
                    <i class="fas fa-plus me-1"></i>Thêm tài khoản
                </button>
            </div>
            <div class="card-body">

                @if($bankingAccounts->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Ngân hàng</th>
                                    <th>Số tài khoản</th>
                                    <th>Tên chủ tài khoản</th>
                                    <th>Chi nhánh</th>
                                    <th>Trạng thái</th>
                                    <th>Mặc định</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($bankingAccounts as $account)
                                <tr>
                                    <td>
                                        <strong>{{ $account->bank_name }}</strong>
                                        @if($account->sepayBank)
                                            <br><small class="text-muted">{{ $account->sepayBank->name }} ({{ $account->sepayBank->code }})</small>
                                        @endif
                                    </td>
                                    <td><code>{{ $account->account_number }}</code></td>
                                    <td>{{ $account->account_name }}</td>
                                    <td>{{ $account->branch ?? 'N/A' }}</td>
                                    <td>
                                        @if($account->is_active)
                                            <span class="badge bg-success">Hoạt động</span>
                                        @else
                                            <span class="badge bg-secondary">Không hoạt động</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($account->is_default)
                                            <span class="badge bg-primary">Mặc định</span>
                                        @else
                                            <button class="btn btn-sm btn-outline-primary" onclick="setDefault({{ $account->id }})">
                                                <i class="fas fa-star me-1"></i>Đặt mặc định
                                            </button>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group table-actions" role="group">
                                            <button type="button" class="btn btn-outline-warning btn-icon-only" 
                                                    onclick="editBankingAccount({{ $account->id }})" 
                                                    title="Sửa">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-danger btn-icon-only" 
                                                    onclick="deleteAccount({{ $account->id }})" 
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
                    <div class="text-center py-5">
                        <i class="fas fa-university fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Chưa có tài khoản ngân hàng nào.</p>
                        <button type="button" class="btn btn-primary" onclick="showCreateBankingModal()">
                            <i class="fas fa-plus me-2"></i>Thêm tài khoản đầu tiên
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@push('modals')
<!-- Banking Account Modal -->
<div class="modal fade" id="bankingAccountModal" tabindex="-1" aria-labelledby="bankingAccountModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bankingAccountModalLabel">Thêm tài khoản ngân hàng</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="bankingAccountForm">
                <div class="modal-body">
                    <input type="hidden" id="banking_account_id" name="id">
                    
                    <div class="mb-3">
                        <label for="sepay_bank_id" class="form-label required">Ngân hàng</label>
                        <select name="sepay_bank_id" id="sepay_bank_id" class="form-select" required>
                            <option value="">Chọn ngân hàng</option>
                            @foreach($sepayBanks as $bank)
                            <option value="{{ $bank->id }}">{{ $bank->name }} ({{ $bank->code }})</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="mb-3">
                        <label for="account_number" class="form-label required">Số tài khoản</label>
                        <input type="text" name="account_number" id="account_number" class="form-control" 
                               placeholder="Nhập số tài khoản" required maxlength="50">
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="mb-3">
                        <label for="account_name" class="form-label required">Tên chủ tài khoản</label>
                        <input type="text" name="account_name" id="account_name" class="form-control" 
                               placeholder="Nhập tên chủ tài khoản" required maxlength="255">
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="mb-3">
                        <label for="branch" class="form-label">Chi nhánh</label>
                        <input type="text" name="branch" id="branch" class="form-control" 
                               placeholder="Nhập chi nhánh (tùy chọn)" maxlength="255">
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" checked>
                                <label class="form-check-label" for="is_active">
                                    Hoạt động
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" name="is_default" id="is_default" value="1">
                                <label class="form-check-label" for="is_default">
                                    Đặt làm mặc định
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="notes" class="form-label">Ghi chú</label>
                        <textarea name="notes" id="notes" class="form-control" rows="3" 
                                  placeholder="Ghi chú thêm về tài khoản ngân hàng (tùy chọn)" maxlength="1000"></textarea>
                        <div class="invalid-feedback"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-spinner fa-spin d-none me-2" id="bankingFormSpinner"></i>
                        <span id="bankingFormSubmitText">Lưu</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endpush

@push('scripts')
<script>
const bankingModal = new bootstrap.Modal(document.getElementById('bankingAccountModal'));

function showCreateBankingModal() {
    document.getElementById('bankingAccountModalLabel').textContent = 'Thêm tài khoản ngân hàng';
    document.getElementById('bankingAccountForm').reset();
    document.getElementById('banking_account_id').value = '';
    document.getElementById('is_active').checked = true;
    document.getElementById('is_default').checked = false;
    clearBankingFormValidation();
    bankingModal.show();
}

function editBankingAccount(id) {
    fetch(`{{ url('staff/organization-banking') }}/${id}`, {
        headers: {
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.account) {
            const account = data.account;
            document.getElementById('bankingAccountModalLabel').textContent = 'Sửa tài khoản ngân hàng';
            document.getElementById('banking_account_id').value = account.id;
            document.getElementById('sepay_bank_id').value = account.sepay_bank_id || '';
            document.getElementById('account_number').value = account.account_number || '';
            document.getElementById('account_name').value = account.account_name || '';
            document.getElementById('branch').value = account.branch || '';
            document.getElementById('is_active').checked = account.is_active || false;
            document.getElementById('is_default').checked = account.is_default || false;
            document.getElementById('notes').value = account.notes || '';
            clearBankingFormValidation();
            bankingModal.show();
        } else {
            Notify.error(data.message || 'Không thể tải thông tin tài khoản', 'Lỗi!');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Notify.error('Có lỗi xảy ra khi tải thông tin tài khoản.', 'Lỗi hệ thống');
    });
}

function clearBankingFormValidation() {
    document.querySelectorAll('#bankingAccountForm .is-invalid').forEach(el => {
        el.classList.remove('is-invalid');
    });
    document.querySelectorAll('#bankingAccountForm .invalid-feedback').forEach(el => {
        el.textContent = '';
    });
}

document.getElementById('bankingAccountForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    clearBankingFormValidation();
    
    const formData = new FormData(this);
    const accountId = formData.get('id');
    const url = accountId 
        ? `{{ url('staff/organization-banking') }}/${accountId}`
        : `{{ route('staff.organization-banking.store') }}`;
    const method = accountId ? 'PUT' : 'POST';
    
    const submitBtn = this.querySelector('button[type="submit"]');
    const spinner = document.getElementById('bankingFormSpinner');
    const submitText = document.getElementById('bankingFormSubmitText');
    
    spinner.classList.remove('d-none');
    submitText.textContent = 'Đang lưu...';
    submitBtn.disabled = true;
    
    fetch(url, {
        method: method,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Notify.success(data.message, 'Thành công!');
            bankingModal.hide();
            setTimeout(() => location.reload(), 1000);
        } else {
            if (data.errors) {
                Object.keys(data.errors).forEach(field => {
                    const input = document.querySelector(`[name="${field}"]`);
                    if (input) {
                        input.classList.add('is-invalid');
                        const feedback = input.parentNode.querySelector('.invalid-feedback');
                        if (feedback) {
                            feedback.textContent = data.errors[field][0];
                        }
                    }
                });
            }
            Notify.error(data.message || 'Có lỗi xảy ra khi lưu tài khoản.', 'Lỗi!');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Notify.error('Có lỗi xảy ra khi lưu tài khoản.', 'Lỗi hệ thống');
    })
    .finally(() => {
        spinner.classList.add('d-none');
        submitText.textContent = 'Lưu';
        submitBtn.disabled = false;
    });
});

function setDefault(id) {
    Notify.confirm({
        title: 'Xác nhận',
        message: 'Bạn có chắc muốn đặt tài khoản này làm mặc định?',
        type: 'info',
        confirmText: 'Xác nhận',
        cancelText: 'Hủy',
        onConfirm: function() {
            fetch(`{{ url('staff/organization-banking') }}/${id}/set-default`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Notify.success(data.message, 'Thành công!');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    Notify.error(data.message, 'Lỗi!');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Notify.error('Có lỗi xảy ra. Vui lòng thử lại.', 'Lỗi hệ thống');
            });
        }
    });
}

function deleteAccount(id) {
    Notify.confirmDelete('tài khoản ngân hàng này', function() {
        fetch(`{{ url('staff/organization-banking') }}/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Notify.success(data.message, 'Thành công!');
                setTimeout(() => location.reload(), 1000);
            } else {
                Notify.error(data.message, 'Lỗi!');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Notify.error('Có lỗi xảy ra. Vui lòng thử lại.', 'Lỗi hệ thống');
        });
    });
}
</script>
@endpush

