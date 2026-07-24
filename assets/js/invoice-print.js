document.addEventListener("DOMContentLoaded", () => {
    const pdfBtn = document.getElementById("btnDownloadPdf");
    const jpgBtn = document.getElementById("btnDownloadJpg");
    const pdfButtons = Array.from(document.querySelectorAll(".js-invoice-download-pdf"));
    const jpgButtons = Array.from(document.querySelectorAll(".js-invoice-download-jpg"));
    const paper = document.getElementById("invoicePaper") || document.querySelector(".invoice-paper");
    const originalTitle = document.title || "Nota-Order";

    function safeFilename(name, extension = "jpg") {
        const ext = String(extension || "jpg").replace(/[^a-zA-Z0-9]/g, "") || "jpg";
        const raw = String(name || paper?.dataset?.filename || originalTitle || "Nota-Order");
        const withoutExt = raw.replace(/\.(pdf|jpg|jpeg|png)$/i, "");
        const base = withoutExt
            .normalize("NFD")
            .replace(/[\u0300-\u036f]/g, "")
            .replace(/[^a-zA-Z0-9\s_-]/g, "")
            .replace(/\s+/g, "-")
            .replace(/-+/g, "-")
            .replace(/^[-_\s]+|[-_\s]+$/g, "");
        return (base || "Nota-Order") + "." + ext.toLowerCase();
    }

    function safeTitle(name) {
        return safeFilename(name, "pdf").replace(/\.pdf$/i, "");
    }

    function setPrintTitle() {
        const base = safeTitle(pdfBtn?.dataset?.filename || paper?.dataset?.filename || originalTitle);
        document.title = base;
        const titleTag = document.querySelector("title");
        if (titleTag) titleTag.textContent = base;
        return base;
    }

    function restoreTitleSoon() {
        window.setTimeout(() => {
            document.title = originalTitle;
            const titleTag = document.querySelector("title");
            if (titleTag) titleTag.textContent = originalTitle;
        }, 800);
    }

    function setButtonLoading(button, isLoading, loadingText, fallbackText) {
        if (!button) return;
        button.classList.toggle("is-loading", isLoading);
        if (isLoading) {
            button.setAttribute("aria-busy", "true");
            button.disabled = true;
            button.dataset.originalText = button.textContent || fallbackText;
            button.textContent = loadingText;
        } else {
            button.removeAttribute("aria-busy");
            button.disabled = false;
            button.textContent = button.dataset.originalText || fallbackText;
        }
    }

    function copyComputedStyles(source, target) {
        if (!source || !target || source.nodeType !== 1 || target.nodeType !== 1) return;
        const computed = window.getComputedStyle(source);
        let cssText = "";
        for (let i = 0; i < computed.length; i++) {
            const prop = computed[i];
            cssText += prop + ":" + computed.getPropertyValue(prop) + ";";
        }
        target.setAttribute("style", cssText);

        const sourceChildren = Array.from(source.children || []);
        const targetChildren = Array.from(target.children || []);
        for (let i = 0; i < sourceChildren.length; i++) {
            copyComputedStyles(sourceChildren[i], targetChildren[i]);
        }
    }

    function blobToDataUrl(blob) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = () => resolve(reader.result);
            reader.onerror = reject;
            reader.readAsDataURL(blob);
        });
    }

    async function inlineImages(clone) {
        const images = Array.from(clone.querySelectorAll("img"));
        await Promise.all(images.map(async (img) => {
            const src = img.getAttribute("src");
            if (!src || src.startsWith("data:")) return;
            try {
                const absolute = new URL(src, window.location.href).href;
                const response = await fetch(absolute, { credentials: "same-origin", cache: "no-store" });
                if (!response.ok) return;
                const blob = await response.blob();
                img.setAttribute("src", await blobToDataUrl(blob));
            } catch (err) {
                console.warn("Gagal inline gambar nota:", err);
            }
        }));
    }

    function waitForImage(img) {
        return new Promise((resolve, reject) => {
            img.onload = resolve;
            img.onerror = reject;
        });
    }


    function parseInvoiceExportData() {
        const node = document.getElementById("invoiceExportData");
        if (node && node.textContent) {
            try {
                return JSON.parse(node.textContent);
            } catch (err) {
                console.warn("Data export nota tidak valid:", err);
            }
        }
        const tableRows = Array.from(document.querySelectorAll("tbody tr")).map((tr) => {
            const cells = tr.querySelectorAll("td");
            return {
                description: (cells[0]?.querySelector(".line-main")?.textContent || cells[0]?.textContent || "").trim(),
                note: (cells[0]?.querySelector(".muted")?.textContent || "").trim(),
                qty_price: "",
                amount: (cells[1]?.textContent || "").trim(),
            };
        });
        return {
            filename: paper?.dataset?.filename || originalTitle || "Nota-Order",
            logo_url: document.querySelector(".logo")?.src || "",
            brand: document.querySelector(".brandname")?.textContent?.trim() || "Ady_vandorez",
            subtitle: document.querySelector(".subtitle")?.textContent?.trim() || "Nota Pembayaran",
            contacts: Array.from(document.querySelectorAll(".invoice-contact span")).map((el) => el.textContent.trim()),
            status: document.querySelector(".badge")?.textContent?.trim() || "",
            invoice_no: document.querySelector(".invno")?.textContent?.trim() || "",
            date_label: "Tanggal: ",
            order_label: "Order: ",
            client_name: document.querySelector(".info-box .value")?.textContent?.trim() || "",
            client_phone: document.querySelector(".info-box .muted")?.textContent?.trim() || "",
            title: document.querySelectorAll(".info-box .value")?.[1]?.textContent?.trim() || "",
            design_body: document.querySelectorAll(".info-box .muted")?.[1]?.textContent?.trim() || "",
            items: tableRows.slice(0, Math.max(0, tableRows.length - 4)),
            addons: tableRows[tableRows.length - 4]?.amount || "Rp 0",
            revision_fee: tableRows[tableRows.length - 3]?.amount || "Rp 0",
            discount: tableRows[tableRows.length - 2]?.amount || "-Rp 0",
            total: tableRows[tableRows.length - 1]?.amount || "Rp 0",
            paid: "",
            remaining: "",
            note: "File Yang Sudah Di Kirim Tidak Bisa Revisi\nKecuali Kesalahan Desainer.",
            footer_left: "Ady_vandorez • Vektor Portrait Artist",
            footer_right: "Terima kasih",
        };
    }

    function loadSameOriginImage(src) {
        return new Promise((resolve) => {
            if (!src) return resolve(null);
            let url;
            try {
                url = new URL(src, window.location.href);
            } catch (err) {
                return resolve(null);
            }
            if (url.origin !== window.location.origin) return resolve(null);
            const img = new Image();
            img.onload = () => resolve(img);
            img.onerror = () => resolve(null);
            img.src = url.href;
        });
    }

    function drawRoundRect(ctx, x, y, w, h, r, fill, stroke, lineWidth = 1) {
        const radius = Math.min(r, w / 2, h / 2);
        ctx.beginPath();
        ctx.moveTo(x + radius, y);
        ctx.lineTo(x + w - radius, y);
        ctx.quadraticCurveTo(x + w, y, x + w, y + radius);
        ctx.lineTo(x + w, y + h - radius);
        ctx.quadraticCurveTo(x + w, y + h, x + w - radius, y + h);
        ctx.lineTo(x + radius, y + h);
        ctx.quadraticCurveTo(x, y + h, x, y + h - radius);
        ctx.lineTo(x, y + radius);
        ctx.quadraticCurveTo(x, y, x + radius, y);
        ctx.closePath();
        if (fill) {
            ctx.fillStyle = fill;
            ctx.fill();
        }
        if (stroke) {
            ctx.strokeStyle = stroke;
            ctx.lineWidth = lineWidth;
            ctx.stroke();
        }
    }

    function font(size, weight = 400) {
        return `${weight} ${size}px Arial, Helvetica, sans-serif`;
    }

    function wrapLines(ctx, text, maxWidth) {
        const paragraphs = String(text || "").split(/\n/);
        const lines = [];
        paragraphs.forEach((paragraph) => {
            const words = paragraph.split(/\s+/).filter(Boolean);
            if (!words.length) {
                lines.push("");
                return;
            }
            let line = "";
            words.forEach((word) => {
                const test = line ? line + " " + word : word;
                if (ctx.measureText(test).width > maxWidth && line) {
                    lines.push(line);
                    line = word;
                } else {
                    line = test;
                }
            });
            lines.push(line);
        });
        return lines;
    }

    function drawWrappedText(ctx, text, x, y, maxWidth, lineHeight, options = {}) {
        ctx.font = font(options.size || 18, options.weight || 400);
        ctx.fillStyle = options.color || "#f8f8f8";
        ctx.textAlign = options.align || "left";
        ctx.textBaseline = "top";
        const lines = wrapLines(ctx, text, maxWidth);
        lines.forEach((line, idx) => ctx.fillText(line, x, y + idx * lineHeight));
        return y + Math.max(1, lines.length) * lineHeight;
    }

    function drawRightText(ctx, text, x, y, size = 18, color = "#f8f8f8", weight = 400) {
        ctx.font = font(size, weight);
        ctx.fillStyle = color;
        ctx.textAlign = "right";
        ctx.textBaseline = "top";
        ctx.fillText(String(text || ""), x, y);
        ctx.textAlign = "left";
    }

    async function renderInvoiceToCanvasManual() {
        const data = parseInvoiceExportData();
        const items = Array.isArray(data.items) ? data.items : [];
        const detailRows = [
            ...items,
            { description: "Add-ons", amount: data.addons || "Rp 0" },
            { description: "Biaya Revisi", amount: data.revision_fee || "Rp 0" },
            { description: "Diskon", amount: data.discount || "-Rp 0" },
        ];

        const logicalWidth = 1400;
        const rowCount = Math.max(4, detailRows.length + 1);
        const logicalHeight = Math.max(980, 790 + rowCount * 76);
        const scale = Math.min(3, Math.max(2, Math.ceil(window.devicePixelRatio || 1)));
        const canvas = document.createElement("canvas");
        canvas.width = logicalWidth * scale;
        canvas.height = logicalHeight * scale;
        const ctx = canvas.getContext("2d");
        if (!ctx) throw new Error("Canvas tidak didukung browser.");
        ctx.scale(scale, scale);
        ctx.imageSmoothingEnabled = true;
        ctx.imageSmoothingQuality = "high";

        const bg = ctx.createLinearGradient(0, 0, logicalWidth, logicalHeight);
        bg.addColorStop(0, "#090909");
        bg.addColorStop(0.55, "#11100f");
        bg.addColorStop(1, "#0a0a0a");
        ctx.fillStyle = bg;
        ctx.fillRect(0, 0, logicalWidth, logicalHeight);

        ctx.save();
        const glow = ctx.createRadialGradient(180, 70, 10, 180, 70, 560);
        glow.addColorStop(0, "rgba(222,179,72,0.22)");
        glow.addColorStop(1, "rgba(222,179,72,0)");
        ctx.fillStyle = glow;
        ctx.fillRect(0, 0, 700, 520);
        ctx.restore();

        drawRoundRect(ctx, 86, 62, 1228, logicalHeight - 124, 28, "#111110", "#362b19", 2);

        const logo = await loadSameOriginImage(data.logo_url);
        if (logo) {
            const boxX = 130, boxY = 100, boxW = 150, boxH = 82;
            const ratio = Math.min(boxW / logo.width, boxH / logo.height);
            const w = logo.width * ratio;
            const h = logo.height * ratio;
            ctx.drawImage(logo, boxX + (boxW - w) / 2, boxY + (boxH - h) / 2, w, h);
        }

        drawWrappedText(ctx, data.brand || "Ady_vandorez", 315, 100, 520, 36, { size: 30, weight: 700, color: "#deb348" });
        drawWrappedText(ctx, data.subtitle || "Nota Pembayaran", 315, 142, 540, 24, { size: 17, color: "#f8f8f8" });
        let contactY = 184;
        (data.contacts || []).forEach((contact) => {
            contactY = drawWrappedText(ctx, contact, 315, contactY, 600, 22, { size: 15, color: "#b6b6b6" });
        });

        const statusText = String(data.status || "").toUpperCase();
        const statusW = Math.max(104, ctx.measureText(statusText).width + 42);
        drawRoundRect(ctx, 1180 - statusW, 100, statusW, 42, 21, "#231b10", "#deb348", 1.5);
        drawRightText(ctx, statusText, 1160, 111, 15, "#f2d275", 700);
        drawRightText(ctx, data.invoice_no || "", 1180, 158, 31, "#f8f8f8", 700);
        drawRightText(ctx, data.date_label || "", 1180, 205, 16, "#b6b6b6", 400);
        drawRightText(ctx, data.order_label || "", 1180, 229, 16, "#b6b6b6", 400);

        ctx.strokeStyle = "#2a261e";
        ctx.lineWidth = 1;
        ctx.beginPath();
        ctx.moveTo(126, 282);
        ctx.lineTo(1274, 282);
        ctx.stroke();

        drawRoundRect(ctx, 126, 316, 548, 120, 18, "#181512", "#2f2a20", 1);
        drawWrappedText(ctx, "Klien", 154, 344, 220, 20, { size: 15, weight: 700, color: "#deb348" });
        drawWrappedText(ctx, data.client_name || "-", 154, 372, 480, 26, { size: 22, weight: 700, color: "#f8f8f8" });
        drawWrappedText(ctx, data.client_phone || "", 154, 404, 480, 21, { size: 15, color: "#b6b6b6" });

        drawRoundRect(ctx, 726, 316, 548, 120, 18, "#181512", "#2f2a20", 1);
        drawWrappedText(ctx, "Judul Pekerjaan", 754, 344, 260, 20, { size: 15, weight: 700, color: "#deb348" });
        drawWrappedText(ctx, data.title || "-", 754, 372, 480, 26, { size: 22, weight: 700, color: "#f8f8f8" });
        drawWrappedText(ctx, data.design_body || "", 754, 404, 480, 21, { size: 15, color: "#b6b6b6" });

        const tableX = 126;
        const tableY = 476;
        const tableW = 1148;
        drawRoundRect(ctx, tableX, tableY, tableW, 58, 18, "#21190d", "#3a2e18", 1);
        drawWrappedText(ctx, "Deskripsi", tableX + 28, tableY + 19, 500, 22, { size: 17, weight: 700, color: "#f2d275" });
        drawRightText(ctx, "Biaya", tableX + tableW - 28, tableY + 19, 17, "#f2d275", 700);

        let y = tableY + 58;
        detailRows.forEach((row, idx) => {
            const desc = row.description || "-";
            const noteText = [row.note, row.qty_price].filter(Boolean).join("\n");
            ctx.font = font(18, 700);
            const descLines = wrapLines(ctx, desc, 760);
            ctx.font = font(14, 400);
            const noteLines = noteText ? wrapLines(ctx, noteText, 760) : [];
            const rowH = Math.max(62, 24 + descLines.length * 24 + noteLines.length * 19);
            ctx.fillStyle = idx % 2 === 0 ? "#121212" : "#151310";
            ctx.fillRect(tableX, y, tableW, rowH);
            ctx.strokeStyle = "#292929";
            ctx.beginPath();
            ctx.moveTo(tableX, y + rowH);
            ctx.lineTo(tableX + tableW, y + rowH);
            ctx.stroke();
            let textY = y + 18;
            textY = drawWrappedText(ctx, desc, tableX + 28, textY, 760, 24, { size: 18, weight: 700, color: "#f8f8f8" });
            if (noteText) {
                drawWrappedText(ctx, noteText, tableX + 28, textY + 2, 760, 19, { size: 14, color: "#b6b6b6" });
            }
            drawRightText(ctx, row.amount || "", tableX + tableW - 28, y + 20, 18, "#f8f8f8", 700);
            y += rowH;
        });

        const totalRowH = 72;
        ctx.fillStyle = "#21190d";
        ctx.fillRect(tableX, y, tableW, totalRowH);
        drawRightText(ctx, "TOTAL", tableX + tableW - 260, y + 24, 18, "#f2d275", 700);
        drawRightText(ctx, data.total || "", tableX + tableW - 28, y + 19, 26, "#f2d275", 700);
        y += totalRowH + 40;

        drawRoundRect(ctx, 126, y, 548, 142, 18, "#181512", "#2f2a20", 1);
        drawWrappedText(ctx, "Keterangan", 154, y + 28, 280, 22, { size: 17, weight: 700, color: "#f8f8f8" });
        drawWrappedText(ctx, data.note || "", 154, y + 60, 480, 24, { size: 16, color: "#b6b6b6" });

        drawRoundRect(ctx, 726, y, 548, 142, 18, "#181512", "#2f2a20", 1);
        drawWrappedText(ctx, "Total", 754, y + 28, 160, 22, { size: 16, color: "#b6b6b6" });
        drawRightText(ctx, data.total || "", 1242, y + 24, 24, "#f2d275", 700);
        drawWrappedText(ctx, "Paid", 754, y + 66, 160, 22, { size: 16, color: "#b6b6b6" });
        drawRightText(ctx, data.paid || "", 1242, y + 64, 18, "#f8f8f8", 700);
        drawWrappedText(ctx, "Sisa", 754, y + 102, 160, 22, { size: 16, color: "#b6b6b6" });
        drawRightText(ctx, data.remaining || "", 1242, y + 100, 18, "#f8f8f8", 700);

        ctx.strokeStyle = "#2a261e";
        ctx.beginPath();
        ctx.moveTo(126, logicalHeight - 130);
        ctx.lineTo(1274, logicalHeight - 130);
        ctx.stroke();
        drawWrappedText(ctx, data.footer_left || "Ady_vandorez", 126, logicalHeight - 102, 560, 22, { size: 16, color: "#f8f8f8" });
        drawRightText(ctx, data.footer_right || "Terima kasih", 1274, logicalHeight - 102, 16, "#b6b6b6", 400);

        return canvas;
    }

    async function renderInvoiceCanvasReliable() {
        try {
            return await renderInvoiceToCanvasManual();
        } catch (manualErr) {
            console.warn("Render manual nota gagal, mencoba render DOM:", manualErr);
            return await renderPaperToCanvas();
        }
    }

    async function renderPaperToCanvas() {
        if (!paper) throw new Error("Elemen nota tidak ditemukan.");

        const rect = paper.getBoundingClientRect();
        const width = Math.ceil(Math.max(rect.width, paper.scrollWidth));
        const height = Math.ceil(Math.max(rect.height, paper.scrollHeight));
        if (!width || !height) throw new Error("Ukuran nota tidak valid.");

        const clone = paper.cloneNode(true);
        clone.removeAttribute("id");
        clone.setAttribute("xmlns", "http://www.w3.org/1999/xhtml");
        copyComputedStyles(paper, clone);
        await inlineImages(clone);

        const xhtml = new XMLSerializer().serializeToString(clone);
        const svg = `
            <svg xmlns="http://www.w3.org/2000/svg" width="${width}" height="${height}" viewBox="0 0 ${width} ${height}">
                <foreignObject width="100%" height="100%">
                    ${xhtml}
                </foreignObject>
            </svg>`;

        const svgBlob = new Blob([svg], { type: "image/svg+xml;charset=utf-8" });
        const svgUrl = URL.createObjectURL(svgBlob);
        const img = new Image();
        img.decoding = "async";
        img.src = svgUrl;
        await waitForImage(img);

        const scale = Math.min(3, Math.max(2, Math.ceil(window.devicePixelRatio || 1)));
        const canvas = document.createElement("canvas");
        canvas.width = width * scale;
        canvas.height = height * scale;
        const ctx = canvas.getContext("2d");
        ctx.imageSmoothingEnabled = true;
        ctx.imageSmoothingQuality = "high";
        ctx.fillStyle = window.getComputedStyle(paper).backgroundColor || "#0a0a0a";
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
        URL.revokeObjectURL(svgUrl);
        return canvas;
    }

    function downloadBlob(blob, filename) {
        const url = URL.createObjectURL(blob);
        const a = document.createElement("a");
        a.href = url;
        a.download = filename;
        a.rel = "noopener";
        document.body.appendChild(a);
        a.click();
        a.remove();
        setTimeout(() => URL.revokeObjectURL(url), 1500);
    }

    function dataUrlToBytes(dataUrl) {
        const base64 = String(dataUrl).split(",")[1] || "";
        const binary = atob(base64);
        const bytes = new Uint8Array(binary.length);
        for (let i = 0; i < binary.length; i++) bytes[i] = binary.charCodeAt(i);
        return bytes;
    }

    function makePdfBlobFromJpeg(jpegBytes, imageWidth, imageHeight) {
        const encoder = new TextEncoder();
        const chunks = [];
        const offsets = [0];
        let position = 0;

        function addString(value) {
            const bytes = encoder.encode(value);
            chunks.push(bytes);
            position += bytes.length;
        }

        function addBytes(bytes) {
            chunks.push(bytes);
            position += bytes.length;
        }

        function startObject(id) {
            offsets[id] = position;
            addString(id + " 0 obj\n");
        }

        function endObject() {
            addString("\nendobj\n");
        }

        const pageLandscape = imageWidth >= imageHeight;
        const pageWidth = pageLandscape ? 841.89 : 595.28;
        const pageHeight = pageLandscape ? 595.28 : 841.89;
        const margin = 18;
        const maxWidth = pageWidth - margin * 2;
        const maxHeight = pageHeight - margin * 2;
        const ratio = Math.min(maxWidth / imageWidth, maxHeight / imageHeight);
        const drawWidth = imageWidth * ratio;
        const drawHeight = imageHeight * ratio;
        const x = (pageWidth - drawWidth) / 2;
        const y = (pageHeight - drawHeight) / 2;
        const content = `q\n${drawWidth.toFixed(2)} 0 0 ${drawHeight.toFixed(2)} ${x.toFixed(2)} ${y.toFixed(2)} cm\n/Im1 Do\nQ\n`;
        const contentBytes = encoder.encode(content);

        addString("%PDF-1.4\n%\xE2\xE3\xCF\xD3\n");

        startObject(1);
        addString("<< /Type /Catalog /Pages 2 0 R >>");
        endObject();

        startObject(2);
        addString("<< /Type /Pages /Kids [3 0 R] /Count 1 >>");
        endObject();

        startObject(3);
        addString(`<< /Type /Page /Parent 2 0 R /MediaBox [0 0 ${pageWidth.toFixed(2)} ${pageHeight.toFixed(2)}] /Resources << /XObject << /Im1 4 0 R >> >> /Contents 5 0 R >>`);
        endObject();

        startObject(4);
        addString(`<< /Type /XObject /Subtype /Image /Width ${imageWidth} /Height ${imageHeight} /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length ${jpegBytes.length} >>\nstream\n`);
        addBytes(jpegBytes);
        addString("\nendstream");
        endObject();

        startObject(5);
        addString(`<< /Length ${contentBytes.length} >>\nstream\n`);
        addBytes(contentBytes);
        addString("endstream");
        endObject();

        const xrefPosition = position;
        addString("xref\n0 6\n0000000000 65535 f \n");
        for (let i = 1; i <= 5; i++) {
            addString(String(offsets[i]).padStart(10, "0") + " 00000 n \n");
        }
        addString(`trailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n${xrefPosition}\n%%EOF`);

        const totalLength = chunks.reduce((sum, chunk) => sum + chunk.length, 0);
        const pdfBytes = new Uint8Array(totalLength);
        let offset = 0;
        chunks.forEach((chunk) => {
            pdfBytes.set(chunk, offset);
            offset += chunk.length;
        });
        return new Blob([pdfBytes], { type: "application/pdf" });
    }

    async function downloadPdfFromBrowser(event) {
        event?.preventDefault?.();
        if (!paper) return;
        const trigger = event?.currentTarget || pdfBtn || pdfButtons[0];
        const filename = safeFilename(trigger?.dataset?.filename || pdfBtn?.dataset?.filename || paper.dataset.filename, "pdf");
        setPrintTitle();
        setButtonLoading(trigger, true, "Menyiapkan PDF...", "Download PDF");

        try {
            const canvas = await renderInvoiceCanvasReliable();
            const jpegDataUrl = canvas.toDataURL("image/jpeg", 0.95);
            const jpegBytes = dataUrlToBytes(jpegDataUrl);
            const pdfBlob = makePdfBlobFromJpeg(jpegBytes, canvas.width, canvas.height);
            downloadBlob(pdfBlob, filename);
        } catch (err) {
            console.warn("Download PDF otomatis gagal, membuka dialog print browser:", err);
            setPrintTitle();
            window.print();
            restoreTitleSoon();
        } finally {
            setButtonLoading(trigger, false, "Menyiapkan PDF...", "Download PDF");
        }
    }

    async function downloadJpgFromBrowser(event) {
        event?.preventDefault?.();
        if (!paper) return;
        const trigger = event?.currentTarget || jpgBtn || jpgButtons[0];
        const filename = safeFilename(trigger?.dataset?.filename || jpgBtn?.dataset?.filename || paper.dataset.filename, "jpg");
        setButtonLoading(trigger, true, "Menyiapkan JPG...", "Download JPG");

        try {
            const canvas = await renderInvoiceCanvasReliable();
            const blob = await new Promise((resolve) => canvas.toBlob(resolve, "image/jpeg", 0.95));
            if (!blob) throw new Error("Browser gagal membuat blob JPG.");
            downloadBlob(blob, filename);
        } catch (err) {
            console.error("Download JPG nota gagal:", err);
            alert("Download JPG gagal dibuat dari browser. Refresh halaman nota lalu coba lagi. Endpoint server tidak dibuka otomatis agar tidak muncul error Situs tidak tersedia.");
        } finally {
            setButtonLoading(trigger, false, "Menyiapkan JPG...", "Download JPG");
        }
    }

    setPrintTitle();
    window.addEventListener("beforeprint", setPrintTitle);
    window.addEventListener("afterprint", restoreTitleSoon);
    pdfButtons.forEach((button) => button.addEventListener("click", downloadPdfFromBrowser));
    jpgButtons.forEach((button) => button.addEventListener("click", downloadJpgFromBrowser));
});
