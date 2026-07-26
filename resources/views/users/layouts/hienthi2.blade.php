{{-- ==========================================================
   TRANG DANH SÁCH BÀI VIẾT — Redesign v3
   Biến nhận vào:
     $level1      — menu cấp 1 (object: id, name, slug)
     $currentMenu — menu hiện tại (object: id, name, slug)
     $posts       — ['baiviet_posts' => LengthAwarePaginator]
========================================================== --}}

@section('title', $level1->name ?? 'Tin tức')

@php
    $catMap = [
        2 => 'Tin tức',
        3 => 'Sự kiện',
        1 => 'Thông báo',
    ];

    $nlPosts = [];
    foreach ($posts['baiviet_posts'] as $p) {
        try {
            $menuSlug = $p->idmenu ? menu_full_slug($p->idmenu) : '';
            $postUrl = url(trim($menuSlug, '/') . '/' . $p->slug);
        } catch (\Throwable $e) {
            $postUrl = url($p->slug);
        }

        $nlPosts[] = [
            'id' => (int) $p->id,
            'tieude' => (string) ($p->tieude ?? ''),
            'slug' => (string) ($p->slug ?? ''),
            'danhmuc' => (int) ($p->danhmuc ?? 0),
            'loaitin' => (int) ($p->loaitin ?? 2),
            'ngaydang' => (string) ($p->ngaydang ?? ''),
            'views' => (int) ($p->views ?? 0),
            'image' => $p->image_url ? asset($p->image_url) : null,
            'hasPdf' => !empty($p->file_url),
            'pdfUrl' => !empty($p->file_url) ? asset($p->file_url) : null,
            'tacgia' => (string) ($p->tacgia_ten ?? ''),
            'url' => $postUrl,
            'catLabel' => $catMap[(int) ($p->danhmuc ?? 0)] ?? 'Khác',
        ];
    }

    $totalAll = $posts['baiviet_posts']->count();
@endphp

{{-- Breadcrumb --}}
<div class="backlink-v2 content">
    <a href="/">Trang chủ</a>
    <span>/</span>
    @if ($currentMenu && isset($level1) && $currentMenu->id !== $level1->id)
        <a href="/{{ $level1->slug }}">{{ $level1->name }}</a>
        <span>/</span>
        <a class="last-a">{{ $currentMenu->name }}</a>
    @else
        <a class="last-a">{{ $level1->name ?? 'Tin tức' }}</a>
    @endif
</div>

<section class="nl-section content">
    <div class="container">

        {{-- Page header --}}
        <div class="nl-header">
            <div class="nl-header-left">
                <h1>{{ $level1->name ?? 'Tin tức & Thông báo' }}</h1>
                <p>Cập nhật thông tin mới nhất từ nhà trường</p>
            </div>
            <div class="nl-header-right">
                <div class="nl-total-badge">{{ $totalAll }} bài viết</div>
                <div class="nl-viewtoggle" role="group" aria-label="Chế độ hiển thị">
                    <button class="nl-vbtn" id="nl-btn-list" onclick="nlSetView('list')" title="Danh sách">
                        <i class="fas fa-list" aria-hidden="true"></i>
                    </button>
                    <button class="nl-vbtn active" id="nl-btn-grid" onclick="nlSetView('grid')" title="Lưới">
                        <i class="fas fa-th" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
        </div>

        {{-- Toolbar: search + sort --}}
        <div class="nl-toolbar">
            <div class="nl-search-wrap">
                <i class="fas fa-search" aria-hidden="true"></i>
                <input class="nl-search" id="nl-search" type="search" placeholder="Tìm kiếm bài viết..."
                    autocomplete="off">
            </div>
            <div class="nl-sort-links">
                <span class="nl-sort-label">Sắp xếp:</span>
                <button class="nl-sl active" onclick="nlSetSort('date',this)">Mới nhất</button>
                <button class="nl-sl" onclick="nlSetSort('views',this)">Lượt xem</button>
                <button class="nl-sl" onclick="nlSetSort('title',this)">A–Z</button>
            </div>
            <span class="nl-result-count" id="nl-count"></span>
        </div>

        {{-- Content --}}
        <div id="nl-content"></div>

        {{-- Pagination --}}
        {{-- Pagination --}}
        <div class="nl-pagi" id="nl-pagi"></div>
        <div id="nl-offcanvas-wrapper"></div>

    </div>
</section>

<script>
    (function() {
        const POSTS = {!! json_encode($nlPosts, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!};

        const PER = 10;
        let filtered = [...POSTS];
        let view = localStorage.getItem('nl-view') || 'grid';
        let sort = 'date';
        let search = '';
        let page = 1;

        const CAT_CLS = {
            1: 'nl-cat-info',
            2: 'nl-cat-danger',
            3: 'nl-cat-success',
            4: 'nl-cat-warn',
            5: 'nl-cat-purple',
        };

        function fmtDate(s) {
            if (!s) return '';
            const d = new Date(s);
            if (isNaN(d)) return s;
            return String(d.getDate()).padStart(2, '0') + '/' +
                String(d.getMonth() + 1).padStart(2, '0') + '/' + d.getFullYear();
        }

        function isNew(s) {
            return s && (Date.now() - new Date(s)) / 86400000 <= 3;
        }

        function esc(s) {
            return String(s || '')
                .replace(/&/g, '&amp;').replace(/</g, '&lt;')
                .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        function escJs(s) {
            return String(s || '')
                .replace(/\\/g, '\\\\')
                .replace(/'/g, "\\'");
        }

        /* ── FILTER + SORT ── */
        function apply() {
            let res = POSTS.filter(function(p) {
                const needle = search.toLowerCase();
                return !needle ||
                    p.tieude.toLowerCase().includes(needle) ||
                    p.catLabel.toLowerCase().includes(needle) ||
                    p.tacgia.toLowerCase().includes(needle);
            });
            if (sort === 'date') res.sort(function(a, b) {
                return String(b.ngaydang).localeCompare(String(a.ngaydang));
            });
            if (sort === 'views') res.sort(function(a, b) {
                return b.views - a.views;
            });
            if (sort === 'title') res.sort(function(a, b) {
                return a.tieude.localeCompare(b.tieude, 'vi');
            });
            filtered = res;
            page = 1;
            render();
            renderPagi();
            document.getElementById('nl-count').textContent = res.length + ' bài viết';
        }

        /* ── RENDER CARD (grid) ── */
        /* ── RENDER CARD (grid) ── */
        /* ── RENDER CARD (grid) ── */
        function renderCard(p, i) {
            const catCls = CAT_CLS[p.danhmuc] || 'nl-cat-info';
            const newHtml = isNew(p.ngaydang) ?
                '<span class="nl-new-badge">Mới</span>' : '';
            const imgHtml = p.image ?
                `<img src="${esc(p.image)}" alt="${esc(p.tieude)}" loading="lazy">` :
                `<div class="nl-card-img-ph"><i class="fas fa-image" aria-hidden="true"></i></div>`;
            const pdf = p.hasPdf ?
                `<button class="nl-pdf-pill"
               onclick="event.stopPropagation();nlOpenPdf('${p.id}','${escJs(p.pdfUrl)}','${escJs(p.tieude)}')">
               <i class="fas fa-file-pdf"></i> PDF
           </button>` : '';

            return `<div class="nl-card" style="animation-delay:${i*0.04}s; cursor:pointer;"
            onclick="nlGo(event, this);" data-url="${esc(p.url)}">
        <div class="nl-card-img">
            ${imgHtml}
            ${newHtml}
            <span class="nl-cat-pill ${catCls}">${esc(p.catLabel)}</span>
        </div>
        <div class="nl-card-body">
            <div class="nl-card-title">${esc(p.tieude)}</div>
            <div class="nl-card-foot">
                <span class="nl-card-date"><i class="fas fa-calendar-alt"></i>${fmtDate(p.ngaydang)}</span>
                ${pdf}
                <span class="nl-card-views"><i class="fas fa-eye"></i>${p.views.toLocaleString('vi-VN')}</span>
            </div>
        </div>
    </div>`;
        }



        /* ── RENDER LIST ITEM ── */
        function renderItem(p, i) {
            const catCls = CAT_CLS[p.danhmuc] || 'nl-cat-info';
            const newHtml = isNew(p.ngaydang) ?
                '<span class="nl-new-badge nl-new-inline">Mới</span>' : '';
            const pdf = p.hasPdf ?
                `<button class="nl-pdf-pill"
           onclick="event.stopPropagation();nlOpenPdf('${p.id}','${escJs(p.pdfUrl)}','${escJs(p.tieude)}')">
           <i class="fas fa-file-pdf"></i> PDF
       </button>` : '';
            const author = p.tacgia ?
                `<span><i class="fas fa-user"></i>${esc(p.tacgia)}</span>` : '';

            return `<div class="nl-item" style="animation-delay:${i*0.04}s">
        <div class="nl-item-bar"></div>
        <div class="nl-item-body">
            <div class="nl-item-top">
                <span class="nl-cat-badge ${catCls}">${esc(p.catLabel)}</span>
                ${newHtml}
            </div>
            <a href="${esc(p.url)}" class="nl-item-title">${esc(p.tieude)}</a>
            <div class="nl-item-meta">
                <span><i class="fas fa-calendar-alt"></i>${fmtDate(p.ngaydang)}</span>
                ${author}${pdf}
                <span class="nl-item-views-right"><i class="fas fa-eye"></i>${p.views.toLocaleString('vi-VN')}</span>
            </div>
        </div>
    </div>`;
        }

        /* ── RENDER ── */
        function render() {
            const area = document.getElementById('nl-content');
            const slice = filtered.slice((page - 1) * PER, page * PER);
            if (!slice.length) {
                area.innerHTML =
                    '<div class="nl-empty"><i class="fas fa-search"></i><p>Không tìm thấy bài viết phù hợp</p></div>';
                return;
            }
            if (view === 'list') {
                area.innerHTML = '<div class="nl-list">' + slice.map(renderItem).join('') + '</div>';
            } else {
                area.innerHTML = '<div class="nl-grid">' + slice.map(renderCard).join('') + '</div>';
            }
        }

        /* ── PAGINATION ── */
        function renderPagi() {
            const pagi = document.getElementById('nl-pagi');
            const total = Math.ceil(filtered.length / PER);
            if (total < 2) {
                pagi.innerHTML = '';
                return;
            }

            const dots = '<span class="nl-pg-dots">…</span>';
            let h = '';

            // Prev
            h += `<button class="nl-pg-btn" ${page===1?'disabled':''} onclick="nlPage(${page-1})">
            <i class="fas fa-chevron-left"></i>
          </button>`;

            for (let i = 1; i <= total; i++) {
                // Luôn hiện: trang 1, trang cuối, trang hiện tại ±1
                const show = i === 1 || i === total || Math.abs(i - page) <= 1;
                if (!show) {
                    // Chèn dấu ... một lần
                    if (i === 2 || i === total - 1) h += dots;
                    continue;
                }
                h += `<button class="nl-pg-btn${i===page?' active':''}" onclick="nlPage(${i})">${i}</button>`;
            }

            // Next
            h += `<button class="nl-pg-btn" ${page===total?'disabled':''} onclick="nlPage(${page+1})">
            <i class="fas fa-chevron-right"></i>
          </button>`;

            pagi.innerHTML = h;
        }

        window.nlPage = function(p) {
            page = p;
            render();
            renderPagi();
            const top = document.getElementById('nl-content').getBoundingClientRect().top + window.scrollY - 90;
            window.scrollTo({
                top: top,
                behavior: 'smooth'
            });
        };
        window.nlSetView = function(v) {
            view = v;
            localStorage.setItem('nl-view', v);
            document.getElementById('nl-btn-list').classList.toggle('active', v === 'list');
            document.getElementById('nl-btn-grid').classList.toggle('active', v === 'grid');
            render();
        };
        window.nlSetSort = function(s, el) {
            sort = s;
            document.querySelectorAll('.nl-sl').forEach(function(b) {
                b.classList.remove('active');
            });
            el.classList.add('active');
            apply();
        };

        var searchTimer;
        document.getElementById('nl-search').addEventListener('input', function() {
            clearTimeout(searchTimer);
            var val = this.value;
            searchTimer = setTimeout(function() {
                search = val.toLowerCase().trim();
                apply();
            }, 220);
        });

        /* init */
        if (view === 'list') {
            document.getElementById('nl-btn-list').classList.add('active');
            document.getElementById('nl-btn-grid').classList.remove('active');
        }
        apply();





    })();


    function nlGo(e, el) {
        if (e.target.closest('.nl-pdf-pill')) return;
        var url = el.getAttribute('data-url');
        if (url) window.location.href = url;
    }

    function nlOpenPdf(id, pdfUrl, title) {
        const offcanvasId = 'nl-pdf-oc-' + id;

        if (!document.getElementById(offcanvasId)) {
            const div = document.createElement('div');
            div.innerHTML = `
            <div class="offcanvas offcanvas-end" tabindex="-1" id="${offcanvasId}"
                style="width:100%;">
                <div class="offcanvas-header border-bottom">
                    <h5 class="offcanvas-title" style="font-size:1.25rem; color: #0E4582; line-height:1.4;max-width:90%">${title}</h5>
                    <button type="button" class="btn-close" style="padding-right:28px;;" data-bs-dismiss="offcanvas"></button>
                </div>
                <div class="offcanvas-body p-2" id="${offcanvasId}-body"
                    style="overflow-y:auto;height:calc(100vh - 57px)">
                    <div style="text-align:center;padding:40px;color:#999">
                        <i class="fas fa-spinner fa-spin fa-2x"></i>
                        <p style="margin-top:12px;font-size:13px">Đang tải PDF...</p>
                    </div>
                </div>
            </div>`;
            document.getElementById('nl-offcanvas-wrapper').appendChild(div.firstElementChild);

            requestAnimationFrame(function() {
                const body = document.getElementById(offcanvasId + '-body');
                if (typeof pdfjsLib !== 'undefined') {
                    nlRenderPdf(pdfUrl, offcanvasId + '-body');
                } else {
                    body.innerHTML = `<div class="d-flex flex-column justify-content-center align-items-center h-100 text-muted">
                        <i class="bi bi-file-earmark-x" style="font-size:50px"></i>
                        <p class="mt-3">Không có bản Xem trước PDF</p>
                    </div>`;
                }
            });
        }

        const el = document.getElementById(offcanvasId);
        const oc = bootstrap.Offcanvas.getOrCreateInstance(el);
        oc.show();
    }

    function nlRenderPdf(pdfUrl, containerId) {
        const container = document.getElementById(containerId);
        container.innerHTML = '';
        pdfjsLib.getDocument(pdfUrl).promise.then(function(pdf) {
            for (let i = 1; i <= pdf.numPages; i++) {
                pdf.getPage(i).then(function(page) {
                    const vp0 = page.getViewport({
                        scale: 1
                    });
                    const scale = (container.clientWidth || 460) / vp0.width;
                    const vp = page.getViewport({
                        scale
                    });
                    const canvas = document.createElement('canvas');
                    canvas.width = vp.width;
                    canvas.height = vp.height;
                    canvas.style.cssText = 'width:100%;display:block;margin-bottom:8px';
                    container.appendChild(canvas);
                    page.render({
                        canvasContext: canvas.getContext('2d'),
                        viewport: vp
                    });
                });
            }
        }).catch(function(error) {
            console.error('Lỗi khi tải PDF:', error);
            container.innerHTML = `<div class="d-flex flex-column justify-content-center align-items-center h-100 text-muted">
                        <i class="bi bi-file-earmark-x" style="font-size:50px"></i>
                        <p class="mt-3">Không có bản Xem trước PDF</p>
                    </div>`;
        });
    }
</script>

<style>
    .nl-section *,
    .nl-section *::before,
    .nl-section *::after {
        box-sizing: border-box;
    }

    .nl-section {
        --nl-blue: #1d5fbf;
        --nl-navy: #0b2f5b;
        --nl-line: #e8ecf2;
        --nl-line-h: #c8d0de;
        --nl-muted: #6b7280;
        --nl-bg: #ffffff;
        --nl-bg2: #f4f6fa;
        --nl-text: #1f2937;
        --nl-r: 6px;
        --nl-rl: 8px;
    }

    .nl-section {
        padding: 0 0 64px;
        font-family: 'Roboto', 'SVN-Gilroy', sans-serif;
        color: var(--nl-text);
        background: #f4f6fa;
    }


    .nl-pdf-pill {
        /* thêm vào cuối rule hiện có */
        cursor: pointer;
        background: none;
    }



    /* ── Header ── */
    .nl-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        margin: 16px 0 20px;
        /* ← xuống dưới thêm cho tránh navbar */
        padding-bottom: 16px;
        border-bottom: 1px solid var(--nl-line);
        flex-wrap: wrap;
    }

    .nl-header-left h1 {
        font-size: 30px;
        /* ← to hơn */
        font-weight: 700;
        color: var(--nl-navy);
        margin: 0 0 4px;
        letter-spacing: -.025em;
        line-height: 1.2;
    }

    .nl-header-left p {
        font-size: 13.5px;
        color: var(--nl-muted);
        margin: 0;
    }

    .nl-header-right {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-shrink: 0;
    }

    .nl-total-badge {
        background: var(--nl-bg);
        border: 1px solid var(--nl-line);
        border-radius: 999px;
        padding: 4px 12px;
        font-size: 12px;
        color: var(--nl-muted);
        white-space: nowrap;
    }

    .nl-viewtoggle {
        display: flex;
        gap: 2px;
        background: var(--nl-bg);
        border: 1px solid var(--nl-line);
        border-radius: var(--nl-r);
        padding: 2px;
    }

    .nl-vbtn {
        width: 30px;
        height: 30px;
        border: none;
        border-radius: 5px;
        background: transparent;
        color: var(--nl-muted);
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        transition: all .13s;
    }

    .nl-vbtn:hover {
        color: var(--nl-text);
    }

    .nl-vbtn.active {
        background: var(--nl-bg2);
        color: var(--nl-text);
    }

    /* ── Toolbar ── */
    .nl-toolbar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 12px;
        margin-bottom: 18px;
    }

    .nl-search-wrap {
        position: relative;
        width: 260px;
        flex-shrink: 0;
    }

    .nl-search-wrap .fas {
        position: absolute;
        left: 10px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 13px;
        color: var(--nl-muted);
        pointer-events: none;
    }

    .nl-search {
        width: 100%;
        height: 34px;
        padding: 0 12px 0 32px;
        border: 1px solid var(--nl-line);
        border-radius: var(--nl-r);
        background: var(--nl-bg);
        font-size: 13px;
        color: var(--nl-text);
        outline: none;
        font-family: inherit;
        transition: border-color .15s;
    }

    .nl-search:focus {
        border-color: var(--nl-line-h);
    }

    .nl-sort-label {
        font-size: 12px;
        color: var(--nl-muted);
    }

    .nl-sort-links {
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .nl-sl {
        font-size: 12px;
        font-weight: 600;
        color: var(--nl-muted);
        background: none;
        border: none;
        border-bottom: 1.5px solid transparent;
        padding: 2px 4px;
        cursor: pointer;
        font-family: inherit;
        transition: all .13s;
    }

    .nl-sl:hover,
    .nl-sl.active {
        color: var(--nl-text);
        border-bottom-color: var(--nl-navy);
    }

    .nl-result-count {
        margin-left: auto;
        font-size: 12px;
        color: var(--nl-muted);
    }

    /* ── Category badge (list view) ── */
    .nl-cat-badge {
        display: inline-block;
        font-size: 10px;
        font-weight: 700;
        letter-spacing: .05em;
        text-transform: uppercase;
        padding: 2px 7px;
        border-radius: 4px;
    }

    /* ── Category pill (floated on card image) ── */
    .nl-cat-pill {
        position: absolute;
        bottom: 8px;
        left: 8px;
        font-size: 10px;
        font-weight: 700;
        letter-spacing: .04em;
        text-transform: uppercase;
        padding: 2px 7px;
        border-radius: 4px;
        pointer-events: none;
    }

    .nl-cat-info {
        background: #dbeafe;
        color: #1e40af;
    }

    .nl-cat-danger {
        background: #fee2e2;
        color: #991b1b;
    }

    .nl-cat-success {
        background: #dcfce7;
        color: #166534;
    }

    .nl-cat-warn {
        background: #fef3c7;
        color: #92400e;
    }

    .nl-cat-purple {
        background: #ede9fe;
        color: #6d28d9;
    }

    /* ── New badge ── */
    .nl-new-badge {
        position: absolute;
        top: 8px;
        right: 8px;
        font-size: 9px;
        font-weight: 800;
        letter-spacing: .06em;
        text-transform: uppercase;
        padding: 2px 7px;
        border-radius: 4px;
        background: #dc2626;
        color: #fff;
        pointer-events: none;
        z-index: 2;
    }

    .nl-new-inline {
        position: static;
        display: inline-block;
        font-size: 9px;
        font-weight: 800;
        padding: 2px 7px;
        border-radius: 4px;
        background: #dc2626;
        color: #fff;
        letter-spacing: .06em;
        text-transform: uppercase;
        vertical-align: middle;
    }

    /* ── GRID VIEW ── */
    .nl-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
        gap: 14px;
    }

    .nl-card {
        display: flex;
        flex-direction: column;
        background: var(--nl-bg);
        border: 1px solid var(--nl-line);
        border-radius: var(--nl-rl);
        /* ← 8px — nhỏ hơn trước */
        overflow: hidden;
        text-decoration: none;
        color: inherit;
        transition: border-color .15s, box-shadow .15s, transform .15s;
        animation: nlFadeUp .2s ease both;
    }

    .nl-card:hover {
        border-color: var(--nl-line-h);
        box-shadow: 0 4px 16px rgba(11, 47, 91, .08);
        transform: translateY(-2px);
    }

    .nl-card-img {
        position: relative;
        aspect-ratio: 16/9;
        background: var(--nl-bg2);
        overflow: hidden;
        flex-shrink: 0;
    }

    .nl-card-img img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
        transition: transform .3s;
    }

    .nl-card:hover .nl-card-img img {
        transform: scale(1.04);
    }

    .nl-card-img-ph {
        width: 100%;
        height: 100%;
        min-height: 135px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 26px;
        color: #d1d5db;
    }

    .nl-card-body {
        padding: 10px 12px 12px;
        display: flex;
        flex-direction: column;
        flex: 1;
    }

    .nl-card-title {
        font-size: 13.5px;
        font-weight: 600;
        color: var(--nl-text);
        line-height: 1.52;
        flex: 1;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
        margin-bottom: 10px;
        transition: color .14s;
    }

    .nl-card:hover .nl-card-title {
        color: var(--nl-blue);
    }

    .nl-card-foot {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 11px;
        color: var(--nl-muted);
        margin-top: auto;
    }

    .nl-card-date {
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    .nl-card-views {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        margin-left: auto;
        /* ← đẩy sang phải */
    }

    .nl-card-foot .fas {
        font-size: 11px;
    }

    /* ── LIST VIEW ── */
    .nl-list {
        display: flex;
        flex-direction: column;
    }

    .nl-item {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 14px 6px;
        border-bottom: 1px solid var(--nl-line);
        animation: nlFadeUp .2s ease both;
        transition: background .12s;
    }

    .nl-item:last-child {
        border-bottom: none;
    }

    .nl-item:hover {
        background: #f9fafb;
        border-radius: var(--nl-r);
    }

    .nl-item-bar {
        width: 2px;
        align-self: stretch;
        flex-shrink: 0;
        border-radius: 999px;
        background: var(--nl-line);
        margin-top: 2px;
        transition: background .14s;
    }

    .nl-item:hover .nl-item-bar {
        background: var(--nl-blue);
    }

    .nl-item-body {
        flex: 1;
        min-width: 0;
    }

    .nl-item-top {
        display: flex;
        align-items: center;
        gap: 6px;
        margin-bottom: 5px;
        flex-wrap: wrap;
    }

    .nl-item-title {
        display: block;
        font-size: 14px;
        font-weight: 600;
        color: var(--nl-text);
        line-height: 1.55;
        text-decoration: none;
        transition: color .13s;
    }

    .nl-item-title:hover {
        color: var(--nl-blue);
    }

    .nl-item-meta {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 7px;
        font-size: 11.5px;
        color: var(--nl-muted);
    }

    .nl-item-meta span {
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    .nl-item-meta .fas {
        font-size: 11px;
    }

    .nl-item-views-right {
        margin-left: auto;
        /* ← đẩy sang phải */
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    .nl-pdf-pill {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-size: 10.5px;
        font-weight: 600;
        color: var(--nl-muted);
        padding: 2px 8px;
        border: 1px solid var(--nl-line);
        border-radius: 4px;
        text-decoration: none;
        transition: all .13s;
    }

    .nl-pdf-pill:hover {
        border-color: var(--nl-line-h);
        color: var(--nl-text);
    }

    /* ── Empty ── */
    .nl-empty {
        padding: 56px 0;
        text-align: center;
        color: var(--nl-muted);
    }

    .nl-empty .fas {
        font-size: 32px;
        display: block;
        margin-bottom: 10px;
        opacity: .3;
    }

    .nl-empty p {
        font-size: 14px;
    }

    /* ── Pagination (redesign) ── */
    /* ── Pagination ── */
    .nl-pagi {
        display: flex;
        align-items: center;
        justify-content: center;
        flex-wrap: wrap;
        gap: 6px;
        margin-top: 32px;
        padding-top: 24px;
        border-top: 1px solid var(--nl-line);
    }

    .nl-pg-btn {
        min-width: 38px;
        height: 38px;
        padding: 0 10px;
        border: 1.5px solid var(--nl-line);
        border-radius: 8px;
        background: var(--nl-bg);
        color: var(--nl-muted);
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 13px;
        font-weight: 600;
        font-family: inherit;
        transition: all .15s;
    }

    .nl-pg-btn:hover:not([disabled]) {
        border-color: var(--nl-blue);
        color: var(--nl-blue);
        background: #eff6ff;
    }

    .nl-pg-btn.active {
        background: var(--nl-navy);
        border-color: var(--nl-navy);
        color: #fff;
        cursor: default;
        box-shadow: 0 2px 8px rgba(11, 47, 91, .22);
    }

    .nl-pg-btn[disabled] {
        opacity: .3;
        cursor: not-allowed;
    }

    .nl-pg-btn:first-child,
    .nl-pg-btn:last-child {
        background: var(--nl-bg2);
    }

    .nl-pg-dots {
        color: var(--nl-muted);
        font-size: 15px;
        padding: 0 2px;
        line-height: 38px;
        user-select: none;
    }

    /* ── Animation ── */
    @keyframes nlFadeUp {
        from {
            opacity: 0;
            transform: translateY(6px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* ── Responsive ── */
    @media (max-width: 768px) {
        .nl-header {
            margin: 24px 0 16px;
        }

        .nl-header-left h1 {
            font-size: 22px;
        }

        .nl-search-wrap {
            width: 100%;
        }

        .nl-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }

        .nl-total-badge {
            display: none;
        }
    }

    /* ← Bỏ rule 480px 1 cột — giữ nguyên 2 cột trên mọi mobile nhỏ */
    @media (max-width: 480px) {
        .nl-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
        }

        .nl-header-left h1 {
            font-size: 19px;
        }

        .nl-card-title {
            font-size: 12px;
            -webkit-line-clamp: 2;
        }

        .nl-card-body {
            padding: 8px 10px 10px;
        }

        .nl-card-foot {
            font-size: 10px;
            gap: 5px;
        }

        .nl-pg-btn {
            min-width: 32px;
            height: 32px;
            font-size: 12px;
        }
    }

    @media (max-width: 991.98px) {
    .backlink-v2 {
        margin-top: 20px;
    }
}
</style>
