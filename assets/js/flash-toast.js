document.addEventListener('DOMContentLoaded', function () {
    const flashes = document.querySelectorAll('.js-flash-toast, .flash-toast');
    if (!flashes.length) return;

    flashes.forEach(function (el, index) {
        const delay = Number(el.dataset.flashDelay || 3200) + (index * 120);
        const removeDelay = 650;

        el.classList.add('flash-toast--show');

        window.setTimeout(function () {
            el.classList.add('flash-toast--hide');
            window.setTimeout(function () {
                if (el && el.parentNode) el.parentNode.removeChild(el);
            }, removeDelay);
        }, delay);
    });
});
