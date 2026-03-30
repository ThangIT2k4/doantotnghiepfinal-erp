@extends('layouts.staff_dashboard')

@section('title', 'Quản lý Bất động sản')

@section('content')
<main class="main-content">
    <div class="container-fluid">
        {{-- 1. Page Header với solid variants --}}
        @include('staff.components.index-page-header', [
            'title' => 'Quản lý Bất động sản',
            'subtitle' => 'Danh sách tất cả bất động sản đang quản lý',
            'icon' => 'fas fa-building',
            'actions' => [
                [
                    'variant' => 'primary',
                    'label' => 'Thêm BĐS mới',
                    'icon' => 'fas fa-plus',
                    'url' => route('staff.properties.create')
                ]
            ]
        ])

        {{-- 2. Statistics Cards --}}
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
                'onFilterClick' => 'htmx-filter',
                'onClearClick' => 'htmx-clear',
                'tableContainerId' => 'properties-table-container',
                'action' => route('staff.properties.index'),
                'columns' => 3
            ])
        </div>

        {{-- 3. Filters với HTMX --}}
        @php
            $filterFields = [
                [
                    'name' => 'search',
                    'label' => 'Tìm kiếm',
                    'type' => 'text',
                    'col' => 'col-md-4',
                    'placeholder' => 'Tên BĐS, đường, quận, phường, chủ sở hữu...',
                    'value' => request('search'),
                ],
                [
                    'name' => 'type',
                    'label' => 'Loại BĐS',
                    'type' => 'select',
                    'col' => 'col-md-2',
                    'empty_option' => 'Tất cả',
                    'options' => collect($propertyTypes)->mapWithKeys(function($type) {
                        return [$type->id => $type->name];
                    })->toArray(),
                    'value' => request('type'),
                ],
                [
                    'name' => 'status',
                    'label' => 'Trạng thái',
                    'type' => 'select',
                    'col' => 'col-md-2',
                    'empty_option' => 'Tất cả',
                    'options' => [
                        '1' => 'Hoạt động',
                        '0' => 'Tạm ngưng',
                    ],
                    'value' => request('status'),
                ],
                [
                    'name' => 'province',
                    'label' => 'Tỉnh/Thành phố (Cũ)',
                    'type' => 'select',
                    'col' => 'col-md-2',
                    'empty_option' => 'Tất cả',
                    'options' => collect($provinces)->mapWithKeys(function($province) {
                        return [$province->code => $province->name];
                    })->toArray(),
                    'value' => request('province'),
                ],
                [
                    'name' => 'district',
                    'label' => 'Quận/Huyện (Cũ)',
                    'type' => 'select',
                    'col' => 'col-md-2',
                    'empty_option' => 'Tất cả',
                    'options' => collect($districts)->mapWithKeys(function($district) {
                        return [$district->code => $district->name];
                    })->toArray(),
                    'value' => request('district'),
                ],
                [
                    'name' => 'province_2025',
                    'label' => 'Tỉnh/Thành phố (Mới 2025)',
                    'type' => 'select',
                    'col' => 'col-md-2',
                    'empty_option' => 'Tất cả',
                    'options' => collect($provinces2025 ?? [])->mapWithKeys(function($province) {
                        return [$province->code => $province->name];
                    })->toArray(),
                    'value' => request('province_2025'),
                ],
                [
                    'name' => 'ward_2025',
                    'label' => 'Phường/Xã (Mới 2025)',
                    'type' => 'select',
                    'col' => 'col-md-2',
                    'empty_option' => 'Tất cả',
                    'options' => collect($wards2025 ?? [])->mapWithKeys(function($ward) {
                        return [$ward->code => ($ward->name_local ?? $ward->name)];
                    })->toArray(),
                    'value' => request('ward_2025'),
                ],
                [
                    'name' => 'date_from',
                    'label' => 'Từ ngày',
                    'type' => 'date',
                    'col' => 'col-md-2',
                    'value' => request('date_from'),
                ],
                [
                    'name' => 'date_to',
                    'label' => 'Đến ngày',
                    'type' => 'date',
                    'col' => 'col-md-2',
                    'value' => request('date_to'),
                ],
            ];
        @endphp
        @include('staff.components.index-filters-htmx', [
            'action' => route('staff.properties.index'),
            'tableContainerId' => 'properties-table-container',
            'statsContainerId' => 'stats-container',
            'fields' => $filterFields,
            'showReset' => true,
            'resetUrl' => route('staff.properties.index')
        ])

        {{-- 4. Table với outline variants cho actions --}}
        @include('staff.asset.properties.partials.table', [
            'properties' => $properties,
            'sortBy' => $sortBy ?? 'id',
            'sortOrder' => $sortOrder ?? 'desc'
        ])
    </div>
</main>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Cascading dropdown for old address system
    const provinceFilter = document.getElementById('filter_province');
    const districtFilter = document.getElementById('filter_district');
    
    if (provinceFilter && districtFilter) {
        provinceFilter.addEventListener('change', function() {
            const provinceCode = this.value;
            
            // Reset district filter
            districtFilter.innerHTML = '<option value="">Tất cả</option>';
            
            if (!provinceCode) {
                districtFilter.disabled = true;
                // Trigger HTMX reload
                htmx.trigger('#index-filters-form', 'submit');
                return;
            }
            
            // Fetch districts for selected province
            fetch(`/staff/api/geo/districts/${provinceCode}`)
                .then(response => response.json())
                .then(data => {
                    data.forEach(district => {
                        const option = document.createElement('option');
                        option.value = district.code;
                        option.textContent = district.name;
                        districtFilter.appendChild(option);
                    });
                    districtFilter.disabled = false;
                    // Trigger HTMX reload after districts are loaded
                    htmx.trigger('#index-filters-form', 'submit');
                })
                .catch(error => {
                    console.error('Error fetching districts:', error);
                    districtFilter.disabled = true;
                });
        });
    }
    
    // Cascading dropdown for 2025 address system
    const province2025Filter = document.getElementById('filter_province_2025');
    const ward2025Filter = document.getElementById('filter_ward_2025');
    
    if (province2025Filter && ward2025Filter) {
        province2025Filter.addEventListener('change', function() {
            const provinceCode = this.value;
            
            // Reset ward filter
            ward2025Filter.innerHTML = '<option value="">Tất cả</option>';
            
            if (!provinceCode) {
                ward2025Filter.disabled = true;
                // Trigger HTMX reload
                htmx.trigger('#index-filters-form', 'submit');
                return;
            }
            
            // Fetch wards for selected province
            fetch(`/staff/api/geo/wards-2025/${provinceCode}`)
                .then(response => response.json())
                .then(data => {
                    data.forEach(ward => {
                        const option = document.createElement('option');
                        option.value = ward.code;
                        option.textContent = ward.name_local || ward.name;
                        ward2025Filter.appendChild(option);
                    });
                    ward2025Filter.disabled = false;
                    // Trigger HTMX reload after wards are loaded
                    htmx.trigger('#index-filters-form', 'submit');
                })
                .catch(error => {
                    console.error('Error fetching wards:', error);
                    ward2025Filter.disabled = true;
                });
        });
    }
});

// HTMX đã tự động handle filters, không cần filterByStatus() và clearAllFilters() nữa

function deleteProperty(id, name) {
    Notify.confirmDelete(`bất động sản "${name}"`, () => {
        // Show preloader
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

        fetch(`/staff/properties/${id}`, {
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
                    // Reload table and stats via HTMX
                    const url = '{{ route("staff.properties.index") }}';
                    htmx.ajax('GET', url, {
                        target: '#properties-table-container',
                        swap: 'innerHTML'
                    });
                }, 1000);
            } else {
                Notify.error(data.message || 'Có lỗi xảy ra', 'Lỗi!');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Notify.error('Không thể xóa bất động sản: ' + error.message + '. Vui lòng thử lại sau hoặc liên hệ Admin để được hỗ trợ.', 'Lỗi hệ thống!');
        })
        .finally(() => {
            if (window.Preloader) {
                window.Preloader.hide();
            }
        });
    });
}
</script>
@endpush
@endsection
