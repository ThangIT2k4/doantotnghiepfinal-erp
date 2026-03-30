@if(isset($recentActivities) && $recentActivities->count() > 0)
<div class="table-responsive">
    <table class="table table-sm table-hover">
        <thead>
            <tr>
                <th width="15%">Loại</th>
                <th width="40%">Mô Tả</th>
                <th width="15%">Trạng Thái</th>
                <th width="30%">Thời Gian</th>
            </tr>
        </thead>
        <tbody>
            @foreach($recentActivities as $activity)
            <tr>
                <td>
                    @if($activity->type == 'lead')
                    <span class="badge bg-primary"><i class="fas fa-user-tag"></i> Lead</span>
                    @elseif($activity->type == 'viewing')
                    <span class="badge bg-info"><i class="fas fa-eye"></i> Viewing</span>
                    @elseif($activity->type == 'booking')
                    <span class="badge bg-warning"><i class="fas fa-calendar-check"></i> Booking</span>
                    @else
                    <span class="badge bg-secondary">{{ ucfirst($activity->type) }}</span>
                    @endif
                </td>
                <td>{{ $activity->title ?? 'N/A' }}</td>
                <td>
                    @if($activity->status == 'new' || $activity->status == 'requested')
                    <span class="badge bg-warning">{{ ucfirst($activity->status) }}</span>
                    @elseif($activity->status == 'confirmed' || $activity->status == 'active' || $activity->status == 'paid')
                    <span class="badge bg-success">{{ ucfirst($activity->status) }}</span>
                    @else
                    <span class="badge bg-secondary">{{ ucfirst($activity->status) }}</span>
                    @endif
                </td>
                <td>
                    <small>{{ \Carbon\Carbon::parse($activity->created_at)->format('d/m/Y H:i') }}</small>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@else
<div class="text-center py-3 text-muted">
    <i class="fas fa-inbox fa-2x mb-2"></i>
    <p>Chưa có hoạt động nào trong 30 ngày gần nhất</p>
</div>
@endif

