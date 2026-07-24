document.addEventListener("DOMContentLoaded", function () {
	const design = document.getElementById("design_type_id");
	const part = document.getElementById("body_part_id");
	const price = document.getElementById("base_price");

	if (!design || !part || !price) return;

	const baseUrl = document.body?.dataset.baseUrl || window.__BASE_URL || "/";

	function loadPrice() {
		if (!design.value || !part.value) {
			price.value = 0;
			return;
		}

		fetch(
			baseUrl +
				"prices/get_base_price?design_type_id=" +
				design.value +
				"&body_part_id=" +
				part.value,
		)
			.then((res) => res.json())
			.then((data) => {
				price.value = data.base_price ?? 0;
			});
	}

	design.addEventListener("change", loadPrice);
	part.addEventListener("change", loadPrice);
});
document.addEventListener("DOMContentLoaded", function () {
	const toggle = document.getElementById("sbToggle");
	const sidebar = document.querySelector(".sidebar.sb");
	const overlay = document.getElementById("sbOverlay");

	// safety check
	if (!toggle || !sidebar || !overlay) return;

	function openSidebar() {
		sidebar.classList.add("open");
		overlay.classList.add("show");
		document.body.classList.add("sb-open");
		toggle.setAttribute("aria-expanded", "true");
	}

	function closeSidebar() {
		sidebar.classList.remove("open");
		overlay.classList.remove("show");
		document.body.classList.remove("sb-open");
		toggle.setAttribute("aria-expanded", "false");
	}

	// buka sidebar
	toggle.addEventListener("click", function (e) {
		e.stopPropagation();
		if (document.body.classList.contains("sb-open")) {
			closeSidebar();
			return;
		}
		openSidebar();
	});

	// tutup sidebar saat klik luar
	overlay.addEventListener("click", closeSidebar);

	// tutup sidebar setelah memilih menu di mobile
	sidebar.querySelectorAll("a.sb__item, .sb__logout").forEach(function (item) {
		item.addEventListener("click", function () {
			if (window.matchMedia("(max-width: 767px)").matches) closeSidebar();
		});
	});

	document.addEventListener("keydown", function (e) {
		if (e.key === "Escape") closeSidebar();
	});
});
