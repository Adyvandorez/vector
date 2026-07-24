document.addEventListener("DOMContentLoaded", () => {
	const chat = document.getElementById("aiChat");
	const input = document.getElementById("aiInput");
	const send = document.getElementById("aiSend");
	const overlay = document.getElementById("aiOverlay");
	const baseUrlRaw = overlay?.dataset.baseUrl || window.BASE_URL || "/";
	const baseUrl = baseUrlRaw.endsWith("/") ? baseUrlRaw : baseUrlRaw + "/";

	const csrf = {
		name: document.querySelector('meta[name="csrf-token-name"]')?.content || "",
		hash: document.querySelector('meta[name="csrf-token-hash"]')?.content || "",
	};

	if (!chat) return;

	function appendCsrf(formData) {
		if (csrf.name && csrf.hash) formData.append(csrf.name, csrf.hash);
		return formData;
	}

	function escapeHtml(value) {
		return String(value ?? "")
			.replace(/&/g, "&amp;")
			.replace(/</g, "&lt;")
			.replace(/>/g, "&gt;")
			.replace(/"/g, "&quot;")
			.replace(/'/g, "&#039;");
	}

	function numberValue(id) {
		const el = document.getElementById(id);
		return Math.max(0, parseInt(el?.value || "0", 10) || 0);
	}

	function setChat(html) {
		chat.innerHTML = html;
		chat.scrollTop = chat.scrollHeight;
	}

	function setNormalInput(enabled) {
		if (input) input.disabled = !enabled;
		if (send) send.disabled = !enabled;
	}

	function setButtonLoading(button, loadingText) {
		if (!button) return () => {};
		const oldText = button.innerHTML;
		button.disabled = true;
		button.innerHTML = loadingText || "Menyimpan...";
		return () => {
			button.disabled = false;
			button.innerHTML = oldText;
		};
	}

	async function postForm(endpoint, formData) {
		const res = await fetch(baseUrl + endpoint, {
			method: "POST",
			body: appendCsrf(formData),
			credentials: "same-origin",
		});

		const text = await res.text();
		let data;
		try {
			data = JSON.parse(text);
		} catch (err) {
			throw new Error("Server tidak mengirim JSON valid. Cek apakah sesi login habis atau ada error PHP.");
		}

		if (!res.ok) {
			throw new Error(data.msg || "Request gagal.");
		}

		return data;
	}

	async function getJson(endpoint) {
		const res = await fetch(baseUrl + endpoint, { credentials: "same-origin" });
		const text = await res.text();
		try {
			return JSON.parse(text);
		} catch (err) {
			throw new Error("Gagal membaca data dari server.");
		}
	}

	let designCount = 0;
	let currentDesignIndex = 0;
	let savedDesigns = [];
	let selectedItems = [];

	window.inputModeActive = false;
	window.orderConfirmMode = false;
	window.tempOrderData = null;

	window.startInputMode = function () {
		window.inputModeActive = true;
		window.orderConfirmMode = false;
		window.tempOrderData = null;

		designCount = 0;
		currentDesignIndex = 0;
		savedDesigns = [];
		selectedItems = [];

		setNormalInput(false);
		renderAskDesignCount();
	};

	window.exitInputMode = function () {
		window.inputModeActive = false;
		window.orderConfirmMode = false;
		window.tempOrderData = null;
		setNormalInput(true);
		setChat(`
			<div class="ai-msg ai">
				✅ Input data selesai.<br>
				Sistem kembali ke mode normal.
			</div>
		`);
	};

	function renderAskDesignCount() {
		setChat(`
			<div class="ai-input-mode">
				<div class="ai-input-card">
					<h3>Berapa jenis desain yang ingin diinput?</h3>
					<div class="ai-input-grid full">
						<div class="ai-field">
							<div class="ai-label">Jumlah Desain</div>
							<input type="number" id="aiDesignCount" min="1" value="1">
						</div>
					</div>
					<div class="ai-input-actions">
						<button type="button" class="ai-next-btn" id="aiNextDesignCount">Lanjut</button>
						<button type="button" class="ai-cancel-btn" id="aiCancelWizard">Batal</button>
					</div>
				</div>
			</div>
		`);
	}

	function renderDesignForm() {
		if (currentDesignIndex >= designCount) {
			renderPriceMatrixForm();
			return;
		}

		setChat(`
			<div class="ai-input-mode">
				<div class="ai-input-card">
					<h3>Jenis Desain ${currentDesignIndex + 1}</h3>
					<div class="ai-input-grid">
						<div class="ai-field">
							<div class="ai-label">Nama Desain</div>
							<input type="text" id="designName" autocomplete="off">
						</div>
						<div class="ai-field">
							<div class="ai-label">Status</div>
							<select id="designStatus">
								<option value="1">Aktif</option>
								<option value="0">Nonaktif</option>
							</select>
						</div>
					</div>
					<div class="ai-input-grid full" style="margin-top:12px;">
						<div class="ai-field">
							<div class="ai-label">Upload Preview</div>
							<input type="file" id="designPreview" accept="image/*">
						</div>
					</div>
					<div class="ai-input-actions">
						<button type="button" class="ai-next-btn" id="aiNextDesign">Simpan & Lanjut</button>
						<button type="button" class="ai-cancel-btn" id="aiCancelWizard">Batal</button>
					</div>
				</div>
			</div>
		`);
	}

	async function renderPriceMatrixForm() {
		try {
			const bodyParts = await getJson("ai/get_body_parts");
			if (!Array.isArray(bodyParts) || bodyParts.length === 0) {
				alert("Data body part kosong. Isi body part dulu sebelum lanjut.");
				renderDesignForm();
				return;
			}

			let html = `<div class="ai-input-mode">`;
			savedDesigns.forEach((design) => {
				html += `
					<div class="ai-input-card">
						<h3>Harga - ${escapeHtml(design.name)}</h3>
						<div class="ai-input-grid full">
				`;

				bodyParts.forEach((part) => {
					html += `
							<div class="ai-field">
								<div class="ai-label">${escapeHtml(part.name)}</div>
								<input type="number" data-design="${escapeHtml(design.id)}" data-part="${escapeHtml(part.id)}" class="price-input" placeholder="Masukkan harga">
							</div>
						`;
				});

				html += `
						</div>
					</div>
				`;
			});

			html += `
				<div class="ai-input-actions">
					<button type="button" class="ai-next-btn" id="aiSavePrices">Simpan Harga</button>
					<button type="button" class="ai-cancel-btn" id="aiCancelWizard">Batal</button>
				</div>
			</div>`;

			setChat(html);
		} catch (err) {
			alert(err.message || "Gagal memuat price matrix.");
			renderDesignForm();
		}
	}

	function renderOrderForm() {
		setChat(`
			<div class="ai-input-mode">
				<div class="ai-input-card">
					<h3>Data Order</h3>
					<div class="ai-input-grid">
						<div class="ai-field">
							<div class="ai-label">Nama Klien</div>
							<input type="text" id="orderClient" autocomplete="off">
						</div>
						<div class="ai-field">
							<div class="ai-label">No WA</div>
							<input type="text" id="orderPhone" autocomplete="off">
						</div>
						<div class="ai-field">
							<div class="ai-label">Judul Pekerjaan</div>
							<input type="text" id="orderTitle" autocomplete="off">
						</div>
						<div class="ai-field">
							<div class="ai-label">Deadline</div>
							<input type="date" id="orderDeadline">
						</div>
						<div class="ai-field">
							<div class="ai-label">DP</div>
							<input type="number" id="orderDP" value="0">
						</div>
					</div>
					<div class="ai-input-actions">
						<button type="button" class="ai-next-btn" id="aiSaveOrder">Simpan Order</button>
						<button type="button" class="ai-cancel-btn" id="aiCancelWizard">Batal</button>
					</div>
				</div>
			</div>
		`);
	}

	function renderExtraForm() {
		setChat(`
			<div class="ai-input-mode">
				<div class="ai-input-card">
					<h3>Tambahan (Opsional)</h3>
					<div class="ai-input-grid">
						<div class="ai-field">
							<div class="ai-label">Biaya Add-ons</div>
							<input type="number" id="orderAddons" value="0" placeholder="Contoh: 50000">
						</div>
						<div class="ai-field">
							<div class="ai-label">Diskon</div>
							<input type="number" id="orderDiscount" value="0">
						</div>
						<div class="ai-field">
							<div class="ai-label">Jumlah Revisi</div>
							<input type="number" id="orderRevision" value="0">
						</div>
						<div class="ai-field">
							<div class="ai-label">Catatan</div>
							<input type="text" id="orderNotes" autocomplete="off">
						</div>
					</div>
					<div class="ai-input-actions">
						<button type="button" class="ai-next-btn" id="aiNextSummary">Lanjut</button>
						<button type="button" class="ai-cancel-btn" id="aiCancelWizard">Batal</button>
					</div>
				</div>
			</div>
		`);
	}

	function renderSummary() {
		if (!window.tempOrderData) {
			alert("Data order belum lengkap.");
			renderOrderForm();
			return;
		}

		let total = 0;
		selectedItems.forEach((item) => {
			total += item.price * item.qty;
		});
		total += numberValueFromData(window.tempOrderData.addons);
		total -= numberValueFromData(window.tempOrderData.discount);
		if (total < 0) total = 0;
		window.tempOrderData.final_total = total;

		window.orderConfirmMode = true;
		setNormalInput(true);
		if (input) {
			input.value = "";
			input.focus();
		}

		setChat(`
			<div class="ai-msg ai">
				<h3>Ringkasan Order</h3>
				<b>Klien:</b> ${escapeHtml(window.tempOrderData.client_name)}<br>
				<b>Judul:</b> ${escapeHtml(window.tempOrderData.title || "Order dari AI Wizard")}<br>
				<b>Total:</b> Rp ${total.toLocaleString("id-ID")}<br>
				<b>DP:</b> Rp ${numberValueFromData(window.tempOrderData.dp).toLocaleString("id-ID")}<br>
				<b>Diskon:</b> Rp ${numberValueFromData(window.tempOrderData.discount).toLocaleString("id-ID")}<br>
				<b>Add-ons:</b> Rp ${numberValueFromData(window.tempOrderData.addons).toLocaleString("id-ID")}<br>
				<b>Revisi:</b> ${numberValueFromData(window.tempOrderData.revision)}<br>
				<b>Catatan:</b> ${escapeHtml(window.tempOrderData.notes || "-")}<br><br>
				⚠️ Konfirmasi simpan order?<br>
				Ketik: <b>ya</b> / <b>batal</b>
			</div>
		`);
	}

	function numberValueFromData(value) {
		return Math.max(0, parseInt(value || "0", 10) || 0);
	}

	window.saveFinalOrder = async function () {
		if (!window.tempOrderData || selectedItems.length === 0) {
			alert("Data order belum lengkap. Ulangi input data dari awal.");
			window.orderConfirmMode = false;
			return;
		}

		const oldInputValue = input ? input.value : "";
		setNormalInput(false);
		setChat(`
			<div class="ai-msg ai">
				⏳ Menyimpan order final ke database...<br>
				Mohon tunggu sampai muncul pesan berhasil.
			</div>
		`);

		try {
			const formData = new FormData();
			formData.append("client_name", window.tempOrderData.client_name);
			formData.append("phone", window.tempOrderData.phone || "");
			formData.append("title", window.tempOrderData.title || "");
			formData.append("deadline", window.tempOrderData.deadline || "");
			formData.append("dp", window.tempOrderData.dp || 0);
			formData.append("addons", window.tempOrderData.addons || 0);
			formData.append("discount", window.tempOrderData.discount || 0);
			formData.append("revision", window.tempOrderData.revision || 0);
			formData.append("notes", window.tempOrderData.notes || "");

			selectedItems.forEach((item, index) => {
				formData.append(`items[${index}][design_id]`, item.design_id);
				formData.append(`items[${index}][body_part_id]`, item.body_part_id);
				formData.append(`items[${index}][price]`, item.price);
				formData.append(`items[${index}][qty]`, item.qty);
			});

			const data = await postForm("ai/save_order_wizard", formData);
			if (!data.status) throw new Error(data.msg || "Gagal menyimpan order.");

			window.inputModeActive = false;
			window.orderConfirmMode = false;
			window.tempOrderData = null;
			setNormalInput(true);
			setChat(`
				<div class="ai-msg ai">
					✅ Input data berhasil disimpan ke database.<br>
					📦 Kode Order: <b>${escapeHtml(data.order_code || "-")}</b><br>
					Total: <b>Rp ${numberValueFromData(data.total).toLocaleString("id-ID")}</b><br>
					Semua tahap selesai dan order sudah muncul di menu Orders.
				</div>
			`);
		} catch (err) {
			window.orderConfirmMode = true;
			setNormalInput(true);
			if (input) input.value = oldInputValue;
			setChat(`
				<div class="ai-msg ai">
					❌ Gagal menyimpan order final.<br>
					${escapeHtml(err.message || "Terjadi kesalahan.")}<br><br>
					Ketik <b>ya</b> untuk mencoba simpan ulang, atau <b>batal</b> untuk membatalkan.
				</div>
			`);
		}
	};

	document.addEventListener("click", async (e) => {
		if (e.target.id === "aiCancelWizard") {
			window.exitInputMode();
			return;
		}

		if (e.target.id === "aiNextDesignCount") {
			const inputEl = document.getElementById("aiDesignCount");
			const val = parseInt(inputEl?.value || "0", 10);
			if (isNaN(val) || val < 1) {
				alert("Masukkan jumlah desain minimal 1");
				inputEl?.focus();
				return;
			}
			designCount = val;
			renderDesignForm();
			return;
		}

		if (e.target.id === "aiNextDesign") {
			const btn = e.target;
			const nameInput = document.getElementById("designName");
			const statusInput = document.getElementById("designStatus");
			const previewInput = document.getElementById("designPreview");
			const name = (nameInput?.value || "").trim();

			if (!name) {
				alert("Nama desain wajib diisi");
				nameInput?.focus();
				return;
			}

			const restore = setButtonLoading(btn, "Menyimpan...");
			try {
				const formData = new FormData();
				formData.append("name", name);
				formData.append("status", statusInput?.value || 1);
				if (previewInput?.files?.[0]) formData.append("preview", previewInput.files[0]);

				const data = await postForm("ai/save_design_wizard", formData);
				if (!data.status || !data.design_id) throw new Error(data.msg || "Desain gagal disimpan.");

				savedDesigns.push({ id: data.design_id, name });
				currentDesignIndex++;
				renderDesignForm();
			} catch (err) {
				alert(err.message || "Terjadi kesalahan saat menyimpan desain.");
				restore();
			}
			return;
		}

		if (e.target.id === "aiSavePrices") {
			const btn = e.target;
			const inputs = document.querySelectorAll(".price-input");
			selectedItems = [];
			const grouped = {};

			inputs.forEach((inputEl) => {
				const price = Math.max(0, parseInt(inputEl.value || "0", 10) || 0);
				const designId = inputEl.dataset.design;
				const bodyPartId = inputEl.dataset.part;
				if (price > 0 && designId && bodyPartId) {
					selectedItems.push({ design_id: designId, body_part_id: bodyPartId, price, qty: 1 });
					if (!grouped[designId]) grouped[designId] = {};
					grouped[designId][bodyPartId] = price;
				}
			});

			if (selectedItems.length === 0) {
				alert("Minimal isi satu harga.");
				return;
			}

			const restore = setButtonLoading(btn, "Menyimpan harga...");
			try {
				for (const designId of Object.keys(grouped)) {
					const formData = new FormData();
					formData.append("design_id", designId);
					Object.keys(grouped[designId]).forEach((bodyPartId) => {
						formData.append(`prices[${bodyPartId}]`, grouped[designId][bodyPartId]);
					});
					const data = await postForm("ai/save_price_matrix_wizard", formData);
					if (!data.status) throw new Error(data.msg || "Gagal menyimpan harga.");
				}
				renderOrderForm();
			} catch (err) {
				alert(err.message || "Terjadi kesalahan saat menyimpan harga.");
				restore();
			}
			return;
		}

		if (e.target.id === "aiSaveOrder") {
			const client = (document.getElementById("orderClient")?.value || "").trim();
			if (!client) {
				alert("Nama klien wajib diisi");
				document.getElementById("orderClient")?.focus();
				return;
			}
			if (selectedItems.length === 0) {
				alert("Item belum dipilih.");
				return;
			}
			window.tempOrderData = {
				client_name: client,
				phone: document.getElementById("orderPhone")?.value || "",
				title: document.getElementById("orderTitle")?.value || "",
				deadline: document.getElementById("orderDeadline")?.value || "",
				dp: numberValue("orderDP"),
			};
			renderExtraForm();
			return;
		}

		if (e.target.id === "aiNextSummary") {
			if (!window.tempOrderData) {
				alert("Data order belum lengkap.");
				renderOrderForm();
				return;
			}
			window.tempOrderData.addons = numberValue("orderAddons");
			window.tempOrderData.discount = numberValue("orderDiscount");
			window.tempOrderData.revision = numberValue("orderRevision");
			window.tempOrderData.notes = document.getElementById("orderNotes")?.value || "";
			renderSummary();
		}
	});

	document.addEventListener("input", (e) => {
		if (!e.target.classList?.contains("price-input")) return;
		const designId = e.target.dataset.design;
		document.querySelectorAll(`.price-input[data-design="${designId}"]`).forEach((inputEl) => {
			if (inputEl !== e.target) inputEl.value = "";
		});
	});
});
