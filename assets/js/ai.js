document.addEventListener("DOMContentLoaded", () => {
	/* =========================
       ELEMENT SELECTOR
    ========================= */

	const overlay = document.getElementById("aiOverlay");

	/* =========================
       KONFIGURASI AI
       Data URL dikirim dari layout/footer.php lewat data-attribute.
       Cara ini menggantikan inline script agar JS lebih rapi.
    ========================= */
	const aiConfig = {
		baseUrl: overlay?.dataset.baseUrl || "/",
		endpoint: overlay?.dataset.endpoint || "ai/chat",
		img: overlay?.dataset.img || "",
	};

	/* =========================
       SECURITY HELPERS
       CSRF token diambil dari meta layout/header.php.
       escapeHtml mencegah teks AI terbaca sebagai HTML.
    ========================= */
	const csrf = {
		name: document.querySelector('meta[name="csrf-token-name"]')?.content || "",
		hash: document.querySelector('meta[name="csrf-token-hash"]')?.content || "",
	};

	function csrfBody(params) {
		const body = new URLSearchParams(params);
		if (csrf.name && csrf.hash) body.append(csrf.name, csrf.hash);
		return body.toString();
	}

	function escapeHtml(value) {
		return String(value ?? "")
			.replace(/&/g, "&amp;")
			.replace(/</g, "&lt;")
			.replace(/>/g, "&gt;")
			.replace(/"/g, "&quot;")
			.replace(/'/g, "&#039;");
	}

	function nl2brEscaped(value) {
		return escapeHtml(value).replace(/\n/g, "<br>");
	}

	// Tetap disediakan untuk ai-input-mode.js agar kompatibel dengan flow lama.
	window.BASE_URL = aiConfig.baseUrl;

	const openBtn = document.getElementById("openAiBtn");
	const closeBtn = document.getElementById("closeAi");
	const refreshBtn = document.getElementById("aiRefresh");
	const chat = document.getElementById("aiChat");
	const input = document.getElementById("aiInput");
	const send = document.getElementById("aiSend");
	const suggestBar = document.getElementById("aiSuggestBar");
	const infoBtn = document.getElementById("aiInfoBtn");
	const infoPage = document.getElementById("aiInfoPage");
	const aiCard = document.getElementById("aiCard");

	function setInfoMode(active) {
		if (!infoPage || !aiCard) return;
		infoPage.classList.toggle("active", active);
		aiCard.classList.toggle("info-mode", active);
	}

	// Tombol info dibuat opsional agar JS tidak error jika elemen tidak ada di halaman tertentu.
	if (infoBtn && infoPage) {
		infoBtn.addEventListener("click", () => {
			setInfoMode(!infoPage.classList.contains("active"));
		});
	}

	if (!overlay || !chat) return;

	document.querySelectorAll(".ai-info-btn").forEach((btn) => {
		btn.addEventListener("click", function () {
			const prompt = this.dataset.prompt;
			if (!prompt) return;

			setInfoMode(false);
			sendMsg(prompt);
		});
	});

	/* =========================
       SUGGEST BUTTON CLICK
    ========================= */

	document.querySelectorAll(".ai-suggest").forEach((btn) => {
		btn.addEventListener("click", () => sendMsg(btn.innerText));
	});

	function hasConversationContent() {
		if (!chat) return false;
		return !!chat.querySelector(".ai-msg, .ai-input-mode, .ai-draft");
	}

	function showHeroOnlyWhenEmpty() {
		if (!chat) return;
		const hero = document.getElementById("aiHero");
		if (hasConversationContent()) {
			if (hero) hero.remove();
			return;
		}
		renderHero();
	}

	/* =========================
       OPEN AI
    ========================= */

	function focusAiInput() {
		if (!input) return;
		setTimeout(() => {
			try { input.focus({ preventScroll: true }); } catch (e) { input.focus(); }
		}, 80);
	}

	if (openBtn) {
		openBtn.addEventListener("click", () => {
			overlay.classList.add("active");

			// Jangan munculkan hero/robot lagi kalau chat lama masih ada.
			// Riwayat chat tetap bertahan saat overlay ditutup, dan baru bersih saat tombol refresh ditekan.
			showHeroOnlyWhenEmpty();
			focusAiInput();
		});
	}

	if (overlay.classList.contains("active")) {
		showHeroOnlyWhenEmpty();
		focusAiInput();
	}

	/* =========================
       CLOSE AI
    ========================= */

	if (closeBtn) {
		closeBtn.addEventListener("click", () => {
			setInfoMode(false);
			overlay.classList.remove("active");
		});
	}

	/* =========================
       REFRESH CHAT
    ========================= */

	if (refreshBtn) {
		refreshBtn.addEventListener("click", () => {
			setInfoMode(false);
			chat.innerHTML = "";
			if (suggestBar) suggestBar.style.display = "flex";
			renderHero();
			focusAiInput();
		});
	}

	/* =========================
       ADD MESSAGE
    ========================= */

	function addMsg(text, type = "ai") {
		const msg = document.createElement("div");
		msg.className = "ai-msg " + type;
		msg.textContent = text;
		chat.appendChild(msg);
		chat.scrollTop = chat.scrollHeight;
	}

	/* =========================
       TYPING INDICATOR
    ========================= */

	function showTyping() {
		const typing = document.createElement("div");
		typing.id = "aiTyping";

		typing.innerHTML = `
            <div class="ai-typing-line">
                <span></span><span></span><span></span>
            </div>
        `;

		chat.appendChild(typing);
		chat.scrollTop = chat.scrollHeight;
	}

	function removeTyping() {
		const t = document.getElementById("aiTyping");
		if (t) t.remove();
	}

	/* =========================
       TYPE EFFECT REPLY
    ========================= */

	function typeReply(text) {
		// ================= INPUT MODE =================
		if (typeof text === "string" && text.trim() === "[INPUT_MODE]") {
			if (typeof window.startInputMode === "function") {
				window.startInputMode();
			} else {
				addMsg("⚠️ Wizard input data belum siap. Refresh halaman lalu coba lagi.", "ai");
			}
			return;
		}
		// ================= FOLLOWUP =================
		if (text.includes("[FOLLOWUP_JSON]")) {
			try {
				const jsonString = text.split("[FOLLOWUP_JSON]")[1].trim();
				const data = JSON.parse(jsonString);

				typeDraftSequential([
					{ title: "Versi Profesional & Ramah", content: data.v1 },
					{ title: "Versi Lebih Tegas", content: data.v2 },
				]);

				return;
			} catch (e) {
				console.error("FOLLOWUP PARSE ERROR:", e);
			}
		}

		// ================= DP =================
		if (text.includes("[DP_JSON]")) {
			try {
				const jsonString = text.split("[DP_JSON]")[1].trim();
				const data = JSON.parse(jsonString);

				typeDraftSequential([
					{ title: "Output Profesional", content: data.v1 },
					{ title: "Versi Lebih Singkat & Tegas", content: data.v2 },
				]);

				return;
			} catch (e) {
				console.error("DP PARSE ERROR:", e);
			}
		}
		// ================= ORDER DONE =================
		if (text.includes("[DONE_JSON]")) {
			try {
				const jsonString = text.split("[DONE_JSON]")[1].trim();
				const data = JSON.parse(jsonString);

				typeDraftSequential([
					{ title: "Output Profesional (Standar Netral)", content: data.v1 },
					{ title: "Versi Lebih Singkat", content: data.v2 },
				]);

				return;
			} catch (e) {
				console.error("DONE PARSE ERROR:", e);
			}
		}
		// ================= SOFT BILLING =================
		if (text.includes("[BILLING_JSON]")) {
			try {
				const jsonString = text.split("[BILLING_JSON]")[1].trim();
				const data = JSON.parse(jsonString);

				typeDraftSequential([
					{ title: "Output Profesional (Halus & Elegan)", content: data.v1 },
					{ title: "Versi Lebih Formal", content: data.v2 },
				]);

				return;
			} catch (e) {
				console.error("BILLING PARSE ERROR:", e);
			}
		}

		// ================= NORMAL MODE =================
		const bubble = document.createElement("div");
		bubble.className = "ai-msg ai";
		chat.appendChild(bubble);

		let i = 0;

		function type() {
			if (i < text.length) {
				bubble.innerHTML = nl2brEscaped(text.substring(0, i + 1));
				i++;
				chat.scrollTop = chat.scrollHeight;
				setTimeout(type, 15);
			}
		}

		type();
	}
	function typeDraftSequential(drafts) {
		let index = 0;

		function next() {
			if (index >= drafts.length) return;

			typeSingleDraft(drafts[index].title, drafts[index].content, () => {
				index++;
				setTimeout(next, 400);
			});
		}

		next();
	}

	function typeSingleDraft(title, content, doneCallback) {
		const bubble = document.createElement("div");
		bubble.className = "ai-msg ai ai-draft";

		bubble.innerHTML = `
        <div class="ai-draft-header">
            <span class="ai-draft-title">${escapeHtml(title)}</span>
            <button class="ai-copy-btn">Salin</button>
        </div>
        <div class="ai-draft-content"></div>
    `;

		const contentDiv = bubble.querySelector(".ai-draft-content");

		chat.appendChild(bubble);
		chat.scrollTop = chat.scrollHeight;

		let i = 0;

		function type() {
			if (i < content.length) {
				contentDiv.innerHTML = nl2brEscaped(content.substring(0, i + 1));
				i++;
				chat.scrollTop = chat.scrollHeight;
				setTimeout(type, 12);
			} else {
				if (doneCallback) doneCallback();
			}
		}

		type();

		bubble.querySelector(".ai-copy-btn").addEventListener("click", function () {
			navigator.clipboard.writeText(content);
			this.innerText = "Disalin ✓";
			setTimeout(() => (this.innerText = "Salin"), 1500);
		});
	}

	function createDraftBubble(title, content) {
		const bubble = document.createElement("div");
		bubble.className = "ai-msg ai ai-draft";

		bubble.innerHTML = `
		<div class="ai-draft-header">
			<div class="ai-draft-title">${escapeHtml(title)}</div>
			<button class="ai-copy-btn">
				<span>Salin</span>
			</button>
		</div>
		<div class="ai-draft-content"></div>
	`;

		chat.appendChild(bubble);
		chat.scrollTop = chat.scrollHeight;

		const contentBox = bubble.querySelector(".ai-draft-content");
		const btn = bubble.querySelector(".ai-copy-btn");

		// 🔥 Typing effect
		let i = 0;
		function type() {
			if (i < content.length) {
				contentBox.innerHTML = nl2brEscaped(content.substring(0, i + 1));
				i++;
				setTimeout(type, 12);
				chat.scrollTop = chat.scrollHeight;
			}
		}
		type();

		// 🔥 Copy button
		btn.addEventListener("click", () => {
			navigator.clipboard.writeText(content);
			btn.innerText = "✓ Disalin";
			setTimeout(() => {
				btn.innerText = "Salin";
			}, 1500);
		});
	}

	/* =========================
       HERO SECTION
    ========================= */

	function renderHero() {
		if (document.getElementById("aiHero")) return;

		const hero = document.createElement("div");
		hero.className = "ai-hero";
		hero.id = "aiHero";

		hero.innerHTML = `
            <img src="${escapeHtml(aiConfig.img)}" class="ai-hero-img">

            <div class="ai-hero-welcome">Welcome to</div>

            <div class="ai-hero-title">
                AI Executive Assistant
            </div>

            <div class="ai-hero-desc">
                Saya siap membantu kamu mengelola<br>
                invoice, laporan, dan insight bisnis.
            </div>
        `;

		chat.appendChild(hero);
	}

	/* =========================
       HIDE HERO
    ========================= */

	function hideHero() {
		const hero = document.getElementById("aiHero");
		if (hero) hero.remove();

		if (suggestBar) suggestBar.style.display = "none";
	}

	/* =========================
   SEND MESSAGE (FIXED)
========================= */

	function sendMsg(customText = null) {
		// ambil text sekali aja (NO DUPLICATE)
		let text = customText || input.value.trim();
		if (!text) return;

		// 🔥 PRIORITAS MODE KONFIRMASI WIZARD
		if (window.orderConfirmMode === true) {
			const val = text.toLowerCase().trim();
			const yesWords = ["ya", "iya", "y", "yes", "ok", "oke", "sip", "siap", "gas", "lanjut", "boleh", "setuju"];
			const cancelWords = ["batal", "cancel", "stop", "jangan", "ga", "gak", "nggak", "tidak", "gajadi", "ga jadi", "gak jadi", "tidak jadi"];

			if (cancelWords.includes(val)) {
				window.orderConfirmMode = false;
				input.value = "";
				if (typeof window.exitInputMode === "function") window.exitInputMode();
				return;
			}

			if (yesWords.includes(val)) {
				window.orderConfirmMode = false;
				input.value = "";
				if (typeof window.saveFinalOrder === "function") {
					window.saveFinalOrder();
				} else {
					addMsg("⚠️ Fungsi simpan final belum siap. Refresh halaman lalu coba lagi.", "ai");
				}
				return;
			}

			addMsg("Jawab ya/oke/sip untuk lanjut, atau batal untuk membatalkan.", "ai");
			input.value = "";
			return;
		}

		// 🚨 BLOCK CHAT JIKA INPUT MODE AKTIF (TAPI BUKAN KONFIRMASI)
		if (window.inputModeActive === true) return;

		// lanjut kirim
		hideHero();
		addMsg(text, "user");
		input.value = "";
		showTyping();

		fetch(aiConfig.endpoint, {
			method: "POST",
			headers: {
				"Content-Type": "application/x-www-form-urlencoded",
			},
			body: csrfBody({ message: text }),
		})
			.then((res) => res.json())
			.then((data) => {
				removeTyping();
				typeReply(data.reply || "Tidak ada respon");
			})
			.catch((err) => {
				console.error("FETCH ERROR:", err);
				removeTyping();
				addMsg("⚠️ Gagal menghubungi AI", "ai");
			});
	}
	/* =========================
       EVENTS
    ========================= */

	if (send) send.addEventListener("click", () => sendMsg());

	if (input)
		input.addEventListener("keydown", (e) => {
			if (e.key === "Enter") {
				e.preventDefault();
				sendMsg();
			}
		});
});
