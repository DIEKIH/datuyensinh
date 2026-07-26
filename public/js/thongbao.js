function formatDate(dateString) {
    if (!dateString) return '';
    const date = new Date(dateString);
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0'); // Tháng tính từ 0
    const year = date.getFullYear();
    return `${day}/${month}/${year}`;
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
        container.innerHTML = `<p class="text-danger p-3">Không thể hiển thị file PDF.</p>`;
    });
}

document.addEventListener('DOMContentLoaded', function () {
    const offcanvases = document.querySelectorAll('.offcanvas');

    offcanvases.forEach(function (offcanvas) {
        offcanvas.addEventListener('shown.bs.offcanvas', function () {
            const pdfUrl = offcanvas.getAttribute('data-pdf-url');
            const containerId = offcanvas.id + '-pdf';

            if (pdfUrl) {
                renderPDF(pdfUrl, containerId);
            }
        });
    });
});












