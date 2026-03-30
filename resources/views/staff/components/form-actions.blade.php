@props([
    'submitLabel' => 'Lưu',
    'submitIcon' => 'fas fa-save',
    'submitVariant' => 'primary',
    'submitSize' => 'md',
    'cancelLabel' => 'Hủy',
    'cancelIcon' => 'fas fa-times',
    'cancelVariant' => 'secondary',
    'cancelSize' => 'md',
    'cancelUrl' => null,
    'showCancel' => true,
    'showSubmit' => true,
    'submitLoading' => false,
    'justify' => 'end', // 'start', 'end', 'between', 'center'
    'class' => '',
])

@php
    $justifyClass = 'justify-content-' . $justify;
@endphp

<div class="d-flex {{ $justifyClass }} gap-2 {{ $class }}">
    @if($showCancel)
        <a href="{{ $cancelUrl ?? url()->previous() }}" class="btn btn-secondary form-action-cancel-btn">
            <i class="{{ $cancelIcon }} me-1"></i>
            {{ $cancelLabel }}
        </a>
    @endif
    
    @if($showSubmit)
        @include('staff.components.button', [
            'type' => 'submit',
            'variant' => $submitVariant,
            'size' => $submitSize,
            'label' => $submitLabel,
            'icon' => $submitIcon,
            'iconPosition' => 'left',
            'loading' => $submitLoading,
            'class' => 'form-action-submit'
        ])
    @endif
</div>

@push('styles')
<style>
/* Form Actions - Đảm bảo buttons có cùng style */
.form-action-cancel,
.form-action-submit {
    min-width: 100px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.form-action-cancel .btn,
.form-action-submit .btn {
    font-weight: 500;
    padding: 0.5rem 1rem;
    text-decoration: none !important;
}

.form-action-cancel .btn i,
.form-action-submit .btn i {
    margin-right: 0.5rem;
}

/* Nút Hủy - Thêm màu nền và bỏ gạch chân */
.form-action-cancel-btn,
.form-action-cancel-btn:hover,
.form-action-cancel-btn:focus,
.form-action-cancel-btn:active,
.form-action-cancel-btn:visited {
    text-decoration: none !important;
    background-color: #f8f9fa !important;
    border: 1px solid #dee2e6 !important;
    color: #6c757d !important;
    font-weight: 500 !important;
    padding: 0.5rem 1rem !important;
    min-width: 100px !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
}

.form-action-cancel-btn:hover {
    background-color: #e9ecef !important;
    border-color: #adb5bd !important;
    color: #495057 !important;
    text-decoration: none !important;
}

.form-action-cancel-btn:focus {
    background-color: #e9ecef !important;
    border-color: #adb5bd !important;
    color: #495057 !important;
    box-shadow: 0 0 0 0.25rem rgba(108, 117, 125, 0.25) !important;
    text-decoration: none !important;
}

.form-action-cancel-btn:active {
    background-color: #e9ecef !important;
    border-color: #adb5bd !important;
    color: #495057 !important;
    text-decoration: none !important;
}

.form-action-cancel-btn i {
    margin-right: 0.5rem !important;
}
</style>
@endpush

