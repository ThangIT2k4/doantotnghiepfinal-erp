@extends('layouts.staff_dashboard')

@section('title', 'System Logs - Diagnostic')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-bug me-2"></i>System Logs - Diagnostic Tool
                    </h5>
                    <small>For debugging production issues without terminal access</small>
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
                                       max="1000">
                            </div>
                            <div class="col-md-6">
                                <label for="filter" class="form-label">Filter keyword</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="filter" 
                                       name="filter" 
                                       value="{{ $filter }}" 
                                       placeholder="e.g., createFromPreview, ERROR, generatePayslips">
                            </div>
                        </div>
                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-sync me-2"></i>Refresh Logs
                            </button>
                            <a href="{{ route('staff.diagnostic.payslip-errors') }}" 
                               class="btn btn-warning"
                               target="_blank">
                                <i class="fas fa-exclamation-triangle me-2"></i>Payslip Errors Only (JSON)
                            </a>
                        </div>
                    </form>
                    
                    <!-- Quick Filters -->
                    <div class="mb-3">
                        <strong>Quick filters:</strong>
                        <div class="btn-group btn-group-sm ms-2" role="group">
                            <a href="?filter=ERROR&lines={{ $lines }}" class="btn btn-outline-danger">ERROR</a>
                            <a href="?filter=createFromPreview&lines={{ $lines }}" class="btn btn-outline-warning">createFromPreview</a>
                            <a href="?filter=generatePayslips&lines={{ $lines }}" class="btn btn-outline-warning">generatePayslips</a>
                            <a href="?filter=calculateMonthlyDeduction&lines={{ $lines }}" class="btn btn-outline-info">calculateMonthlyDeduction</a>
                            <a href="?filter=No%20organization&lines={{ $lines }}" class="btn btn-outline-secondary">No organization</a>
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
                        <pre style="margin: 0; color: #00ff00; font-family: 'Courier New', monospace; font-size: 12px; white-space: pre-wrap;">{{ $logContent }}</pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    pre {
        max-height: 70vh;
        overflow-y: auto;
    }
</style>
@endsection

