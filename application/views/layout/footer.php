<!-- SIDEBAR OVERLAY -->
<div class="sb-overlay" id="sbOverlay"></div>


<!-- =========================
     MAIN APP SCRIPT
========================= -->
<script src="<?= base_url('assets/js/theme-mode.js?v=20260721ui1'); ?>"></script>
<script src="<?= base_url('assets/js/app.js?v=20260721ui1'); ?>"></script>
<script src="<?= base_url('assets/js/ui-confirm.js?v=20260620ui3'); ?>"></script>
<script src="<?= base_url('assets/js/flash-toast.js?v=20260721ui1'); ?>"></script>
<script src="<?= base_url('assets/js/image-lightbox.js?v=20260721ui1'); ?>"></script>

<!-- AI overlay dipisah agar footer tetap ringkas dan mudah dirawat. -->
<?php $this->load->view('ai/overlay'); ?>

<!-- =========================
     PAGE SCRIPT
     Script khusus halaman dimuat sebelum AI agar tidak saling menimpa.
========================= -->
<?php if (!empty($page_js) && is_array($page_js)): ?>
    <?php foreach ($page_js as $js): ?>
        <script src="<?= base_url('assets/js/' . $js . '?v=20260721ui1'); ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>

<!-- Chat AI utama -->
<script src="<?= base_url('assets/js/ai.js?v=20260620inputfix1'); ?>"></script>

<!-- Wizard input data AI -->
<script src="<?= base_url('assets/js/ai-input-mode.js?v=20260620inputfix1'); ?>"></script>

</body>
</html>
