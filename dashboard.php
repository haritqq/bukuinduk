<?php
require 'config.php';

// Proteksi halaman, jika belum login, tendang ke login.php
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$message_type = '';

// Ambil ID guru dari user_id
$result_guru_id = $conn->query("SELECT id, nama_lengkap, foto_profil FROM guru WHERE user_id = $user_id");
$guru_data = $result_guru_id->fetch_assoc();
$guru_id = $guru_data['id'];
$nama_guru = $guru_data['nama_lengkap'];
$foto_profil = $guru_data['foto_profil'];


// --- LOGIKA PENANGANAN FORM (POST REQUEST) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Aksi untuk Simpan/Update BIODATA
    if (isset($_POST['simpan_biodata'])) {
        // Upload Foto
        if (isset($_FILES['foto_profil']) && $_FILES['foto_profil']['error'] == 0) {
            $target_dir = "uploads/";
            $new_filename = $user_id . "_" . basename($_FILES["foto_profil"]["name"]);
            $target_file = $target_dir . $new_filename;
            if (move_uploaded_file($_FILES["foto_profil"]["tmp_name"], $target_file)) {
                $foto_profil_sql = ", foto_profil = '$new_filename'";
            }
        } else {
            $foto_profil_sql = "";
        }
        
        $sql = "UPDATE guru SET 
                    nama_lengkap = ?, nama_panggilan = ?, nip = ?, no_karpeg = ?, tempat_lahir = ?, 
                    tanggal_lahir = ?, jenis_kelamin = ?, gol_darah = ?, agama = ?, status_pernikahan = ?, 
                    suku = ?, alamat = ?, kegemaran = ?, penghargaan = ?
                    $foto_profil_sql
                WHERE id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssssssssssi", 
            $_POST['nama_lengkap'], $_POST['nama_panggilan'], $_POST['nip'], $_POST['no_karpeg'], $_POST['tempat_lahir'],
            $_POST['tanggal_lahir'], $_POST['jenis_kelamin'], $_POST['gol_darah'], $_POST['agama'], $_POST['status_pernikahan'],
            $_POST['suku'], $_POST['alamat'], $_POST['kegemaran'], $_POST['penghargaan'], $guru_id
        );
        if ($stmt->execute()) {
             $message = "Biodata berhasil diperbarui!"; $message_type = 'success';
        } else {
             $message = "Error: " . $stmt->error; $message_type = 'error';
        }
    }
    
    // 2. Aksi untuk Tambah RIWAYAT PENDIDIKAN
    if (isset($_POST['tambah_pendidikan'])) {
        $sql = "INSERT INTO riwayat_pendidikan (guru_id, tingkat, tahun_mulai, tahun_selesai, nama_institusi, alamat_institusi, jurusan, ijazah_tahun) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isssssss", $guru_id, $_POST['tingkat'], $_POST['tahun_mulai'], $_POST['tahun_selesai'], $_POST['nama_institusi'], $_POST['alamat_institusi'], $_POST['jurusan'], $_POST['ijazah_tahun']);
        if ($stmt->execute()) {
            $message = "Riwayat Pendidikan berhasil ditambahkan!"; $message_type = 'success';
        } else {
            $message = "Error: " . $stmt->error; $message_type = 'error';
        }
    }

    // 3. Aksi untuk Tambah RIWAYAT PELATIHAN
    if (isset($_POST['tambah_pelatihan'])) {
        $sql = "INSERT INTO riwayat_pelatihan (guru_id, jenis_bidang_studi, alamat, tahun, lama_pelatihan, penyelenggara, sponsor, hasil, keterangan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issssssss", $guru_id, $_POST['jenis_bidang_studi'], $_POST['alamat'], $_POST['tahun'], $_POST['lama_pelatihan'], $_POST['penyelenggara'], $_POST['sponsor'], $_POST['hasil'], $_POST['keterangan']);
        if ($stmt->execute()) {
            $message = "Riwayat Pelatihan berhasil ditambahkan!"; $message_type = 'success';
        } else {
            $message = "Error: " . $stmt->error; $message_type = 'error';
        }
    }

    // 4. Aksi untuk Tambah RIWAYAT PEKERJAAN
    if (isset($_POST['tambah_pekerjaan'])) {
        $sql = "INSERT INTO riwayat_pekerjaan (guru_id, sk_dari, sk_tanggal, sk_nomor, uraian_pangkat_jabatan, golongan_ruang, gaji_pokok, terhitung_mulai, terhitung_sampai, masa_kerja, keterangan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isssssissss", $guru_id, $_POST['sk_dari'], $_POST['sk_tanggal'], $_POST['sk_nomor'], $_POST['uraian'], $_POST['gol_ruang'], $_POST['gaji_pokok'], $_POST['terhitung_mulai'], $_POST['terhitung_sampai'], $_POST['masa_kerja'], $_POST['keterangan']);
         if ($stmt->execute()) {
            $message = "Riwayat Pekerjaan berhasil ditambahkan!"; $message_type = 'success';
        } else {
            $message = "Error: " . $stmt->error; $message_type = 'error';
        }
    }

    // Redirect untuk menghindari resubmit form saat refresh
    header("Location: dashboard.php?page=" . ($_POST['page'] ?? 'biodata') . "&msg=" . urlencode($message) . "&type=" . $message_type);
    exit();
}

// Ambil pesan dari URL jika ada
if(isset($_GET['msg'])){
    $message = $_GET['msg'];
    $message_type = $_GET['type'];
}

// --- AMBIL SEMUA DATA DARI DATABASE UNTUK DITAMPILKAN ---
$biodata = $conn->query("SELECT * FROM guru WHERE id = $guru_id")->fetch_assoc();
$pendidikan = $conn->query("SELECT * FROM riwayat_pendidikan WHERE guru_id = $guru_id ORDER BY tahun_selesai DESC");
$pelatihan = $conn->query("SELECT * FROM riwayat_pelatihan WHERE guru_id = $guru_id ORDER BY tahun DESC");
$pekerjaan = $conn->query("SELECT * FROM riwayat_pekerjaan WHERE guru_id = $guru_id ORDER BY sk_tanggal DESC");

// Menentukan halaman aktif
$page = $_GET['page'] ?? 'dashboard';

// Helper function untuk sanitasi output
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Buku Induk Guru</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .msg { padding: 10px; margin-bottom: 20px; border-radius: 5px; color: #fff; }
        .msg.success { background-color: #28a745; }
        .msg.error { background-color: #dc3545; }
    </style>
</head>
<body>
    <div class="app-container">
        <aside class="sidebar">
            <div class="sidebar-header"><h3>Buku Induk Digital</h3></div>
            <ul class="sidebar-menu">
                <li><a href="?page=dashboard" class="menu-link <?= $page == 'dashboard' ? 'active' : '' ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="?page=biodata" class="menu-link <?= $page == 'biodata' ? 'active' : '' ?>"><i class="fas fa-user"></i> Biodata Diri</a></li>
                <li><a href="?page=pendidikan" class="menu-link <?= $page == 'pendidikan' ? 'active' : '' ?>"><i class="fas fa-graduation-cap"></i> Riwayat Pendidikan</a></li>
                <li><a href="?page=pelatihan" class="menu-link <?= $page == 'pelatihan' ? 'active' : '' ?>"><i class="fas fa-chalkboard-teacher"></i> Riwayat Pelatihan</a></li>
                <li><a href="?page=pekerjaan" class="menu-link <?= $page == 'pekerjaan' ? 'active' : '' ?>"><i class="fas fa-briefcase"></i> Riwayat Pekerjaan</a></li>
                <li><a href="?page=print" class="menu-link <?= $page == 'print' ? 'active' : '' ?>"><i class="fas fa-print"></i> Print Dokumen</a></li>
                <li><a href="logout.php" class="menu-link logout"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <header class="header">
                <div class="header-left">
                    <div class="welcome-text">
                        <b>Selamat Datang, <?= e($nama_guru) ?></b>
                        <small>Jabatan Saat Ini: Guru Mata Pelajaran</small>
                    </div>
                </div>
                <div class="header-center"><div id="clock"></div></div>
                <div class="header-right">
                    <img src="uploads/<?= e($foto_profil) ?>" alt="Foto Profil" class="profile-pic-header" onerror="this.src='https://via.placeholder.com/40'">
                </div>
            </header>

            <div class="content-area">
                <?php if (!empty($message)): ?>
                    <div class="msg <?= $message_type ?>"><?= $message ?></div>
                <?php endif; ?>

                <?php if ($page == 'dashboard'): 
                    // Menghitung jumlah data untuk statistik
                    $jumlah_pendidikan = $pendidikan->num_rows;
                    $jumlah_pelatihan = $pelatihan->num_rows;
                    $jumlah_pekerjaan = $pekerjaan->num_rows;
                ?>
                <div id="dashboard-content">
                    
                    <div class="dashboard-grid">
                        <div class="stat-card stat-card-1">
                            <div class="icon-box"><i class="fas fa-graduation-cap"></i></div>
                            <div class="info">
                                <h4><?= $jumlah_pendidikan ?></h4>
                                <p>Riwayat Pendidikan</p>
                            </div>
                        </div>
                        <div class="stat-card stat-card-2">
                            <div class="icon-box"><i class="fas fa-chalkboard-teacher"></i></div>
                            <div class="info">
                                <h4><?= $jumlah_pelatihan ?></h4>
                                <p>Riwayat Pelatihan</p>
                            </div>
                        </div>
                        <div class="stat-card stat-card-3">
                            <div class="icon-box"><i class="fas fa-briefcase"></i></div>
                            <div class="info">
                                <h4><?= $jumlah_pekerjaan ?></h4>
                                <p>Riwayat Pekerjaan</p>
                            </div>
                        </div>
                        <div class="stat-card stat-card-4">
                            <div class="icon-box"><i class="fas fa-check-circle"></i></div>
                            <div class="info">
                                <h4>75%</h4>
                                <p>Kelengkapan Data</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="action-grid">
                        <div class="action-card profile-card-dashboard">
                            <h3>Profil Saya</h3>
                            <img src="uploads/<?= e($biodata['foto_profil']) ?>" alt="Foto Profil" class="profile-pic-large" onerror="this.src='https://via.placeholder.com/120'">
                            <h3 style="margin-bottom: 5px;"><?= e($biodata['nama_lengkap']) ?></h3>
                            <p style="color: var(--text-muted);">NIP: <?= e($biodata['nip']) ?></p><br>
                            <a href="?page=biodata" class="btn" style="margin-top: 20px;">Edit Profil</a>
                        </div>

                        <div class="action-card">
                            <h3>Akses Cepat</h3>
                            <p>Lengkapi atau perbarui data Anda dengan mudah melalui tautan di bawah ini.</p>
                            <ul style="list-style: none; padding-left: 0;">
                                <li style="margin-bottom: 10px;"><a href="?page=pendidikan" style="text-decoration: none;">+ Tambah Riwayat Pendidikan</a></li>
                                <li style="margin-bottom: 10px;"><a href="?page=pelatihan" style="text-decoration: none;">+ Tambah Riwayat Pelatihan</a></li>
                                <li style="margin-bottom: 10px;"><a href="?page=pekerjaan" style="text-decoration: none;">+ Tambah Riwayat Pekerjaan</a></li>
                                <li style="margin-top: 20px;"><a href="?page=print" style="text-decoration: none; font-weight: 600;">🖨️ Lihat & Cetak Dokumen Lengkap</a></li>
                            </ul>
                        </div>
                    </div>

                </div>
                <?php endif; ?>
                
                <?php if ($page == 'biodata'): ?>
                <div class="content-section">
                    <form action="dashboard.php" method="post" enctype="multipart/form-data">
                        <input type="hidden" name="page" value="biodata">
                        <h4>Formulir Biodata Diri</h4>
                        <div class="form-group">
                            <label>Upload Foto Profil (Kosongkan jika tidak ingin mengubah)</label>
                            <input type="file" name="foto_profil">
                        </div>
                        <div class="form-grid">
                            <div class="form-group"><label>Nama Lengkap</label><input type="text" name="nama_lengkap" value="<?= e($biodata['nama_lengkap']) ?>"></div>
                            <div class="form-group"><label>Nama Panggilan</label><input type="text" name="nama_panggilan" value="<?= e($biodata['nama_panggilan']) ?>"></div>
                            <div class="form-group"><label>NIP / NUPTK</label><input type="text" name="nip" value="<?= e($biodata['nip']) ?>"></div>
                            <div class="form-group"><label>Nomor Karpeg</label><input type="text" name="no_karpeg" value="<?= e($biodata['no_karpeg']) ?>"></div>
                            <div class="form-group"><label>Tempat Lahir</label><input type="text" name="tempat_lahir" value="<?= e($biodata['tempat_lahir']) ?>"></div>
                            <div class="form-group"><label>Tanggal Lahir</label><input type="date" name="tanggal_lahir" value="<?= e($biodata['tanggal_lahir']) ?>"></div>
                            <div class="form-group"><label>Jenis Kelamin</label><select name="jenis_kelamin">
                                <option value="Laki-laki" <?= $biodata['jenis_kelamin'] == 'Laki-laki' ? 'selected' : '' ?>>Laki-laki</option>
                                <option value="Perempuan" <?= $biodata['jenis_kelamin'] == 'Perempuan' ? 'selected' : '' ?>>Perempuan</option>
                            </select></div>
                            <div class="form-group"><label>Gol. Darah</label><input type="text" name="gol_darah" value="<?= e($biodata['gol_darah']) ?>"></div>
                            <div class="form-group"><label>Agama</label><input type="text" name="agama" value="<?= e($biodata['agama']) ?>"></div>
                            <div class="form-group"><label>Status Pernikahan</label><input type="text" name="status_pernikahan" value="<?= e($biodata['status_pernikahan']) ?>"></div>
                            <div class="form-group"><label>Suku/Kepercayaan</label><input type="text" name="suku" value="<?= e($biodata['suku']) ?>"></div>
                            <div class="form-group full-width"><label>Alamat Rumah</label><textarea name="alamat" rows="3"><?= e($biodata['alamat']) ?></textarea></div>
                            <div class="form-group"><label>Kegemaran</label><input type="text" name="kegemaran" value="<?= e($biodata['kegemaran']) ?>"></div>
                            <div class="form-group full-width"><label>Penghargaan</label><textarea name="penghargaan" rows="3"><?= e($biodata['penghargaan']) ?></textarea></div>
                        </div>
                        <button type="submit" name="simpan_biodata" class="btn">Simpan Biodata</button>
                    </form>
                </div>
                <?php endif; ?>

                <?php if ($page == 'pendidikan'): ?>
                <div class="content-section">
                    <h4>Data Riwayat Pendidikan</h4>
                    <table class="data-table">
                        <thead><tr><th>No</th><th>Tingkat</th><th>Tahun Mulai</th><th>Tahun Selesai</th><th>Nama Institusi</th><th>Alamat Institusi</th><th>Jurusan</th><th>Ijazah Tahun</th></tr></thead>
                        <tbody>
                            <?php $no=1; while($row = $pendidikan->fetch_assoc()): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><?= e($row['tingkat']) ?></td>
                                <td><?= e($row['tahun_mulai']) ?></td>
                                <td><?= e($row['tahun_selesai']) ?></td>
                                <td><?= e($row['nama_institusi']) ?></td>
                                <td><?= e($row['alamat_institusi']) ?></td>
                                <td><?= e($row['jurusan']) ?></td>
                                <td><?= e($row['ijazah_tahun']) ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <hr style="margin: 20px 0;">
                    <h4>Tambah Riwayat Pendidikan Baru</h4>
                    <form action="dashboard.php" method="post">
                        <input type="hidden" name="page" value="pendidikan">
                        <div class="form-grid">
                            <div class="form-group"><label>Tingkat</label><input type="text" name="tingkat" required></div>
                            <div class="form-group"><label>Tahun Mulai</label><input type="text" name="tahun_mulai" required></div>
                            <div class="form-group"><label>Tahun Selesai</label><input type="text" name="tahun_selesai" required></div>
                            <div class="form-group"><label>Nama Sekolah/Institusi</label><input type="text" name="nama_institusi" required></div>
                            <div class="form-group"><label>Alamat Sekolah/Institusi</label><input type="text" name="alamat_institusi" required></div>
                            <div class="form-group"><label>Jurusan</label><input type="text" name="jurusan"></div>
                            <div class="form-group"><label>Ijazah Tahun</label><input type="text" name="ijazah_tahun"></div>
                        </div>
                        <button type="submit" name="tambah_pendidikan" class="btn">Tambah & Simpan</button>
                    </form>
                </div>
                <?php endif; ?>

                <?php if ($page == 'pelatihan'): ?>
                <div class="content-section">
                    <h4>Data Riwayat Pelatihan</h4>
                     <table class="data-table">
                        <thead><tr><th>No</th><th>Nama / Jenis Bidang Studi</th><th>Penyelenggara</th><th>Sponsor</th><th>Alamat</th><th>Tahun</th><th>Lama</th><th>Hasil</th><th>Ket.</th></tr></thead>
                        <tbody>
                            <?php $no=1; while($row = $pelatihan->fetch_assoc()): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><?= e($row['jenis_bidang_studi']) ?></td>
                                <td><?= e($row['penyelenggara']) ?></td>
                                <td><?= e($row['sponsor']) ?></td>
                                <td><?= e($row['alamat']) ?></td>
                                <td><?= e($row['tahun']) ?></td>
                                <td><?= e($row['lama_pelatihan']) ?></td>
                                <td><?= e($row['hasil']) ?></td>
                                <td><?= e($row['keterangan']) ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <hr style="margin: 20px 0;">
                    <h4>Tambah Riwayat Pelatihan Baru</h4>
                    <form action="dashboard.php" method="post">
                         <input type="hidden" name="page" value="pelatihan">
                         <div class="form-grid">
                            <div class="form-group"><label>Nama / Jenis Bidang Studi</label><input type="text" name="jenis_bidang_studi"></div>
                            <div class="form-group"><label>Alamat</label><input type="text" name="alamat"></div>
                            <div class="form-group"><label>Tahun</label><input type="text" name="tahun"></div>
                            <div class="form-group"><label>Lama Pelatihan</label><input type="text" name="lama_pelatihan"></div>
                            <div class="form-group"><label>Penyelenggara</label><input type="text" name="penyelenggara"></div>
                            <div class="form-group"><label>Sponsor</label><input type="text" name="sponsor"></div>
                            <div class="form-group"><label>Hasil</label><input type="text" name="hasil"></div>
                            <div class="form-group full-width"><label>Keterangan</label><textarea name="keterangan" rows="2"></textarea></div>
                         </div>
                        <button type="submit" name="tambah_pelatihan" class="btn">Tambah & Simpan</button>
                    </form>
                </div>
                <?php endif; ?>

                 <?php if ($page == 'pekerjaan'): ?>
                <div class="content-section">
                    <h4>Data Riwayat Pekerjaan</h4>
                    <table class="data-table">
                        <thead><tr><th>No</th><th>SK Dari</th><th>SK Tgl</th><th>SK No.</th><th>Pangkat</th><th>Gol. Ruang</th><th>Gaji Pokok</th><th>Th. Mulai</th><th>Th. Sampai</th><th>Masa Kerja</th><th>Ket.</th></tr></thead>
                        <tbody>
                             <?php $no=1; while($row = $pekerjaan->fetch_assoc()): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><?= e($row['sk_dari']) ?></td>
                                <td><?= e($row['sk_tanggal']) ?></td>
                                <td><?= e($row['sk_nomor']) ?></td>
                                <td><?= e($row['uraian_pangkat_jabatan']) ?></td>
                                <td><?= e($row['golongan_ruang']) ?></td>
                                <td><?= e($row['gaji_pokok']) ?></td>
                                <td><?= e($row['terhitung_mulai']) ?></td>
                                <td><?= e($row['terhitung_sampai']) ?></td>
                                <td><?= e($row['masa_kerja']) ?></td>
                                <td><?= e($row['keterangan']) ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <hr style="margin: 20px 0;">
                    <h4>Tambah Riwayat Pekerjaan Baru</h4>
                    <form action="dashboard.php" method="post">
                        <input type="hidden" name="page" value="pekerjaan">
                        <div class="form-grid">
                            <div class="form-group"><label>Surat Keputusan (Dari)</label><input type="text" name="sk_dari"></div>
                            <div class="form-group"><label>SK Tanggal</label><input type="date" name="sk_tanggal"></div>
                            <div class="form-group"><label>SK Nomor</label><input type="text" name="sk_nomor"></div>
                            <div class="form-group"><label>Golongan Ruang</label><input type="text" name="gol_ruang"></div>
                            <div class="form-group full-width"><label>Uraian Perubahan Pangkat & Jabatan</label><textarea name="uraian" rows="2"></textarea></div>
                            <!-- <div class="form-group"><label>Gaji Pokok</label><input type="number" name="gaji_pokok"></div> -->
                            <div class="form-group">
                                <label>Gaji Pokok</label>
                                <input type="text" name="gaji_pokok" id="gaji_pokok_input">
                            </div>
                            <div class="form-group"><label>Terhitung Mulai</label><input type="text" name="terhitung_mulai"></div>
                            <div class="form-group"><label>Terhitung Sampai</label><input type="text" name="terhitung_sampai"></div>
                            <div class="form-group"><label>Masa Kerja</label><input type="text" name="masa_kerja"></div>
                            <div class="form-group"><label>Keterangan</label><input type="text" name="keterangan"></div>
                        </div>
                        <button type="submit" name="tambah_pekerjaan" class="btn">Tambah & Simpan</button>
                    </form>
                </div>
                <?php endif; ?>
                
                <?php if ($page == 'print'): ?>
                <div id="print-content" class="content-section">
                    <div class="print-controls" style="text-align:right; margin-bottom:20px;">
                        <button class="btn" onclick="window.print()"><i class="fas fa-print"></i> Print Dokumen</button>
                    </div>
                    <div class="print-area">
                        <h2 style="text-align:center;">SMA NEGERI 8 BANDA ACEH</h2>
                        <h2 style="text-align:center;">BUKU INDUK TENAGA KEPENDIDIKAN DAN GURU</h2>
                        <hr><br>
                        <h3>A. Biodata Diri</h3>
                        <table class="biodata-table">
                            <tr>
                                <td rowspan="15"><img src="uploads/<?= e($foto_profil) ?>" alt="Foto Profil" style="width: 150px; height: 200px;" onerror="this.src='https://via.placeholder.com/40'"></td>
                            </tr>
                            <tr>
                                <td>1.</td>
                                <td>a. </td>
                                <td><p><strong> Nama Lengkap</strong></p></td>
                                <td><b>:</b></td>
                                <td><?= e($biodata['nama_lengkap']) ?></td>
                            </tr>
                            <tr>
                                <td></td>
                                <td>b. </td>
                                <td><p><strong> Nama Panggilan</strong></p></td>
                                <td><b>:</b></td>
                                <td><?= e($biodata['nama_panggilan']) ?></td>
                            </tr>
                            <tr>
                                <td>2.</td>
                                <td></td>
                                <td><p><strong>NIP</strong></p></td>
                                <td><b>:</b></td>
                                <td> <?= e($biodata['nip']) ?> </td>
                            </tr>
                            <tr>
                                <td>3.</td>
                                <td></td>
                                <td><p><strong>No. KARPEG</strong></p></td>
                                <td><b>:</b></td>
                                <td><?= e($biodata['no_karpeg']) ?></td>
                            </tr>
                            <tr>
                                <td>4.</td>
                                <td></td>
                                <td><p><strong>Tempat Lahir</strong> </p></td>
                                <td><b>:</b></td>
                                <td><?= e($biodata['tempat_lahir']) ?></td>
                            </tr>
                            <tr>
                                <td>5.</td>
                                <td></td>
                                <td><p><strong>Tanggal Lahir</strong> </p></td>
                                <td><b>:</b></td>
                                <td><?= e($biodata['tanggal_lahir']) ?></td>
                            </tr>
                            <tr>
                                <td>6.</td>
                                <td></td>
                                <td><p><strong>Jenis Kelamin</strong> </p></td>
                                <td><b>:</b></td>
                                <td><?= e($biodata['jenis_kelamin']) ?></td>
                            </tr>
                            <tr>
                                <td>7. </td>
                                <td></td>
                                <td><p><strong>Gol Darah</strong> </p></td>
                                <td><b>:</b></td>
                                <td><?= e($biodata['gol_darah']) ?></td>
                            </tr>
                            <tr>
                                <td>8.</td>
                                <td></td>
                                <td><p><strong>Agama</strong> </p></td>
                                <td><b>:</b></td>
                                <td><?= e($biodata['agama']) ?></td>
                            </tr>
                            <tr>
                                <td>9.</td>
                                <td></td>
                                <td><p><strong>Status Penikahan</strong> </p></td>
                                <td><b>:</b></td>
                                <td><?= e($biodata['status_pernikahan']) ?></td>
                            </tr>
                            <tr>
                                <td>10.</td>
                                <td></td>
                                <td><p><strong>Suku</strong> </p></td>
                                <td><b>:</b></td>
                                <td><?= e($biodata['suku']) ?></td>
                            </tr>
                            <tr>
                                <td>11.</td>
                                <td></td>
                                <td><p><strong>Alamat</strong> </p></td>
                                <td><b>:</b></td>
                                <td><?= e($biodata['alamat']) ?></td>
                            </tr>
                            <tr>
                                <td>12.</td>
                                <td></td>
                                <td><p><strong>Kegemaran</strong> </p></td>
                                <td><b>:</b></td>
                                <td><?= e($biodata['kegemaran']) ?></td>
                            </tr>
                            <tr>
                                <td>13.</td>
                                <td></td>
                                <td><p><strong>Penghargaan</strong> </p></td>
                                <td><b>:</b></td>
                                <td><?= e($biodata['penghargaan']) ?></td>
                            </tr>
                        </table><br>
                        
                        <hr><br>
                        <h3>B. Riwayat Pendidikan</h3>
                        <table class="data-table">
                            <thead><tr><th>No</th><th>Tingkat</th><th>Institusi</th><th>Jurusan</th><th>Tahun Selesai</th></tr></thead>
                            <tbody>
                                <?php mysqli_data_seek($pendidikan, 0); $no=1; while($row = $pendidikan->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><?= e($row['tingkat']) ?></td>
                                    <td><?= e($row['nama_institusi']) ?></td>
                                    <td><?= e($row['jurusan']) ?></td>
                                    <td><?= e($row['tahun_selesai']) ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>

                        <hr><br>
                        <h3>C. Riwayat Pelatihan</h3>
                        <table class="data-table">
                            <thead><tr><th>No</th><th>Nama / Jenis Bidang Studi</th><th>Penyelenggara</th><th>Tahun</th><th>Lama Pelatihan</th><th>Sponsor</th></tr></thead>
                            <tbody>
                                <?php mysqli_data_seek($pelatihan, 0); $no=1; while($row = $pelatihan->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><?= e($row['jenis_bidang_studi']) ?></td>
                                    <td><?= e($row['penyelenggara']) ?></td>
                                    <td><?= e($row['sponsor']) ?></td>
                                    <td><?= e($row['tahun']) ?></td>
                                    <td><?= e($row['lama_pelatihan']) ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>

                        <hr><br>
                        <h3>C. Riwayat Pekerjaan</h3>
                        <table class="data-table">
                            <thead><tr><th>No</th><th>SK Dari</th><th>SK Tgl</th><th>SK No.</th><th>Pangkat</th><th>Gol./Ruang</th><th>Gaji Pokok</th><th>Th. Mulai</th><th>Th. Sampai</th><th>Masa Kerja</th><th>Ket.</th></tr></thead>
                            <tbody>
                                <?php mysqli_data_seek($pekerjaan, 0); $no=1; while($row = $pekerjaan->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><?= e($row['sk_dari']) ?></td>
                                    <td><?= e($row['sk_tanggal']) ?></td>
                                    <td><?= e($row['sk_nomor']) ?></td>
                                    <td><?= e($row['uraian_pangkat_jabatan']) ?></td>
                                    <td><?= e($row['golongan_ruang']) ?></td>
                                    <td><?= e($row['gaji_pokok']) ?></td>
                                    <td><?= e($row['terhitung_mulai']) ?></td>
                                    <td><?= e($row['terhitung_sampai']) ?></td>
                                    <td><?= e($row['masa_kerja']) ?></td>
                                    <td><?= e($row['keterangan']) ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

            </div>
        </main>
    </div>

    

    <script>
        document.addEventListener('DOMContentLoaded', function() {
        const inputElement = document.getElementById('gaji_pokok_input');

        // Fungsi untuk memformat angka dengan pemisah ribuan (titik)
        function formatRupiah(angka) {
            // Hapus semua karakter non-angka (kecuali koma/titik desimal jika diperlukan,
            // tapi untuk gaji pokok, kita anggap bilangan bulat)
            let bilangan = String(angka).replace(/[^0-9]/g, ''); 
            
            if (bilangan === '') return '';

            // Ubah menjadi format angka dengan pemisah ribuan titik
            let number_string = bilangan.toString(),
                sisa    = number_string.length % 3,
                rupiah  = number_string.substr(0, sisa),
                ribuan  = number_string.substr(sisa).match(/\d{3}/g);
            
            if (ribuan) {
                separator = sisa ? '.' : '';
                rupiah += separator + ribuan.join('.');
            }
            return rupiah;
        }

        // Event listener yang dipicu saat ada input (ketika mengetik)
        inputElement.addEventListener('input', function(e) {
            this.value = formatRupiah(this.value);
        });
        
        // Optional: Jika Anda ingin nilai yang dikirim ke server adalah angka murni,
        // Anda perlu membuat hidden input atau membersihkan nilai sebelum dikirim.
        // Contoh saat formulir disubmit:
        // document.querySelector('form').addEventListener('submit', function(e) {
        //     let nilaiMurni = inputElement.value.replace(/[^0-9]/g, '');
        //     // Simpan nilaiMurni ke hidden input atau ubah nilai inputElement.value
        // });
    });

        function updateClock() {
            const now = new Date();
            const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
            const day = days[now.getDay()];
            const date = now.toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' });
            const time = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            document.getElementById('clock').innerHTML = `${day}, ${date} | ${time}`;
        }
        setInterval(updateClock, 1000);
        updateClock();
    </script>
</body>
</html>