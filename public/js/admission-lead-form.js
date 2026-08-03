$(function () {
    const csrf = $('meta[name="csrf-token"]').attr('content');

    function setMessage(type, message) {
        const color = type === 'success' ? 'text-success' : 'text-danger';
        $('#leadFormMessage').removeClass('text-success text-danger').addClass(color).text(message);
    }

    $.ajax({
        url: '/api/admission/scoring/dynamic-fields',
        method: 'GET'
    }).done(function(fields) {
        if (!fields || !fields.length) return;
        const container = $('#dynamicFieldsContainer');
        
        fields.forEach(function(field) {
            const key = field.data_field.replace('custom.', '');
            const requiredAttr = field.is_required ? 'required' : '';
            const requiredLabel = field.is_required ? ' *' : '';
            let inputHtml = '';
            
            if (field.input_type === 'select') {
                inputHtml = `<select class="form-control" name="custom[${key}]" ${requiredAttr}>`;
                inputHtml += `<option value="">-- Chọn ${field.criterion_name} --</option>`;
                if (field.options && field.options.length) {
                    field.options.forEach(opt => {
                        inputHtml += `<option value="${opt.value}">${opt.label}</option>`;
                    });
                }
                inputHtml += `</select>`;
            } else if (field.input_type === 'checkbox') {
                if (field.options && field.options.length) {
                    inputHtml = `<div style="display: flex; flex-wrap: wrap; gap: 10px;">`;
                    field.options.forEach((opt, idx) => {
                        inputHtml += `
                        <label style="display: flex; align-items: center; gap: 4px; font-weight: normal;">
                            <input type="checkbox" name="custom[${key}][]" value="${opt.value}">
                            ${opt.label}
                        </label>`;
                    });
                    inputHtml += `</div>`;
                } else {
                    inputHtml = `
                    <label style="display: flex; align-items: center; gap: 4px; font-weight: normal;">
                        <input type="checkbox" name="custom[${key}]" value="1">
                        Có
                    </label>`;
                }
            } else {
                const type = field.input_type === 'number' ? 'number' : (field.input_type === 'date' ? 'date' : 'text');
                inputHtml = `<input class="form-control" type="${type}" name="custom[${key}]" ${requiredAttr}>`;
            }
            
            const groupHtml = `
            <div class="form-group">
                <label class="form-label">${field.criterion_name}${requiredLabel}</label>
                ${inputHtml}
            </div>`;
            container.append(groupHtml);
        });
    });

    let provincesData = [];
    $.get('https://provinces.open-api.vn/api/?depth=2', function(res) {
        provincesData = res;
        let html = '<option value="">-- Chọn Tỉnh/Thành --</option>';
        res.forEach(p => {
            html += `<option value="${p.name}" data-code="${p.code}">${p.name}</option>`;
        });
        $('#selectProvince').html(html);
    });

    $('#selectProvince').on('change', function() {
        const code = $(this).find(':selected').data('code');
        const districtSelect = $('#selectDistrict');
        if (!code) {
            districtSelect.html('<option value="">-- Chọn Quận/Huyện --</option>').prop('disabled', true);
            return;
        }
        
        const province = provincesData.find(p => p.code == code);
        if (province && province.districts) {
            let html = '<option value="">-- Chọn Quận/Huyện --</option>';
            province.districts.forEach(d => {
                html += `<option value="${d.name}">${d.name}</option>`;
            });
            districtSelect.html(html).prop('disabled', false);
        }
    });

    $('#publicLeadForm').on('submit', function (event) {
        event.preventDefault();

        const form = $(this);
        const submitButton = form.find('button[type="submit"]');
        submitButton.prop('disabled', true).text('Dang gui...');
        setMessage('success', '');

        $.ajax({
            url: '/dang-ky-tu-van-n8n',
            method: 'POST',
            data: form.serialize(),
            headers: { 'X-CSRF-TOKEN': csrf },
        }).done(function (res) {
            setMessage('success', res.message || 'Da ghi nhan thong tin dang ky.');
            form[0].reset();
            if (res.rag_answer) {
                $('#leadRagResultContent').text(res.rag_answer);
                $('#leadRagResultBox').slideDown();
            } else {
                $('#leadRagResultBox').slideUp();
            }
        }).fail(function (xhr) {
            const errors = xhr.responseJSON && xhr.responseJSON.errors;
            if (errors) {
                const firstKey = Object.keys(errors)[0];
                setMessage('error', errors[firstKey][0]);
                return;
            }

            setMessage('error', 'Khong the gui dang ky. Vui long thu lai.');
        }).always(function () {
            submitButton.prop('disabled', false).html('<i class="fas fa-paper-plane me-1"></i> Gui dang ky');
        });
    });

    $('#publicAskRag').on('click', function () {
        const question = $('#publicRagQuestion').val().trim();

        if (!question) {
            $('#publicRagAnswer').text('Nhap cau hoi truoc khi tra cuu.');
            return;
        }

        $('#publicRagAnswer').text('Dang tra cuu kho quy che...');

        $.ajax({
            url: '/api/n8n/rag/answer',
            method: 'POST',
            data: {
                question: question,
                channel: $('input[name="utm_source"]').val() || 'website',
            },
        }).done(function (res) {
            const data = res.data || {};
            const sources = (data.sources || []).map(function (source) {
                return source.document_title;
            }).filter(Boolean);

            $('#publicRagAnswer').text((data.answer || 'Chua co cau tra loi.') + (sources.length ? '\n\nNguon: ' + sources.join(', ') : ''));
        }).fail(function () {
            $('#publicRagAnswer').text('Chua the tra cuu RAG luc nay. Vui long de lai thong tin de tu van vien lien he.');
        });
    });
});
