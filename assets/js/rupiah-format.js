document.addEventListener("DOMContentLoaded", () => {
    function onlyDigits(value) {
        return String(value || "").replace(/[^0-9]/g, "");
    }

    function formatRupiahNumber(value, allowEmpty = false) {
        const digits = onlyDigits(value);
        if (!digits) return allowEmpty ? "" : "0";
        return digits.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }

    function bindMoney(input) {
        const allowEmpty = input.hasAttribute("data-empty");
        input.value = formatRupiahNumber(input.value, allowEmpty);

        input.addEventListener("focus", () => {
            if (input.value === "0") input.value = "";
        });

        input.addEventListener("input", () => {
            const caretEnd = input.selectionStart === input.value.length;
            input.value = formatRupiahNumber(input.value, allowEmpty);
            if (caretEnd) input.selectionStart = input.selectionEnd = input.value.length;
        });

        input.addEventListener("blur", () => {
            input.value = formatRupiahNumber(input.value, allowEmpty);
        });
    }

    document.querySelectorAll(".js-money").forEach(bindMoney);

    window.viMoney = {
        digits: onlyDigits,
        format(value) { return formatRupiahNumber(value, false); },
        int(value) {
            const digits = onlyDigits(value);
            return digits ? parseInt(digits, 10) : 0;
        },
    };
});
