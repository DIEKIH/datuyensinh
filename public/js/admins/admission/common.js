(function (window, $) {
    'use strict';

    const csrf = $('meta[name="csrf-token"]').attr('content');

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': csrf
        }
    });

    function escapeHtml(value) {
        return String(value === null || value === undefined ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function formatDate(value) {
        if (!value) return '';

        const date = new Date(value);
        if (Number.isNaN(date.getTime())) return escapeHtml(value);

        return date.toLocaleString('vi-VN');
    }

    function getPaginator(response) {
        const payload = response && response.data !== undefined
            ? response.data
            : response;

        if (Array.isArray(payload)) {
            return {
                data: payload,
                current_page: 1,
                last_page: 1
            };
        }

        return payload || {
            data: [],
            current_page: 1,
            last_page: 1
        };
    }

    function renderPagination(paginator, container, loadFunction) {
        const $container = $(container);

        if (!paginator || !paginator.last_page || paginator.last_page <= 1) {
            $container.empty();
            return;
        }

        let html = '<ul class="pagination pagination-sm justify-content-end mb-0">';

        if (paginator.current_page > 1) {
            html += '<li class="page-item"><a class="page-link" href="#" data-page="' +
                (paginator.current_page - 1) + '">Trước</a></li>';
        }

        for (let page = 1; page <= paginator.last_page; page += 1) {
            const visible = page === 1 ||
                page === paginator.last_page ||
                (page >= paginator.current_page - 2 && page <= paginator.current_page + 2);

            if (visible) {
                html += '<li class="page-item ' +
                    (page === paginator.current_page ? 'active' : '') +
                    '"><a class="page-link" href="#" data-page="' + page + '">' + page + '</a></li>';
            } else if (page === paginator.current_page - 3 || page === paginator.current_page + 3) {
                html += '<li class="page-item disabled"><span class="page-link">…</span></li>';
            }
        }

        if (paginator.current_page < paginator.last_page) {
            html += '<li class="page-item"><a class="page-link" href="#" data-page="' +
                (paginator.current_page + 1) + '">Sau</a></li>';
        }

        html += '</ul>';
        $container.html(html);

        $container.off('click.admissionPagination').on(
            'click.admissionPagination',
            'a.page-link[data-page]',
            function (event) {
                event.preventDefault();
                loadFunction(Number($(this).data('page')) || 1);
            }
        );
    }

    function errorMessage(xhr, fallback) {
        if (xhr && xhr.responseJSON) {
            if (xhr.responseJSON.message) return xhr.responseJSON.message;
            if (xhr.responseJSON.error) return xhr.responseJSON.error;

            if (xhr.responseJSON.errors) {
                return Object.values(xhr.responseJSON.errors)
                    .reduce(function (all, messages) {
                        return all.concat(messages);
                    }, [])
                    .join('\n');
            }
        }

        return fallback || 'Không thể xử lý yêu cầu.';
    }

    function tableMessage(colspan, message, type) {
        const className = type === 'error'
            ? 'admission-error'
            : (type === 'loading' ? 'admission-loading' : 'admission-empty');

        return '<tr><td colspan="' + colspan + '" class="' + className + '">' +
            escapeHtml(message) + '</td></tr>';
    }

    window.AdmissionCms = {
        escapeHtml: escapeHtml,
        formatDate: formatDate,
        getPaginator: getPaginator,
        renderPagination: renderPagination,
        errorMessage: errorMessage,
        tableMessage: tableMessage
    };
})(window, jQuery);
