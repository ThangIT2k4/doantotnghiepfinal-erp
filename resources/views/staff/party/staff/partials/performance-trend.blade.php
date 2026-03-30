@if(isset($monthlyTrend) && count($monthlyTrend) > 0)
<div style="position: relative; height: 400px;">
    <canvas id="performanceTrendChart"></canvas>
</div>
<script>
// Initialize chart after this partial is loaded
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(() => {
        if (typeof initializePerformanceTrendChart === 'function') {
            initializePerformanceTrendChart();
        }
    }, 100);
});
</script>
@else
<div class="text-center py-3 text-muted">
    <i class="fas fa-chart-bar fa-2x mb-2"></i>
    <p>Chưa có dữ liệu xu hướng</p>
</div>
@endif

