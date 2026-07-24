document.addEventListener("DOMContentLoaded", () => {
    /* =========================================================
       ORDER DETAIL SCRIPT
       ---------------------------------------------------------
       Mengatur mode edit/hapus preview gambar.
    ========================================================= */

    const btnEdit = document.getElementById("btnEditPreview");
    const btnDel = document.getElementById("btnDeletePreview");
    const formDel = document.getElementById("formDeletePreview");
    const hint = document.getElementById("previewHint");
    const checksWrap = document.querySelectorAll(".preview-check");
    const checks = document.querySelectorAll(".preview-checkbox");

    if (!btnEdit || !btnDel) return;

    let editMode = false;

    function updateDeleteState() {
        let anyChecked = false;
        checks.forEach((checkbox) => {
            if (checkbox.checked) anyChecked = true;
        });
        btnDel.disabled = !anyChecked;
    }

    function setEditMode(isActive) {
        editMode = isActive;

        checksWrap.forEach((wrap) => {
            wrap.classList.toggle("is-visible", isActive);
        });

        if (hint) hint.classList.toggle("is-visible", isActive);

        if (!isActive) {
            checks.forEach((checkbox) => {
                checkbox.checked = false;
            });
            btnDel.disabled = true;
            btnEdit.textContent = "Edit";
        } else {
            btnEdit.textContent = "Selesai";
            updateDeleteState();
        }
    }

    btnEdit.addEventListener("click", () => setEditMode(!editMode));
    checks.forEach((checkbox) => checkbox.addEventListener("change", updateDeleteState));

    if (formDel) {
        formDel.addEventListener("submit", async (e) => {
            if (formDel.dataset.confirmAccepted === "1") return;
            e.preventDefault();
            if (btnDel.disabled) return;
            const ok = window.viConfirm
                ? await window.viConfirm("Hapus preview yang dipilih?")
                : true;
            if (!ok) return;
            formDel.dataset.confirmAccepted = "1";
            HTMLFormElement.prototype.submit.call(formDel);
        });
    }

    setEditMode(false);
});
