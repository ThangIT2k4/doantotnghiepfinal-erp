@props([
    'headers' => [],
    'rows' => [],
    'emptyMessage' => 'Không có dữ liệu',
    'class' => '',
    'striped' => false,
    'hover' => true,
])

<div class="table-responsive">
    <table class="table table-modern {{ $striped ? 'table-striped' : '' }} {{ $hover ? 'table-hover' : '' }} {{ $class }}">
        @if(!empty($headers))
        <thead>
            <tr>
                @foreach($headers as $header)
                <th>{{ is_array($header) ? ($header['label'] ?? '') : $header }}</th>
                @endforeach
            </tr>
        </thead>
        @endif
        <tbody>
            @if(!empty($rows))
                @foreach($rows as $row)
                <tr>
                    @foreach($row as $cell)
                    <td>{{ $cell }}</td>
                    @endforeach
                </tr>
                @endforeach
            @else
                <tr>
                    <td colspan="{{ count($headers) }}" class="text-center py-5">
                        <div class="empty-state">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <p class="text-muted">{{ $emptyMessage }}</p>
                        </div>
                    </td>
                </tr>
            @endif
        </tbody>
    </table>
</div>

@push('styles')
<style>
/* Modern Tables */
.table-modern {
    border-radius: 12px;
    overflow: hidden;
}

.table-modern thead {
    background: var(--blue-gradient);
    color: white;
}

.table-modern thead th {
    border: none;
    padding: 1rem;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.85rem;
    letter-spacing: 0.5px;
}

.table-modern tbody tr {
    transition: all 0.2s ease;
    border-bottom: 1px solid #F5F5F5;
}

.table-modern tbody tr:hover {
    background: var(--blue-bg-light);
    transform: scale(1.01);
}

.table-modern tbody td {
    padding: 1rem;
    vertical-align: middle;
}

.table-modern .empty-state {
    padding: 2rem;
}
</style>
@endpush

