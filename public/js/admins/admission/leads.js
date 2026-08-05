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
                        '<td><strong>' + Cms.escapeHtml(lead.full_name || 'Chưa có tên') + '</strong>' +
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

    loadLeads(1);
});
