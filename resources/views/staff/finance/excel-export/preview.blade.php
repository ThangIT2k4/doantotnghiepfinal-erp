@if(isset($data) && count($data) > 0)
    <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
        <table class="table table-striped table-bordered table-sm table-hover">
            <thead class="table-light sticky-top">
                <tr>
                    @foreach($columns as $column)
                        <th class="text-nowrap bg-light">{{ $column }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($data as $row)
                    <tr>
                        @php
                            $rowArray = is_array($row) ? $row : (array) $row;
                        @endphp
                        @foreach($columns as $index => $column)
                            @php
                                // Use columnKeys if available, otherwise try to map by index
                                if (isset($columnKeys) && isset($columnKeys[$index])) {
                                    $key = $columnKeys[$index];
                                } else {
                                    $keys = array_keys($rowArray);
                                    $key = $keys[$index] ?? null;
                                }
                                $value = $rowArray[$key] ?? '';
                            @endphp
                            <td class="text-nowrap">{{ $value }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-3 d-flex justify-content-between align-items-center">
        <small class="text-muted">
            <i class="fas fa-info-circle"></i> Hiển thị <strong>{{ count($data) }}</strong> / <strong>{{ $total ?? 0 }}</strong> bản ghi
        </small>
        @if(isset($total) && $total > count($data))
            <small class="text-muted">
                <i class="fas fa-exclamation-triangle"></i> Chỉ hiển thị {{ count($data) }} bản ghi đầu tiên
            </small>
        @endif
    </div>
@else
    <div class="alert alert-info text-center py-4">
        <i class="fas fa-info-circle fa-2x mb-2"></i>
        <p class="mb-0">Không có dữ liệu để hiển thị với bộ lọc đã chọn</p>
    </div>
@endif

