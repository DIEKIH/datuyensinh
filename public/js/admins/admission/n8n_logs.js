$(function () {
    'use strict';

    if (!$('#admissionN8nPage').length) {
        return;
    }

    const Cms = window.AdmissionCms;

    function isErrorStatus(status) {
        const normalized = String(status || '')
            .trim()
            .toLowerCase();

        return [
            'failed',
            'error'
        ].indexOf(normalized) !== -1;
    }

    function statusBadge(status) {
        if (isErrorStatus(status)) {
            return (
                '<span class="badge bg-danger">' +
                'Lỗi' +
                '</span>'
            );
        }

        return (
            '<span class="badge bg-success">' +
            'Thành công' +
            '</span>'
        );
    }

    function eventLabel(eventType) {
        const event = String(eventType || '')
            .trim()
            .toLowerCase();

        const labels = {
            'n8n_workflow_error': 'Lỗi luồng n8n',
            'webhook_upsert_lead': 'Tiếp nhận tương tác',
            'run_lead_campaign': 'Chiến dịch chăm sóc Lead',
            'system_log': 'Nhật ký hệ thống',
            'new_article': 'Xử lý bài viết mới',
            'incoming_message': 'Tin nhắn mới',
            'new_lead': 'Lead mới',
            'lead_became_hot': 'Lead chuyển sang Hot',
            'toxic_comment_action': 'Xử lý bình luận vi phạm'
        };

        return labels[event] || eventType || 'Không xác định';
    }

    function parsePayload(payload) {
        if (!payload) {
            return {};
        }

        if (typeof payload === 'object') {
            return payload;
        }

        try {
            return JSON.parse(payload);
        } catch (error) {
            return {};
        }
    }

    function renderDetails(log) {
        const payload = parsePayload(log.payload);

        const message = String(
            log.message || ''
        ).trim();

        const node = String(
            payload.node ||
            payload.last_node ||
            ''
        ).trim();

        const executionId = String(
            payload.execution_id || ''
        ).trim();

        let html = '';

        if (message) {
            html += (
                '<div>' +
                Cms.escapeHtml(message) +
                '</div>'
            );
        }

        if (node) {
            html += (
                '<div class="text-muted mt-1">' +
                '<strong>Node:</strong> ' +
                Cms.escapeHtml(node) +
                '</div>'
            );
        }

        if (executionId) {
            html += (
                '<div class="text-muted">' +
                '<strong>Mã lần chạy:</strong> ' +
                Cms.escapeHtml(executionId) +
                '</div>'
            );
        }

        if (!html) {
            html = (
                '<span class="text-muted">' +
                'Không có thông tin chi tiết' +
                '</span>'
            );
        }

        return html;
    }

    function loadLogs(page) {
        page = page || 1;

        $('#n8nRows').html(
            Cms.tableMessage(
                5,
                'Đang tải nhật ký n8n...',
                'loading'
            )
        );

        $.get(
            '/admin/admission-cms/n8n/logs',
            {
                keyword: $('#n8nKeyword').val(),
                status: $('#n8nStatus').val(),
                page: page
            }
        )
            .done(function (response) {
                const paginator =
                    Cms.getPaginator(response);

                const data =
                    paginator.data || [];

                if (!data.length) {
                    $('#n8nRows').html(
                        Cms.tableMessage(
                            5,
                            'Chưa có nhật ký phù hợp.',
                            'empty'
                        )
                    );

                    $('#n8nPagination').empty();

                    return;
                }

                $('#n8nRows').html(
                    data.map(function (log) {
                        return (
                            '<tr>' +

                            '<td>' +
                                '<strong>' +
                                    Cms.escapeHtml(
                                        log.workflow ||
                                        'Không xác định'
                                    ) +
                                '</strong>' +
                            '</td>' +

                            '<td>' +
                                Cms.escapeHtml(
                                    eventLabel(
                                        log.event_type
                                    )
                                ) +
                            '</td>' +

                            '<td>' +
                                statusBadge(
                                    log.status
                                ) +
                            '</td>' +

                            '<td class="small">' +
                                renderDetails(log) +
                            '</td>' +

                            '<td class="text-nowrap">' +
                                Cms.formatDate(
                                    log.created_at
                                ) +
                            '</td>' +

                            '</tr>'
                        );
                    }).join('')
                );

                Cms.renderPagination(
                    paginator,
                    '#n8nPagination',
                    loadLogs
                );
            })
            .fail(function (xhr) {
                $('#n8nRows').html(
                    Cms.tableMessage(
                        5,
                        Cms.errorMessage(
                            xhr,
                            'Không tải được nhật ký n8n.'
                        ),
                        'error'
                    )
                );
            });
    }

    $('#reloadN8nLogs').on(
        'click',
        function () {
            loadLogs(1);
        }
    );

    $('#n8nStatus').on(
        'change',
        function () {
            loadLogs(1);
        }
    );

    $('#n8nKeyword').on(
        'keydown',
        function (event) {
            if (event.key === 'Enter') {
                loadLogs(1);
            }
        }
    );

    loadLogs(1);
});