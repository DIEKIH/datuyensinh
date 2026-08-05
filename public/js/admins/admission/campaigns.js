$(function () {
    'use strict';

    if (!$('#admissionCampaignPage').length) {
        return;
    }

    const csrf = $('meta[name="csrf-token"]').attr('content') || '';

    function firstError(xhr, fallback) {
        const response = xhr.responseJSON || {};
        const errors = response.errors || {};
        const keys = Object.keys(errors);

        if (keys.length) {
            const value = errors[keys[0]];

            if (Array.isArray(value) && value.length) {
                return value[0];
            }

            if (value) {
                return String(value);
            }
        }

        return response.message || fallback;
    }

    function showResult(success, message, data) {
        const $result = $('#campaignResult');

        let html = '<div class="fw-bold">'
            + (success
                ? '<i class="fas fa-circle-check me-1"></i>'
                : '<i class="fas fa-circle-exclamation me-1"></i>')
            + $('<div>').text(message || '').html()
            + '</div>';

        if (success && data) {
            html += '<div class="campaign-summary">'
                + '<div class="campaign-summary-item">'
                + '<strong>' + Number(data.recipient_count || 0) + '</strong>'
                + '<span>Tổng lượt gửi</span></div>'
                + '<div class="campaign-summary-item">'
                + '<strong>' + Number(data.email_count || 0) + '</strong>'
                + '<span>Email</span></div>'
                + '<div class="campaign-summary-item">'
                + '<strong>' + Number(data.messenger_count || 0) + '</strong>'
                + '<span>Messenger</span></div>'
                + '</div>'
                + '<div class="mt-2">'
                + 'Mã chiến dịch: <strong>'
                + $('<div>').text(data.campaign_id || '').html()
                + '</strong></div>';
        }

        $result
            .toggleClass('is-error', !success)
            .html(html)
            .stop(true, true)
            .slideDown(160);
    }

    function submitCampaign() {
        const $form = $('#campaignForm');
        const formElement = $form.get(0);
        const $button = $('#runCampaignButton');
        const oldHtml = $button.html();

        if (!formElement.checkValidity()) {
            formElement.reportValidity();
            return;
        }

        if (
            !$form.find('[name="send_email"]').is(':checked')
            && !$form.find('[name="send_messenger"]').is(':checked')
        ) {
            showResult(
                false,
                'Vui lòng chọn ít nhất một kênh gửi.'
            );
            return;
        }

        $button
            .prop('disabled', true)
            .html(
                '<i class="fas fa-spinner fa-spin me-1"></i>'
                + 'Đang chuyển sang n8n...'
            );

        $('#campaignResult').stop(true, true).hide();

        $.ajax({
            url: '/admin/admission-cms/nurture',
            method: 'POST',
            data: $form.serialize(),
            headers: {
                'X-CSRF-TOKEN': csrf
            }
        })
            .done(function (response) {
                showResult(
                    true,
                    response.message || 'Đã chạy chiến dịch.',
                    response.data || {}
                );

                if (typeof toastr !== 'undefined') {
                    toastr.success(
                        response.message || 'Đã chạy chiến dịch.'
                    );
                }
            })
            .fail(function (xhr) {
                const message = firstError(
                    xhr,
                    'Không thể chạy chiến dịch.'
                );

                showResult(false, message);

                if (typeof toastr !== 'undefined') {
                    toastr.error(message);
                }
            })
            .always(function () {
                $button.prop('disabled', false).html(oldHtml);
            });
    }

    $('#campaignForm').on('submit', function (event) {
        event.preventDefault();

        const message =
            'Chiến dịch sẽ gửi cho các Lead đạt điểm tối thiểu '
            + 'qua những kênh đã chọn. Tiếp tục chạy?';

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Chạy chiến dịch?',
                text: message,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Chạy chiến dịch',
                cancelButtonText: 'Hủy',
                confirmButtonColor: '#2563eb'
            }).then(function (result) {
                if (result.isConfirmed) {
                    submitCampaign();
                }
            });

            return;
        }

        if (window.confirm(message)) {
            submitCampaign();
        }
    });
});
