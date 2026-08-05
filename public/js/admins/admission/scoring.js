$(function () {
    'use strict';

    if (!$('#admissionScoringPage').length) return;

    const Cms = window.AdmissionCms;

    function parseOptions(value) {
        if (Array.isArray(value)) return value;
        if (!value) return [];
        try { return JSON.parse(value); } catch (error) { return []; }
    }

    function loadCriteria() {
        $('#criteriaRows').html(Cms.tableMessage(10, 'Đang tải tiêu chí...', 'loading'));

        $.get('/admin/admission-cms/scoring/criteria')
            .done(function (response) {
                const data = Array.isArray(response) ? response : (response.data || []);

                if (!data.length) {
                    $('#criteriaRows').html(Cms.tableMessage(10, 'Chưa có tiêu chí chấm điểm.', 'empty'));
                    return;
                }

                $('#criteriaRows').html(data.map(function (criterion) {
                    const status = criterion.is_active
                        ? '<span class="badge bg-success">Đang bật</span>'
                        : '<span class="badge bg-secondary">Đã tắt</span>';
                    const publicBadge = criterion.show_on_public_form
                        ? '<div><span class="badge bg-info text-dark mt-1">Form public</span></div>'
                        : '';
                    const encoded = encodeURIComponent(JSON.stringify(criterion));

                    return '<tr>' +
                        '<td><strong>' + Cms.escapeHtml(criterion.criterion_code) + '</strong></td>' +
                        '<td>' + Cms.escapeHtml(criterion.criterion_name) + '</td>' +
                        '<td>' + Cms.escapeHtml(criterion.category || 'Chưa phân loại') + '</td>' +
                        '<td><span class="admission-code">' + Cms.escapeHtml(criterion.data_field) + '</span></td>' +
                        '<td>' + Cms.escapeHtml(criterion.input_type || 'text') + publicBadge + '</td>' +
                        '<td><strong>' + Cms.escapeHtml(criterion.operator) + '</strong></td>' +
                        '<td>' + Cms.escapeHtml(criterion.comparison_value || '') + '</td>' +
                        '<td><strong class="text-success">+' + Cms.escapeHtml(criterion.score) + '</strong></td>' +
                        '<td>' + status + '</td>' +
                        '<td class="text-nowrap">' +
                            '<button class="btn btn-outline-primary btn-sm btn-edit-criterion" data-criterion="' + encoded + '"><i class="fas fa-edit"></i></button> ' +
                            '<button class="btn btn-outline-danger btn-sm btn-delete-criterion" data-id="' + criterion.id + '"><i class="fas fa-trash"></i></button>' +
                        '</td>' +
                        '</tr>';
                }).join(''));
            })
            .fail(function (xhr) {
                $('#criteriaRows').html(Cms.tableMessage(10, Cms.errorMessage(xhr, 'Không tải được tiêu chí.'), 'error'));
            });
    }

    function resetForm() {
        $('#criterionForm')[0].reset();
        $('#crit_id').val('');
        $('#crit_priority').val(0);
        $('#crit_options_wrap').hide();
        $('#critModalTitle').text('Thêm tiêu chí chấm điểm');
    }

    $('#btnAddNewCrit').on('click', resetForm);
    $('#reloadCriteria').on('click', loadCriteria);

    $('#crit_input_type').on('change', function () {
        const show = ['select', 'checkbox'].indexOf($(this).val()) !== -1;
        $('#crit_options_wrap').toggle(show);
    });

    $('#criterionForm').on('submit', function (event) {
        event.preventDefault();

        const id = $('#crit_id').val();
        let options = null;
        const rawOptions = $('#crit_options').val().trim();

        if (['select', 'checkbox'].indexOf($('#crit_input_type').val()) !== -1 && rawOptions) {
            options = rawOptions.split(',').map(function (value) {
                value = value.trim();
                return { label: value, value: value };
            }).filter(function (item) { return item.value; });
        }

        $.ajax({
            url: id
                ? '/admin/admission-cms/scoring/criteria/' + id
                : '/admin/admission-cms/scoring/criteria',
            method: id ? 'PUT' : 'POST',
            data: {
                criterion_code: $('#crit_code').val(),
                criterion_name: $('#crit_name').val(),
                category: $('#crit_category').val(),
                data_field: $('#crit_field').val(),
                input_type: $('#crit_input_type').val() || 'text',
                options: options,
                show_on_public_form: $('#crit_show_public').is(':checked') ? 1 : 0,
                is_required: $('#crit_is_required').is(':checked') ? 1 : 0,
                operator: $('#crit_op').val(),
                comparison_value: $('#crit_val').val(),
                score: $('#crit_score').val(),
                priority: $('#crit_priority').val() || 0,
                is_active: 1
            }
        })
            .done(function () {
                bootstrap.Modal.getOrCreateInstance(document.getElementById('criterionModal')).hide();
                toastr.success('Đã lưu tiêu chí.');
                resetForm();
                loadCriteria();
            })
            .fail(function (xhr) { toastr.error(Cms.errorMessage(xhr, 'Không thể lưu tiêu chí.')); });
    });

    $(document).on('click', '.btn-edit-criterion', function () {
        const criterion = JSON.parse(decodeURIComponent($(this).attr('data-criterion')));
        const options = parseOptions(criterion.options);

        $('#crit_id').val(criterion.id);
        $('#crit_code').val(criterion.criterion_code);
        $('#crit_name').val(criterion.criterion_name);
        $('#crit_category').val(criterion.category || '');
        $('#crit_field').val(criterion.data_field);
        $('#crit_input_type').val(criterion.input_type || 'text').trigger('change');
        $('#crit_options').val(options.map(function (item) { return item.label || item.value; }).join(', '));
        $('#crit_show_public').prop('checked', Boolean(criterion.show_on_public_form));
        $('#crit_is_required').prop('checked', Boolean(criterion.is_required));
        $('#crit_op').val(criterion.operator);
        $('#crit_val').val(criterion.comparison_value || '');
        $('#crit_score').val(criterion.score);
        $('#crit_priority').val(criterion.priority || 0);
        $('#critModalTitle').text('Sửa tiêu chí chấm điểm');

        bootstrap.Modal.getOrCreateInstance(document.getElementById('criterionModal')).show();
    });

    $(document).on('click', '.btn-delete-criterion', function () {
        const id = $(this).data('id');
        if (!window.confirm('Xóa tiêu chí này?')) return;

        $.ajax({
            url: '/admin/admission-cms/scoring/criteria/' + id,
            method: 'DELETE'
        })
            .done(function () {
                toastr.success('Đã xóa tiêu chí.');
                loadCriteria();
            })
            .fail(function (xhr) { toastr.error(Cms.errorMessage(xhr, 'Không thể xóa tiêu chí.')); });
    });

    loadCriteria();
});
