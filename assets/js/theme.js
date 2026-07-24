(function () {
	const KEY = "vi_theme";

	function apply(theme) {
		document.documentElement.setAttribute("data-theme", theme);
	}

	// on load
	const saved = localStorage.getItem(KEY);
	if (saved === "light" || saved === "dark") {
		apply(saved);
	} else {
		apply("dark"); // default
	}

	window.VI_THEME = {
		toggle: function () {
			const cur = document.documentElement.getAttribute("data-theme") || "dark";
			const next = cur === "dark" ? "light" : "dark";
			apply(next);
			localStorage.setItem(KEY, next);

			// update toggle UI if exists
			const chk = document.getElementById("themeToggle");
			if (chk) chk.checked = next === "light";
		},
		syncUI: function () {
			const cur = document.documentElement.getAttribute("data-theme") || "dark";
			const chk = document.getElementById("themeToggle");
			if (chk) chk.checked = cur === "light";
		},
	};
})();
