@if($properties->count() > 0)
    <div class="table-responsive">
        <table class="table table-sm">
            <thead>
                <tr>
                    <th>Tên BĐS</th>
                    <th>Chu kỳ</th>
                    <th>Ngày tạo hóa đơn</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @foreach($properties as $property)
                    <tr>
                        <td><strong>{{ $property->name }}</strong></td>
                        <td>
                            @php
                                $effectiveCycle = $property->getEffectivePaymentCycle();
                            @endphp
                            @if($effectiveCycle)
                                <span class="badge bg-info" title="{{ $effectiveCycle->notes ?? '' }}">
                                    {{ $effectiveCycle->cycle_type_name }}
                                </span>
                                @if($property->payment_cycle_id)
                                    <br><small class="text-muted">Từ BĐS</small>
                                @else
                                    <br><small class="text-muted">Từ tổ chức</small>
                                @endif
                            @else
                                <span class="text-muted">Chưa cài đặt</span>
                            @endif
                        </td>
                        <td>
                            @php
                                $effectiveCycle = $property->getEffectivePaymentCycle();
                            @endphp
                            @if($effectiveCycle && $effectiveCycle->billing_day)
                                <span class="badge bg-primary">{{ $effectiveCycle->billing_day }}</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            <button type="button" class="btn btn-sm btn-outline-primary btn-icon-only" 
                                    onclick="showPropertySettings({{ $property->id }}, '{{ $property->name }}')"
                                    title="Cài đặt">
                                <i class="fas fa-cog"></i>
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@else
    <div class="text-center py-4">
        <i class="fas fa-home fa-3x text-muted mb-3"></i>
        <p class="text-muted mt-2">Chưa có bất động sản nào</p>
    </div>
@endif

