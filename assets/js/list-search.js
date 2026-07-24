document.addEventListener("DOMContentLoaded", () => {
    function go(input) {
        const url = input.dataset.searchUrl || window.location.pathname;
        const q = (input.value || "").trim();
        const target = q ? `${url}?q=${encodeURIComponent(q)}` : url;
        const current = window.location.pathname + window.location.search;
        const normalizedTarget = new URL(target, window.location.origin);
        const normalizedCurrent = new URL(current, window.location.origin);
        if (normalizedTarget.pathname + normalizedTarget.search !== normalizedCurrent.pathname + normalizedCurrent.search) {
            window.location = target;
        }
    }

    document.querySelectorAll(".js-enter-search").forEach((input) => {
        let timer = null;

        input.addEventListener("input", () => {
            clearTimeout(timer);
            timer = setTimeout(() => go(input), 450);
        });

        input.addEventListener("keydown", (e) => {
            if (e.key !== "Enter") return;
            e.preventDefault();
            clearTimeout(timer);
            go(input);
        });
    });
});
