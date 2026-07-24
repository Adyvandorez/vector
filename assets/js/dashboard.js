document.addEventListener("DOMContentLoaded", () => {
    /* =========================================================
       DASHBOARD PAGE SCRIPT
       ---------------------------------------------------------
       JS ini hanya mengisi nilai visual dinamis dari data PHP
       yang ditaruh sebagai data-attribute di dashboard.php.
    ========================================================= */

    // Progress goal completion
    document.querySelectorAll("[data-dashboard-progress]").forEach((el) => {
        const pct = parseInt(el.dataset.pct || "0", 10);
        el.style.width = `${Math.max(0, Math.min(100, pct))}%`;
    });

    // Tinggi bar chart order harian
    document.querySelectorAll("[data-dashboard-bar]").forEach((el) => {
        const height = parseInt(el.dataset.height || "6", 10);
        el.style.height = `${Math.max(0, height)}px`;
    });
});
