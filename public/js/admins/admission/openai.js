$(function () {
    'use strict';

    if (!$('#admissionOpenAiPage').length) return;

    const Cms = window.AdmissionCms;

    function loadConfig() {
        $('#openaiFileRows').html(Cms.tableMessage(3, 'Đang đồng bộ danh sách file...', 'loading'));

        $.get('/admin/admission-cms/openai/config')
            .done(function (response) {
                if (!response.success) {
                    toastr.error(response.message || 'Không tải được cấu hình OpenAI.');
                    return;
                }

                const data = response.data || {};
                $('#openaiPrompt').val(data.instructions || '');

                const files = data.files || [];
                if (!files.length) {
                    $('#openaiFileRows').html(Cms.tableMessage(3, 'Chưa có file nào trong Vector Store.', 'empty'));
                    return;
                }

                $('#openaiFileRows').html(files.map(function (file) {
                    return '<tr>' +
                        '<td><strong>' + Cms.escapeHtml(file.filename) + '</strong><div class="text-muted small">' + Cms.escapeHtml(file.created_at) + '</div></td>' +
                        '<td>' + Cms.escapeHtml(file.bytes) + '</td>' +
                        '<td><button class="btn btn-outline-danger btn-sm btn-delete-openai-file" data-id="' + Cms.escapeHtml(file.id) + '"><i class="fas fa-trash"></i></button></td>' +
                        '</tr>';
                }).join(''));
            })
            .fail(function (xhr) {
                $('#openaiFileRows').html(Cms.tableMessage(3, Cms.errorMessage(xhr, 'Không tải được dữ liệu OpenAI.'), 'error'));
            });
    }

    $('#saveOpenAiPrompt').on('click', function () {
        const $button = $(this);
        const oldHtml = $button.html();
        $button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Đang lưu...');

        $.post('/admin/admission-cms/openai/prompt', {
            instructions: $('#openaiPrompt').val()
        })
            .done(function () { toastr.success('Đã đồng bộ instructions lên OpenAI.'); })
            .fail(function (xhr) { toastr.error(Cms.errorMessage(xhr, 'Không thể lưu instructions.')); })
            .always(function () { $button.prop('disabled', false).html(oldHtml); });
    });

    $('#chooseOpenAiFile').on('click', function () { $('#uploadOpenAiFile').trigger('click'); });

    $('#uploadOpenAiFile').on('change', function () {
        const file = this.files && this.files[0];
        if (!file) return;

        const formData = new FormData();
        formData.append('file', file);
        toastr.info('Đang tải tài liệu lên OpenAI...');

        $.ajax({
            url: '/admin/admission-cms/openai/files',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false
        })
            .done(function () {
                toastr.success('Đã tải tài liệu lên OpenAI.');
                loadConfig();
            })
            .fail(function (xhr) { toastr.error(Cms.errorMessage(xhr, 'Không thể tải tài liệu.')); })
            .always(function () { $('#uploadOpenAiFile').val(''); });
    });

    $(document).on('click', '.btn-delete-openai-file', function () {
        const fileId = $(this).data('id');
        if (!window.confirm('Xóa file này khỏi OpenAI và dữ liệu liên quan?')) return;

        $.ajax({
            url: '/admin/admission-cms/openai/files/' + encodeURIComponent(fileId),
            method: 'DELETE'
        })
            .done(function () {
                toastr.success('Đã xóa file.');
                loadConfig();
            })
            .fail(function (xhr) { toastr.error(Cms.errorMessage(xhr, 'Không thể xóa file.')); });
    });

    loadConfig();
});
