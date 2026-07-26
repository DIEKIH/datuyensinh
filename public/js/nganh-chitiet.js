/* nganh-chitiet.js */

// ── SCROLL ANIMATION ──────────────────────────────────────
const animateObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('is-visible');
            animateObserver.unobserve(entry.target);
        }
    });
}, { threshold: 0.1 });

document.querySelectorAll('.nd-animate').forEach(el => animateObserver.observe(el));

