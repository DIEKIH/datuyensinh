$(function () {
    'use strict';

    if (!$('#admissionDashboardPage').length) return;

    const Cms = window.AdmissionCms;
    let funnelChart = null;
    let sourceChart = null;
    let deflectionChart = null;

    function buildCharts(data) {
        if (typeof Chart === 'undefined') return;

        const funnel = data.funnel || { total: 0, warm: 0, hot: 0 };
        const sources = data.sources || [];
        const tickets = data.tickets || { total: 0, answered: 0, pending: 0 };

        const funnelCanvas = document.getElementById('funnelChart');
        if (funnelCanvas) {
            if (funnelChart) funnelChart.destroy();
            funnelChart = new Chart(funnelCanvas, {
                type: 'bar',
                data: {
                    labels: ['Tổng lead', 'Warm', 'Hot'],
                    datasets: [{
                        label: 'Số lượng lead',
                        data: [funnel.total || 0, funnel.warm || 0, funnel.hot || 0],
                        backgroundColor: ['#dbeafe', '#fde68a', '#fecaca'],
                        borderColor: ['#2563eb', '#d97706', '#dc2626'],
                        borderWidth: 1,
                        borderRadius: 7
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: 'y',
                    plugins: { legend: { display: false } },
                    scales: { x: { beginAtZero: true, ticks: { precision: 0 } } }
                }
            });
        }

        const sourceCanvas = document.getElementById('sourceChart');
        if (sourceCanvas) {
            if (sourceChart) sourceChart.destroy();
            sourceChart = new Chart(sourceCanvas, {
                type: 'doughnut',
                data: {
                    labels: sources.map(function (item) {
                        return String(item.channel || 'Không rõ').toUpperCase();
                    }),
                    datasets: [{
                        data: sources.map(function (item) { return item.total || 0; }),
                        backgroundColor: ['#2563eb', '#06b6d4', '#16a34a', '#f59e0b', '#7c3aed', '#64748b'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '66%',
                    plugins: { legend: { position: 'bottom' } }
                }
            });
        }

        const total = Number(tickets.total || 0);
        const answered = Number(tickets.answered || 0);
        const pending = Number(tickets.pending || 0);
        const rate = total > 0 ? Math.round((answered / total) * 100) : 0;
        $('#deflectionRateText').text(rate + '%');

        const deflectionCanvas = document.getElementById('deflectionChart');
        if (deflectionCanvas) {
            if (deflectionChart) deflectionChart.destroy();
            deflectionChart = new Chart(deflectionCanvas, {
                type: 'doughnut',
                data: {
                    labels: ['AI xử lý', 'Chuyển tư vấn viên'],
                    datasets: [{
                        data: [answered, pending],
                        backgroundColor: ['#16a34a', '#ef4444'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '68%',
                    plugins: { legend: { position: 'bottom' } }
                }
            });
        }
    }

    function loadStats() {
        $.get('/admin/admission-cms/stats')
            .done(function (response) {
                if (!response.success) return;

                const data = response.data || {};
                Object.keys(data).forEach(function (key) {
                    if (typeof data[key] !== 'object') {
                        $('[data-stat="' + key + '"]').text(data[key]);
                    }
                });

                buildCharts(data);
            })
            .fail(function (xhr) {
                toastr.error(Cms.errorMessage(xhr, 'Không tải được dữ liệu tổng quan.'));
            });
    }

    $('#runNurtureBtn').on('click', function () {
        const $button = $(this);
        const oldHtml = $button.html();

        $button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Đang chạy...');

        $.post('/admin/admission-cms/nurture')
            .done(function (response) {
                if (response.success === false) {
                    toastr.error(response.message || 'Không thể chạy chiến dịch.');
                    return;
                }
                toastr.success(response.message || 'Đã chạy chiến dịch chăm sóc.');
            })
            .fail(function (xhr) {
                toastr.error(Cms.errorMessage(xhr, 'Không thể chạy chiến dịch.'));
            })
            .always(function () {
                $button.prop('disabled', false).html(oldHtml);
            });
    });

    loadStats();
});
