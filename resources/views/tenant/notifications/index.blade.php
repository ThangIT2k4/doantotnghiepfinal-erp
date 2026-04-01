@extends('layouts.app')

@section('title', 'Trung tâm thông báo')

@include('tenant.components.theme-config')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/user/notifications.css') }}?v={{ time() }}">
<link rel="stylesheet" href="{{ asset('assets/css/tenant/form-blue-theme.css') }}?v={{ time() }}">
<style>
/* Notifications Container with Blue Theme */
.notifications-list-blue {
    background: white;
    border-radius: 16px;
    padding: 1.5rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    border: 1px solid var(--blue-border);
}

/* Notification Cards with Blue Theme */
.notification-card-blue {
    background: white;
    border: 1px solid var(--blue-border);
    border-radius: 16px;
    padding: 0;
    margin-bottom: 1.5rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.notification-card-blue:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 30px rgba(39, 102, 236, 0.15);
    border-color: var(--blue-light);
}

.notification-card-blue.unread {
    border-left: 4px solid var(--blue-primary);
    background: linear-gradient(135deg, var(--blue-bg-light) 0%, #ffffff 100%);
}

.notification-card-blue.important {
    border-left: 4px solid #ef4444;
    background: linear-gradient(135deg, #fef2f2 0%, #ffffff 100%);
}

.notification-card-blue.read {
    opacity: 0.85;
    background: #f9fafb;
}

.notification-icon-blue {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    margin-right: 16px;
    flex-shrink: 0;
    color: white;
}

.notification-icon-blue.payment {
    background: linear-gradient(135deg, #ef4444, #f87171);
}

.notification-icon-blue.contract {
    background: linear-gradient(135deg, #f59e0b, #fbbf24);
}

.notification-icon-blue.appointment {
    background: linear-gradient(135deg, #10b981, #34d399);
}

.notification-icon-blue.review {
    background: linear-gradient(135deg, #8b5cf6, #a78bfa);
}

.notification-icon-blue.maintenance {
    background: linear-gradient(135deg, #06b6d4, #22d3ee);
}

.notification-icon-blue.system {
    background: linear-gradient(135deg, #6b7280, #9ca3af);
}

.notification-content-blue {
    flex: 1;
    min-width: 0;
    padding: 1.5rem;
}

.notification-header-blue {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 8px;
}

.notification-title-blue {
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--blue-primary);
    margin: 0;
    line-height: 1.4;
}

.notification-time-blue {
    font-size: 0.875rem;
    color: #6b7280;
    white-space: nowrap;
    margin-left: 12px;
}

.notification-message-blue {
    color: #4b5563;
    line-height: 1.5;
    margin-bottom: 12px;
    font-size: 0.95rem;
}


.notification-info-blue {
    padding-left: 16px;
}

.notification-meta-blue {
    display: flex;
    gap: 12px;
    margin-top: 12px;
    flex-wrap: wrap;
}

.meta-item-blue {
    display: flex;
    align-items: center;
    font-size: 0.8rem;
    color: #6b7280;
    background: var(--blue-bg-light);
    padding: 4px 8px;
    border-radius: 6px;
}

.meta-item-blue.important {
    background: #fef2f2;
    color: #dc2626;
    font-weight: 500;
}

.notification-actions-blue {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.notification-actions-blue .btn {
    border-radius: 8px;
    font-weight: 500;
    font-size: 0.875rem;
    padding: 8px 12px;
    transition: all 0.2s ease;
    text-align: center;
}

.notification-actions-blue .btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

.notification-status-badge-blue {
    position: absolute;
    top: 16px;
    right: 16px;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 4px;
}

.notification-status-badge-blue.unread {
    background: #dbeafe;
    color: #1d4ed8;
}

.notification-status-badge-blue.read {
    background: #f3f4f6;
    color: #6b7280;
}

.btn-mark-read {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    border: 2px solid #e5e7eb;
    background: #fff;
    color: #6b7280;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
    cursor: pointer;
}

.btn-mark-read:hover {
    border-color: #3b82f6;
    color: #3b82f6;
    background: #eff6ff;
}

.btn-mark-read:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* Enhanced stats cards */
.stat-card {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 20px;
    text-align: center;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #3b82f6, #1d4ed8);
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.stat-card.payment::before {
    background: linear-gradient(90deg, #ef4444, #dc2626);
}

.stat-card.contract::before {
    background: linear-gradient(90deg, #f59e0b, #d97706);
}

.stat-card.appointment::before {
    background: linear-gradient(90deg, #10b981, #059669);
}

.stat-card.review::before {
    background: linear-gradient(90deg, #8b5cf6, #7c3aed);
}

.stat-card.maintenance::before {
    background: linear-gradient(90deg, #06b6d4, #0891b2);
}

.stat-card.total::before {
    background: linear-gradient(90deg, #6b7280, #4b5563);
}

.stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 12px;
    font-size: 20px;
    color: white;
}

.stat-card.payment .stat-icon {
    background: linear-gradient(135deg, #ef4444, #f87171);
}

.stat-card.contract .stat-icon {
    background: linear-gradient(135deg, #f59e0b, #fbbf24);
}

.stat-card.appointment .stat-icon {
    background: linear-gradient(135deg, #10b981, #34d399);
}

.stat-card.review .stat-icon {
    background: linear-gradient(135deg, #8b5cf6, #a78bfa);
}

.stat-card.maintenance .stat-icon {
    background: linear-gradient(135deg, #06b6d4, #22d3ee);
}

.stat-card.total .stat-icon {
    background: linear-gradient(135deg, #6b7280, #9ca3af);
}

.stat-content h3 {
    font-size: 2rem;
    font-weight: 700;
    color: #1f2937;
    margin: 0 0 4px 0;
}

.stat-content p {
    color: #6b7280;
    font-weight: 500;
    margin: 0;
    font-size: 0.9rem;
}

/* Enhanced filter section */
.filter-section {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 24px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.search-box {
    position: relative;
}

.search-box i {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #9ca3af;
    z-index: 2;
}

.search-box input {
    padding-left: 40px;
    border-radius: 8px;
    border: 2px solid #e5e7eb;
    transition: all 0.2s ease;
}

.search-box input:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-select {
    border-radius: 8px;
    border: 2px solid #e5e7eb;
    transition: all 0.2s ease;
}

.form-select:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.filter-tabs {
    display: flex;
    gap: 4px;
    background: #f3f4f6;
    padding: 4px;
    border-radius: 8px;
}

.filter-tab {
    padding: 8px 16px;
    border: none;
    background: transparent;
    color: #6b7280;
    font-weight: 500;
    border-radius: 6px;
    transition: all 0.2s ease;
    cursor: pointer;
}

.filter-tab.active,
.filter-tab:hover {
    background: #fff;
    color: #3b82f6;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

/* Empty state with Blue Theme */
.empty-state-blue {
    text-align: center;
    padding: 60px 20px;
    background: white;
    border-radius: 16px;
    border: 1px solid var(--blue-border);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

.empty-icon-blue {
    font-size: 4rem;
    color: var(--blue-primary);
    margin-bottom: 16px;
    opacity: 0.5;
}

.empty-state-blue h3 {
    color: var(--blue-primary);
    margin-bottom: 8px;
    font-weight: 600;
}

.empty-state-blue p {
    color: #6b7280;
    margin: 0;
}

/* Type Filter with Blue Theme */
.type-filter-blue {
    width: 100%;
}

.type-select-blue {
    border-radius: 12px;
    border: 2px solid var(--blue-border);
    padding: 0.75rem 1rem;
    font-size: 1rem;
    transition: all 0.3s ease;
    background: var(--blue-bg-light);
}

.type-select-blue:focus {
    border-color: var(--blue-primary);
    background: white;
    box-shadow: 0 0 0 0.2rem rgba(39, 102, 236, 0.25);
    outline: none;
}

/* HTMX Loading Indicator */
.htmx-indicator-blue {
    text-align: center;
    padding: 2rem;
}

.htmx-indicator-blue .spinner-border {
    color: var(--blue-primary);
}

/* Notification Status Colors */
:root {
    --status-notification-all: #2766ec;
    --status-notification-all-light: #dbeafe;
    --status-notification-all-border: #2766ec;
    --status-notification-all-gradient: linear-gradient(135deg, #1E4FC8 0%, #2766ec 100%);

    --status-notification-unread: #3b82f6;
    --status-notification-unread-light: #dbeafe;
    --status-notification-unread-border: #3b82f6;
    --status-notification-unread-gradient: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%);

    --status-notification-read: #10b981;
    --status-notification-read-light: #d1fae5;
    --status-notification-read-border: #10b981;
    --status-notification-read-gradient: linear-gradient(135deg, #059669 0%, #10b981 100%);

    --status-notification-important: #ef4444;
    --status-notification-important-light: #fee2e2;
    --status-notification-important-border: #ef4444;
    --status-notification-important-gradient: linear-gradient(135deg, #dc2626 0%, #ef4444 100%);
}

/* Status-specific colors for notification filter tabs */
.filter-tab-blue[data-status="all"]:hover:not(.active) {
    background: var(--status-notification-all-light);
    border-color: var(--status-notification-all);
    color: var(--status-notification-all);
}

.filter-tab-blue[data-status="all"].active {
    background: var(--status-notification-all-gradient);
    border-color: var(--status-notification-all-border);
    box-shadow: 0 4px 15px rgba(39, 102, 236, 0.3);
    color: white;
}

.filter-tab-blue[data-status="unread"]:hover:not(.active) {
    background: var(--status-notification-unread-light);
    border-color: var(--status-notification-unread);
    color: var(--status-notification-unread);
}

.filter-tab-blue[data-status="unread"].active {
    background: var(--status-notification-unread-gradient);
    border-color: var(--status-notification-unread-border);
    box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
    color: white;
}

.filter-tab-blue[data-status="read"]:hover:not(.active) {
    background: var(--status-notification-read-light);
    border-color: var(--status-notification-read);
    color: var(--status-notification-read);
}

.filter-tab-blue[data-status="read"].active {
    background: var(--status-notification-read-gradient);
    border-color: var(--status-notification-read-border);
    box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
    color: white;
}

.filter-tab-blue[data-status="important"]:hover:not(.active) {
    background: var(--status-notification-important-light);
    border-color: var(--status-notification-important);
    color: var(--status-notification-important);
}

.filter-tab-blue[data-status="important"].active {
    background: var(--status-notification-important-gradient);
    border-color: var(--status-notification-important-border);
    box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);
    color: white;
}

/* Status badge */
.notification-status-badge {
    position: absolute;
    top: 16px;
    right: 16px;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 4px;
}

.notification-status-badge.unread {
    background: #dbeafe;
    color: #1d4ed8;
}

.notification-status-badge.read {
    background: #f3f4f6;
    color: #6b7280;
}

/* Notification info layout */
.notification-info {
    padding-left: 16px;
}

.notification-meta {
    display: flex;
    gap: 16px;
    margin-top: 12px;
    flex-wrap: wrap;
}

.meta-item {
    display: flex;
    align-items: center;
    font-size: 0.8rem;
    color: #6b7280;
    background: #f3f4f6;
    padding: 4px 8px;
    border-radius: 6px;
}

.meta-item.important {
    background: #fef2f2;
    color: #dc2626;
    font-weight: 500;
}

/* Enhanced notification actions */
.notification-actions {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.notification-actions .btn {
    border-radius: 8px;
    font-weight: 500;
    font-size: 0.8rem;
    padding: 8px 12px;
    transition: all 0.2s ease;
    text-align: center;
}

.notification-actions .btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

/* Enhanced header actions */
.header-actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.header-actions .btn {
    border-radius: 8px;
    font-weight: 500;
    padding: 8px 16px;
    transition: all 0.2s ease;
}

.header-actions .btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

/* Enhanced page header */
.notifications-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 40px 0;
    margin-bottom: 32px;
    border-radius: 16px;
    position: relative;
    overflow: hidden;
}

.notifications-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="50" cy="50" r="1" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
    opacity: 0.3;
}

.notifications-header .header-content {
    position: relative;
    z-index: 2;
}

.notifications-header .header-icon {
    width: 64px;
    height: 64px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    margin-right: 20px;
    backdrop-filter: blur(10px);
}

.notifications-header .page-title {
    font-size: 2.5rem;
    font-weight: 700;
    margin: 0 0 8px 0;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.notifications-header .page-subtitle {
    font-size: 1.1rem;
    opacity: 0.9;
    margin: 0;
}

/* Enhanced stats section */
.stats-section {
    margin-bottom: 32px;
}

/* Enhanced filter section */
.filter-section {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 16px;
    padding: 24px;
    margin-bottom: 32px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
}

/* Enhanced notifications list */
.notifications-list {
    background: #fff;
    border-radius: 16px;
    padding: 24px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
}

/* Responsive improvements */
@media (max-width: 768px) {
    .notification-card {
        padding: 16px;
    }
    
    .notification-content .row {
        flex-direction: column;
    }
    
    .notification-content .col-md-1,
    .notification-content .col-md-8,
    .notification-content .col-md-3 {
        width: 100%;
        margin-bottom: 16px;
    }
    
    .notification-info {
        padding-left: 0;
    }
    
    .notification-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .notification-time {
        margin-left: 0;
        margin-top: 4px;
    }
    
    .notification-actions {
        margin-top: 12px;
    }
    
    .stat-card {
        padding: 16px;
    }
    
    .stat-content h3 {
        font-size: 1.5rem;
    }
    
    .notifications-header {
        padding: 24px 0;
        margin-bottom: 24px;
    }
    
    .notifications-header .page-title {
        font-size: 2rem;
    }
    
    .notifications-header .header-icon {
        width: 48px;
        height: 48px;
        font-size: 20px;
        margin-right: 16px;
    }
    
    .header-actions {
        flex-direction: column;
        width: 100%;
    }
    
    .header-actions .btn {
        width: 100%;
        margin-bottom: 8px;
    }
}

/* Notification detail modal styles */
.notification-detail {
    padding: 20px;
}

.detail-header {
    display: flex;
    align-items: flex-start;
    margin-bottom: 24px;
    padding-bottom: 16px;
    border-bottom: 1px solid #e5e7eb;
}

.detail-icon {
    width: 64px;
    height: 64px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: white;
    margin-right: 20px;
    flex-shrink: 0;
}

.detail-icon.payment {
    background: linear-gradient(135deg, #ef4444, #f87171);
}

.detail-icon.contract {
    background: linear-gradient(135deg, #f59e0b, #fbbf24);
}

.detail-icon.appointment {
    background: linear-gradient(135deg, #10b981, #34d399);
}

.detail-icon.review {
    background: linear-gradient(135deg, #8b5cf6, #a78bfa);
}

.detail-icon.maintenance {
    background: linear-gradient(135deg, #06b6d4, #22d3ee);
}

.detail-icon.system {
    background: linear-gradient(135deg, #6b7280, #9ca3af);
}

.detail-info {
    flex: 1;
}

.detail-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0 0 12px 0;
    line-height: 1.3;
}

.detail-meta {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.detail-meta .meta-item {
    display: flex;
    align-items: center;
    font-size: 0.875rem;
    color: #6b7280;
    background: #f3f4f6;
    padding: 6px 12px;
    border-radius: 8px;
    font-weight: 500;
}

.detail-meta .meta-item.unread {
    background: #dbeafe;
    color: #1d4ed8;
}

.detail-meta .meta-item.read {
    background: #f3f4f6;
    color: #6b7280;
}

.detail-meta .meta-item.important {
    background: #fef2f2;
    color: #dc2626;
    font-weight: 600;
}

.detail-content {
    margin-bottom: 24px;
}

.detail-content h6 {
    color: #374151;
    font-weight: 600;
    margin-bottom: 12px;
}

.content-text {
    background: #f9fafb;
    padding: 16px;
    border-radius: 8px;
    border-left: 4px solid #3b82f6;
    line-height: 1.6;
    color: #4b5563;
    font-size: 0.95rem;
}


.detail-actions {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.detail-actions .btn {
    border-radius: 8px;
    font-weight: 500;
    padding: 8px 16px;
    transition: all 0.2s ease;
}

.detail-actions .btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

@media (max-width: 768px) {
    .detail-header {
        flex-direction: column;
        align-items: center;
        text-align: center;
    }
    
    .detail-icon {
        margin-right: 0;
        margin-bottom: 16px;
    }
    
    .detail-meta {
        justify-content: center;
    }
    
    .detail-actions {
        justify-content: center;
    }
}
</style>
@endpush

@push('scripts')
<script src="{{ asset('assets/js/user/notifications.js') }}?v={{ time() }}"></script>
@endpush

@section('content')
<div class="page-container-blue">
    <div class="container">
        <!-- Page Header -->
        @include('tenant.components.page-header', [
            'title' => 'Trung tâm thông báo',
            'subtitle' => 'Theo dõi tất cả thông báo và cập nhật quan trọng',
            'icon' => 'fas fa-bell',
            'actions' => [
                ['label' => 'Về Dashboard', 'url' => route('tenant.dashboard'), 'icon' => 'fas fa-arrow-left', 'variant' => 'outline-secondary'],
                ['label' => 'Cài đặt', 'onclick' => 'openNotificationSettings()', 'icon' => 'fas fa-cog', 'variant' => 'outline-primary', 'type' => 'button'],
                ['label' => 'Đánh dấu đã đọc', 'onclick' => 'markAllAsRead()', 'icon' => 'fas fa-check-double', 'variant' => 'outline-primary', 'type' => 'button'],
            ]
        ])

        <!-- Stats Cards -->
        <div id="stats-cards-container">
            @php
                $notificationStats = [
                    [
                        'icon' => 'fas fa-bell',
                        'value' => $stats['total'] ?? 0,
                        'label' => 'Tất cả',
                        'active' => request('status', 'all') === 'all',
                        'data-filter' => 'all',
                        'statusClass' => 'total',
                        'hx-get' => route('tenant.notifications', ['type' => request('type'), 'status' => 'all', 'search' => request('search')]),
                        'hx-target' => '#notifications-list-container',
                        'hx-swap' => 'innerHTML',
                        'hx-push-url' => 'true',
                        'hx-indicator' => '#htmx-loading',
                        'title' => 'Click để xem tất cả thông báo'
                    ],
                    [
                        'icon' => 'fas fa-check-circle',
                        'value' => $stats['read'] ?? 0,
                        'label' => 'Đã đọc',
                        'active' => request('status') === 'read',
                        'data-filter' => 'read',
                        'statusClass' => 'read',
                        'hx-get' => route('tenant.notifications', ['type' => request('type'), 'status' => 'read', 'search' => request('search')]),
                        'hx-target' => '#notifications-list-container',
                        'hx-swap' => 'innerHTML',
                        'hx-push-url' => 'true',
                        'hx-indicator' => '#htmx-loading',
                        'title' => 'Click để xem thông báo đã đọc'
                    ],
                    [
                        'icon' => 'fas fa-circle',
                        'value' => $stats['unread'] ?? 0,
                        'label' => 'Chưa đọc',
                        'active' => request('status') === 'unread',
                        'data-filter' => 'unread',
                        'statusClass' => 'unread',
                        'hx-get' => route('tenant.notifications', ['type' => request('type'), 'status' => 'unread', 'search' => request('search')]),
                        'hx-target' => '#notifications-list-container',
                        'hx-swap' => 'innerHTML',
                        'hx-push-url' => 'true',
                        'hx-indicator' => '#htmx-loading',
                        'title' => 'Click để xem thông báo chưa đọc'
                    ],
                    [
                        'icon' => 'fas fa-exclamation-triangle',
                        'value' => $stats['important'] ?? 0,
                        'label' => 'Quan trọng',
                        'active' => request('status') === 'important',
                        'data-filter' => 'important',
                        'statusClass' => 'important',
                        'hx-get' => route('tenant.notifications', ['type' => request('type'), 'status' => 'important', 'search' => request('search')]),
                        'hx-target' => '#notifications-list-container',
                        'hx-swap' => 'innerHTML',
                        'hx-push-url' => 'true',
                        'hx-indicator' => '#htmx-loading',
                        'title' => 'Click để xem thông báo quan trọng'
                    ]
                ];
            @endphp
            @include('tenant.components.stats-cards', [
                'stats' => $notificationStats,
                'columns' => 4,
                'class' => 'mb-4'
            ])
        </div>

        <!-- Filter and Search -->
        <div id="filter-section-container">
        @php
            $filterTabs = [
                [
                    'label' => 'Tất cả',
                    'value' => 'all',
                    'active' => request('status', 'all') == 'all',
                    'hx-get' => route('tenant.notifications', ['type' => request('type'), 'status' => 'all', 'search' => request('search')]),
                    'hx-target' => '#notifications-list-container',
                    'hx-swap' => 'innerHTML',
                    'hx-push-url' => 'true',
                    'hx-indicator' => '#htmx-loading',
                    'hx-trigger' => 'click',
                    'icon' => 'fas fa-folder'
                ],
                [
                    'label' => 'Chưa đọc',
                    'value' => 'unread',
                    'active' => request('status') == 'unread',
                    'hx-get' => route('tenant.notifications', ['type' => request('type'), 'status' => 'unread', 'search' => request('search')]),
                    'hx-target' => '#notifications-list-container',
                    'hx-swap' => 'innerHTML',
                    'hx-push-url' => 'true',
                    'hx-indicator' => '#htmx-loading',
                    'hx-trigger' => 'click',
                    'icon' => 'fas fa-circle'
                ],
                [
                    'label' => 'Đã đọc',
                    'value' => 'read',
                    'active' => request('status') == 'read',
                    'hx-get' => route('tenant.notifications', ['type' => request('type'), 'status' => 'read', 'search' => request('search')]),
                    'hx-target' => '#notifications-list-container',
                    'hx-swap' => 'innerHTML',
                    'hx-push-url' => 'true',
                    'hx-indicator' => '#htmx-loading',
                    'hx-trigger' => 'click',
                    'icon' => 'fas fa-check-circle'
                ],
                [
                    'label' => 'Quan trọng',
                    'value' => 'important',
                    'active' => request('status') == 'important',
                    'hx-get' => route('tenant.notifications', ['type' => request('type'), 'status' => 'important', 'search' => request('search')]),
                    'hx-target' => '#notifications-list-container',
                    'hx-swap' => 'innerHTML',
                    'hx-push-url' => 'true',
                    'hx-indicator' => '#htmx-loading',
                    'hx-trigger' => 'click',
                    'icon' => 'fas fa-exclamation-triangle'
                ]
            ];
            
            $additionalFields = '<div class="type-filter-blue">
                <select class="form-select type-select-blue" name="type" id="typeFilter" 
                        hx-get="' . route('tenant.notifications') . '"
                        hx-target="#notifications-list-container"
                        hx-swap="innerHTML"
                        hx-push-url="true"
                        hx-indicator="#htmx-loading"
                        hx-trigger="change"
                        hx-include="[name=\'search\'], [name=\'status\']">
                    <option value="">Tất cả loại</option>
                    <option value="payment" ' . (request('type') === 'payment' ? 'selected' : '') . '>💳 Thanh toán</option>
                    <option value="contract" ' . (request('type') === 'contract' ? 'selected' : '') . '>📄 Hợp đồng</option>
                    <option value="appointment" ' . (request('type') === 'appointment' ? 'selected' : '') . '>📅 Lịch hẹn</option>
                    <option value="review" ' . (request('type') === 'review' ? 'selected' : '') . '>⭐ Đánh giá</option>
                    <option value="maintenance" ' . (request('type') === 'maintenance' ? 'selected' : '') . '>🔧 Sửa chữa</option>
                    <option value="system" ' . (request('type') === 'system' ? 'selected' : '') . '>⚙️ Hệ thống</option>
                </select>
            </div>';
        @endphp
        @include('tenant.components.filter-section', [
            'searchPlaceholder' => 'Tìm kiếm thông báo...',
            'searchValue' => request('search'),
            'filters' => $filterTabs,
            'formId' => 'filterForm',
            'searchInputId' => 'searchInput',
            'hxGet' => route('tenant.notifications'),
            'hxTarget' => '#notifications-list-container',
            'hxSwap' => 'innerHTML',
            'hxPushUrl' => 'true',
            'hxIndicator' => '#htmx-loading',
            'hxTrigger' => 'input delay:500ms from:#searchInput, change from:#typeFilter',
            'additionalFields' => $additionalFields
        ])
        </div>

        <!-- HTMX Loading Indicator -->
        <div id="htmx-loading" class="htmx-indicator-blue" style="display: none;">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Đang tải...</span>
            </div>
            <p class="mt-2 text-muted">Đang tải dữ liệu...</p>
        </div>

        <!-- Notifications List -->
        <div class="notifications-list-blue" id="notifications-list-container">
            @include('tenant.notifications.partials.notifications-list', ['notifications' => $notifications])
        </div>
    </div>
</div>

<!-- Notification Detail Modal -->
<div class="modal fade" id="notificationDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Chi tiết thông báo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="notification-detail-content" id="notificationDetailContent">
                    <!-- Notification details will be loaded here -->
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Đang tải thông tin thông báo...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                <button type="button" class="btn btn-success" onclick="markCurrentNotificationAsRead()" id="markReadBtn">
                    <i class="fas fa-check me-1"></i>Đánh dấu đã đọc
                </button>
                <button type="button" class="btn btn-primary" onclick="replyToNotification()" id="replyBtn">
                    <i class="fas fa-reply me-1"></i>Phản hồi
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Notification Settings Modal -->
<div class="modal fade" id="settingsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                <h5 class="modal-title">
                    <i class="fas fa-cog me-2"></i>Cài đặt thông báo
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="settingsModalBody">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Đang tải...</span>
                    </div>
                    <p class="mt-2 text-muted">Đang tải cài đặt...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-primary" onclick="saveNotificationSettings()" id="saveSettingsBtn">
                    <i class="fas fa-save me-1"></i>Lưu cài đặt
                </button>
            </div>
        </div>
    </div>
</div>
@endsection
