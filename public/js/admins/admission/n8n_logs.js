$(function () {
    'use strict';

    if (!$('#admissionN8nPage').length) return;

    const Cms = window.AdmissionCms;

    function statusBadge(status) {
        const normalized = String(status || '').toLowerCase();
        let css = 'bg-secondary';
        if (['success', 'completed'].indexOf(normalized) !== -1) css = 'bg-success';
        if (['received', 'processing'].indexOf(normalized) !== -1) css = 'bg-primary';
        if (['failed', 'error'].indexOf(normalized) !== -1) css = 'bg-danger';
        return '<span class="badge ' + css + '">' + Cms.escapeHtml(status || 'Không rõ') + '</span>';
    }

    function loadLogs(page) {
        page = page || 1;
        $('#n8nRows').html(Cms.tableMessage(5, 'Đang tải nhật ký n8n...', 'loading'));

        $.get('/admin/admission-cms/n8n/logs', {
            keyword: $('#n8nKeyword').val(),
            status: $('#n8nStatus').val(),
            page: page
        })
            .done(function (response) {
                const paginator = Cms.getPaginator(response);
                const data = paginator.data || [];

                if (!data.length) {
                    $('#n8nRows').html(Cms.tableMessage(5, 'Chưa có nhật ký phù hợp.', 'empty'));
                    $('#n8nPagination').empty();
                    return;
                }

                $('#n8nRows').html(data.map(function (log) {
                    return '<tr>' +
                        '<td><strong>' + Cms.escapeHtml(log.workflow || 'Không rõ') + '</strong></td>' +
                        '<td>' + Cms.escapeHtml(log.event_type || '') + '</td>' +
                        '<td>' + statusBadge(log.status) + '</td>' +
                        '<td class="small">' + Cms.escapeHtml(log.message || '') + '</td>' +
                        '<td class="text-nowrap">' + Cms.formatDate(log.created_at) + '</td>' +
                        '</tr>';
                }).join(''));

                Cms.renderPagination(paginator, '#n8nPagination', loadLogs);
            })
            .fail(function (xhr) {
                $('#n8nRows').html(Cms.tableMessage(5, Cms.errorMessage(xhr, 'Không tải được nhật ký n8n.'), 'error'));
            });
    }

    $('#reloadN8nLogs').on('click', function () { loadLogs(1); });
    $('#n8nStatus').on('change', function () { loadLogs(1); });
    $('#n8nKeyword').on('keydown', function (event) {
        if (event.key === 'Enter') loadLogs(1);
    });

    loadLogs(1);
});
