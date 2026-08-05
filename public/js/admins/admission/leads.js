$(function () {
    'use strict';

    if (!$('#admissionLeadsPage').length) return;

    const Cms = window.AdmissionCms;

    const statusLabels = {
        new: 'Mới',
        contacted: 'Đã liên hệ',
        qualified: 'Đủ tiềm năng',
        enrolled: 'Đã nhập học',
        lost: 'Không tiếp tục'
    };

    function statusOption(value, current) {
        return '<option value="' + value + '"' +
            (value === current ? ' selected' : '') + '>' +
            statusLabels[value] + '</option>';
    }

    function loadLeads(page) {
        page = page || 1;
        $('#leadRows').html(Cms.tableMessage(7, 'Đang tải danh sách lead...', 'loading'));

        $.get('/admin/admission-cms/leads', {
            keyword: $('#leadKeyword').val(),
            score_grade: $('#leadGrade').val(),
            page: page
        })
            .done(function (response) {
                const paginator = Cms.getPaginator(response);
                const data = paginator.data || [];

                if (!data.length) {
                    $('#leadRows').html(Cms.tableMessage(7, 'Chưa có lead phù hợp.', 'empty'));
                    $('#leadsPagination').empty();
                    return;
                }

                const rows = data.map(function (lead) {
                    const grade = String(lead.score_grade || 'cold').toLowerCase();
                    const contacts = [];

                    if (lead.phone) contacts.push('<div><i class="fas fa-phone-alt text-muted me-1"></i>' + Cms.escapeHtml(lead.phone) + '</div>');
                    if (lead.email) contacts.push('<div class="small text-muted"><i class="fas fa-envelope me-1"></i>' + Cms.escapeHtml(lead.email) + '</div>');
                    if (lead.facebook_account_id) contacts.push('<div class="small text-muted"><i class="fab fa-facebook me-1"></i>Facebook ID: ' + Cms.escapeHtml(lead.facebook_account_id) + '</div>');
                    if (lead.zalo_account_id) contacts.push('<div class="small text-muted">Zalo ID: ' + Cms.escapeHtml(lead.zalo_account_id) + '</div>');

                    return '<tr>' +
                        '<td><a href="#" class="view-lead text-decoration-none fw-bold" data-id="' + lead.id + '">' + Cms.escapeHtml(lead.full_name || 'Chưa có tên') + '</a>' +
                            '<div class="text-muted small">Lead #' + Cms.escapeHtml(lead.id) + '</div></td>' +
                        '<td>' + (contacts.length ? contacts.join('') : '<span class="text-muted">Chưa có thông tin</span>') + '</td>' +
                        '<td><strong>' + Cms.escapeHtml(lead.channel || 'Không rõ') + '</strong>' +
                            '<div class="text-muted small">' + Cms.escapeHtml(lead.source_campaign || '') + '</div></td>' +
                        '<td>' + Cms.escapeHtml(lead.intended_major || 'Chưa xác định') +
                            '<div class="text-muted small">' + Cms.escapeHtml(lead.province || '') + '</div></td>' +
                        '<td><span class="admission-score ' + grade + '">' +
                            Cms.escapeHtml(lead.score || 0) + ' ' + grade.toUpperCase() + '</span></td>' +
                        '<td><select class="form-select form-select-sm lead-status" data-id="' + lead.id + '" data-note="' + Cms.escapeHtml(lead.note || '') + '">' +
                            statusOption('new', lead.status) +
                            statusOption('contacted', lead.status) +
                            statusOption('qualified', lead.status) +
                            statusOption('enrolled', lead.status) +
                            statusOption('lost', lead.status) +
                            '</select></td>' +
                        '<td class="text-nowrap">' + Cms.formatDate(lead.last_interaction_at || lead.updated_at) + '</td>' +
                        '</tr>';
                }).join('');

                $('#leadRows').html(rows);
                Cms.renderPagination(paginator, '#leadsPagination', loadLeads);
            })
            .fail(function (xhr) {
                $('#leadRows').html(Cms.tableMessage(7, Cms.errorMessage(xhr, 'Không tải được danh sách lead.'), 'error'));
                console.error('Lỗi load leads:', xhr.status, xhr.responseText);
            });
    }

    $('#reloadLeads').on('click', function () { loadLeads(1); });
    $('#leadGrade').on('change', function () { loadLeads(1); });
    $('#leadKeyword').on('keydown', function (event) {
        if (event.key === 'Enter') loadLeads(1);
    });

    $('#leadForm').on('submit', function (event) {
        event.preventDefault();
        const $form = $(this);
        const $submit = $form.find('[type="submit"]');
        const oldHtml = $submit.html();

        $submit.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Đang lưu...');

        $.post('/admin/admission-cms/leads', $form.serialize())
            .done(function () {
                const modalElement = document.getElementById('leadModal');
                bootstrap.Modal.getOrCreateInstance(modalElement).hide();
                $form[0].reset();
                $form.find('[name="channel"]').val('manual');
                toastr.success('Đã lưu lead.');
                loadLeads(1);
            })
            .fail(function (xhr) {
                toastr.error(Cms.errorMessage(xhr, 'Không thể lưu lead.'));
            })
            .always(function () {
                $submit.prop('disabled', false).html(oldHtml);
            });
    });

    $(document).on('change', '.lead-status', function () {
        const $select = $(this);
        $select.prop('disabled', true);

        $.post('/admin/admission-cms/leads/' + $select.data('id') + '/status', {
            status: $select.val(),
            note: $select.data('note') || ''
        })
            .done(function () { toastr.success('Đã cập nhật trạng thái lead.'); })
            .fail(function (xhr) {
                toastr.error(Cms.errorMessage(xhr, 'Không thể cập nhật trạng thái.'));
                loadLeads(1);
            })
            .always(function () { $select.prop('disabled', false); });
    });

    $(document).on('click', '.view-lead', function (e) {
        e.preventDefault();
        const id = $(this).data('id');
        $('#detailLeadName').text('Đang tải...');
        $('#detailScoreLogs').html('<tr><td colspan="5" class="text-center text-muted">Đang tải...</td></tr>');
        $('#detailActivities').html('<div class="text-center text-muted">Đang tải...</div>');
        
        const modal = new bootstrap.Modal(document.getElementById('leadDetailModal'));
        modal.show();

        $.get('/admin/admission-cms/leads/' + id)
            .done(function (res) {
                if (res.success && res.data) {
                    const lead = res.data.lead;
                    $('#detailLeadName').text(lead.full_name || 'Chưa có tên');

                    // Score Logs
                    const logs = res.data.scoreLogs || [];
                    if (logs.length > 0) {
                        const logsHtml = logs.map(l => {
                            const criterionName = l.criterion_name ? (l.criterion_name + ' <small class="text-muted">(' + l.criterion_code + ')</small>') : '<i class="text-muted">N/A</i>';
                            const scoreClass = l.score_added > 0 ? 'text-success' : 'text-muted';
                            return '<tr>' +
                                '<td>' + Cms.formatDate(l.created_at) + '</td>' +
                                '<td>' + Cms.escapeHtml(l.source_channel || 'N/A') + '</td>' +
                                '<td>' + Cms.escapeHtml(l.action_type || 'N/A') + '</td>' +
                                '<td>' + criterionName + '</td>' +
                                '<td class="fw-bold ' + scoreClass + '">+' + Cms.escapeHtml(l.score_added) + '</td>' +
                                '</tr>';
                        }).join('');
                        $('#detailScoreLogs').html(logsHtml);
                    } else {
                        $('#detailScoreLogs').html('<tr><td colspan="5" class="text-center text-muted">Chưa có lịch sử cộng điểm</td></tr>');
                    }

                    // Activities
                    const activities = res.data.activities || [];
                    if (activities.length > 0) {
                        const actHtml = activities.map(a => {
                            const icon = a.direction === 'inbound' ? '<i class="fas fa-arrow-right text-primary me-2"></i>' : '<i class="fas fa-arrow-left text-success me-2"></i>';
                            return '<div class="mb-3 pb-3 border-bottom">' +
                                '<div class="d-flex justify-content-between mb-1">' +
                                '<div>' + icon + '<strong>' + Cms.escapeHtml(a.channel) + ' - ' + Cms.escapeHtml(a.type) + '</strong></div>' +
                                '<div class="small text-muted">' + Cms.formatDate(a.occurred_at) + '</div>' +
                                '</div>' +
                                '<div class="bg-light p-2 rounded small">' + Cms.escapeHtml(a.content || '(Không có nội dung)') + '</div>' +
                                '</div>';
                        }).join('');
                        $('#detailActivities').html(actHtml);
                    } else {
                        $('#detailActivities').html('<div class="text-center text-muted">Chưa có tương tác nào</div>');
                    }
                }
            })
            .fail(function (xhr) {
                $('#detailLeadName').text('Lỗi tải dữ liệu');
                $('#detailScoreLogs').html('<tr><td colspan="5" class="text-center text-danger">Không tải được lịch sử cộng điểm</td></tr>');
            });
    });

    loadLeads(1);
});
