@extends('layouts.staff_dashboard')

@section('title', 'Notification Logs - Diagnostic')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-bell me-2"></i>Notification Logs - Diagnostic Tool
                    </h5>
                    <small>For debugging notification issues (tenant not receiving notifications)</small>
                </div>
                
                <div class="card-body">
                    <!-- Filter Form -->
                    <form method="GET" class="mb-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="lines" class="form-label">Number of lines</label>
                                <input type="number" 
                                       class="form-control" 
                                       id="lines" 
                                       name="lines" 
                                       value="{{ $lines }}" 
                                       min="10" 
                                       max="2000">
                            </div>
                            <div class="col-md-6">
                                <label for="filter" class="form-label">Filter keyword</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="filter" 
                                       name="filter" 
                                       value="{{ $filter }}" 
                                       placeholder="e.g., NotificationFromAuditService, tenant, audit_log_id">
                            </div>
                        </div>
                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-sync me-2"></i>Refresh Logs
                            </button>
                            <a href="{{ route('staff.diagnostic.logs') }}" 
                               class="btn btn-secondary">
                                <i class="fas fa-list me-2"></i>View All Logs
                            </a>
                        </div>
                    </form>
                    
                    <!-- Quick Filters -->
                    <div class="mb-3">
                        <strong>Quick filters:</strong>
                        <div class="btn-group btn-group-sm ms-2" role="group">
                            <a href="?filter=NotificationFromAuditService&lines={{ $lines }}" class="btn btn-outline-primary">NotificationFromAuditService</a>
                            <a href="?filter=tenant&lines={{ $lines }}" class="btn btn-outline-info">tenant</a>
                            <a href="?filter=audit_log_id&lines={{ $lines }}" class="btn btn-outline-info">audit_log_id</a>
                            <a href="?filter=shouldUserReceiveNotification&lines={{ $lines }}" class="btn btn-outline-warning">shouldUserReceiveNotification</a>
                            <a href="?filter=isEntityOwnedByTenant&lines={{ $lines }}" class="btn btn-outline-warning">isEntityOwnedByTenant</a>
                            <a href="?filter=getTenantRecipients&lines={{ $lines }}" class="btn btn-outline-success">getTenantRecipients</a>
                            <a href="?filter=ERROR&lines={{ $lines }}" class="btn btn-outline-danger">ERROR</a>
                            <a href="?filter=WARNING&lines={{ $lines }}" class="btn btn-outline-warning">WARNING</a>
                            <a href="?lines={{ $lines }}" class="btn btn-outline-secondary">Clear filter</a>
                        </div>
                    </div>
                    
                    <!-- Log Info -->
                    <div class="alert alert-info">
                        <strong>Log file:</strong> {{ $logFile }}<br>
                        <strong>Showing:</strong> Last {{ $lines }} lines
                        @if($filter)
                        <br><strong>Filter:</strong> "{{ $filter }}"
                        @endif
                    </div>
                    
                    <!-- Log Content -->
                    <div class="bg-dark text-light p-3" style="border-radius: 5px; overflow-x: auto;">
                        <pre id="log-content" style="margin: 0; color: #00ff00; font-family: 'Courier New', monospace; font-size: 12px; white-space: pre-wrap;">{{ $logContent }}</pre>
                    </div>
                    
                    <!-- Actions -->
                    <div class="mt-3">
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="copyLogs()">
                            <i class="fas fa-copy me-2"></i>Copy Logs
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="downloadLogs()">
                            <i class="fas fa-download me-2"></i>Download Logs
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="scrollToTop()">
                            <i class="fas fa-arrow-up me-2"></i>Scroll to Top
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="scrollToBottom()">
                            <i class="fas fa-arrow-down me-2"></i>Scroll to Bottom
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    #log-content {
        max-height: 70vh;
        overflow-y: auto;
    }
    
    /* Highlight important keywords */
    #log-content {
        line-height: 1.6;
    }
</style>

<script>
function copyLogs() {
    const logContent = document.getElementById('log-content').textContent;
    navigator.clipboard.writeText(logContent).then(() => {
        alert('Logs copied to clipboard!');
    }).catch(err => {
        console.error('Failed to copy:', err);
        alert('Failed to copy logs');
    });
}

function downloadLogs() {
    const logContent = document.getElementById('log-content').textContent;
    const blob = new Blob([logContent], { type: 'text/plain' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'notification-logs-' + new Date().toISOString().replace(/[:.]/g, '-') + '.txt';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);
}

function scrollToTop() {
    const logContent = document.getElementById('log-content');
    logContent.scrollTop = 0;
}

function scrollToBottom() {
    const logContent = document.getElementById('log-content');
    logContent.scrollTop = logContent.scrollHeight;
}
</script>
@endsection

