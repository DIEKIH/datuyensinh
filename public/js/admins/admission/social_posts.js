$(function () {
    'use strict';

    if (!$('#admissionSocialPostsPage').length) return;

    const Cms = window.AdmissionCms;

    function parsePlatforms(value) {
        if (Array.isArray(value)) return value;
        try { return JSON.parse(value || '[]'); } catch (error) { return []; }
    }

    function loadSocialPosts(page) {
        page = page || 1;
        $('#socialPostsBody').html(Cms.tableMessage(4, 'Đang tải lịch sử bài đăng...', 'loading'));

        $.get('/admin/admission-cms/social-posts', { page: page })
            .done(function (response) {
                const paginator = Cms.getPaginator(response);
                const data = paginator.data || [];

                if (!data.length) {
                    $('#socialPostsBody').html(Cms.tableMessage(4, 'Chưa có bài viết nào được ghi nhận.', 'empty'));
                    $('#socialPostsPagination').empty();
                    return;
                }

                $('#socialPostsBody').html(data.map(function (post) {
                    const platforms = parsePlatforms(post.platforms);
                    const badges = platforms.map(function (platform) {
                        return '<span class="admission-platform-badge">' + Cms.escapeHtml(platform) + '</span>';
                    }).join('');
                    const image = post.image_url
                        ? '<img src="' + Cms.escapeHtml(post.image_url) + '" class="admission-image-thumb" alt="Ảnh bài đăng">'
                        : '<span class="text-muted">Không có ảnh</span>';

                    return '<tr>' +
                        '<td>' + (badges || '<span class="text-muted">Không rõ</span>') + '</td>' +
                        '<td><strong>' + Cms.escapeHtml(post.title || 'Bài viết tự động') + '</strong>' +
                            '<div class="admission-answer mt-2 small">' + Cms.escapeHtml(post.content || '') + '</div></td>' +
                        '<td>' + image + '</td>' +
                        '<td class="text-nowrap">' + Cms.formatDate(post.published_at || post.created_at) + '</td>' +
                        '</tr>';
                }).join(''));

                Cms.renderPagination(paginator, '#socialPostsPagination', loadSocialPosts);
            })
            .fail(function (xhr) {
                $('#socialPostsBody').html(Cms.tableMessage(4, Cms.errorMessage(xhr, 'Không tải được lịch sử bài đăng.'), 'error'));
            });
    }

    $('#reloadSocialPosts').on('click', function () { loadSocialPosts(1); });
    loadSocialPosts(1);
});
