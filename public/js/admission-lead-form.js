$(function () {
    'use strict';

    const csrf = $('meta[name="csrf-token"]').attr('content') || '';

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function setMessage(type, message) {
        const $message = $('#leadFormMessage');

        $message
            .removeClass('text-success text-danger')
            .addClass(type === 'success' ? 'text-success' : 'text-danger')
            .text(message || '');
    }

    function setSubmitting(isSubmitting) {
        const $button = $('#leadSubmitButton');

        $button.prop('disabled', isSubmitting);

        if (isSubmitting) {
            $button.html(
                '<i class="fas fa-spinner fa-spin"></i>'
                + '<span>Đang gửi thông tin...</span>'
            );
            return;
        }

        $button.html(
            '<i class="fas fa-paper-plane"></i>'
            + '<span>Gửi đăng ký tư vấn</span>'
        );
    }

    function resetLocationFields() {
        $('#selectDistrict')
            .html('<option value="">-- Chọn Quận/Huyện --</option>')
            .prop('disabled', true);
    }

    function createSelectField(field, key, requiredAttribute) {
        let html = `
            <select
                class="form-control"
                name="custom[${escapeHtml(key)}]"
                ${requiredAttribute}
            >
                <option value="">
                    -- Chọn ${escapeHtml(field.criterion_name)} --
                </option>
        `;

        const options = Array.isArray(field.options)
            ? field.options
            : [];

        options.forEach(function (option) {
            html += `
                <option value="${escapeHtml(option.value)}">
                    ${escapeHtml(option.label)}
                </option>
            `;
        });

        html += '</select>';

        return html;
    }

    function createCheckboxField(field, key) {
        const options = Array.isArray(field.options)
            ? field.options
            : [];

        if (!options.length) {
            return `
                <div class="dynamic-checkbox-group">
                    <label class="dynamic-checkbox-option">
                        <input
                            type="checkbox"
                            name="custom[${escapeHtml(key)}]"
                            value="1"
                        >
                        <span>Có</span>
                    </label>
                </div>
            `;
        }

        const items = options.map(function (option) {
            return `
                <label class="dynamic-checkbox-option">
                    <input
                        type="checkbox"
                        name="custom[${escapeHtml(key)}][]"
                        value="${escapeHtml(option.value)}"
                    >
                    <span>${escapeHtml(option.label)}</span>
                </label>
            `;
        }).join('');

        return `<div class="dynamic-checkbox-group">${items}</div>`;
    }

    function createInputField(field, key, requiredAttribute) {
        let type = 'text';

        if (field.input_type === 'number') {
            type = 'number';
        } else if (field.input_type === 'date') {
            type = 'date';
        }

        return `
            <input
                class="form-control"
                type="${type}"
                name="custom[${escapeHtml(key)}]"
                ${requiredAttribute}
            >
        `;
    }

    function renderDynamicFields(fields) {
        const $container = $('#dynamicFieldsContainer');
        const $section = $('#dynamicFieldsSection');

        $container.empty();

        if (!Array.isArray(fields) || !fields.length) {
            $section.hide();
            return;
        }

        fields.forEach(function (field) {
            if (!field || !field.data_field || !field.criterion_name) {
                return;
            }

            const key = String(field.data_field).replace(/^custom\./, '');
            const requiredAttribute = field.is_required ? 'required' : '';
            const requiredMark = field.is_required
                ? '<span class="required-mark">*</span>'
                : '';

            let control = '';

            if (field.input_type === 'select') {
                control = createSelectField(
                    field,
                    key,
                    requiredAttribute
                );
            } else if (field.input_type === 'checkbox') {
                control = createCheckboxField(field, key);
            } else {
                control = createInputField(
                    field,
                    key,
                    requiredAttribute
                );
            }

            $container.append(`
                <div class="form-group">
                    <label class="form-label">
                        ${escapeHtml(field.criterion_name)}
                        ${requiredMark}
                    </label>
                    ${control}
                </div>
            `);
        });

        $section.toggle($container.children().length > 0);
    }

    $.ajax({
        url: '/api/admission/scoring/dynamic-fields',
        method: 'GET'
    })
        .done(renderDynamicFields)
        .fail(function () {
            $('#dynamicFieldsSection').hide();
        });

    let provincesData = [];

    $.get('https://provinces.open-api.vn/api/?depth=2')
        .done(function (response) {
            provincesData = Array.isArray(response) ? response : [];

            let options = '<option value="">-- Chọn Tỉnh/Thành --</option>';

            provincesData.forEach(function (province) {
                options += `
                    <option
                        value="${escapeHtml(province.name)}"
                        data-code="${escapeHtml(province.code)}"
                    >
                        ${escapeHtml(province.name)}
                    </option>
                `;
            });

            $('#selectProvince').html(options);
        })
        .fail(function () {
            $('#selectProvince').html(
                '<option value="">Không tải được danh sách Tỉnh/Thành</option>'
            );
        });

    $('#selectProvince').on('change', function () {
        const provinceCode = $(this).find(':selected').data('code');
        const $district = $('#selectDistrict');

        if (!provinceCode) {
            resetLocationFields();
            return;
        }

        const province = provincesData.find(function (item) {
            return String(item.code) === String(provinceCode);
        });

        const districts = province && Array.isArray(province.districts)
            ? province.districts
            : [];

        let options = '<option value="">-- Chọn Quận/Huyện --</option>';

        districts.forEach(function (district) {
            options += `
                <option value="${escapeHtml(district.name)}">
                    ${escapeHtml(district.name)}
                </option>
            `;
        });

        $district
            .html(options)
            .prop('disabled', districts.length === 0);
    });

    $('#publicLeadForm').on('submit', function (event) {
        event.preventDefault();

        const form = this;

        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const $form = $(form);

        setSubmitting(true);
        setMessage('success', '');
        $('#leadRagResultBox').stop(true, true).hide();

        $.ajax({
            url: '/dang-ky-tu-van-n8n',
            method: 'POST',
            data: $form.serialize(),
            headers: {
                'X-CSRF-TOKEN': csrf
            }
        })
            .done(function (response) {
                setMessage(
                    'success',
                    response.message
                        || 'Đã ghi nhận thông tin đăng ký tư vấn.'
                );

                form.reset();
                resetLocationFields();

                if (response.rag_answer) {
                    $('#leadRagResultContent').text(response.rag_answer);
                    $('#leadRagResultBox').stop(true, true).slideDown(180);
                }
            })
            .fail(function (xhr) {
                const response = xhr.responseJSON || {};
                const errors = response.errors || null;

                if (errors && Object.keys(errors).length) {
                    const firstKey = Object.keys(errors)[0];
                    const firstError = Array.isArray(errors[firstKey])
                        ? errors[firstKey][0]
                        : errors[firstKey];

                    setMessage(
                        'error',
                        firstError || 'Thông tin gửi lên chưa hợp lệ.'
                    );
                    return;
                }

                setMessage(
                    'error',
                    response.message
                        || 'Không thể gửi đăng ký. Vui lòng thử lại.'
                );
            })
            .always(function () {
                setSubmitting(false);
            });
    });
});
