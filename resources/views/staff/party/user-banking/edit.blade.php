@extends('layouts.staff_dashboard')

@section('title', 'Chỉnh sửa thông tin ngân hàng - ' . $user->full_name)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-edit mr-2"></i>
                        Chỉnh sửa thông tin ngân hàng - {{ $user->full_name }}
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('staff.user-banking.show', $user) }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Quay lại
                        </a>
                    </div>
                </div>

                <form action="{{ route('staff.user-banking.update', ['user_banking' => $user]) }}" method="POST" id="userBankingForm">
                    @csrf
                    @method('PUT')
                    
                    <div class="card-body">
                        <div class="row">
                            <!-- Personal Information -->
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title">
                                            <i class="fas fa-user-circle mr-2"></i>
                                            Thông tin cá nhân
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group">
                                            <label for="tax_code">Mã số thuế</label>
                                            <input type="text" name="tax_code" id="tax_code" class="form-control" 
                                                   value="{{ old('tax_code', $user->tax_code) }}" 
                                                   placeholder="Nhập mã số thuế">
                                            @error('tax_code')
                                                <div class="text-danger small">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="form-group">
                                            <label for="id_card_number">Số CMND/CCCD</label>
                                            <input type="text" name="id_card_number" id="id_card_number" class="form-control" 
                                                   value="{{ old('id_card_number', $user->id_card_number) }}" 
                                                   placeholder="Nhập số CMND/CCCD">
                                            @error('id_card_number')
                                                <div class="text-danger small">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="id_card_issue_date">Ngày cấp</label>
                                                    <input type="date" name="id_card_issue_date" id="id_card_issue_date" class="form-control" 
                                                           value="{{ old('id_card_issue_date', $user->id_card_issue_date?->format('Y-m-d')) }}">
                                                    @error('id_card_issue_date')
                                                        <div class="text-danger small">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="id_card_issue_place">Nơi cấp</label>
                                                    <input type="text" name="id_card_issue_place" id="id_card_issue_place" class="form-control" 
                                                           value="{{ old('id_card_issue_place', $user->id_card_issue_place) }}" 
                                                           placeholder="Nơi cấp CMND/CCCD">
                                                    @error('id_card_issue_place')
                                                        <div class="text-danger small">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="birth_date">Ngày sinh</label>
                                                    <input type="date" name="birth_date" id="birth_date" class="form-control" 
                                                           value="{{ old('birth_date', $user->birth_date?->format('Y-m-d')) }}">
                                                    @error('birth_date')
                                                        <div class="text-danger small">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="gender">Giới tính</label>
                                                    <select name="gender" id="gender" class="form-control">
                                                        <option value="">Chọn giới tính</option>
                                                        <option value="male" {{ old('gender', $user->gender) == 'male' ? 'selected' : '' }}>Nam</option>
                                                        <option value="female" {{ old('gender', $user->gender) == 'female' ? 'selected' : '' }}>Nữ</option>
                                                        <option value="other" {{ old('gender', $user->gender) == 'other' ? 'selected' : '' }}>Khác</option>
                                                    </select>
                                                    @error('gender')
                                                        <div class="text-danger small">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label for="address">Địa chỉ</label>
                                            <textarea name="address" id="address" class="form-control" rows="3" 
                                                      placeholder="Nhập địa chỉ">{{ old('address', $user->address) }}</textarea>
                                            @error('address')
                                                <div class="text-danger small">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Banking Information -->
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title">
                                            <i class="fas fa-university mr-2"></i>
                                            Thông tin ngân hàng
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group">
                                            <label for="sepay_bank_id">Ngân hàng <span class="text-danger">*</span></label>
                                            <select name="sepay_bank_id" id="sepay_bank_id" class="form-control">
                                                <option value="">Chọn ngân hàng</option>
                                                @foreach($sepayBanks as $bank)
                                                    <option value="{{ $bank->id }}" 
                                                            {{ old('sepay_bank_id', $user->sepay_bank_id) == $bank->id ? 'selected' : '' }}>
                                                        {{ $bank->name }} ({{ $bank->code }})
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('sepay_bank_id')
                                                <div class="text-danger small">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="form-group">
                                            <label for="account_number">Số tài khoản <span class="text-danger">*</span></label>
                                            <input type="text" name="account_number" id="account_number" class="form-control" 
                                                   value="{{ old('account_number', $user->account_number) }}" 
                                                   placeholder="Nhập số tài khoản">
                                            @error('account_number')
                                                <div class="text-danger small">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="form-group">
                                            <label for="account_holder_name">Tên chủ tài khoản <span class="text-danger">*</span></label>
                                            <input type="text" name="account_holder_name" id="account_holder_name" class="form-control" 
                                                   value="{{ old('account_holder_name', $user->account_holder_name) }}" 
                                                   placeholder="Nhập tên chủ tài khoản">
                                            @error('account_holder_name')
                                                <div class="text-danger small">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="branch_name">Tên chi nhánh</label>
                                                    <input type="text" name="branch_name" id="branch_name" class="form-control" 
                                                           value="{{ old('branch_name', $user->branch_name) }}" 
                                                           placeholder="Tên chi nhánh">
                                                    @error('branch_name')
                                                        <div class="text-danger small">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="branch_code">Mã chi nhánh</label>
                                                    <input type="text" name="branch_code" id="branch_code" class="form-control" 
                                                           value="{{ old('branch_code', $user->branch_code) }}" 
                                                           placeholder="Mã chi nhánh">
                                                    @error('branch_code')
                                                        <div class="text-danger small">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label for="swift_code">Mã SWIFT</label>
                                            <input type="text" name="swift_code" id="swift_code" class="form-control" 
                                                   value="{{ old('swift_code', $user->swift_code) }}" 
                                                   placeholder="Mã SWIFT">
                                            @error('swift_code')
                                                <div class="text-danger small">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="form-group">
                                            <label for="banking_notes">Ghi chú ngân hàng</label>
                                            <textarea name="banking_notes" id="banking_notes" class="form-control" rows="3" 
                                                      placeholder="Ghi chú về thông tin ngân hàng">{{ old('banking_notes', $user->banking_notes) }}</textarea>
                                            @error('banking_notes')
                                                <div class="text-danger small">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <div class="row">
                            <div class="col-12 text-right">
                                <a href="{{ route('staff.user-banking.show', $user) }}" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Hủy
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Lưu thay đổi
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Form validation
    $('#userBankingForm').on('submit', function(e) {
        var isValid = true;
        var requiredFields = ['sepay_bank_id', 'account_number', 'account_holder_name'];
        
        requiredFields.forEach(function(field) {
            if (!$('#' + field).val()) {
                $('#' + field).addClass('is-invalid');
                isValid = false;
            } else {
                $('#' + field).removeClass('is-invalid');
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            alert('Vui lòng điền đầy đủ các trường bắt buộc');
        }
    });
    
    // Remove validation class on input
    $('input, select, textarea').on('input change', function() {
        $(this).removeClass('is-invalid');
    });
});
</script>
@endpush
