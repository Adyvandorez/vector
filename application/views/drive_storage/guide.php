<div class="card drive-page drive-guide-page">
    <div class="card-header drive-page-header">
        <div>
            <h1>Panduan Google Drive Storage</h1>
            <div class="small">Alur aman untuk koneksi, sinkronisasi, migrasi, dan pembersihan file.</div>
        </div>
        <a class="btn mobile-back-btn" href="<?= base_url('drive-storage'); ?>">Kembali</a>
    </div>

    <div class="card-body drive-page-body">
        <div class="drive-intro small">
            Panduan ini menjelaskan alur penggunaan Google Drive Storage pada Vector Invoice secara ringkas dan profesional.
            Gunakan halaman ini saat pertama kali menghubungkan Drive, melakukan sinkronisasi file yang sudah ada, melakukan migrasi preview lama, atau membersihkan file lokal.
        </div>

        <hr class="sep">

        <div class="drive-stat-grid drive-guide-steps">
            <div class="card drive-stat-card">
                <div class="drive-stat-label small">Tahap 1</div>
                <div class="drive-stat-value drive-guide-title">Hubungkan Drive</div>
                <div class="drive-stat-note small">Login dengan akun Google pemilik folder Vector Invoice Storage.</div>
            </div>
            <div class="card drive-stat-card">
                <div class="drive-stat-label small">Tahap 2</div>
                <div class="drive-stat-value drive-guide-title">Sinkron / Migrasi</div>
                <div class="drive-stat-note small">Sinkronkan file yang sudah ada atau upload preview lama ke Google Drive.</div>
            </div>
            <div class="card drive-stat-card">
                <div class="drive-stat-label small">Tahap 3</div>
                <div class="drive-stat-value drive-guide-title">Bersihkan Lokal</div>
                <div class="drive-stat-note small">Hapus file preview lokal besar yang sudah aman, thumbnail kecil tetap disimpan.</div>
            </div>
        </div>

        <hr class="sep">

        <div class="drive-guide-grid">
            <div class="card drive-guide-card">
                <h2>Langkah Pemakaian</h2>
                <ol class="small drive-guide-list">
                    <li>Klik tombol <b>Hubungkan Google Drive</b> pada halaman Drive Storage.</li>
                    <li>Login menggunakan akun Google pemilik folder <b>Vector Invoice Storage</b>.</li>
                    <li>Setujui izin akses Drive yang diminta oleh aplikasi.</li>
                    <li>Jika file sudah terlihat ada di Google Drive tetapi status database masih pending, klik <b>Sinkronkan File Drive yang Sudah Ada</b> terlebih dahulu.</li>
                    <li>Jika file belum ada di Google Drive, klik <b>Migrasikan Preview Lama ke Drive</b>.</li>
                    <li>Jika file cukup banyak, klik tombol sinkronisasi atau migrasi beberapa kali sampai jumlah pending menjadi <b>0</b>.</li>
                    <li>Pastikan preview gambar tampil cepat memakai thumbnail lokal kecil.</li>
                    <li>Upload file master CDR dari halaman Jenis Desain atau Detail Order jika diperlukan.</li>
                    <li>Jika semua sudah aman, klik <b>Bersihkan File Lokal Aman</b> untuk menghapus file preview lokal besar yang sudah tervalidasi.</li>
                </ol>
            </div>

            <div class="card drive-guide-card">
                <h2>Penjelasan Tombol</h2>

                <div class="drive-guide-item">
                    <div class="u-bold">Hubungkan Google Drive</div>
                    <div class="small">Menghubungkan Vector Invoice dengan akun Google kamu memakai OAuth 2.0.</div>
                </div>

                <div class="drive-guide-item">
                    <div class="u-bold">Sinkronkan File Drive yang Sudah Ada</div>
                    <div class="small">Mencocokkan database dengan file yang sudah ada di Google Drive agar tidak terjadi upload duplikat.</div>
                </div>

                <div class="drive-guide-item">
                    <div class="u-bold">Migrasikan Preview Lama ke Drive</div>
                    <div class="small">Mengupload gambar lama ke Google Drive dan membuat thumbnail lokal kecil secara bertahap.</div>
                </div>

                <div class="drive-guide-item">
                    <div class="u-bold">Bersihkan File Lokal Aman</div>
                    <div class="small">Menghapus file preview lokal besar yang sudah punya Drive ID valid. Thumbnail kecil tetap disimpan.</div>
                </div>

                <div class="drive-guide-item is-last">
                    <div class="u-bold">Putuskan Koneksi</div>
                    <div class="small">Menghapus token OAuth dari database. File yang sudah ada di Google Drive tidak ikut terhapus.</div>
                </div>
            </div>
        </div>

        <div class="card drive-guide-card drive-security-card">
            <h2>Catatan Keamanan</h2>
            <ul class="small drive-guide-list">
                <li>Jangan hapus file lokal besar sebelum migrasi berhasil dan thumbnail lokal tampil normal.</li>
                <li>Gunakan tombol <b>Bersihkan File Lokal Aman</b> agar sistem memvalidasi file Drive terlebih dahulu sebelum menghapus backup lokal.</li>
                <li>Jika Google Drive belum terhubung, proses migrasi dan pembersihan lokal tidak akan dijalankan.</li>
                <li>Jika suatu file belum memiliki Drive ID, sistem akan melewatinya dan tidak akan menghapus file lokalnya.</li>
                <li>File CDR hanya disimpan di Drive, bukan di folder lokal.</li>
            </ul>
        </div>
    </div>
</div>
