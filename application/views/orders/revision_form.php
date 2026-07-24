<div class="card">
    <div class="card-header">
        <h1>Edit Revisi</h1>
        <a class="btn mobile-back-btn" href="<?= base_url('orders/view/' . $order_id); ?>">Kembali</a>
    </div>

    <div class="card-body">
        <form method="post">
            <?= csrf_field(); ?>
            <div class="small">Catatan revisi</div>
            <textarea class="input" name="note" rows="5" required><?= htmlspecialchars($rev->note); ?></textarea>

            <div class="small u-mt-10">Biaya revisi</div>
            <input class="input js-money" type="text" inputmode="numeric" name="fee" value="<?= number_format((int)$rev->fee, 0, ',', '.'); ?>">

            <hr class="sep">
            <button class="btn btn-red" type="submit">Update Revisi</button>
        </form>
    </div>
</div>