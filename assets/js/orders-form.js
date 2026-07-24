document.addEventListener("DOMContentLoaded", () => {
    /* =========================================================
       ORDER FORM SCRIPT
       ---------------------------------------------------------
       Mengatur tambah/hapus item desain, auto harga matrix,
       dan format rupiah agar input uang mudah dibaca.
    ========================================================= */

    const configEl = document.getElementById("ordersFormData");
    if (!configEl) return;

    const config = JSON.parse(configEl.textContent || "{}");
    const baseUrl = config.base_url || "/";
    const designs = config.designs || [];
    const parts = config.parts || [];
    const existingItems = config.existing_items || [];

    function escapeHtml(value) {
        return String(value ?? "")
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function digits(value) {
        return String(value || "").replace(/[^0-9]/g, "");
    }

    function intMoney(value) {
        const d = digits(value);
        return d ? parseInt(d, 10) : 0;
    }

    function formatMoney(value) {
        const d = digits(value);
        if (!d) return "0";
        return d.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }

    function bindMoneyInput(input) {
        if (!input || input.dataset.moneyBound === "1") return;
        input.dataset.moneyBound = "1";
        input.value = formatMoney(input.value);
        input.addEventListener("input", () => {
            input.value = formatMoney(input.value);
        });
    }

    function buildOptions(list, selected) {
        let html = `<option value="">-- pilih --</option>`;

        list.forEach((x) => {
            const isSelected = selected && parseInt(selected, 10) === parseInt(x.id, 10);
            html += `<option value="${escapeHtml(x.id)}" ${isSelected ? "selected" : ""}>${escapeHtml(x.name)}</option>`;
        });

        return html;
    }

    function itemRowHtml(pref = {}) {
        pref = pref || {};
        const dSelected = pref.design_type_id || "";
        const bSelected = pref.body_part_id || "";
        const qty = pref.qty || 1;
        const price = pref.price || 0;
        const note = pref.note || "";

        return `
            <div class="card order-item-card" data-item>
                <div class="row3">
                    <div>
                        <div class="small">Jenis</div>
                        <select class="item-design vi-custom-select" name="item_design_type_id[]">
                            ${buildOptions(designs, dSelected)}
                        </select>
                    </div>

                    <div>
                        <div class="small">Bagian</div>
                        <select class="item-body vi-custom-select" name="item_body_part_id[]">
                            ${buildOptions(parts, bSelected)}
                        </select>
                    </div>

                    <div>
                        <div class="small">Harga (auto dari matrix)</div>
                        <input class="input item-price js-money" name="item_price[]" type="text" inputmode="numeric" value="${escapeHtml(formatMoney(price))}">
                    </div>
                </div>

                <div class="row3 u-mt-10">
                    <div>
                        <div class="small">Qty</div>
                        <input class="input item-qty" name="item_qty[]" type="number" value="${escapeHtml(qty)}" min="1">
                    </div>
                    <div class="u-grid-span-2">
                        <div class="small">Catatan (opsional)</div>
                        <input class="input" name="item_note[]" value="${escapeHtml(note)}" placeholder="misal: 2 orang, background, dll">
                    </div>
                </div>

                <div class="order-item-actions">
                    <button class="btn btn-red js-remove-item" type="button">Hapus Item</button>
                </div>
            </div>`;
    }

    function refreshCustomSelects(root) {
        if (window.VICustomSelect && typeof window.VICustomSelect.refresh === "function") {
            window.VICustomSelect.refresh(root || document);
        }
    }

    function attachAutoPrice(itemEl) {
        refreshCustomSelects(itemEl);
        const designSel = itemEl.querySelector(".item-design");
        const bodySel = itemEl.querySelector(".item-body");
        const priceInput = itemEl.querySelector(".item-price");
        bindMoneyInput(priceInput);

        async function fetchPrice() {
            const designId = designSel.value;
            const bodyPartId = bodySel.value;
            if (!designId || !bodyPartId) return;

            const url = `${baseUrl}prices/get_base_price?design_type_id=${encodeURIComponent(designId)}&body_part_id=${encodeURIComponent(bodyPartId)}`;
            const res = await fetch(url);
            const data = await res.json();

            // Harga otomatis hanya mengganti jika nilai masih 0 agar admin tetap bisa override manual.
            if (intMoney(priceInput.value) === 0) {
                priceInput.value = formatMoney(data.base_price || 0);
            }
        }

        designSel.addEventListener("change", fetchPrice);
        bodySel.addEventListener("change", fetchPrice);
    }

    function createItemElement(pref) {
        const temp = document.createElement("div");
        temp.innerHTML = itemRowHtml(pref).trim();
        return temp.firstElementChild;
    }

    function addItemTop(pref) {
        const wrap = document.getElementById("itemsWrap");
        if (!wrap) return;

        wrap.insertAdjacentHTML("afterbegin", itemRowHtml(pref || {}));
        attachAutoPrice(wrap.firstElementChild);
    }

    function addItemBottom(pref) {
        const wrap = document.getElementById("itemsWrap");
        if (!wrap) return;

        const el = createItemElement(pref || {});
        wrap.appendChild(el);
        attachAutoPrice(el);
    }

    const addBtn = document.getElementById("addItemBtn");
    const wrap = document.getElementById("itemsWrap");

    if (addBtn) addBtn.addEventListener("click", () => addItemTop({}));

    if (wrap) {
        wrap.addEventListener("click", (e) => {
            const removeBtn = e.target.closest(".js-remove-item");
            if (removeBtn) removeBtn.closest("[data-item]")?.remove();
        });
    }

    if (existingItems.length) {
        existingItems.forEach((item) => addItemBottom(item));
    } else {
        addItemTop({});
    }

    refreshCustomSelects(document);
});
