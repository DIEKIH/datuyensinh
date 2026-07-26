fetch('/menus')
    .then(res => res.json())
    .then(res => {
        if (res.data && res.data.length > 0) {
            renderMenu(res.data);
        }
    })
    .catch(err => console.warn('Không thể tải menu:', err));

function renderMenu(menuArray) {
    const navUl = document.getElementById('dynamic-menu');
    if (!navUl) return;
    menuArray
        .filter(item => item.slug !== 'thong-bao') // 👈 bỏ thông báo
        .forEach(item => {
            // if (item.slug === 'thong-tin-tuyen-sinh') {
            //     item.children = []; // 👈 ẩn children cấp 2,3
            // }
            navUl.appendChild(buildMenuItem(item));
        });
}

function buildMenuItem(item) {
    const url = '/' + item.slug;

    if (item.children && item.children.length > 0) {
        const li = document.createElement('li');
        li.className = 'nav-item dropdown';

        const a = document.createElement('a');
        a.className = 'nav-link';
        a.href = url;
        a.setAttribute('role', 'button');
        a.textContent = item.name;

        const ul = document.createElement('ul');
        ul.className = 'dropdown-menu';
        // 👇 truyền slug cha xuống
        item.children.forEach(child => ul.appendChild(buildMenuChildItem(child, item.slug)));

        li.appendChild(a);
        li.appendChild(ul);
        return li;
    } else {
        const li = document.createElement('li');
        li.className = 'nav-item';

        const a = document.createElement('a');
        a.className = 'nav-link';
        a.href = url;
        a.textContent = item.name;

        li.appendChild(a);
        return li;
    }
}

function buildMenuChildItem(item, parentSlug = '') {
    const url = '/' + (parentSlug ? parentSlug + '/' : '') + item.slug;

    if (item.children && item.children.length > 0) {
        const li = document.createElement('li');
        li.className = 'dropdown-submenu';

        const a = document.createElement('a');
        a.className = 'dropdown-item';
        a.href = url;
        a.textContent = item.name;

        const ul = document.createElement('ul');
        ul.className = 'dropdown-menu';
        // 👇 truyền slug đầy đủ xuống cấp 3
        item.children.forEach(child => ul.appendChild(buildMenuChildItem(child, parentSlug + '/' + item.slug)));

        li.appendChild(a);
        li.appendChild(ul);
        return li;
    } else {
        const li = document.createElement('li');
        const a = document.createElement('a');
        a.className = 'dropdown-item';
        a.href = url;
        a.textContent = item.name;
        li.appendChild(a);
        return li;
    }
}


function getMenuUrl(item) {
    switch (parseInt(item.loaimanhinh)) {
        case 1:
            return '/' + item.slug;
        case 2:
            return '/' + item.slug;
        default:
            return '/' + item.slug;
    }
}

$(document).ready(function () {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
});

function formatDate(dateStr) {
    const d = new Date(dateStr);
    const day = d.getDate().toString().padStart(2, '0');
    const month = (d.getMonth() + 1).toString().padStart(2, '0');
    const year = d.getFullYear();
    return `${day}/${month}/${year}`;
}

// banner
$.ajax({
    type: "get",
    url: "/banners",
    timeout: 10000,
    success: function (res) {
        renderBanners(res.data || []);
    },
    error: function (xhr) {
        console.error('Lỗi /banners:', xhr.status, xhr.responseText);
        renderBanners([]);
    }
});

function renderBanners(banners) {
    const swiperWrapper = $('#banner-list');
    swiperWrapper.empty();

    if (!banners || !Array.isArray(banners) || banners.length === 0) {
        // Không có banner → hiển thị 1 slide mặc định
        swiperWrapper.append(`
            <div class="swiper-slide">
                <a class="d-block" title="">
                    <img class="banner-ts-image img-fluid" src="./images/system/Rectangle_ngang.jpg" 
                        alt="Banner mặc định">
                </a>
            </div>
        `);
    } else {
        banners.forEach(function (banner) {
            const imgSrc = banner.image_url || './images/system/Rectangle_ngang.jpg'; // fallback
            const slideHTML = `
                <div class="swiper-slide">
                    <a class="d-block" title="">
                        <img class="banner-ts-image img-fluid" src="${imgSrc}" 
                            alt="" itemprop="contentUrl">
                    </a>
                </div>
            `;
            swiperWrapper.append(slideHTML);
        });
    }

    // Khởi động lại Swiper
    initSwiper();
}


function initSwiper() {
    const bannerSwiper = new Swiper('.bannerSwiper', {
        loop: true,
        autoplay: {
            delay: 4000,
            disableOnInteraction: false
        },
        pagination: {
            el: '.bannerSwiper .swiper-pagination',
            clickable: true
        },
        navigation: {
            nextEl: '.bannerSwiper .swiper-button-next',
            prevEl: '.bannerSwiper .swiper-button-prev'
        }
    });
}


// tintucnoibat
$.ajax({
    type: "get",
    url: "/tintucnoibat",
    timeout: 10000,
    success: function (res) {
        renderTintucnoibat(res.data || []);
    },
    error: function (xhr) {
        console.error('Lỗi /tintucnoibat:', xhr.status, xhr.responseText);
        renderTintucnoibat([]);
    }
});


// Thay toàn bộ function renderTintucnoibat
let featuredNewsSwiper = null;

function initFeaturedNewsSwiper() {
    if (featuredNewsSwiper) {
        featuredNewsSwiper.destroy(true, true);
    }

    featuredNewsSwiper = new Swiper('.featuredSwiper', {
        loop: true,
        slidesPerView: 1,
        spaceBetween: 0,
        speed: 700,
        autoHeight: false,
        observer: true,
        observeParents: true,
        autoplay: {
            delay: 4000,
            disableOnInteraction: false,
            pauseOnMouseEnter: true
        },
        pagination: {
            el: '.featured-swiper-pagination',
            clickable: true
        },
        navigation: {
            nextEl: '.featured-swiper-next',
            prevEl: '.featured-swiper-prev'
        }
    });
}

// function renderTintucnoibat(tintucnoibatArray) {
//     const wrapper = $('#featured-news-wrapper');
//     wrapper.empty();

//     // Chỉ lấy 5 bài đầu
//     // Moi sua: slider chi render toi da 5 bai gan nhat tra ve tu backend.
//     const sliced = Array.isArray(tintucnoibatArray)
//         ? tintucnoibatArray.slice(0, 5)
//         : [];

//     sliced.forEach(function (tintucnoibat) {
//         const articleUrl = `/${tintucnoibat.menu_slug ?? 'tin-tuc'}/${tintucnoibat.slug}`;

//         const html = `
//             <div class="swiper-slide">
//                 <div class="news-item">
//                     <div class="news-img hover01">
//                         <a href="${articleUrl}">
//                             <figure>
//                                 <img src="${tintucnoibat.image_url}" alt="${tintucnoibat.tieude}">
//                             </figure>
//                         </a>
//                     </div>

//                     <div class="news-content">
//                         <div class="news-title one-line ellipsis-text pb-2">
//                             <h3>
//                                 <a href="${articleUrl}" class="text-hover">
//                                     ${tintucnoibat.tieude}
//                                 </a>
//                             </h3>
//                         </div>
//                         <div class="news-summary text-sub two-line ellipsis-text">
//                             <p>${tintucnoibat.tomtat}</p>
//                         </div>
//                     </div>

//                     <div class="news-info d-flex justify-content-start gap-3 text-sub">
//                         <div class="news-date me-3">
//                             <i class="fa-regular fa-clock me-1"></i>
//                             ${formatDate(tintucnoibat.ngaydang)}
//                         </div>
//                         <div class="news-views">
//                             <i class="fa-regular fa-eye me-1"></i>
//                             ${tintucnoibat.views}
//                         </div>
//                     </div>
//                 </div>
//             </div>
//         `;
//         wrapper.append(html);
//     });

//     // Khởi Swiper SAU khi append xong
//     initFeaturedNewsSwiper();
// }



function renderTintucnoibat(tintucnoibatArray) {
    const wrapper = $('#featured-news-wrapper');
    wrapper.empty();

    // Chỉ lấy 5 bài đầu
    // Moi sua: slider chi render toi da 5 bai gan nhat tra ve tu backend.
    const sliced = Array.isArray(tintucnoibatArray)
        ? tintucnoibatArray.slice(0, 5)
        : [];

    sliced.forEach(function (tintucnoibat) {
        // tintucnoibat
        const articleUrl = `/${tintucnoibat.full_menu_slug ?? 'tin-tuc'}/${tintucnoibat.slug}`;
        const html = `
            <div class="swiper-slide">
                <div class="news-item">
                    <div class="news-img hover01">
                        <a href="${articleUrl}">
                            <figure>
                                <img src="${tintucnoibat.image_url}" alt="${tintucnoibat.tieude}">
                            </figure>
                        </a>
                    </div>

                    <div class="news-content">
                        <div class="news-title one-line ellipsis-text pb-2">
                            <h3>
                                <a href="${articleUrl}" class="text-hover">
                                    ${tintucnoibat.tieude}
                                </a>
                            </h3>
                        </div>
                        <div class="news-summary text-sub two-line ellipsis-text">
                            <p>${tintucnoibat.tomtat}</p>
                        </div>
                    </div>

                    <div class="news-info d-flex justify-content-start gap-3 text-sub">
                        <div class="news-date me-3">
                            <i class="fa-regular fa-clock me-1"></i>
                            ${formatDate(tintucnoibat.ngaydang)}
                        </div>
                        <div class="news-views">
                            <i class="fa-regular fa-eye me-1"></i>
                            ${tintucnoibat.views}
                        </div>
                    </div>
                </div>
            </div>
        `;
        wrapper.append(html);
    });

    // Khởi Swiper SAU khi append xong
    initFeaturedNewsSwiper();
}


// tintucnho
$.ajax({
    type: "get",
    url: "/tintucnho",
    timeout: 10000,
    success: function (res) {
        renderTintucnho(res.data || []);
    },
    error: function (xhr) {
        console.error('Lỗi /tintucnho:', xhr.status, xhr.responseText);
        renderTintucnho([]);
    }
});



// function renderTintucnho(tintucnhoArray) {
//     const container = $('#tin-tuc-nho-container');
//     container.empty();

//     const slicedData = tintucnhoArray.slice(0, 5);  // chỉ lấy 5 bài

//     slicedData.forEach(function (tintucnho, index) {
//         const isLast = index === slicedData.length - 1;  // so sánh đúng

//         const html = `
//             <div class="col-nho">
//                 <div class="position-relative ${isLast ? 'fake-news-card' : ''}">
//                     ${isLast ? `
//                     <div class="fake-news-blur">` : ''}

//                         <div class="ratio-container news-img mb-3">
//                             <a href="/${tintucnho.menu_slug ?? 'tin-tuc'}/${tintucnho.slug}">
//         <figure>
//             <img class="img-fluid" src="${tintucnho.image_url}" alt="">
//         </figure>
//     </a>
//                         </div>

//                         <div class="news-content mt-0">
//                             <div class="news-title left-title">
//                                 <h3>
//                                     <a class="ellipsis-text two-line text-hover"
//                href="/${tintucnho.menu_slug ?? 'tin-tuc'}/${tintucnho.slug}">
//                 ${tintucnho.tieude}
//             </a>
//                                 </h3>
//                             </div>
//                             <div class="news-summary two-line ellipsis-text text-sub">
//                                 <p>${tintucnho.tomtat}</p>
//                             </div>
//                             <div class="news-info mt-3 d-flex justify-content-between align-items-center text-sub">
//                                 <div class="news-date">
//                                     <i class="fa-regular fa-clock me-1"></i> ${formatDate(tintucnho.ngaydang)}
//                                 </div>
//                                 <div class="news-views">
//                                     <i class="fa-regular fa-eye"></i> ${tintucnho.views}
//                                 </div>
//                             </div>
//                         </div>

//                         ${isLast ? `
//                     </div>
//                     <a href="/tin-tuc/"
//                         class="position-absolute top-0 start-0 w-100 h-100 d-none d-lg-flex align-items-center justify-content-center fake-news-overlay text-decoration-none">
//                         <div class="d-inline-block button">
//                             Xem thêm
//                             <svg fill="currentColor" viewBox="0 0 24 24" class="icon">
//                                 <path clip-rule="evenodd"
//                                     d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zm4.28 10.28a.75.75 0 000-1.06l-3-3a.75.75 0 10-1.06 1.06l1.72 1.72H8.25a.75.75 0 000 1.5h5.69l-1.72 1.72a.75.75 0 101.06 1.06l3-3z"
// fill-rule="evenodd"></path>
//                             </svg>
//                         </div>
//                     </a>
//                     <a href="/tin-tuc/"
//                         class="position-absolute top-0 start-0 w-100 h-100 d-flex d-lg-none align-items-center justify-content-center fake-news-overlay text-decoration-none">
//                         <div class="d-inline-block button">
//                             Xem thêm
//                             <svg fill="currentColor" viewBox="0 0 24 24" class="icon">
//                                 <path clip-rule="evenodd"
//                                     d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zm4.28 10.28a.75.75 0 000-1.06l-3-3a.75.75 0 10-1.06 1.06l1.72 1.72H8.25a.75.75 0 000 1.5h5.69l-1.72 1.72a.75.75 0 101.06 1.06l3-3z"
//                                     fill-rule="evenodd"></path>
//                             </svg>
//                         </div>
//                     </a>
//                     ` : ''}
//                 </div>
//             </div>
//         `;

//         container.append(html);
//     });
// }

function renderTintucnho(tintucnhoArray) {
    const container = $('#tin-tuc-nho-container');
    container.empty();

    const slicedData = tintucnhoArray.slice(0, 5);  // chỉ lấy 5 bài

    slicedData.forEach(function (tintucnho, index) {
        const isLast = index === slicedData.length - 1;  // so sánh đúng

        const html = `
            <div class="col-nho">
                <div class="position-relative ${isLast ? 'fake-news-card' : ''}">
                    ${isLast ? `
                    <div class="fake-news-blur">` : ''}

                        <div class="ratio-container news-img mb-3">
                            <a href="/${tintucnho.full_menu_slug ?? 'tin-tuc'}/${tintucnho.slug}">
        <figure>
            <img class="img-fluid" src="${tintucnho.image_url}" alt="">
        </figure>
    </a>
                        </div>

                        <div class="news-content mt-0">
                            <div class="news-title left-title">
                                <h3>
                                    <a class="ellipsis-text two-line text-hover"
               href="/${tintucnho.full_menu_slug ?? 'tin-tuc'}/${tintucnho.slug}">
                ${tintucnho.tieude}
            </a>
                                </h3>
                            </div>
                            <div class="news-summary two-line ellipsis-text text-sub">
                                <p>${tintucnho.tomtat}</p>
                            </div>
                            <div class="news-info mt-3 d-flex justify-content-between align-items-center text-sub">
                                <div class="news-date">
                                    <i class="fa-regular fa-clock me-1"></i> ${formatDate(tintucnho.ngaydang)}
                                </div>
                                <div class="news-views">
                                    <i class="fa-regular fa-eye"></i> ${tintucnho.views}
                                </div>
                            </div>
                        </div>

                        ${isLast ? `
                    </div>
                    <a href="/tin-tuc/"
                        class="position-absolute top-0 start-0 w-100 h-100 d-none d-lg-flex align-items-center justify-content-center fake-news-overlay text-decoration-none">
                        <div class="d-inline-block button">
                            Xem thêm
                            <svg fill="currentColor" viewBox="0 0 24 24" class="icon">
                                <path clip-rule="evenodd"
                                    d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zm4.28 10.28a.75.75 0 000-1.06l-3-3a.75.75 0 10-1.06 1.06l1.72 1.72H8.25a.75.75 0 000 1.5h5.69l-1.72 1.72a.75.75 0 101.06 1.06l3-3z"
fill-rule="evenodd"></path>
                            </svg>
                        </div>
                    </a>
<a href="/tin-tuc/"
                        class="position-absolute top-0 start-0 w-100 h-100 d-flex d-lg-none align-items-center justify-content-center fake-news-overlay text-decoration-none">
                        <div class="d-inline-block button">
                            Xem thêm
                            <svg fill="currentColor" viewBox="0 0 24 24" class="icon">
                                <path clip-rule="evenodd"
                                    d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zm4.28 10.28a.75.75 0 000-1.06l-3-3a.75.75 0 10-1.06 1.06l1.72 1.72H8.25a.75.75 0 000 1.5h5.69l-1.72 1.72a.75.75 0 101.06 1.06l3-3z"
                                    fill-rule="evenodd"></path>
                            </svg>
                        </div>
                    </a>
                    ` : ''}
                </div>
            </div>
        `;

        container.append(html);
    });
}

// sukiennho
$.ajax({
    type: "GET",
    url: "/sukiennho",
    timeout: 10000,
    success: function (res) {
        renderSukiennho(res.data || []);
    },
    error: function (xhr) {
        console.error('Lỗi /sukiennho:', xhr.status, xhr.responseText);
        renderSukiennho([]);
    }
});


function formatDate(datetime) {

    if (!datetime) return '';

    const date = new Date(datetime);

    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();

    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');

    return `${day}/${month}/${year} ${hours}:${minutes}`;
}

function parseEventDateTime(datetime) {
    if (!datetime) return null;

    const normalized = String(datetime).replace(' ', 'T');
    const date = new Date(normalized);

    return Number.isNaN(date.getTime()) ? null : date;
}

function getEventStatus(sukien) {
    const now = new Date();
    const startTime = parseEventDateTime(sukien.thoigian_bd);
    const endTime = parseEventDateTime(sukien.thoigian_kt);

    if (startTime && now < startTime) {
        return {
            key: 'upcoming',
            label: 'Sắp diễn ra',
            badgeClass: 'sk-badge--upcoming'
        };
    }

    if (!endTime || now <= endTime) {
        return {
            key: 'active',
            label: 'Đang diễn ra',
            badgeClass: 'sk-badge--active'
        };
    }

    return {
        key: 'done',
        label: 'Đã diễn ra',
        badgeClass: 'sk-badge--done'
    };
}

function renderEventBadge(sukien) {
    const status = getEventStatus(sukien);

    return `<span class="sk-badge ${status.badgeClass}" data-event-status="${status.key}">${status.label}</span>`;
}

function getEventTimeText(sukien) {
    const status = getEventStatus(sukien);
    const startText = sukien.thoigian_bd ? formatDate(sukien.thoigian_bd) : '';
    const endText = sukien.thoigian_kt ? formatDate(sukien.thoigian_kt) : '';

    if (status.key === 'done') {
        return endText ? `Đã kết thúc lúc ${endText}` : 'Đã kết thúc';
    }

    if (status.key === 'active') {
        return startText ? `Đang diễn ra lúc ${startText}` : 'Đang diễn ra';
    }

    return startText ? `Sắp diễn ra lúc ${startText}` : 'Sắp diễn ra';
}

let sukienStatusTimer = null;

function refreshSukienStatuses() {
    document.querySelectorAll('#sukien-container [data-sukien-card]').forEach((card) => {
        const sukien = {
            thoigian_bd: card.dataset.thoigianBd || '',
            thoigian_kt: card.dataset.thoigianKt || ''
        };
        const timeElement = card.querySelector('[data-event-time]');

        if (!timeElement) return;

        timeElement.innerHTML = `<i class="fa-regular fa-clock"></i>${getEventTimeText(sukien)}`;
    });
}

function startSukienStatusAutoRefresh() {
    if (sukienStatusTimer) {
        clearInterval(sukienStatusTimer);
    }

    refreshSukienStatuses();
    sukienStatusTimer = setInterval(refreshSukienStatuses, 30000);
}

function renderSukiennho(sukiennhoArray) {
    const container = $('#sukien-container');
    container.empty();

    sukiennhoArray.forEach((sukien, index) => {
        const isLast = index === sukiennhoArray.length - 1;

        // Chỉ hiện thời gian bắt đầu, không xử lý trạng thái
        const timeText = getEventTimeText(sukien);

        const html = `
            <div class="position-relative ${isLast ? 'fake-news-card' : ''}"
                 data-sukien-card="true"
                 data-thoigian-bd="${sukien.thoigian_bd || ''}"
                 data-thoigian-kt="${sukien.thoigian_kt || ''}">
                <div class="${isLast ? 'fake-news-blur ' : ''}sk-card">

                    <div class="sk-thumb hover01">
                        <a href="/${sukien.menu_slug ?? 'su-kien'}/${sukien.slug}">
                            <figure>
                                <img src="${sukien.image_url}"
                                     alt="${sukien.tieude}"
                                     onerror="this.style.opacity='0'">
                            </figure>
                        </a>
                    </div>

                    <div class="sk-body">
                        <a href="/${sukien.menu_slug ?? 'su-kien'}/${sukien.slug}" class="sk-title">
                            ${sukien.tieude}
                        </a>

                        ${sukien.tomtat ? `
                        <div class="sk-summary">
                            ${sukien.tomtat}
                        </div>` : ''}

                        <div class="sk-meta">
                            ${timeText ? `
                            <span class="sk-meta-time" data-event-time="true">
                                <i class="fa-regular fa-clock"></i>${timeText}
                            </span>` : '<span class="sk-meta-time"></span>'}
                            ${sukien.diadiem ? `
                            <span class="sk-meta-place">
                                <i class="fa-solid fa-location-dot"></i>${sukien.diadiem}
                            </span>` : ''}
                        </div>
                    </div>

                </div>

                ${isLast ? `
                    <a href="/su-kien/"
                       class="position-absolute top-0 start-0 w-100 h-100 d-none d-lg-flex align-items-center justify-content-center fake-news-overlay text-decoration-none">
                        <div class="d-inline-block button">
                            Sự kiện khác
                            <svg fill="currentColor" viewBox="0 0 24 24" class="icon">
                                <path clip-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zm4.28 10.28a.75.75 0 000-1.06l-3-3a.75.75 0 10-1.06 1.06l1.72 1.72H8.25a.75.75 0 000 1.5h5.69l-1.72 1.72a.75.75 0 101.06 1.06l3-3z" fill-rule="evenodd"></path>
                            </svg>
                        </div>
                    </a>
                    <a href="/su-kien/"
                       class="position-absolute top-0 start-0 w-100 h-100 d-flex d-lg-none align-items-center justify-content-center fake-news-overlay text-decoration-none">
                        <div class="d-inline-block button">
                            Sự kiện khác
                            <svg fill="currentColor" viewBox="0 0 24 24" class="icon">
                                <path clip-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zm4.28 10.28a.75.75 0 000-1.06l-3-3a.75.75 0 10-1.06 1.06l1.72 1.72H8.25a.75.75 0 000 1.5h5.69l-1.72 1.72a.75.75 0 101.06 1.06l3-3z" fill-rule="evenodd"></path>
                            </svg>
                        </div>
                    </a>
                ` : ''}
            </div>
        `;

        container.append(html);
    });

    startSukienStatusAutoRefresh();
}


// thongbaonho
// thongbaonho
$.ajax({
    type: "GET",
    url: "/thongbaonho",
    timeout: 10000,
    success: function (res) {
        renderThongbaonho(res.data || []);
    },
    error: function (xhr) {
        console.error('Lỗi /thongbaonho:', xhr.status, xhr.responseText);
        renderThongbaonho([]);
    }
});

function createOffcanvas(id, title) {
    return `
        <div class="offcanvas offcanvas-end col-6" tabindex="-1" id="${id}"
            aria-labelledby="${id}-label" style="height: 100vh;">
            <div class="offcanvas-header">
                <h5 id="${id}-label">${title}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Đóng"></button>
            </div>
            <div class="offcanvas-body p-0" style="height: calc(100vh - 56px); overflow-y: auto;">
                <div id="${id}-pdf" class="p-3"></div>
            </div>
        </div>
    `;
}


function mountThongbaoTrack(trackElement, listHtml, shouldAnimate) {
    if (!trackElement) return;

    if (!listHtml) {
        trackElement.innerHTML = '';
        trackElement.classList.remove('is-animated');
        return;
    }

    trackElement.innerHTML = shouldAnimate
        ? `
            <ul class="mb-0 ps-0 list-with-marker thongbao-list">${listHtml}</ul>
            <ul class="mb-0 ps-0 list-with-marker thongbao-list" aria-hidden="true">${listHtml}</ul>
        `
        : `<ul class="mb-0 ps-0 list-with-marker thongbao-list">${listHtml}</ul>`;

    trackElement.classList.toggle('is-animated', shouldAnimate);
}


// Moi sua: override lai phan thong bao theo giao dien goc va auto-scroll trong khung.
function createThongbaonho(item, offcanvasId, isLast = false) {
    let isNew = false;

    if (item.ngaydang) {
        const postDate = new Date(item.ngaydang);
        const now = new Date();
        const diffTime = now - postDate;
        const diffDays = diffTime / (1000 * 60 * 60 * 24);

        if (diffDays <= 3) {
            isNew = true;
        }
    }

    const newBadge = isNew
        ? `<img src="/images/system/new.gif" alt="New" class="me-1">`
        : '';

    return `
        <li class="ellipsis-text two-line mb-4 noti-item"
            data-bs-toggle="offcanvas"
            data-bs-target="#${offcanvasId}">
            ${newBadge}${item.tieude}
        </li>
    `;
}

// Moi sua: tu dong cuon ben trong ul cu, khong lam bung layout.
function setupThongbaoAutoScroll(element) {
    if (!element) return;

    if (element._autoScrollTimer) {
        clearInterval(element._autoScrollTimer);
        element._autoScrollTimer = null;
    }

    element.scrollTop = 0;

    const maxScroll = element.scrollHeight - element.clientHeight;
    if (maxScroll <= 0) return;

    const startAutoScroll = function () {
        element._autoScrollTimer = setInterval(function () {
            if (element.scrollTop >= maxScroll) {
                element.scrollTop = 0;
            } else {
                element.scrollTop += 1;
            }
        }, 45);
    };

    startAutoScroll();

    element.onmouseenter = function () {
        if (element._autoScrollTimer) {
            clearInterval(element._autoScrollTimer);
            element._autoScrollTimer = null;
        }
    };

    element.onmouseleave = function () {
        if (!element._autoScrollTimer) {
            startAutoScroll();
        }
    };
}

// Moi sua: render thong bao vao danh sach desktop/mobile goc roi bat auto-scroll.
function renderThongbaonho(data) {
    const container = $('#thongbaonho');
    const mobileContainer = $('#thongbaonho-mobile');
    const offcanvasWrapper = $('#offcanvas-wrapper');
    const offcanvasWrapperMobile = $('#offcanvas-wrapper-mobile');
    const items = Array.isArray(data) ? data : [];

    container.empty();
    mobileContainer.empty();
    offcanvasWrapper.empty();
    offcanvasWrapperMobile.empty();

    items.forEach(function (item, index) {
        const offcanvasId = `offcanvas${item.id}`;
        const pdfContainerId = `${offcanvasId}-pdf`;
        const offcanvasIdMobile = `offcanvas-mobile-${item.id}`;
        const pdfContainerIdMobile = `${offcanvasIdMobile}-pdf`;
        const isLast = index === items.length - 1;

        container.append(createThongbaonho(item, offcanvasId, isLast));
        mobileContainer.append(createThongbaonho(item, offcanvasIdMobile, isLast));
        offcanvasWrapper.append(createOffcanvas(offcanvasId, item.tieude));
        offcanvasWrapperMobile.append(createOffcanvas(offcanvasIdMobile, item.tieude));

        setTimeout(function () {
            const pdfContainer = document.getElementById(pdfContainerId);
            if (!pdfContainer) return;
            if (item.file_url) {
                renderPDF(item.file_url, pdfContainerId);
            } else {
                pdfContainer.innerHTML = `
                    <div class="d-flex flex-column justify-content-center align-items-center h-100 text-muted">
                        <i class="bi bi-file-earmark-x" style="font-size:50px"></i>
                        <p class="mt-3">Không có bản Xem trước PDF</p>
                    </div>
                `;
            }
        }, 0);

        setTimeout(function () {
            const pdfContainerMobile = document.getElementById(pdfContainerIdMobile);
            if (!pdfContainerMobile) return;
            if (item.file_url) {
                renderPDF(item.file_url, pdfContainerIdMobile);
            } else {
                pdfContainerMobile.innerHTML = `
                    <div class="d-flex flex-column justify-content-center align-items-center h-100 text-muted">
                        <i class="bi bi-file-earmark-x" style="font-size:50px"></i>
                        <p class="mt-3">Không có bản Xem trước PDF</p>
                    </div>
                `;
            }
        }, 0);
    });

    setupThongbaoAutoScroll(document.getElementById('thongbaonho'));
    setupThongbaoAutoScroll(document.getElementById('thongbaonho-mobile'));
}

function renderPDF(pdfUrl, containerId) {
    const container = document.getElementById(containerId);
    container.innerHTML = ''; // Clear trước khi render

    const loadingTask = pdfjsLib.getDocument(pdfUrl);
    loadingTask.promise.then(function (pdf) {
        const totalPages = pdf.numPages;

        for (let pageNumber = 1; pageNumber <= totalPages; pageNumber++) {
            pdf.getPage(pageNumber).then(function (page) {
                // Lấy viewport gốc với scale = 1
                const unscaledViewport = page.getViewport({ scale: 1 });
                const containerWidth = container.clientWidth;

                // Tự động scale theo chiều rộng container
                const scale = containerWidth / unscaledViewport.width;
                const viewport = page.getViewport({ scale });

                // Tạo canvas để vẽ
                const canvas = document.createElement('canvas');
                const context = canvas.getContext('2d');
                canvas.width = viewport.width;
                canvas.height = viewport.height;

                // CSS hiển thị đẹp
                canvas.style.width = '100%';
                canvas.style.marginBottom = '20px';
                canvas.style.display = 'block';

                container.appendChild(canvas);

                const renderContext = {
                    canvasContext: context,
                    viewport: viewport
                };

                page.render(renderContext);
            });
        }
    }).catch(function (error) {
        console.error('Lỗi khi tải PDF:', error);
        container.innerHTML = `<div class="d-flex flex-column justify-content-center align-items-center h-100 text-muted">
                        <i class="bi bi-file-earmark-x" style="font-size:50px"></i>
                        <p class="mt-3">Không có bản Xem trước PDF</p>
                    </div>`;
    });
}

// Chỉ gán 1 lần cho toàn bộ offcanvas
document.addEventListener('shown.bs.offcanvas', function (e) {
    const offcanvasId = e.target.id;
    const trigger = document.querySelector(`[data-bs-target="#${offcanvasId}"]`);
    const pdfUrl = trigger?.getAttribute('data-pdf-url');
    const pdfContainer = document.getElementById(`${offcanvasId}-pdf`);
    if (pdfUrl && pdfContainer) {
        loadPDF(pdfUrl, pdfContainer);
    }
});



// nganhnho
$.ajax({
    type: "GET",
    url: "/nganhnho",
    timeout: 10000,
    success: function (res) {
        renderNganhnho(res.data || []);
    },
    error: function (xhr) {
        console.error('Lỗi /nganhnho:', xhr.status, xhr.responseText);
        renderNganhnho([]);
    }
});

function renderNganhnho(data) {
    const container = $('.nganhSwiper .swiper-wrapper');
    container.empty();

    data.forEach(function (item) {
        const href = '/nganh/' + (item.slug || item.id);

        container.append(`
            <div class="swiper-slide">
                <a class="n9-card" href="${href}">
                    <div class="n9-thumb">
                        <img src="${item.image_url}" alt="${item.ten_nganh}"
                             onerror="this.src='/images/system/Rectangle_3897.jpg'">
                    </div>
                    <div class="n9-body">
                        <p class="n9-name">${item.ten_nganh}</p>
                        <div class="n9-meta">
                            <div class="n9-left">
                                ${item.ten_khoa ? `<span class="n9-khoa">Khoa: ${item.ten_khoa}</span>` : ''}
                                ${item.ma_nganh ? `<span class="n9-ma">Mã ngành: ${item.ma_nganh}</span>` : ''}
                            </div>
                            <div class="n9-icon" aria-hidden="true">
                                <i class="ti ti-arrow-up-right"></i>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        `);
    });

    // Khởi tạo hoặc update Swiper
    const swiperEl = document.querySelector('.nganhSwiper');
    if (!swiperEl) return;

    if (swiperEl.swiper) {
        // Đã khởi tạo rồi thì update
        swiperEl.swiper.update();
    } else {
        // Chưa có thì khởi tạo mới
        new Swiper('.nganhSwiper', {
            slidesPerView: 1,
            spaceBetween: 16,
            loop: false,
            navigation: {
                nextEl: '.nganhSwiper .swiper-button-next',
                prevEl: '.nganhSwiper .swiper-button-prev',
            },
            pagination: {
                el: '.nganhSwiper .swiper-pagination',
                clickable: true,
            },
            breakpoints: {
                576: { slidesPerView: 2 },
                768: { slidesPerView: 3 },
                1024: { slidesPerView: 4 },
            }
        });
    }
}



// highlight stats
$.get('/highlight-stats', function (res) {
    if (!res.data || !res.data.length) return;
    renderHighlightStats(res.data);
});

function renderHighlightStats(data) {
    const wrapper = document.querySelector('.stats-swiper .swiper-wrapper');
    if (!wrapper) return;
    wrapper.innerHTML = '';

    // Nhân 3 lần để swiper loop không bị thiếu slide
    const tripled = [...data, ...data, ...data];
    tripled.forEach(function (item) {
        const slide = document.createElement('div');
        slide.className = 'swiper-slide';
        slide.innerHTML = `
            <div class="col-12">
                <i class="${item.icon} highlight-icon"></i>
                <div class="highlight-number">${item.so_luong}</div>
                <div>${item.title}</div>
            </div>
        `;
        wrapper.appendChild(slide);
    });

    // Destroy swiper cũ nếu có rồi init lại
    const swiperEl = document.querySelector('.stats-swiper');
    if (swiperEl && swiperEl.swiper) {
        swiperEl.swiper.destroy(true, true);
    }

    new Swiper('.stats-swiper', {
        loop: true,
        autoplay: { delay: 3000, disableOnInteraction: false },
        breakpoints: {
            0: { slidesPerView: 1, spaceBetween: 30 },
            576: { slidesPerView: 2, spaceBetween: 20 },
            768: { slidesPerView: 3, spaceBetween: 30 },
            992: { slidesPerView: 4, spaceBetween: 40 },
        }
    });
}

// Visitor stats — tự cập nhật mỗi 30 giây, không cần reload trang
function loadVisitorStats() {
    $.get('/visitor/stats', function (res) {
        const fmt = n => Number(n).toLocaleString('vi-VN');
        $('#vc-tong').text(fmt(res.tong_cong));
        $('#vc-tong2').text(fmt(res.tong_cong));
        $('#vc-homnay').text(fmt(res.hom_nay));
        $('#vc-tuannay').text(fmt(res.tuan_nay));
        $('#vc-thangnay').text(fmt(res.thang_nay));
    });
}

// Gọi ngay khi load
let isLoadingVisitorStats = false;

function loadVisitorStats() {
    if (isLoadingVisitorStats) return;

    isLoadingVisitorStats = true;

    $.ajax({
        type: 'GET',
        url: '/visitor/stats',
        success: function (res) {
            const fmt = n => Number(n || 0).toLocaleString('vi-VN');

            $('#vc-tong').text(fmt(res.tong_cong));
            $('#vc-tong2').text(fmt(res.tong_cong));
            $('#vc-homnay').text(fmt(res.hom_nay));
            $('#vc-tuannay').text(fmt(res.tuan_nay));
            $('#vc-thangnay').text(fmt(res.thang_nay));
        },
        error: function (xhr) {
            console.error('Lỗi /visitor/stats:', xhr.status, xhr.responseText);
        },
        complete: function () {
            isLoadingVisitorStats = false;
        }
    });
}

loadVisitorStats();

// 30 giây, không phải 3 giây
setInterval(loadVisitorStats, 30000);