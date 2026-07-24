document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll(".js-enter-search").forEach((input) => {
        input.addEventListener("keydown", (e) => {
            if (e.key !== "Enter") return;
            const url = input.dataset.searchUrl || "orders";
            const q = (input.value || "").trim();
            window.location = q ? `${url}?q=${encodeURIComponent(q)}` : url;
        });
    });
});
