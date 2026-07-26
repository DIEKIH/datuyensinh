$(function () {
    const csrf = $('meta[name="csrf-token"]').attr('content');

    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': csrf },
    });

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function formatDate(value) {
        if (!value) return '';
        const date = new Date(value);
        if (Number.isNaN(date.getTime())) return value;
        return date.toLocaleString('vi-VN');
    }

    function loadStats() {
        $.get('/admin/admission-cms/stats', function (res) {
            if (!res.success) return;
            Object.keys(res.data).forEach(function (key) {
                $('[data-stat="' + key + '"]').text(res.data[key]);
            });
        });
    }

    function loadLeads() {
        const params = {
            keyword: $('#leadKeyword').val(),
            score_grade: $('#leadGrade').val(),
        };

        $.get('/admin/admission-cms/leads', params, function (res) {
            const rows = (res.data && res.data.data ? res.data.data : []).map(function (lead) {
                const grade = lead.score_grade || 'cold';
                return '<tr>' +
                    '<td><strong>' + escapeHtml(lead.full_name || 'Chua co ten') + '</strong><div class="text-muted small">#' + lead.id + '</div></td>' +
                    '<td>' + escapeHtml(lead.phone) + '<div class="text-muted small">' + escapeHtml(lead.email) + '</div></td>' +
                    '<td>' + escapeHtml(lead.channel) + '<div class="text-muted small">' + escapeHtml(lead.source_campaign) + '</div></td>' +
                    '<td>' + escapeHtml(lead.intended_major) + '<div class="text-muted small">' + escapeHtml(lead.province) + '</div></td>' +
                    '<td><span class="lead-score ' + grade + '">' + lead.score + ' ' + grade + '</span></td>' +
                    '<td><select class="form-select form-select-sm lead-status" data-id="' + lead.id + '" data-note="' + escapeHtml(lead.note) + '">' +
                        statusOption('new', lead.status) +
                        statusOption('contacted', lead.status) +
                        statusOption('qualified', lead.status) +
                        statusOption('enrolled', lead.status) +
                        statusOption('lost', lead.status) +
                    '</select></td>' +
                    '<td>' + formatDate(lead.last_interaction_at || lead.updated_at) + '</td>' +
                '</tr>';
            }).join('');

            $('#leadRows').html(rows || '<tr><td colspan="7" class="text-center text-muted">Chua co lead.</td></tr>');
        });
    }

    function statusOption(value, current) {
        return '<option value="' + value + '"' + (value === current ? ' selected' : '') + '>' + value + '</option>';
    }

    function loadDocuments() {
        $.get('/admin/admission-cms/documents', function (res) {
            const rows = (res.data || []).map(function (doc) {
                return '<tr>' +
                    '<td><strong>' + escapeHtml(doc.title) + '</strong><div class="text-muted small">' + escapeHtml(doc.source_url) + '</div></td>' +
                    '<td>' + escapeHtml(doc.category) + '</td>' +
                    '<td>' + escapeHtml(doc.status) + '</td>' +
                    '<td>' + doc.chunks_count + '</td>' +
                    '<td>' + formatDate(doc.updated_at) + '</td>' +
                '</tr>';
            }).join('');

            $('#documentRows').html(rows || '<tr><td colspan="5" class="text-center text-muted">Chua co tai lieu.</td></tr>');
        });
    }

    function loadN8nLogs() {
        $.get('/admin/admission-cms/n8n/logs', function (res) {
            const rows = (res.data || []).map(function (log) {
                return '<tr>' +
                    '<td>' + escapeHtml(log.workflow) + '</td>' +
                    '<td>' + escapeHtml(log.event_type) + '</td>' +
                    '<td>' + escapeHtml(log.status) + '</td>' +
                    '<td class="small">' + escapeHtml(log.message) + '</td>' +
                    '<td>' + formatDate(log.created_at) + '</td>' +
                '</tr>';
            }).join('');

            $('#n8nRows').html(rows || '<tr><td colspan="5" class="text-center text-muted">Chua co log.</td></tr>');
        });
    }

    $('[data-tab-target]').on('click', function () {
        $('[data-tab-target]').removeClass('active');
        $(this).addClass('active');
        $('.tab-panel').addClass('hidden');
        $('#' + $(this).data('tab-target')).removeClass('hidden');

        if ($(this).data('tab-target') === 'ragTab') loadDocuments();
        if ($(this).data('tab-target') === 'n8nTab') loadN8nLogs();
        if ($(this).data('tab-target') === 'openaiTab') loadOpenAiConfig();
    });

    function loadOpenAiConfig() {
        $('#openaiFileRows').html('<tr><td colspan="3" class="text-center"><i class="fas fa-spinner fa-spin"></i> Đang đồng bộ...</td></tr>');
        $.get('/admin/admission-cms/openai/config', function (res) {
            if (!res.success) {
                toastr.error(res.message || 'Lỗi khi tải dữ liệu OpenAI');
                return;
            }
            $('#openaiPrompt').val(res.data.instructions);
            
            const fileRows = res.data.files.map(function(f) {
                return `<tr>
                    <td><strong>${escapeHtml(f.filename)}</strong><br><small class="text-muted">${f.created_at}</small></td>
                    <td>${f.bytes}</td>
                    <td><button class="btn btn-sm btn-danger btn-delete-oaifile" data-id="${f.id}">Xóa</button></td>
                </tr>`;
            }).join('');
            
            $('#openaiFileRows').html(fileRows || '<tr><td colspan="3" class="text-center">Chưa có file nào trên Vector Store của OpenAI</td></tr>');
        });
    }

    $('#saveOpenAiPrompt').on('click', function() {
        const btn = $(this);
        btn.prop('disabled', true).text('Đang lưu...');
        $.post('/admin/admission-cms/openai/prompt', { instructions: $('#openaiPrompt').val() })
            .done(function() { toastr.success('Đã đồng bộ Prompt lên OpenAI thành công!'); })
            .fail(function() { toastr.error('Lỗi khi lưu prompt'); })
            .always(function() { btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i> Lưu & Đồng bộ lên OpenAI'); });
    });

    $(document).on('click', '.btn-delete-oaifile', function() {
        if (!confirm('Bạn có chắc muốn xóa vĩnh viễn file này khỏi bộ nhớ của OpenAI?')) return;
        const btn = $(this);
        btn.prop('disabled', true).text('...');
        $.ajax({
            url: '/admin/admission-cms/openai/files/' + btn.data('id'),
            type: 'DELETE',
            success: function() { toastr.success('Đã xóa file'); loadOpenAiConfig(); },
            error: function() { toastr.error('Lỗi xóa file'); btn.prop('disabled', false).text('Xóa'); }
        });
    });

    $('#uploadOpenAiFile').on('change', function() {
        if (!this.files[0]) return;
        const formData = new FormData();
        formData.append('file', this.files[0]);
        
        toastr.info('Đang tải file lên OpenAI, vui lòng không tắt trang...');
        $.ajax({
            url: '/admin/admission-cms/openai/files',
            type: 'POST',
            data: formData,
            processData: false, contentType: false,
            success: function() { toastr.success('Tải tài liệu lên OpenAI thành công!'); loadOpenAiConfig(); },
            error: function() { toastr.error('Lỗi khi upload file lên OpenAI'); }
        });
        $(this).val('');
    });

    $('#reloadLeads').on('click', loadLeads);
    $('#leadKeyword, #leadGrade').on('change keyup', function (event) {
        if (event.type === 'change' || event.key === 'Enter') {
            loadLeads();
        }
    });

    $('#leadForm').on('submit', function (event) {
        event.preventDefault();
        $.post('/admin/admission-cms/leads', $(this).serialize())
            .done(function () {
                $('#leadModal').modal('hide');
                $('#leadForm')[0].reset();
                loadStats();
                loadLeads();
                toastr.success('Da luu lead.');
            })
            .fail(function (xhr) {
                toastr.error((xhr.responseJSON && xhr.responseJSON.message) || 'Khong the luu lead.');
            });
    });

    $(document).on('change', '.lead-status', function () {
        const select = $(this);
        $.post('/admin/admission-cms/leads/' + select.data('id') + '/status', {
            status: select.val(),
            note: select.data('note') || '',
        }).done(function () {
            loadStats();
            toastr.success('Da cap nhat trang thai.');
        });
    });

    $('#documentForm').on('submit', function (event) {
        event.preventDefault();
        const formData = new FormData(this);
        
        $.ajax({
            url: '/admin/admission-cms/documents',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (res) {
                $('#documentForm')[0].reset();
                $('#documentForm [name="category"]').val('quy_che');
                $('#documentForm [name="status"]').val('active');
                loadStats();
                loadDocuments();
                toastr.success(res.message || ('Da luu tai lieu va tao ' + res.chunks + ' chunks.'));
            },
            error: function (xhr) {
                const errMsg = xhr.responseJSON && xhr.responseJSON.errors 
                    ? Object.values(xhr.responseJSON.errors).flat().join('<br>')
                    : (xhr.responseJSON && xhr.responseJSON.message || 'Khong the luu tai lieu.');
                toastr.error(errMsg);
            }
        });
    });

    $('#askRag').on('click', function () {
        const question = $('#ragQuestion').val();
        if (!question.trim()) {
            toastr.warning('Nhap cau hoi truoc.');
            return;
        }

        $('#ragAnswer').text('Dang tra cuu...');
        $.post('/admin/admission-cms/rag/ask', { question: question })
            .done(function (res) {
                const data = res.data || {};
                const sources = (data.sources || []).map(function (source) {
                    return source.document_title + ' #' + source.chunk_index;
                }).join(', ');
                $('#ragAnswer').text((data.answer || '') + (sources ? '\n\nNguon: ' + sources : ''));
            })
            .fail(function () {
                $('#ragAnswer').text('Khong the tra cuu RAG.');
            });
    });

    // Scoring Criteria Logic
    function loadCriteria() {
        $('#criteriaRows').html('<tr><td colspan="8" class="text-center"><i class="fas fa-spinner fa-spin"></i> Đang tải...</td></tr>');
        $.get('/api/admission/scoring/criteria', function (res) {
            const rows = res.map(function (c) {
                const status = c.is_active ? '<span class="badge bg-success">Đang bật</span>' : '<span class="badge bg-secondary">Đã tắt</span>';
                return `<tr>
                    <td><strong>${escapeHtml(c.criterion_code)}</strong></td>
                    <td>${escapeHtml(c.criterion_name)}</td>
                    <td><code>${escapeHtml(c.data_field)}</code></td>
                    <td><strong>${escapeHtml(c.operator)}</strong></td>
                    <td>${escapeHtml(c.comparison_value || '')}</td>
                    <td><span class="text-success fw-bold">+${c.score}</span></td>
                    <td>${status}</td>
                    <td>
                        <button class="btn btn-sm btn-outline-info btn-edit-crit" data-crit='${JSON.stringify(c)}'><i class="fas fa-edit"></i></button>
                        <button class="btn btn-sm btn-outline-danger btn-del-crit" data-id="${c.id}"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>`;
            }).join('');
            $('#criteriaRows').html(rows || '<tr><td colspan="8" class="text-center text-muted">Chưa có tiêu chí nào.</td></tr>');
        });
    }

    $('[data-tab-target]').on('click', function () {
        if ($(this).data('tab-target') === 'scoringTab') loadCriteria();
    });

    $('#reloadCriteria').on('click', loadCriteria);

    $('#criterionForm').on('submit', function (e) {
        e.preventDefault();
        const id = $('#crit_id').val();
        const data = {
            criterion_code: $('#crit_code').val(),
            criterion_name: $('#crit_name').val(),
            data_field: $('#crit_field').val(),
            operator: $('#crit_op').val(),
            comparison_value: $('#crit_val').val(),
            score: $('#crit_score').val(),
            priority: $('#crit_priority').val() || 0,
            is_active: 1
        };

        const url = id ? '/api/admission/scoring/criteria/' + id : '/api/admission/scoring/criteria';
        const method = id ? 'PUT' : 'POST';

        $.ajax({
            url: url, type: method, data: data,
            success: function () {
                toastr.success('Đã lưu tiêu chí thành công');
                $('#criterionForm')[0].reset();
                $('#crit_id').val('');
                $('#crit_cancel').addClass('hidden');
                loadCriteria();
            },
            error: function (xhr) {
                toastr.error('Lỗi khi lưu tiêu chí. Mã tiêu chí có thể đã trùng.');
            }
        });
    });

    $(document).on('click', '.btn-edit-crit', function () {
        const c = $(this).data('crit');
        $('#crit_id').val(c.id);
        $('#crit_code').val(c.criterion_code);
        $('#crit_name').val(c.criterion_name);
        $('#crit_field').val(c.data_field);
        $('#crit_op').val(c.operator);
        $('#crit_val').val(c.comparison_value);
        $('#crit_score').val(c.score);
        $('#crit_priority').val(c.priority);
        $('#crit_cancel').removeClass('hidden');
    });

    $('#crit_cancel').on('click', function () {
        $('#criterionForm')[0].reset();
        $('#crit_id').val('');
        $(this).addClass('hidden');
    });

    $(document).on('click', '.btn-del-crit', function () {
        if (!confirm('Bạn có chắc muốn xóa tiêu chí này?')) return;
        $.ajax({
            url: '/api/admission/scoring/criteria/' + $(this).data('id'),
            type: 'DELETE',
            success: function () {
                toastr.success('Đã xóa tiêu chí');
                loadCriteria();
            }
        });
    });

    loadStats();
    loadLeads();
});
