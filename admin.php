<?php
session_start();
date_default_timezone_set('Asia/Jakarta'); 

$file_json = 'artikel.json';
$file_archive = 'archive.json'; // DATABASE BARU KHUSUS ARCHIVE
$file_traffic = 'traffic.json';

if (!file_exists($file_json)) { file_put_contents($file_json, '[]'); }
if (!file_exists($file_archive)) { file_put_contents($file_archive, '[]'); }
if (!file_exists($file_traffic)) { file_put_contents($file_traffic, json_encode(['hits' => 0, 'visitors' => []])); }

// --- API TRACKER / CCTV ---
if (isset($_GET['action']) && $_GET['action'] == 'track') {
    $ip = $_SERVER['REMOTE_ADDR'];
    $waktu = date('d-M-Y H:i:s');
    $traffic_data = json_decode(file_get_contents($file_traffic), true);
    $traffic_data['hits']++; 
    array_unshift($traffic_data['visitors'], ['ip' => $ip, 'waktu' => $waktu]);
    $traffic_data['visitors'] = array_slice($traffic_data['visitors'], 0, 50);
    file_put_contents($file_traffic, json_encode($traffic_data, JSON_PRETTY_PRINT));
    exit; 
}

// --- LOGIKA LOG KELUAR ---
if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    session_destroy();
    header("Location: admin.php");
    exit;
}

// --- LOGIKA LOG MASUK ---
if (isset($_POST['login'])) {
    $input_user = $_POST['username'];
    $input_pass = $_POST['password'];

    $username_admin = 'admin'; 
  
    $hash_password_benar = 'c8004d3757b5ccc77e9f0f65654d946b725763cc169c764b7ae7b62e0fab8388';

    if ($input_user === $username_admin && hash('sha256', $input_pass) === $hash_password_benar) {
        $_SESSION['status_login'] = true;
        header("Location: admin.php");
        exit;
    } else {
        $error_login = "Akses Ditolak! Username atau Password salah.";
    }
}

// --- LOGIKA UPLOAD EVENT ---
if (isset($_POST['upload_event'])) {
    $data_baru = [
        'id' => time(), 
        'judul' => $_POST['judul'],
        'tanggal' => $_POST['tanggal'],
        'gambar' => $_POST['gambar'],
        'deskripsi' => $_POST['deskripsi'],
        'link_dokumen' => $_POST['link_dokumen']
    ];
    $data_artikel = json_decode(file_get_contents($file_json), true);
    $data_artikel[] = $data_baru;
    if (file_put_contents($file_json, json_encode($data_artikel, JSON_PRETTY_PRINT))) {
        // Vaksin Anti-Kloning (Redirect)
        header("Location: admin.php?page=upload&status=sukses");
        exit;
    } 
}

// --- LOGIKA UPLOAD ARCHIVE ---
if (isset($_POST['upload_archive'])) {
    $data_baru_archive = [
        'id' => time(), 
        'judul' => $_POST['judul_archive'],
        'gambar' => $_POST['gambar_archive']
    ];
    $data_archive_json = json_decode(file_get_contents($file_archive), true);
    $data_archive_json[] = $data_baru_archive;
    if (file_put_contents($file_archive, json_encode($data_archive_json, JSON_PRETTY_PRINT))) {
        // Vaksin Anti-Kloning (Redirect)
        header("Location: admin.php?page=archive&status=sukses");
        exit;
    } 
}

// Menangkap pesan sukses dari URL setelah di-redirect
if (isset($_GET['status']) && $_GET['status'] == 'sukses') {
    $sukses_pesan = "Mantap! Data berhasil dipublikasikan.";
}

$traffic_data = json_decode(file_get_contents($file_traffic), true);
$current_page = isset($_GET['page']) ? $_GET['page'] : 'upload';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Garnadyaksa</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
    <style>
        :root { --primary-red: #D32F2F; --deep-black: #121212; --clean-white: #FFFFFF; --slate-gray: #F5F5F5; }
        body { font-family: 'Open Sans', sans-serif; background-color: var(--slate-gray); margin: 0; color: var(--deep-black); overflow-x: hidden; }
        
        body.login-mode { display: flex; justify-content: center; align-items: center; height: 100vh; }
        .login-box { width: 360px; max-width: 90%; margin: 0 auto; }
        .login-logo { font-size: 2.2rem; font-family: 'Montserrat', sans-serif; font-weight: 700; margin-bottom: 1.5rem; text-align: center; }
        .login-logo span { color: var(--primary-red); }
        .login-card { background: var(--clean-white); padding: 30px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); border-top: 5px solid var(--primary-red); }
        .login-card p { text-align: center; color: #666; margin-bottom: 20px; font-weight: 600; }
        
        /* HEADER & HAMBURGER MENU */
        .admin-header { background-color: var(--deep-black); color: var(--clean-white); display: flex; justify-content: space-between; align-items: center; padding: 1rem 5%; box-shadow: 0 2px 10px rgba(0,0,0,0.2); position: sticky; top: 0; z-index: 1000; }
        .admin-logo { font-family: 'Montserrat', sans-serif; font-size: 1.5rem; font-weight: 700; z-index: 1001; }
        .admin-logo span { color: var(--primary-red); }
        
        .hamburger { display: flex; flex-direction: column; cursor: pointer; gap: 6px; z-index: 1001; }
        .hamburger span { width: 30px; height: 3px; background-color: var(--clean-white); border-radius: 3px; transition: all 0.3s ease; }
        .hamburger.active span:nth-child(1) { transform: translateY(9px) rotate(45deg); }
        .hamburger.active span:nth-child(2) { opacity: 0; }
        .hamburger.active span:nth-child(3) { transform: translateY(-9px) rotate(-45deg); }

        .admin-nav { position: fixed; top: 0; right: -250px; width: 250px; height: 100vh; background-color: var(--deep-black); display: flex; flex-direction: column; padding-top: 80px; transition: right 0.3s ease; box-shadow: -5px 0 15px rgba(0,0,0,0.5); z-index: 1000; }
        .admin-nav.active { right: 0; }
        .admin-nav a { color: #ccc; text-decoration: none; font-weight: 600; padding: 15px 25px; border-left: 4px solid transparent; transition: 0.3s; font-family: 'Montserrat', sans-serif; }
        .admin-nav a:hover { color: var(--clean-white); background-color: #222; }
        .admin-nav a.active { color: var(--clean-white); border-left: 4px solid var(--primary-red); background-color: #1a1a1a; }
        
        .nav-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 999; display: none; opacity: 0; transition: opacity 0.3s; }
        .nav-overlay.active { display: block; opacity: 1; }
        
        .admin-container { max-width: 700px; margin: 2rem auto; background: var(--clean-white); padding: 2.5rem; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); border-top: 5px solid var(--primary-red); width: 90%; }
        h2 { font-family: 'Montserrat', sans-serif; text-align: center; margin-top: 0; margin-bottom: 1.5rem; }
        
        .stats-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px; }
        .stat-box { background: var(--deep-black); color: white; padding: 20px; border-radius: 8px; text-align: center; border-bottom: 4px solid var(--primary-red); }
        .stat-box h3 { margin: 0; font-size: 2.5rem; color: var(--primary-red); font-family: 'Montserrat', sans-serif; }
        .stat-box p { margin: 5px 0 0 0; font-size: 0.9rem; color: #ccc; font-weight: 600; }
        .table-ip { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 0.85rem; }
        .table-ip th, .table-ip td { padding: 12px; border-bottom: 1px solid #eee; text-align: left; }
        .table-ip th { background-color: var(--slate-gray); font-weight: 600; }
        .scroll-box { max-height: 300px; overflow-y: auto; border: 1px solid #eee; border-radius: 6px; }

        .form-group { margin-bottom: 1.5rem; }
        label { display: block; font-weight: 600; margin-bottom: 0.5rem; color: #444; }
        input, textarea { width: 100%; padding: 0.9rem; border: 1px solid #ccc; border-radius: 6px; font-family: 'Open Sans', sans-serif; box-sizing: border-box; background-color: #fcfcfc; }
        input:focus, textarea:focus { outline: none; border-color: var(--primary-red); }
        textarea { resize: vertical; height: 120px; }
        button { width: 100%; background-color: var(--primary-red); color: white; padding: 1rem; border: none; border-radius: 6px; font-size: 1.1rem; font-weight: bold; cursor: pointer; transition: 0.3s; font-family: 'Montserrat', sans-serif; }
        button:hover { background-color: #b71c1c; }
        
        .alert { padding: 1rem; margin-bottom: 1.5rem; border-radius: 6px; text-align: center; font-weight: bold; }
        .alert-error { background-color: #ffebee; color: #c62828; border: 1px solid #ffcdd2; }
        .alert-success { background-color: #e8f5e9; color: #2e7d32; border: 1px solid #c8e6c9; }
        .nav-keluar { border-top: 1px solid #333; margin-top: auto; color: var(--primary-red) !important; }
    </style>
</head>

<?php if(!isset($_SESSION['status_login'])): ?>
<body class="login-mode">
    <div class="login-box">
        <div class="login-logo">Admin<span>Panel</span></div>
        <div class="login-card">
            <p>Silakan Log Masuk</p>
            <?php if(isset($error_login)): ?> <div class="alert alert-error"><?= $error_login ?></div> <?php endif; ?>
            <form action="" method="POST">
                <div class="form-group"><input type="text" name="username" placeholder="Username" required></div>
                <div class="form-group"><input type="password" name="password" placeholder="Password" required></div>
                <button type="submit" name="login">Log Masuk</button>
            </form>
        </div>
    </div>
</body>

<?php else: ?>
<body>
    <header class="admin-header">
        <div class="admin-logo">Admin<span>Panel</span></div>
        <div class="hamburger" id="hamburger-icon">
            <span></span><span></span><span></span>
        </div>
        
        <nav class="admin-nav" id="sidebar-menu">
            <a href="admin.php?page=upload" class="<?= $current_page == 'upload' ? 'active' : '' ?>">📝 Upload Event</a>
            <a href="admin.php?page=archive" class="<?= $current_page == 'archive' ? 'active' : '' ?>">📸 Upload Archive</a>
            <a href="admin.php?page=traffic" class="<?= $current_page == 'traffic' ? 'active' : '' ?>">📊 Traffic Web</a>
            <a href="index.html" target="_blank">🌐 Lihat Web OSIS</a>
            <a href="admin.php?action=logout" class="nav-keluar" onclick="return confirm('Yakin ingin keluar?')">🚪 Keluar</a>
        </nav>
    </header>

    <div class="nav-overlay" id="overlay"></div>

    <?php if($current_page == 'traffic'): ?>
        <div class="admin-container">
            <h2>Statistik Traffic</h2>
            <div class="stats-grid">
                <div class="stat-box"><h3><?= $traffic_data['hits'] ?></h3><p>Total Klik</p></div>
                <div class="stat-box"><h3><?= count($traffic_data['visitors']) ?></h3><p>Jejak IP</p></div>
            </div>
            <div class="scroll-box">
                <table class="table-ip">
                    <tr><th>Waktu Akses (WIB)</th><th>Alamat IP</th></tr>
                    <?php if(empty($traffic_data['visitors'])): ?>
                        <tr><td colspan="2" style="text-align:center;">Belum ada pengunjung</td></tr>
                    <?php else: ?>
                        <?php foreach($traffic_data['visitors'] as $v): ?>
                            <tr><td><?= $v['waktu'] ?></td><td><span style="font-family: monospace; font-weight:bold; color:var(--primary-red);"><?= $v['ip'] ?></span></td></tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </table>
            </div>
        </div>

    <?php elseif($current_page == 'archive'): ?>
        <div class="admin-container">
            <h2>Upload Foto Archive</h2>
            <?php if(isset($sukses_pesan)): ?> <div class="alert alert-success"><?= $sukses_pesan ?></div> <?php endif; ?>
            <?php if(isset($error_pesan)): ?> <div class="alert alert-error"><?= $error_pesan ?></div> <?php endif; ?>
            <form action="" method="POST">
                <div class="form-group"><label>Judul Kegiatan (Muncul saat di-hover)</label><input type="text" name="judul_archive" required placeholder="Contoh: LDKS 2025"></div>
                <div class="form-group"><label>Link Foto / Gambar</label><input type="url" name="gambar_archive" required placeholder="https://..."></div>
                <button type="submit" name="upload_archive">Tambahkan ke Archive</button>
            </form>
        </div>

    <?php else: ?>
        <div class="admin-container">
            <h2>Upload Event Baru</h2>
            <?php if(isset($sukses_pesan)): ?> <div class="alert alert-success"><?= $sukses_pesan ?></div> <?php endif; ?>
            <?php if(isset($error_pesan)): ?> <div class="alert alert-error"><?= $error_pesan ?></div> <?php endif; ?>
            <form action="" method="POST">
                <div class="form-group"><label>Judul Event</label><input type="text" name="judul" required></div>
                <div class="form-group"><label>Tanggal Pelaksanaan</label><input type="text" name="tanggal" required placeholder="Contoh: 15 Oktober 2026"></div>
                <div class="form-group"><label>Link Gambar Poster</label><input type="url" name="gambar" required placeholder="https://..."></div>
                <div class="form-group"><label>Link Dokumentasi (G-Drive)</label><input type="url" name="link_dokumen" placeholder="https://..." required></div>
                <div class="form-group"><label>Deskripsi Lengkap</label><textarea name="deskripsi" required></textarea></div>
                <button type="submit" name="upload_event">Publikasikan Event</button>
            </form>
        </div>
    <?php endif; ?>

    <script>
        const hamburger = document.getElementById('hamburger-icon');
        const sidebar = document.getElementById('sidebar-menu');
        const overlay = document.getElementById('overlay');

        function toggleMenu() {
            hamburger.classList.toggle('active');
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        }

        hamburger.addEventListener('click', toggleMenu);
        overlay.addEventListener('click', toggleMenu);
    </script>
</body>
<?php endif; ?>
</html>