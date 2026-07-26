$(function () {
    const csrf = $('meta[name="csrf-token"]').attr('content');

    function setMessage(type, message) {
        const color = type === 'success' ? 'text-success' : 'text-danger';
        $('#leadFormMessage').removeClass('text-success text-danger').addClass(color).text(message);
    }

    $('#publicLeadForm').on('submit', function (event) {
        event.preventDefault();

        const form = $(this);
        const submitButton = form.find('button[type="submit"]');
        submitButton.prop('disabled', true).text('Dang gui...');
        setMessage('success', '');

        $.ajax({
            url: '/dang-ky-tu-van',
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
