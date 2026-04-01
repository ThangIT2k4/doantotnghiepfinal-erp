{{-- Theme Configuration Component - Blue Theme #2766ec --}}
@push('styles')
<style>
/* Blue Theme Configuration - Standard Colors */
:root {
    --blue-primary: #2766ec;
    --blue-light: #4A85F0;
    --blue-lighter: #6DA3F4;
    --blue-dark: #1E4FC8;
    --blue-darker: #1638A4;
    --blue-gradient: linear-gradient(135deg, #1E4FC8 0%, #2766ec 50%, #4A85F0 100%);
    --blue-gradient-light: linear-gradient(135deg, #2766ec 0%, #4A85F0 100%);
    --blue-bg-light: #F0F4FF;
    --blue-border: #D6E4FF;
    --blue-text-light: #e0e7ff;
    --blue-shadow: rgba(39, 102, 236, 0.3);
    --blue-shadow-light: rgba(39, 102, 236, 0.15);
    --blue-shadow-dark: rgba(39, 102, 236, 0.4);

    /* Status Colors */
    --status-active: #28a745;
    --status-active-light: #d4edda;
    --status-active-border: #28a745;
    --status-active-gradient: linear-gradient(135deg, #20c997 0%, #28a745 100%);
    
    --status-expiring: #ff9800;
    --status-expiring-light: #fff3cd;
    --status-expiring-border: #ff9800;
    --status-expiring-gradient: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
    
    --status-expired: #dc3545;
    --status-expired-light: #f8d7da;
    --status-expired-border: #dc3545;
    --status-expired-gradient: linear-gradient(135deg, #e74c3c 0%, #dc3545 100%);
    
    --status-all: #2766ec;
    --status-all-light: #F0F4FF;
    --status-all-border: #2766ec;
    --status-all-gradient: linear-gradient(135deg, #1E4FC8 0%, #2766ec 50%, #4A85F0 100%);
    
    /* Ticket Status Colors */
    --status-ticket-open: #3b82f6;
    --status-ticket-open-light: #dbeafe;
    --status-ticket-open-border: #3b82f6;
    --status-ticket-open-gradient: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%);
    
    --status-ticket-in_progress: #f59e0b;
    --status-ticket-in_progress-light: #fef3c7;
    --status-ticket-in_progress-border: #f59e0b;
    --status-ticket-in_progress-gradient: linear-gradient(135deg, #d97706 0%, #f59e0b 100%);
    
    --status-ticket-resolved: #10b981;
    --status-ticket-resolved-light: #d1fae5;
    --status-ticket-resolved-border: #10b981;
    --status-ticket-resolved-gradient: linear-gradient(135deg, #059669 0%, #10b981 100%);
    
    --status-ticket-closed: #6b7280;
    --status-ticket-closed-light: #f3f4f6;
    --status-ticket-closed-border: #6b7280;
    --status-ticket-closed-gradient: linear-gradient(135deg, #4b5563 0%, #6b7280 100%);
    
    --status-ticket-cancelled: #ef4444;
    --status-ticket-cancelled-light: #fee2e2;
    --status-ticket-cancelled-border: #ef4444;
    --status-ticket-cancelled-gradient: linear-gradient(135deg, #dc2626 0%, #ef4444 100%);
}

/* Page Container with Blue Background */
.page-container-blue {
    background: linear-gradient(to bottom, #F0F4FF 0%, #ffffff 100%);
    min-height: 100vh;
    padding: 2rem 0;
}
</style>
@endpush

