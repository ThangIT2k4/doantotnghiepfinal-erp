@extends('layouts.staff_dashboard')

@section('title', 'Quản lý phân quyền')

@section('content')
<main class="main-content">
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="h3 mb-0 text-gray-800">
                            <i class="fas fa-key me-2"></i>Quản lý phân quyền
                        </h1>
                        <p class="text-muted mb-0">Người dùng: <strong>{{ $targetUser->full_name ?? $user->full_name ?? 'N/A' }}</strong> ({{ $targetUser->email ?? $user->email ?? 'N/A' }})</p>
                        <p class="text-muted mb-0">Vai trò: <strong>{{ $orgUser->role->name ?? 'N/A' }}</strong></p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('staff.users.show', $targetUser->id ?? $user->id ?? null) }}" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Quay lại
                        </a>
                        <button type="button" class="btn btn-primary" onclick="saveAllCapabilities()">
                            <i class="fas fa-save me-1"></i>Lưu tất cả
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Info Alert -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Lưu ý:</strong>
                    <ul class="mb-0 mt-2">
                        <li>Quyền từ <strong>Role mặc định</strong> được áp dụng tự động theo vai trò.</li>
                        <li>Quyền <strong>Override</strong> sẽ ghi đè quyền mặc định của role.</li>
                        <li>Quyền <strong>Deny</strong> sẽ từ chối quyền ngay cả khi role có quyền đó.</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Tabs Navigation -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2">
                    @foreach($allCapabilities as $category => $capabilities)
                        <button type="button" class="btn btn-sm btn-outline-primary {{ $loop->first ? 'active' : '' }}" onclick="toggleCapabilityTab('{{ $category }}', this)">
                            <i class="fas fa-{{ $category == 'ticket' ? 'ticket-alt' : ($category == 'lease' ? 'file-contract' : ($category == 'invoice' ? 'file-invoice' : ($category == 'property' ? 'building' : ($category == 'party' ? 'users' : 'folder')))) }} me-2"></i>
                            {{ ucfirst(str_replace('_', ' ', $category)) }}
                            <span class="badge bg-secondary ms-2">{{ $capabilities->count() }}</span>
                        </button>
                    @endforeach
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="expandAllCapabilityTabs()">
                        <i class="fas fa-expand"></i> Mở tất cả
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="collapseAllCapabilityTabs()">
                        <i class="fas fa-compress"></i> Đóng tất cả
                    </button>
                </div>
            </div>
        </div>

        <!-- Capabilities by Category -->
        <div class="row">
            @foreach($allCapabilities as $category => $capabilities)
                <div class="col-12 mb-4">
                    <div class="card shadow-sm tab-content" id="tab-{{ $category }}" style="{{ $loop->first ? '' : 'display: none;' }}">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0">
                                <i class="fas fa-{{ $category == 'ticket' ? 'ticket-alt' : ($category == 'lease' ? 'file-contract' : ($category == 'invoice' ? 'file-invoice' : ($category == 'property' ? 'building' : ($category == 'party' ? 'users' : 'folder')))) }} me-2"></i>
                                {{ ucfirst(str_replace('_', ' ', $category)) }}
                                <small class="text-white-50">({{ $capabilities->count() }} quyền)</small>
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 40%">Quyền</th>
                                            <th style="width: 30%">Mặc định</th>
                                            <th style="width: 30%">Override</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($capabilities as $cap)
                                            @php
                                                $roleHas = isset($roleCaps[$cap->key_code]) && $roleCaps[$cap->key_code] === true;
                                                $userHas = isset($userCapabilities[$cap->key_code]) && $userCapabilities[$cap->key_code];
                                                $override = $overrides->get($cap->key_code);
                                                $isOverride = $override !== null;
                                                $overrideGranted = $isOverride && $override->granted && !$override->revoked_at;
                                                $overrideDenied = $isOverride && (!$override->granted || $override->revoked_at);
                                            @endphp
                                            <tr>
                                                <td>
                                                    <div class="small">
                                                        <strong>{{ $cap->name }}</strong>
                                                        <br>
                                                        <code class="text-muted">{{ $cap->key_code }}</code>
                                                    </div>
                                                </td>
                                                <td>
                                                    @if($roleCaps['*'] ?? false)
                                                        <span class="badge bg-info">All (*)</span>
                                                    @elseif($roleHas)
                                                        <span class="badge bg-success">Có</span>
                                                    @else
                                                        <span class="badge bg-secondary">Không</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <select class="form-select form-select-sm capability-select" 
                                                            data-capability="{{ $cap->key_code }}"
                                                            data-user-id="{{ $targetUser->id ?? $user->id ?? null }}">
                                                        <option value="inherit" {{ !$isOverride ? 'selected' : '' }}>
                                                            Kế thừa
                                                        </option>
                                                        <option value="grant" {{ $overrideGranted ? 'selected' : '' }}>
                                                            Cấp quyền
                                                        </option>
                                                        <option value="deny" {{ $overrideDenied ? 'selected' : '' }}>
                                                            Từ chối
                                                        </option>
                                                    </select>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</main>

@push('styles')
<style>
.tab-content {
    animation: fadeIn 0.3s ease-in;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.btn.active {
    font-weight: bold;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.tab-content.hidden {
    display: none !important;
}

/* Table improvements */
.table-responsive {
    max-height: 600px;
    overflow-y: auto;
}

.table thead th {
    position: sticky;
    top: 0;
    background-color: #f8f9fa;
    z-index: 10;
    box-shadow: 0 2px 2px -1px rgba(0, 0, 0, 0.1);
}
</style>
@endpush

@push('scripts')
<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

// Capability Tab Management System
const capabilityTabStates = {};

// Initialize tab states from localStorage
document.addEventListener('DOMContentLoaded', function() {
    const savedStates = localStorage.getItem('capabilityTabStates');
    if (savedStates) {
        try {
            const parsed = JSON.parse(savedStates);
            Object.assign(capabilityTabStates, parsed);
        } catch (e) {
            console.error('Error loading tab states:', e);
        }
    }
    
    // Initialize all tabs - first tab visible by default
    @foreach($allCapabilities as $category => $capabilities)
        @if($loop->first)
            capabilityTabStates['{{ $category }}'] = true;
        @else
            if (capabilityTabStates['{{ $category }}'] === undefined) {
                capabilityTabStates['{{ $category }}'] = false;
            }
        @endif
    @endforeach
    
    // Restore tab states
    Object.keys(capabilityTabStates).forEach(category => {
        const tab = document.getElementById(`tab-${category}`);
        const button = document.querySelector(`button[onclick*="toggleCapabilityTab('${category}'"]`);
        if (tab && button) {
            if (capabilityTabStates[category]) {
                tab.style.display = '';
                button.classList.add('active');
            } else {
                tab.style.display = 'none';
                button.classList.remove('active');
            }
        }
    });
    
    // Save states to localStorage
    localStorage.setItem('capabilityTabStates', JSON.stringify(capabilityTabStates));
});

// Toggle capability tab visibility
function toggleCapabilityTab(category, button) {
    const tab = document.getElementById(`tab-${category}`);
    if (!tab) return;
    
    capabilityTabStates[category] = !capabilityTabStates[category];
    
    if (capabilityTabStates[category]) {
        tab.style.display = '';
        button.classList.add('active');
    } else {
        tab.style.display = 'none';
        button.classList.remove('active');
    }
    
    // Save states to localStorage
    localStorage.setItem('capabilityTabStates', JSON.stringify(capabilityTabStates));
}

// Expand all capability tabs
function expandAllCapabilityTabs() {
    @foreach($allCapabilities as $category => $capabilities)
        const tab{{ $loop->index }} = document.getElementById('tab-{{ $category }}');
        const button{{ $loop->index }} = document.querySelector(`button[onclick*="toggleCapabilityTab('{{ $category }}'"]`);
        if (tab{{ $loop->index }}) {
            tab{{ $loop->index }}.style.display = '';
            capabilityTabStates['{{ $category }}'] = true;
        }
        if (button{{ $loop->index }}) {
            button{{ $loop->index }}.classList.add('active');
        }
    @endforeach
    
    localStorage.setItem('capabilityTabStates', JSON.stringify(capabilityTabStates));
}

// Collapse all capability tabs
function collapseAllCapabilityTabs() {
    @foreach($allCapabilities as $category => $capabilities)
        const tab{{ $loop->index }} = document.getElementById('tab-{{ $category }}');
        const button{{ $loop->index }} = document.querySelector(`button[onclick*="toggleCapabilityTab('{{ $category }}'"]`);
        if (tab{{ $loop->index }}) {
            tab{{ $loop->index }}.style.display = 'none';
            capabilityTabStates['{{ $category }}'] = false;
        }
        if (button{{ $loop->index }}) {
            button{{ $loop->index }}.classList.remove('active');
        }
    @endforeach
    
    localStorage.setItem('capabilityTabStates', JSON.stringify(capabilityTabStates));
}

function grantCapability(userId, capabilityKey) {
    return fetch(`/staff/users/${userId}/capabilities/grant`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
        },
        body: JSON.stringify({ capability_key: capabilityKey })
    }).then(res => res.json());
}

function revokeCapability(userId, capabilityKey) {
    return fetch(`/staff/users/${userId}/capabilities/revoke`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
        },
        body: JSON.stringify({ capability_key: capabilityKey })
    }).then(res => res.json());
}

function removeOverride(userId, capabilityKey) {
    return fetch(`/staff/users/${userId}/capabilities/remove-override`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
        },
        body: JSON.stringify({ capability_key: capabilityKey })
    }).then(res => res.json());
}

function bulkUpdateCapabilities(userId, capabilities) {
    return fetch(`/staff/users/${userId}/capabilities/bulk`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
        },
        body: JSON.stringify({ capabilities: capabilities })
    });
}

// Handle individual capability change
document.querySelectorAll('.capability-select').forEach(select => {
    select.addEventListener('change', async function() {
        const capabilityKey = this.dataset.capability;
        const userId = parseInt(this.dataset.userId);
        const value = this.value;

        if (window.Preloader) window.Preloader.show();

        try {
            let result;
            if (value === 'inherit') {
                // Remove override to inherit from role
                result = await removeOverride(userId, capabilityKey);
            } else if (value === 'grant') {
                result = await grantCapability(userId, capabilityKey);
            } else if (value === 'deny') {
                // Deny = revoke (create or update override with granted = false)
                result = await revokeCapability(userId, capabilityKey);
            }

            if (result.success) {
                if (window.Notify) {
                    window.Notify.success(result.message || 'Đã cập nhật quyền thành công!', 'Thành công!');
                }
                // Reload page after 1 second to reflect changes
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                this.value = this.getAttribute('data-original-value') || 'inherit';
                if (window.Notify) {
                    window.Notify.error(result.message || 'Có lỗi xảy ra', 'Lỗi!');
                }
            }
        } catch (error) {
            this.value = this.getAttribute('data-original-value') || 'inherit';
            console.error('Error:', error);
            if (window.Notify) {
                window.Notify.error('Lỗi hệ thống: ' + error.message, 'Lỗi!');
            }
        } finally {
            if (window.Preloader) window.Preloader.hide();
        }
    });

    // Store original value
    select.setAttribute('data-original-value', select.value);
});

// Save all capabilities
function saveAllCapabilities() {
    const userId = parseInt(document.querySelector('.capability-select')?.dataset.userId || 0);
    if (!userId) {
        if (window.Notify) window.Notify.error('Không tìm thấy user ID', 'Lỗi!');
        return;
    }

    const capabilities = [];
    document.querySelectorAll('.capability-select').forEach(select => {
        const capabilityKey = select.dataset.capability;
        const value = select.value;
        
        if (value !== 'inherit') {
            capabilities.push({
                key: capabilityKey,
                granted: value === 'grant'
            });
        }
    });

    // Validate: Must have at least 1 override (not all inherit)
    if (capabilities.length === 0) {
        if (window.Notify) {
            window.Notify.error(
                'Bạn phải chọn ít nhất một quyền để override (cấp quyền hoặc từ chối). Không thể để tất cả đều kế thừa.',
                'Lỗi!'
            );
        }
        return;
    }

    if (window.Preloader) window.Preloader.show();

    bulkUpdateCapabilities(userId, capabilities)
        .then(async response => {
            const result = await response.json();
            
            if (!response.ok) {
                // Handle validation errors (422)
                if (response.status === 422) {
                    let errorMessage = result.message || 'Có lỗi xảy ra khi xác thực dữ liệu.';
                    
                    // Parse validation errors if available
                    if (result.errors) {
                        const errorMessages = [];
                        if (typeof result.errors === 'object') {
                            Object.keys(result.errors).forEach(key => {
                                if (Array.isArray(result.errors[key])) {
                                    errorMessages.push(...result.errors[key]);
                                } else {
                                    errorMessages.push(result.errors[key]);
                                }
                            });
                        }
                        if (errorMessages.length > 0) {
                            errorMessage = errorMessages.join('<br>');
                        }
                    }
                    
                    if (window.Notify) {
                        window.Notify.error(errorMessage, 'Lỗi xác thực!');
                    }
                    console.error('Validation errors:', result.errors || result);
                } else {
                    // Other errors
                    if (window.Notify) {
                        window.Notify.error(result.message || 'Có lỗi xảy ra', 'Lỗi!');
                    }
                }
                return;
            }
            
            if (result.success) {
                if (window.Notify) {
                    window.Notify.success(
                        result.message || `Đã cập nhật ${result.granted || 0} quyền cấp và ${result.revoked || 0} quyền thu hồi!`,
                        'Thành công!'
                    );
                }
                // Reload page after 1 second
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                if (window.Notify) {
                    window.Notify.error(result.message || 'Có lỗi xảy ra', 'Lỗi!');
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            if (window.Notify) {
                window.Notify.error('Lỗi hệ thống: ' + (error.message || 'Không thể kết nối đến server'), 'Lỗi!');
            }
        })
        .finally(() => {
            if (window.Preloader) window.Preloader.hide();
        });
}
</script>
@endpush
@endsection

