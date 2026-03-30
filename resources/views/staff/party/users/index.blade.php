@extends('layouts.staff_dashboard')

@section('title', 'Quản lý người dùng')

@section('content')
<main class="main-content">
    <div class="container-fluid">
        <!-- Page Header -->
        @include('staff.components.index-page-header', [
            'title' => 'Quản lý người dùng',
            'subtitle' => 'Quản lý tài khoản người dùng trong hệ thống',
            'icon' => 'fas fa-users',
            'actions' => [
                [
                    'variant' => 'primary',
                    'label' => 'Thêm người dùng',
                    'icon' => 'fas fa-plus',
                    'url' => route('staff.users.create')
                ]
            ]
        ])

        <!-- Statistics Cards -->
        @php
            $stats = $stats ?? [
                'total' => 0,
                'active' => 0,
                'inactive' => 0,
            ];
            
            $statsFormatted = [
                'total' => [
                    'value' => $stats['total'] ?? 0,
                    'label' => 'Tổng cộng',
                    'icon' => 'fa-list',
                    'color' => 'primary',
                    'filter' => '',
                ],
                'active' => [
                    'value' => $stats['active'] ?? 0,
                    'label' => 'Hoạt động',
                    'icon' => 'fa-check-circle',
                    'color' => 'success',
                    'filter' => '1',
                ],
                'inactive' => [
                    'value' => $stats['inactive'] ?? 0,
                    'label' => 'Tạm ngưng',
                    'icon' => 'fa-pause-circle',
                    'color' => 'warning',
                    'filter' => '0',
                ],
            ];
        @endphp
        <div id="stats-container">
            @include('staff.components.statistics-cards', [
                'stats' => $statsFormatted,
                'currentFilter' => request('status', ''),
                'filterKey' => 'status',
                'onFilterClick' => 'htmx-filter', // Use HTMX instead of JavaScript
                'onClearClick' => 'htmx-clear', // Use HTMX instead of JavaScript
                'tableContainerId' => 'users-table-container',
                'action' => route('staff.users.index'),
                'columns' => 3
            ])
        </div>

        <!-- Filters với HTMX -->
        @include('staff.components.index-filters-htmx', [
            'action' => route('staff.users.index'),
            'tableContainerId' => 'users-table-container',
            'statsContainerId' => 'stats-container',
            'fields' => [
                [
                    'name' => 'search',
                    'label' => 'Tìm kiếm',
                    'type' => 'text',
                    'placeholder' => 'Tên, email, số điện thoại...',
                    'value' => request('search'),
                    'col' => 'col-md-3',
                ],
                [
                    'name' => 'role_id',
                    'label' => 'Vai trò',
                    'type' => 'select',
                    'empty_option' => 'Tất cả vai trò',
                    'options' => $roles->pluck('name', 'id')->toArray(),
                    'value' => request('role_id', ''),
                    'col' => 'col-md-2',
                    'class' => 'select2',
                ],
                [
                    'name' => 'status',
                    'label' => 'Trạng thái',
                    'type' => 'select',
                    'empty_option' => 'Tất cả',
                    'options' => [
                        '1' => 'Hoạt động',
                        '0' => 'Tạm ngưng',
                    ],
                    'value' => request('status', ''),
                    'col' => 'col-md-2',
                ],
                [
                    'name' => 'date',
                    'label' => 'Ngày tạo',
                    'type' => 'date-range',
                    'col' => 'col-md-4',
                ],
            ],
            'showReset' => true,
            'resetUrl' => route('staff.users.index')
        ])

        <!-- Table Container -->
        <div id="users-table-container">
            @include('staff.components.index-table', [
                'items' => $users,
                'tableContainerId' => 'users-table-container',
                'selectable' => false, // Disable bulk actions
                'columns' => [
                [
                    'name' => 'id',
                    'label' => 'ID',
                    'format' => function($user) {
                        return '<span class="badge bg-secondary">#' . $user->id . '</span>';
                    },
                    'sortable' => true,
                ],
                [
                    'name' => 'full_name',
                    'label' => 'Họ tên',
                    'format' => function($user) {
                        return '<h6 class="mb-0">' . ($user->userProfile->full_name ?? '-') . '</h6>';
                    },
                    'sortable' => true,
                ],
                [
                    'name' => 'email',
                    'label' => 'Email',
                    'format' => function($user) {
                        return '<small class="text-muted">' . $user->email . '</small>';
                    },
                    'sortable' => true,
                ],
                [
                    'name' => 'phone',
                    'label' => 'Số điện thoại',
                    'format' => function($user) {
                        return $user->phone ? '<small class="text-muted">' . $user->phone . '</small>' : '<span class="text-muted">-</span>';
                    },
                    'sortable' => true,
                ],
                [
                    'name' => 'roles',
                    'label' => 'Vai trò',
                    'format' => function($user) {
                        if ($user->userRoles->count() > 0) {
                            $badges = $user->userRoles->map(function($role) {
                                return '<span class="badge bg-info">' . $role->name . '</span>';
                            })->implode(' ');
                            return $badges;
                        }
                        return '<span class="text-muted">Chưa có vai trò</span>';
                    },
                    'sortable' => false,
                ],
                [
                    'name' => 'status',
                    'label' => 'Trạng thái',
                    'format' => function($user) {
                        if ($user->status) {
                            return '<span class="badge bg-success">Hoạt động</span>';
                        }
                        return '<span class="badge bg-warning">Tạm ngưng</span>';
                    },
                    'sortable' => true,
                ],
                [
                    'name' => 'created_at',
                    'label' => 'Ngày tạo',
                    'format' => function($user) {
                        return '<small class="text-muted">' . $user->created_at->format('d/m/Y H:i') . '</small>';
                    },
                    'sortable' => true,
                ],
                [
                    'name' => 'updated_at',
                    'label' => 'Cập nhật',
                    'format' => function($user) {
                        return '<small class="text-muted">' . $user->updated_at->format('d/m/Y H:i') . '</small>';
                    },
                    'sortable' => true,
                ],
            ],
            'sortBy' => request('sort_by', 'created_at'),
            'sortOrder' => request('sort_order', 'desc'),
            'rowActions' => function($user) {
                $actions = [
                    [
                        'variant' => 'outline-primary',
                        'icon' => 'fas fa-eye',
                        'iconPosition' => 'only',
                        'tooltip' => 'Xem chi tiết',
                        'url' => route('staff.users.show', $user->id)
                    ],
                    [
                        'variant' => 'outline-warning',
                        'icon' => 'fas fa-edit',
                        'iconPosition' => 'only',
                        'tooltip' => 'Sửa',
                        'url' => route('staff.users.edit', $user->id)
                    ],
                ];
                
                // Only show delete button if not current user
                if ($user->id !== auth()->id()) {
                    $userName = $user->userProfile->full_name ?? $user->email;
                    $actions[] = [
                        'variant' => 'outline-danger',
                        'icon' => 'fas fa-trash',
                        'iconPosition' => 'only',
                        'tooltip' => 'Xóa',
                        'onclick' => "deleteUser({$user->id}, '" . addslashes($userName) . "')",
                        'type' => 'button',
                    ];
                }
                
                return $actions;
            },
            'emptyMessage' => 'Chưa có người dùng nào',
            'emptyIcon' => 'fa-users',
            'emptyAction' => [
                'variant' => 'primary',
                'label' => 'Thêm người dùng mới',
                'icon' => 'fas fa-plus',
                'url' => route('staff.users.create')
            ]
            ])
        </div>
    </div>
</main>
@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
.select2-container--default .select2-selection--single {
    height: 38px;
    border: 1px solid #ced4da;
    border-radius: 0.375rem;
}
.select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 36px;
    padding-left: 12px;
}
.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 36px;
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
// Initialize Select2 for searchable dropdowns
function initializeSelect2() {
    $('.select2').each(function() {
        const $select = $(this);
        
        // Destroy existing Select2 if any
        if ($select.hasClass('select2-hidden-accessible')) {
            $select.select2('destroy');
        }
        
        const currentValue = $select.val();
        
        // Initialize Select2
        $select.select2({
            placeholder: function() {
                return $(this).data('placeholder') || 'Chọn...';
            },
            allowClear: true,
            width: '100%'
        });

        // Ensure the current value is maintained (especially empty value)
        if (currentValue === null || currentValue === '' || currentValue === undefined) {
            $select.val('').trigger('change');
        } else {
            $select.val(currentValue).trigger('change');
        }
    });
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', initializeSelect2);

// Re-initialize after HTMX updates
document.body.addEventListener('htmx:afterSwap', function(event) {
    if (event.detail.target.id === 'users-table-container' || event.detail.target.closest('#users-table-container')) {
        initializeSelect2();
    }
});

// Debug HTMX sort links
document.body.addEventListener('htmx:configRequest', function(event) {
    console.log('HTMX Request:', event.detail);
});

// Ensure HTMX handles sort links
document.addEventListener('DOMContentLoaded', function() {
    // Check if HTMX is loaded
    if (typeof htmx !== 'undefined') {
        console.log('HTMX is loaded and ready');
        
        // Manually process sort links to ensure HTMX works
        document.querySelectorAll('.sort-link[hx-get]').forEach(function(link) {
            // HTMX should automatically handle these, but ensure it's processed
            htmx.process(link);
        });
    } else {
        console.warn('HTMX is not loaded');
    }
});

// Re-process sort links after HTMX swaps
document.body.addEventListener('htmx:afterSwap', function(event) {
    if (event.detail.target.id === 'users-table-container' || event.detail.target.closest('#users-table-container')) {
        // Re-process HTMX attributes on new sort links
        if (typeof htmx !== 'undefined') {
            event.detail.target.querySelectorAll('.sort-link[hx-get]').forEach(function(link) {
                htmx.process(link);
            });
        }
    }
});

// HTMX đã tự động handle filters, không cần JavaScript functions này nữa
// filterByStatus() và clearAllFilters() đã được thay thế bằng HTMX attributes

function deleteUser(id, name) {
    if (typeof Notify !== 'undefined' && Notify.confirmDelete) {
    Notify.confirmDelete(`người dùng "${name}"`, () => {
        if (window.Preloader) {
            window.Preloader.show();
        }

        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        if (!csrfToken) {
            console.error('CSRF token not found');
            Notify.error('Lỗi bảo mật: Không tìm thấy CSRF token. Vui lòng tải lại trang và thử lại.', 'Lỗi bảo mật!');
            if (window.Preloader) {
                window.Preloader.hide();
            }
            return;
        }

        fetch(`/staff/users/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrfToken.getAttribute('content'),
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                Notify.success(data.message, 'Đã xóa!');
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                Notify.error(data.message || 'Có lỗi xảy ra', 'Lỗi!');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Notify.error('Không thể xóa người dùng: ' + error.message + '. Vui lòng thử lại sau hoặc liên hệ Admin để được hỗ trợ.', 'Lỗi hệ thống!');
        })
        .finally(() => {
            if (window.Preloader) {
                window.Preloader.hide();
            }
        });
    });
    } else {
        if (confirm('Bạn có chắc chắn muốn xóa người dùng "' + name + '"?')) {
            if (window.Preloader) {
                window.Preloader.show();
            }
            const csrfToken = document.querySelector('meta[name="csrf-token"]');
            if (!csrfToken) {
                console.error('CSRF token not found');
                alert('Lỗi bảo mật: Không tìm thấy CSRF token. Vui lòng tải lại trang và thử lại.');
                if (window.Preloader) {
                    window.Preloader.hide();
                }
                return;
            }
            fetch(`/staff/users/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken.getAttribute('content'),
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    alert('Người dùng đã được xóa thành công!');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    alert('Có lỗi xảy ra: ' + (data.message || 'Không xác định'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Không thể xóa người dùng: ' + error.message + '. Vui lòng thử lại sau hoặc liên hệ Admin để được hỗ trợ.');
            })
            .finally(() => {
                if (window.Preloader) {
                    window.Preloader.hide();
                }
            });
        }
    }
}
</script>
@endpush
