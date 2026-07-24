document.addEventListener("DOMContentLoaded", () => {
    /* =========================================================
       LOGIN PAGE SCRIPT
       ---------------------------------------------------------
       Tombol show/hide password. Dipisahkan dari view agar
       application/views/auth/login.php tetap bersih.
    ========================================================= */

    const toggle = document.getElementById("togglePassword");
    const password = document.getElementById("pw");

    if (!toggle || !password) return;

    toggle.addEventListener("click", () => {
        password.type = password.type === "password" ? "text" : "password";
    });
});
