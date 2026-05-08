/**
 * Revenue Reports JavaScript
 * QLPhongTro - Revenue reporting dashboard functionality
 */

class RevenueReports {
    constructor() {
        this.charts = {};
        this.init();
    }

    init() {
        this.initializeCharts();
        this.setupEventListeners();
        this.setupDataTables();
    }

    initializeCharts() {
        // Revenue Trend Chart
        this.initRevenueTrendChart();
        
        // Revenue by Type Chart
        this.initRevenueByTypeChart();
    }

    initRevenueTrendChart() {
        const ctx = document.getElementById('revenueTrendChart');
        if (!ctx) return;

        this.charts.revenueTrend = new Chart(ctx, {
            type: 'line',
            data: {
                labels: this.getChartLabels(),
                datasets: [{
                    label: 'Doanh thu',
                    data: this.getChartData(),
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    tension: 0.1,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                scales: {
                    x: {
                        display: true,
                        title: {
                            display: true,
                            text: 'Thời gian'
                        }
                    },
                    y: {
                        beginAtZero: true,
                        display: true,
                        title: {
                            display: true,
                            text: 'Doanh thu (VND)'
                        },
                        ticks: {
                            callback: function(value) {
                                return new Intl.NumberFormat('vi-VN').format(value);
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Doanh thu: ' + new Intl.NumberFormat('vi-VN').format(context.parsed.y) + ' VND';
                            }
                        }
                    }
                }
            }
        });
    }

    initRevenueByTypeChart() {
        const ctx = document.getElementById('revenueByTypeChart');
        if (!ctx) return;

        this.charts.revenueByType = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Cho thuê', 'Bán'],
                datasets: [{
                    data: this.getRevenueByTypeData(),
                    backgroundColor: [
                        'rgba(54, 162, 235, 0.8)',
                        'rgba(255, 99, 132, 0.8)'
                    ],
                    borderColor: [
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 99, 132, 1)'
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((context.parsed / total) * 100).toFixed(1);
                                return context.label + ': ' + 
                                       new Intl.NumberFormat('vi-VN').format(context.parsed) + 
                                       ' VND (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });
    }

    getChartLabels() {
        // Get labels from the page data
        const labelsElement = document.querySelector('[data-chart-labels]');
        if (labelsElement) {
            return JSON.parse(labelsElement.dataset.chartLabels);
        }
        return [];
    }

    getChartData() {
        // Get data from the page data
        const dataElement = document.querySelector('[data-chart-data]');
        if (dataElement) {
            return JSON.parse(dataElement.dataset.chartData);
        }
        return [];
    }

    getRevenueByTypeData() {
        // Get revenue by type data from the page
        const rentalElement = document.querySelector('[data-rental-revenue]');
        const saleElement = document.querySelector('[data-sale-revenue]');
        
        const rental = rentalElement ? parseInt(rentalElement.dataset.rentalRevenue) : 0;
        const sale = saleElement ? parseInt(saleElement.dataset.saleRevenue) : 0;
        
        return [rental, sale];
    }

    setupEventListeners() {
        // Filter form submission
        const filterForm = document.querySelector('form[method="GET"]');
        if (filterForm) {
            filterForm.addEventListener('submit', (e) => {
                this.showLoading();
            });
        }

        // Export buttons
        const exportBtns = document.querySelectorAll('[data-export]');
        exportBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                this.handleExport(btn.dataset.export);
            });
        });

        // Chart type change
        const chartTypeBtns = document.querySelectorAll('[data-chart-type]');
        chartTypeBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                this.changeChartType(btn.dataset.chartType);
            });
        });
    }

    setupDataTables() {
        const dataTable = document.getElementById('dataTable');
        if (dataTable && typeof $.fn.DataTable !== 'undefined') {
            $(dataTable).DataTable({
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/vi.json"
                },
                "pageLength": 25,
                "order": [[ 1, "desc" ]],
                "columnDefs": [
                    { "orderable": false, "targets": [9] }
                ],
                "dom": '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                       '<"row"<"col-sm-12"tr>>' +
                       '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
                "responsive": true
            });
        }
    }

    changeChartType(type) {
        if (this.charts.revenueTrend) {
            this.charts.revenueTrend.config.type = type;
            this.charts.revenueTrend.update();
        }
    }

    handleExport(type) {
        switch (type) {
            case 'pdf':
                this.exportToPDF();
                break;
            case 'excel':
                this.exportToExcel();
                break;
            case 'csv':
                this.exportToCSV();
                break;
            default:
                this.showNotification('Chức năng xuất báo cáo đang được phát triển', 'info');
        }
    }

    exportToPDF() {
        this.showNotification('Chức năng xuất PDF đang được phát triển', 'info');
    }

    exportToExcel() {
        this.showNotification('Chức năng xuất Excel đang được phát triển', 'info');
    }

    exportToCSV() {
        this.showNotification('Chức năng xuất CSV đang được phát triển', 'info');
    }

    showLoading() {
        const submitBtn = document.querySelector('button[type="submit"]');
        if (submitBtn) {
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang tải...';
            submitBtn.disabled = true;
        }
    }

    showNotification(message, type = 'info') {
        if (typeof Notify !== 'undefined') {
            Notify[type](message);
        } else {
            alert(message);
        }
    }

    // Utility methods
    formatCurrency(amount) {
        return new Intl.NumberFormat('vi-VN', {
            style: 'currency',
            currency: 'VND'
        }).format(amount);
    }

    formatNumber(number) {
        return new Intl.NumberFormat('vi-VN').format(number);
    }

    formatDate(date) {
        return new Intl.DateTimeFormat('vi-VN').format(new Date(date));
    }

    // Chart update methods
    updateRevenueTrendChart(data) {
        if (this.charts.revenueTrend) {
            this.charts.revenueTrend.data.datasets[0].data = data;
            this.charts.revenueTrend.update();
        }
    }

    updateRevenueByTypeChart(rental, sale) {
        if (this.charts.revenueByType) {
            this.charts.revenueByType.data.datasets[0].data = [rental, sale];
            this.charts.revenueByType.update();
        }
    }

    // Refresh data
    refreshData() {
        // This would typically make an AJAX call to refresh the data
        this.showNotification('Đang làm mới dữ liệu...', 'info');
        
        // Simulate API call
        setTimeout(() => {
            this.showNotification('Dữ liệu đã được làm mới', 'success');
        }, 1000);
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    window.revenueReports = new RevenueReports();
});

// Global functions for backward compatibility
function changeChartType(type) {
    if (window.revenueReports) {
        window.revenueReports.changeChartType(type);
    }
}

function exportReport() {
    if (window.revenueReports) {
        window.revenueReports.handleExport('pdf');
    }
}

function exportDetailReport() {
    if (window.revenueReports) {
        window.revenueReports.handleExport('excel');
    }
}

function resetFilter() {
    document.getElementById('start_date').value = '';
    document.getElementById('end_date').value = '';
    document.getElementById('period').value = 'monthly';
    document.querySelector('form').submit();
}
