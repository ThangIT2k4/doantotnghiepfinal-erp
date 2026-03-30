@push('styles')
<style>
/* Icon Only Buttons - Modern Style */
.btn-icon-only {
    min-width: 38px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
}

.btn-icon-only:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.btn-icon-only:active {
    transform: translateY(0);
}

/* Button Group - Icon Only Buttons (như Viewings Index) */
.btn-group .btn-icon-only {
    border-radius: 0;
    border-right: none; /* Bỏ border bên phải */
    margin-left: 0;
}

.btn-group .btn-icon-only:first-child {
    border-top-left-radius: 0.375rem;
    border-bottom-left-radius: 0.375rem;
    border-right: none;
}

.btn-group .btn-icon-only:last-child {
    border-top-right-radius: 0.375rem;
    border-bottom-right-radius: 0.375rem;
    border-right: 1px solid; /* Giữ border bên phải cho button cuối */
}

.btn-group .btn-icon-only:not(:first-child):not(:last-child) {
    border-right: none;
}

/* Bỏ underline cho links trong buttons */
.btn-group a.btn,
.btn-group a.btn:hover,
.btn-group a.btn:focus,
.btn-group a.btn:active {
    text-decoration: none;
}

/* Đảm bảo border color phù hợp với variant */
.btn-group .btn-outline-primary.btn-icon-only {
    border-color: #0d6efd;
}

.btn-group .btn-outline-primary.btn-icon-only:last-child {
    border-right-color: #0d6efd;
}

.btn-group .btn-outline-warning.btn-icon-only {
    border-color: #ffc107;
}

.btn-group .btn-outline-warning.btn-icon-only:last-child {
    border-right-color: #ffc107;
}

.btn-group .btn-outline-danger.btn-icon-only {
    border-color: #dc3545;
}

.btn-group .btn-outline-danger.btn-icon-only:last-child {
    border-right-color: #dc3545;
}

.btn-group .btn-outline-info.btn-icon-only {
    border-color: #0dcaf0;
}

.btn-group .btn-outline-info.btn-icon-only:last-child {
    border-right-color: #0dcaf0;
}

.btn-group .btn-outline-success.btn-icon-only {
    border-color: #198754;
}

.btn-group .btn-outline-success.btn-icon-only:last-child {
    border-right-color: #198754;
}

/* Delete Button - Enhanced Color */
.btn-danger-hover {
    background-color: #dc3545;
    border-color: #dc3545;
    color: #fff;
}

.btn-danger-hover:hover {
    background-color: #bb2d3b;
    border-color: #b02a37;
    color: #fff;
    box-shadow: 0 4px 8px rgba(220, 53, 69, 0.3);
}

.btn-danger-hover:focus {
    background-color: #bb2d3b;
    border-color: #b02a37;
    color: #fff;
    box-shadow: 0 0 0 0.25rem rgba(220, 53, 69, 0.5);
}

/* Dropdown Menu - Enhanced Colors */
.dropdown-menu .dropdown-item.text-primary:hover {
    background-color: rgba(13, 110, 253, 0.1);
    color: #0d6efd !important;
}

.dropdown-menu .dropdown-item.text-success:hover {
    background-color: rgba(25, 135, 84, 0.1);
    color: #198754 !important;
}

.dropdown-menu .dropdown-item.text-danger:hover {
    background-color: rgba(220, 53, 69, 0.1);
    color: #dc3545 !important;
}

.dropdown-menu .dropdown-item.text-warning:hover {
    background-color: rgba(255, 193, 7, 0.1);
    color: #ffc107 !important;
}

.dropdown-menu .dropdown-item.text-info:hover {
    background-color: rgba(13, 202, 240, 0.1);
    color: #0dcaf0 !important;
}

/* Icon spacing in dropdown */
.dropdown-menu .dropdown-item i {
    width: 18px;
    text-align: center;
}

/* Flex Layout - Bỏ border và underline cho icon-only buttons (như Invoices Index) */
.d-flex.gap-2 .btn-icon-only,
.d-flex.gap-2 a.btn {
    text-decoration: none;
}

.d-flex.gap-2 .btn-icon-only:hover,
.d-flex.gap-2 a.btn:hover,
.d-flex.gap-2 .btn-icon-only:focus,
.d-flex.gap-2 a.btn:focus,
.d-flex.gap-2 .btn-icon-only:active,
.d-flex.gap-2 a.btn:active {
    text-decoration: none;
}

/* Bỏ border cho outline buttons trong flex layout */
.d-flex.gap-2 .btn-outline-primary,
.d-flex.gap-2 .btn-outline-warning,
.d-flex.gap-2 .btn-outline-danger,
.d-flex.gap-2 .btn-outline-info,
.d-flex.gap-2 .btn-outline-success {
    border: none;
}

.d-flex.gap-2 .btn-outline-primary:hover,
.d-flex.gap-2 .btn-outline-warning:hover,
.d-flex.gap-2 .btn-outline-danger:hover,
.d-flex.gap-2 .btn-outline-info:hover,
.d-flex.gap-2 .btn-outline-success:hover {
    border: none;
}

/* Edit Button - Màu xanh đậm hơn khi hover */
.btn-outline-warning.btn-icon-only:hover,
.btn-outline-warning:hover {
    background-color: rgba(13, 110, 253, 0.25) !important;
    border-color: #0a58ca !important;
    color: #0a58ca !important;
    box-shadow: 0 2px 6px rgba(10, 88, 202, 0.4);
}

.btn-outline-warning.btn-icon-only:focus,
.btn-outline-warning:focus {
    background-color: rgba(13, 110, 253, 0.25) !important;
    border-color: #0a58ca !important;
    color: #0a58ca !important;
    box-shadow: 0 0 0 0.25rem rgba(10, 88, 202, 0.3);
}

/* Outline Primary - Màu xanh đậm hơn khi hover */
.btn-outline-primary.btn-icon-only:hover,
.btn-outline-primary:hover {
    background-color: rgba(10, 88, 202, 0.25) !important;
    border-color: #0a58ca !important;
    color: #0a58ca !important;
    box-shadow: 0 2px 6px rgba(10, 88, 202, 0.4);
}

.btn-outline-primary.btn-icon-only:focus,
.btn-outline-primary:focus {
    background-color: rgba(10, 88, 202, 0.25) !important;
    border-color: #0a58ca !important;
    color: #0a58ca !important;
    box-shadow: 0 0 0 0.25rem rgba(10, 88, 202, 0.3);
}

/* Primary Button - Màu xanh đậm hơn khi hover */
.btn-primary:hover {
    background-color: #0a58ca !important;
    border-color: #0a58ca !important;
    color: #fff !important;
    box-shadow: 0 2px 6px rgba(10, 88, 202, 0.4);
}

.btn-primary:focus {
    background-color: #0a58ca !important;
    border-color: #0a58ca !important;
    color: #fff !important;
    box-shadow: 0 0 0 0.25rem rgba(10, 88, 202, 0.3);
}

.btn-primary.btn-icon-only:hover {
    background-color: #0a58ca !important;
    border-color: #0a58ca !important;
    color: #fff !important;
    box-shadow: 0 2px 6px rgba(10, 88, 202, 0.4);
}

.btn-primary.btn-icon-only:focus {
    background-color: #0a58ca !important;
    border-color: #0a58ca !important;
    color: #fff !important;
    box-shadow: 0 0 0 0.25rem rgba(10, 88, 202, 0.3);
}

/* Responsive adjustments */
@media (max-width: 576px) {
    .btn-icon-only {
        min-width: 36px;
        padding: 0.25rem 0.5rem;
    }
}
</style>
@endpush

