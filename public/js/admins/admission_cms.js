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

    let funnelChart = null;
    let sourceChart = null;
    let deflectionChart = null;

    function loadStats() {
        $.get('/admin/admission-cms/stats', function (res) {
            if (!res.success) return;
            
            // 1. Cập nhật các con số text
            Object.keys(res.data).forEach(function (key) {
                if (typeof res.data[key] !== 'object') {
                    $('[data-stat="' + key + '"]').text(res.data[key]);
                }
            });

            // 2. Vẽ biểu đồ Phễu (Funnel)
            const funnel = res.data.funnel || { total: 0, warm: 0, hot: 0 };
            const ctxFunnel = document.getElementById('funnelChart');
            if (ctxFunnel) {
                if (funnelChart) funnelChart.destroy();
                funnelChart = new Chart(ctxFunnel, {
                    type: 'bar',
                    data: {
                        labels: ['Tổng Khách (Total)', 'Quan tâm (Warm)', 'Chốt (Hot)'],
                        datasets: [{
                            label: 'Số lượng Lead',
                            data: [funnel.total, funnel.warm, funnel.hot],
                            backgroundColor: ['#e2e8f0', '#fef08a', '#fca5a5'],
                            borderColor: ['#94a3b8', '#eab308', '#ef4444'],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        indexAxis: 'y', // Biểu đồ ngang cho giống phễu
                        plugins: { legend: { display: false } }
                    }
                });
            }

            // 3. Vẽ biểu đồ Nguồn
            const sources = res.data.sources || [];
            const ctxSource = document.getElementById('sourceChart');
            if (ctxSource && sources.length > 0) {
                const labels = sources.map(s => s.channel.toUpperCase());
                const data = sources.map(s => s.total);
                if (sourceChart) sourceChart.destroy();
                sourceChart = new Chart(ctxSource, {
                    type: 'doughnut',
                    data: {
                        labels: labels,
                        datasets: [{
                            data: data,
                            backgroundColor: ['#3b82f6', '#06b6d4', '#10b981', '#f59e0b', '#8b5cf6'],
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { position: 'bottom' } }
                    }
                });
            }

            // 4. Vẽ biểu đồ Bot Deflection
            const tickets = res.data.tickets || { total: 0, answered: 0, pending: 0 };
            const ctxDeflection = document.getElementById('deflectionChart');
            if (ctxDeflection && tickets.total > 0) {
                const deflectionRate = Math.round((tickets.answered / tickets.total) * 100);
                $('#deflectionRateText').text(deflectionRate + '%');
                
                if (deflectionChart) deflectionChart.destroy();
                deflectionChart = new Chart(ctxDeflection, {
                    type: 'pie',
                    data: {
                        labels: ['Bot Tự Xử Lý', 'Chuyển Nhân Viên'],
                        datasets: [{
                            data: [tickets.answered, tickets.pending],
                            backgroundColor: ['#22c55e', '#ef4444'],
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } }
                    }
                });
            }
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

    function loadApprovals() {
        $('#approvalList').html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Đang tải...</div>');
        $.get('/admin/admission-cms/approvals', function(res) {
            if (!res.success || res.data.length === 0) {
                $('#approvalList').html('<div class="text-center text-muted py-4">Tất cả đều trống. Không có mục nào chờ duyệt! 🎉</div>');
                return;
            }
            const html = res.data.map(function(item) {
                return `
                <div class="card shadow-sm border-0 mb-3" id="approval-card-${item.id}">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <h5 class="card-title text-primary"><i class="fas fa-user-circle me-1"></i> ${escapeHtml(item.customer_name)}</h5>
                            <span class="badge bg-secondary">Kênh: ${escapeHtml(item.channel)}</span>
                        </div>
                        <p class="mb-1 text-muted"><i class="fas fa-envelope me-1"></i> ${escapeHtml(item.customer_email || 'N/A')} &nbsp; | &nbsp; <i class="fas fa-phone me-1"></i> ${escapeHtml(item.customer_phone || 'N/A')}</p>
                        <hr>
                        <div class="mb-3">
                            <label class="fw-bold text-dark">Câu hỏi của khách hàng:</label>
                            <div class="p-2 bg-light rounded border">${escapeHtml(item.question)}</div>
                        </div>
                        <div class="mb-3">
                            <label class="fw-bold text-success">Câu trả lời do AI soạn thảo (Draft):</label>
                            <textarea id="ai-answer-${item.id}" class="form-control" rows="4">${escapeHtml(item.ai_answer)}</textarea>
                        </div>
                        <div class="mb-3">
                            <input type="text" id="ai-feedback-${item.id}" class="form-control" placeholder="Ghi chú yêu cầu AI sửa lại (Nếu sai sót)...">
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-success btn-sm btn-approval-action" data-id="${item.id}" data-action="approve">
                                <i class="fas fa-check"></i> Chấp nhận & Gửi đi
                            </button>
                            <button class="btn btn-warning btn-sm btn-approval-action" data-id="${item.id}" data-action="rewrite">
                                <i class="fas fa-robot"></i> Bắt AI viết lại
                            </button>
                            <button class="btn btn-danger btn-sm btn-approval-action" data-id="${item.id}" data-action="reject">
                                <i class="fas fa-times"></i> Huỷ bỏ
                            </button>
                        </div>
                    </div>
                </div>`;
            }).join('');
            $('#approvalList').html(html);
        });
    }

    $(document).on('click', '.btn-approval-action', function() {
        const id = $(this).data('id');
        const action = $(this).data('action');
        const answer = $('#ai-answer-' + id).val();
        const feedback = $('#ai-feedback-' + id).val();
        
        const btn = $(this);
        const originalText = btn.html();
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Xử lý...');

        $.post(`/admin/admission-cms/approvals/${id}/action`, {
            action: action,
            ai_answer: answer,
            admin_feedback: feedback
        }).done(function(res) {
            toastr.success(res.message);
            if (action === 'rewrite') {
                $('#ai-answer-' + id).val(res.new_answer);
                btn.prop('disabled', false).html(originalText);
            } else {
                $('#approval-card-' + id).fadeOut(300, function() { $(this).remove(); });
            }
        }).fail(function(xhr) {
            btn.prop('disabled', false).html(originalText);
            let msg = 'Lỗi hệ thống!';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                msg = xhr.responseJSON.message;
            }
            toastr.error(msg);
        });
    });

    $('#reloadApprovals').on('click', loadApprovals);

    $('#runNurtureCommand').on('click', function () {
        const btn = $(this);
        const originalText = btn.html();
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Đang chạy...');
        $.post('/admin/admission-cms/nurture')
            .done(function (res) {
                alert(res.message || 'Chạy kịch bản nuôi dưỡng thành công!');
            })
            .fail(function (err) {
                alert('Lỗi: ' + (err.responseJSON?.error || 'Không thể chạy Nurture'));
            })
            .always(function () {
                btn.prop('disabled', false).html(originalText);
            });
    });

    $('[data-tab-target]').on('click', function () {
        $('.tabs-line button').removeClass('active');
        $(this).addClass('active');
        $('.tab-panel').addClass('hidden');
        $('#' + $(this).data('tab-target')).removeClass('hidden');

        if ($(this).data('tab-target') === 'approvalTab') loadApprovals();
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



    // Scoring Criteria Logic
    function loadCriteria() {
        $('#criteriaRows').html('<tr><td colspan="8" class="text-center"><i class="fas fa-spinner fa-spin"></i> Đang tải...</td></tr>');
        $.get('/admin/admission-cms/scoring/criteria', function (res) {
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

    $('#runNurtureBtn').on('click', function() {
        const btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i> Đang chạy...');
        
        $.post('/admin/admission-cms/nurture')
            .done(function(res) {
                if(res.success) {
                    toastr.success(res.message);
                    console.log("Nurture Output:\n" + res.output);
                    alert("Kết quả chạy chiến dịch:\n\n" + res.output);
                } else {
                    toastr.error(res.message);
                }
            })
            .fail(function(xhr) {
                toastr.error('Lỗi kết nối khi chạy chiến dịch.');
            })
            .always(function() {
                btn.prop('disabled', false).html('<i class="fas fa-paper-plane me-2"></i> Chạy Chiến Dịch Ngay');
            });
    });

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

        const url = id ? '/admin/admission-cms/scoring/criteria/' + id : '/admin/admission-cms/scoring/criteria';
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
            url: '/admin/admission-cms/scoring/criteria/' + $(this).data('id'),
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
