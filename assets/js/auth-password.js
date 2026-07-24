document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll(".toggle-eye[data-target]").forEach((button) => {
        button.addEventListener("click", () => {
            const input = document.getElementById(button.dataset.target);
            if (!input) return;
            input.type = input.type === "password" ? "text" : "password";
        });
    });
});
