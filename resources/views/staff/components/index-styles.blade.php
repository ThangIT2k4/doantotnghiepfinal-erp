<style>
/* Statistics Cards */
.stat-card {
    border: 2px solid transparent;
    transition: all 0.3s ease;
}


/* Improve spacing and consistent sizing for stats cards */
/* Keep Bootstrap .row behavior so columns fill the width correctly */
.statistics-cards-container {
    /* use Bootstrap gutters; do not override display */
}

.statistics-cards-container > [class*=col-] {
    padding-left: 0.5rem;
    padding-right: 0.5rem;
    margin-bottom: 1rem;
}

.stat-card {
    border-radius: 12px;
    overflow: hidden;
}

.stat-card .card-body {
    padding: 1.25rem 1rem;
    min-height: 120px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
}

.stat-card.border-primary,
.stat-card.border-warning,
.stat-card.border-success,
.stat-card.border-danger,
.stat-card.border-info,
.stat-card.border-secondary {
    border-color: currentColor !important;
}

/* Filter Form */
.filter-section .card {
    border: 1px solid #dee2e6;
}

.filter-section .form-label {
    font-weight: 500;
    color: #495057;
    margin-bottom: 0.5rem;
}

/* Table Styles */
.index-table th {
    background-color: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
    font-weight: 600;
    color: #495057;
    white-space: nowrap;
}

.index-table td {
    vertical-align: middle;
    border-bottom: 1px solid #dee2e6;
}

.index-table tbody tr:hover {
    background-color: #f8f9fa;
}

.index-table th a {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    text-decoration: none;
    color: inherit;
}

.index-table th a:hover {
    color: #0d6efd !important;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .stat-card {
        margin-bottom: 1rem;
    }
    
    .statistics-cards-container > [class*=col-] {
        padding-left: 0.75rem;
        padding-right: 0.75rem;
    }
    
    .index-table {
        font-size: 0.875rem;
    }
    
    .btn-group {
        flex-direction: column;
    }
    
    .btn-group .btn {
        border-radius: 0.375rem !important;
        margin-bottom: 0.25rem;
    }
}
</style>

