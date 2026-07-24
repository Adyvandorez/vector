<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php
$ai_info_groups = [
    [
        'title' => 'Monitoring & Data Bisnis',
        'description' => 'Gunakan bagian ini untuk membaca kondisi utama bisnis secara cepat, mulai dari income, order, invoice, status pembayaran, hingga aktivitas harian.',
        'items' => [
            [
                'title' => '1. Ringkasan Bulanan',
                'body'  => 'Menampilkan laporan performa bisnis pada bulan berjalan, termasuk total order, total income, order lunas, order belum lunas, serta gambaran pencapaian target bulanan.',
                'prompt' => 'Ringkasan bulan ini',
            ],
            [
                'title' => '2. Total Income Bulan Ini',
                'body'  => 'Menampilkan total pemasukan bulan berjalan secara ringkas tanpa detail tambahan. Cocok untuk pengecekan cepat performa pendapatan.',
                'prompt' => 'Total income bulan ini',
            ],
            [
                'title' => '3. Order Hari Ini',
                'body'  => 'Menampilkan jumlah order atau transaksi yang masuk pada hari ini agar aktivitas bisnis harian dapat dipantau dengan cepat.',
                'prompt' => 'Order hari ini',
            ],
            [
                'title' => '4. Invoice Belum Lunas',
                'body'  => 'Menampilkan daftar order yang belum lunas, termasuk nominal yang sudah dibayar, sisa tagihan, dan status pembayaran.',
                'prompt' => 'Cek order belum lunas',
            ],
            [
                'title' => '5. Order Nilai Tertinggi',
                'body'  => 'Mengidentifikasi transaksi dengan nilai terbesar untuk membantu melihat order paling signifikan dalam periode berjalan.',
                'prompt' => 'Order paling mahal',
            ],
            [
                'title' => '6. Total Seluruh Order',
                'body'  => 'Menampilkan jumlah seluruh order yang pernah tercatat di sistem sejak awal penggunaan.',
                'prompt' => 'Total seluruh order',
            ],
            [
                'title' => '7. Daftar Order Lunas',
                'body'  => 'Menampilkan daftar invoice yang sudah dibayar lunas untuk kebutuhan validasi pembayaran dan pengecekan riwayat transaksi.',
                'prompt' => 'Order lunas',
            ],
            [
                'title' => '8. Order Dalam Proses',
                'body'  => 'Menampilkan order yang masih aktif dikerjakan, seperti order berstatus masuk, proses, revisi, atau belum selesai.',
                'prompt' => 'Order proses',
            ],
        ],
    ],
    [
        'title' => 'Analisis & Insight Eksekutif',
        'description' => 'Bagian ini membantu membaca data secara lebih strategis. AI tidak hanya menampilkan angka, tetapi juga memberikan ringkasan, perbandingan, prioritas, dan rekomendasi bisnis.',
        'items' => [
            [
                'title' => '9. Desain Paling Laris',
                'body'  => 'Menganalisis jenis desain atau produk yang paling sering dipesan untuk membantu menentukan strategi promosi dan fokus produksi.',
                'prompt' => 'Desain paling laku',
            ],
            [
                'title' => '10. Bagian Paling Sering Dipesan',
                'body'  => 'Menampilkan bagian atau kategori body part yang paling sering muncul pada order, sehingga pola permintaan pelanggan lebih mudah dibaca.',
                'prompt' => 'Bagian paling sering dipesan',
            ],
            [
                'title' => '11. Klien Paling Aktif',
                'body'  => 'Mengidentifikasi pelanggan yang paling sering melakukan transaksi. Berguna untuk strategi loyalty, follow-up, dan relationship management.',
                'prompt' => 'Klien paling sering order',
            ],
            [
                'title' => '12. Pembayaran Terlambat',
                'body'  => 'Mendeteksi invoice yang melewati batas waktu pembayaran agar dapat segera ditindaklanjuti secara profesional.',
                'prompt' => 'Cek pembayaran tertunda',
            ],
            [
                'title' => '13. Perbandingan Omzet',
                'body'  => 'Membandingkan omzet bulan ini dengan bulan sebelumnya untuk melihat apakah performa bisnis sedang naik, turun, atau stabil.',
                'prompt' => 'Bandingkan omzet bulan ini',
            ],
            [
                'title' => '14. Analisis Penyebab Income Turun',
                'body'  => 'Membantu membaca kemungkinan penyebab penurunan income, seperti berkurangnya order, banyak invoice belum lunas, atau turunnya nilai transaksi.',
                'prompt' => 'Kenapa income bulan ini turun',
            ],
            [
                'title' => '15. Prioritas Follow-up Customer',
                'body'  => 'Menentukan pelanggan yang paling perlu di-follow up berdasarkan sisa pembayaran, nilai invoice, dan urgensi penyelesaian.',
                'prompt' => 'Siapa yang harus follow up hari ini',
            ],
            [
                'title' => '16. Deteksi Order Macet',
                'body'  => 'Mendeteksi order yang terlalu lama berada pada status proses atau revisi agar tidak terlewat dalam pengerjaan.',
                'prompt' => 'Cek order macet',
            ],
            [
                'title' => '17. Prediksi Income Bulan Ini',
                'body'  => 'Menghitung estimasi income sampai akhir bulan berdasarkan data pembayaran yang sudah masuk dan potensi pelunasan invoice berjalan.',
                'prompt' => 'Prediksi income bulan ini',
            ],
            [
                'title' => '18. Rekomendasi Harga',
                'body'  => 'Memberikan saran desain atau produk yang berpotensi dinaikkan harganya berdasarkan tingkat permintaan dan kontribusi terhadap pendapatan.',
                'prompt' => 'Desain mana yang cocok dinaikkan harganya',
            ],
            [
                'title' => '19. Ringkasan Harian Owner',
                'body'  => 'Membuat ringkasan singkat untuk owner mengenai aktivitas bisnis hari ini, seperti order baru, pembayaran masuk, dan hal yang perlu diprioritaskan.',
                'prompt' => 'Buat ringkasan harian owner',
            ],
            [
                'title' => '20. Laporan Bulanan',
                'body'  => 'Menyusun laporan bulanan yang lebih terstruktur, berisi performa order, pemasukan, invoice belum lunas, dan rekomendasi tindak lanjut.',
                'prompt' => 'Buat laporan bulanan',
            ],
            [
                'title' => '21. Desain Kurang Laku',
                'body'  => 'Menampilkan desain yang jarang dipesan agar dapat dipertimbangkan untuk promosi ulang, evaluasi harga, atau dinonaktifkan.',
                'prompt' => 'Desain yang jarang dipesan',
            ],
        ],
    ],
    [
        'title' => 'Generator Pesan Customer',
        'description' => 'AI dapat membantu membuat pesan siap kirim untuk customer dengan bahasa yang sopan, profesional, dan tetap terasa natural.',
        'items' => [
            [
                'title' => '22. Follow-up Pembayaran',
                'body'  => 'Membuat pesan pengingat pembayaran kepada customer dengan bahasa profesional dan tidak memaksa.',
                'prompt' => 'Buat pesan follow up pembayaran',
            ],
            [
                'title' => '23. Reminder DP',
                'body'  => 'Membuat pesan pengingat pembayaran Down Payment sebelum proses produksi dimulai.',
                'prompt' => 'Buat reminder DP',
            ],
            [
                'title' => '24. Konfirmasi Order Selesai',
                'body'  => 'Membuat pesan pemberitahuan bahwa order telah selesai dikerjakan dan siap dikirim atau diambil.',
                'prompt' => 'Order sudah selesai',
            ],
            [
                'title' => '25. Penagihan Halus',
                'body'  => 'Membuat pesan penagihan dengan nada sopan, tenang, dan profesional tanpa memberikan tekanan berlebihan kepada pelanggan.',
                'prompt' => 'Buat penagihan halus',
            ],
        ],
    ],
    [
        'title' => 'Simulasi & Perencanaan',
        'description' => 'Gunakan fitur ini untuk membantu mengambil keputusan sebelum melakukan perubahan harga atau mengevaluasi target bisnis.',
        'items' => [
            [
                'title' => '26. Simulasi Kenaikan Harga',
                'body'  => 'Menghitung estimasi dampak kenaikan harga terhadap potensi income agar keputusan perubahan harga lebih terukur.',
                'prompt' => 'Simulasi kenaikan harga',
            ],
            [
                'title' => '27. Cek Target Bulan',
                'body'  => 'Mengevaluasi apakah target omzet bulan berjalan sudah tercapai, termasuk persentase pencapaian dan sisa target yang perlu dikejar.',
                'prompt' => 'Cek target bulan',
            ],
        ],
    ],
    [
        'title' => 'Input Data Cepat',
        'description' => 'AI dapat membantu membuka mode input data agar admin lebih cepat menambahkan data baru tanpa harus mencari menu secara manual.',
        'items' => [
            [
                'title' => '28. Input Data Baru',
                'body'  => 'Membuka wizard input data untuk membantu admin menambahkan data melalui alur yang lebih cepat dan terarah.',
                'prompt' => 'Input data',
            ],
        ],
    ],
    [
        'title' => 'Tindakan Sistem',
        'description' => 'Beberapa perintah dapat mengubah data sistem. Untuk keamanan, tindakan penting harus melalui validasi atau konfirmasi sebelum dijalankan.',
        'danger' => true,
        'items' => [
            [
                'title' => '29. Tandai Order Lunas',
                'body'  => 'Mengubah status pembayaran order menjadi lunas setelah pembayaran benar-benar diterima dan data sudah divalidasi.',
                'prompt' => 'Tandai lunas',
                'danger' => true,
            ],
            [
                'title' => '30. Ubah Harga Desain',
                'body'  => 'Mengubah harga desain atau produk tertentu. Perubahan ini dapat memengaruhi transaksi berikutnya.',
                'prompt' => 'Ubah harga',
                'danger' => true,
            ],
            [
                'title' => '31. Nonaktifkan Desain',
                'body'  => 'Menonaktifkan desain agar tidak lagi digunakan dalam order baru. Cocok untuk produk yang sudah tidak diproduksi.',
                'prompt' => 'Nonaktifkan desain',
                'danger' => true,
            ],
            [
                'title' => '32. Hapus Order',
                'body'  => 'Menghapus data order dari sistem. Tindakan ini bersifat permanen dan harus dilakukan dengan hati-hati.',
                'prompt' => 'Hapus order',
                'danger' => true,
            ],
            [
                'title' => '33. Hapus Client',
                'body'  => 'Menghapus data client tertentu dari sistem apabila data tersebut sudah tidak digunakan atau memang perlu dibersihkan.',
                'prompt' => 'Hapus client',
                'danger' => true,
            ],
        ],
    ],
];

$quick_suggestions = [
    'Ringkasan bulan ini',
    'Order belum lunas',
    'Cek order macet',
    'Prediksi income bulan ini',
    'Buat pesan follow-up',
];
?>

<!-- =========================
     AI OVERLAY
========================= -->
<div id="aiOverlay"
    data-base-url="<?= base_url(); ?>"
    data-endpoint="<?= base_url('ai/chat'); ?>"
    data-img="<?= base_url('assets/img/Ai.png'); ?>">

    <div id="aiCard">

        <!-- HEADER -->
        <div class="ai-header">
            <div class="ai-title">
                <div class="ai-logo">⚡</div>
                AI Executive Assistant
            </div>

            <div class="ai-actions">
                <button class="ai-btn-top" id="aiInfoBtn" title="Panduan AI">?</button>
                <button class="ai-btn-top" id="aiRefresh" title="Muat ulang percakapan">⟳</button>
                <button class="ai-btn-top ai-btn-close" id="closeAi" title="Tutup">✕</button>
            </div>
        </div>

        <!-- CHAT AREA -->
        <div class="ai-chat" id="aiChat"></div>

        <!-- AI INFO PAGE -->
        <div class="ai-info-page" id="aiInfoPage">
            <div class="ai-info-inner">

                <h2>Tentang AI Executive Assistant</h2>

                <p>
                    AI Executive Assistant adalah asisten bisnis cerdas yang dirancang untuk membantu admin membaca,
                    menganalisis, dan memahami data invoice secara cepat, terstruktur, dan profesional.
                </p>

                <p>
                    Melalui perintah teks sederhana, AI dapat membantu memantau order, menghitung income,
                    membaca status pembayaran, menganalisis performa bisnis, membuat pesan untuk customer,
                    hingga memberikan rekomendasi tindak lanjut berdasarkan data yang tersedia di sistem.
                </p>

                <p>
                    Gunakan AI ini sebagai pendamping operasional harian. Admin tidak perlu membuka banyak menu
                    untuk mengecek informasi penting, cukup ketik perintah atau pilih salah satu tombol panduan
                    di bawah ini.
                </p>

                <?php foreach ($ai_info_groups as $group): ?>
                    <div class="ai-info-group">
                        <h2><?= html_escape($group['title']); ?></h2>

                        <?php if (!empty($group['description'])): ?>
                            <p><?= html_escape($group['description']); ?></p>
                        <?php endif; ?>

                        <?php foreach ($group['items'] as $item): ?>
                            <?php
                            $is_danger = !empty($item['danger']);
                            $btn_class = $is_danger ? 'ai-info-btn danger' : 'ai-info-btn';
                            ?>

                            <div class="ai-info-section">
                                <h3><?= html_escape($item['title']); ?></h3>

                                <p><?= html_escape($item['body']); ?></p>

                                <div class="ai-info-command">
                                    <button class="<?= $btn_class; ?>"
                                        data-prompt="<?= html_escape($item['prompt']); ?>">
                                        <?= html_escape($item['prompt']); ?>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>

                <div class="ai-info-group">
                    <h2>Tips Penggunaan</h2>

                    <div class="ai-info-section">
                        <h3>Gunakan perintah yang jelas</h3>
                        <p>
                            Tulis perintah secara singkat tetapi spesifik. Contohnya:
                            <strong>“Cek order belum lunas”</strong>,
                            <strong>“Prediksi income bulan ini”</strong>, atau
                            <strong>“Siapa yang harus follow up hari ini”</strong>.
                        </p>
                    </div>

                    <div class="ai-info-section">
                        <h3>Periksa ulang sebelum menjalankan aksi penting</h3>
                        <p>
                            Untuk tindakan seperti menghapus data, menandai lunas, mengubah harga, atau
                            menonaktifkan desain, pastikan data yang dipilih sudah benar sebelum melanjutkan.
                        </p>
                    </div>

                    <div class="ai-info-section">
                        <h3>Gunakan hasil AI sebagai pendukung keputusan</h3>
                        <p>
                            AI membantu membaca pola dan memberikan rekomendasi berdasarkan data sistem.
                            Keputusan akhir tetap berada pada admin atau owner bisnis.
                        </p>
                    </div>
                </div>

            </div>
        </div>

        <!-- SUGGESTIONS -->
        <div class="ai-suggest-bar" id="aiSuggestBar">
            <?php foreach ($quick_suggestions as $suggestion): ?>
                <button class="ai-suggest"><?= html_escape($suggestion); ?></button>
            <?php endforeach; ?>
        </div>

        <!-- INPUT AREA -->
        <div class="ai-input-wrap">
            <div class="ai-input-container">
                <input id="aiInput"
                    placeholder="Tanyakan data bisnis, invoice, pembayaran, atau insight customer..."
                    autocomplete="off">

                <button id="aiSend">Kirim</button>
            </div>
        </div>

    </div>
</div>