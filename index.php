<?php
session_start();
require_once "function.php";

if (!isset($_SESSION["akun-admin"]) && !isset($_SESSION["akun-user"])) {
    header("Location: login.php");
    exit;
}

if (isset($_GET["transaksi"])) {
    $menu = ambil_data("SELECT * FROM transaksi");
} else if (isset($_GET["pesanan"])) {
    // Hapus notifikasi jika admin mengunjungi halaman pesanan
    if (isset($_SESSION["notif-pesanan-baru"])) {
        unset($_SESSION["notif-pesanan-baru"]);
    }

    $menu = ambil_data("
        SELECT p.kode_pesanan, tk.nama_pelanggan, p.kode_menu, p.qty
        FROM pesanan AS p
        JOIN transaksi AS tk ON (tk.kode_pesanan = p.kode_pesanan)
    ");
} else {
    if (!isset($_GET["search"])) {
        $menu = ambil_data("SELECT * FROM menu ORDER BY kode_menu DESC");
    } else {
        $key_search = $_GET["key-search"];
        $menu = ambil_data("
            SELECT * FROM menu 
            WHERE nama LIKE '%$key_search%' OR
                  harga LIKE '%$key_search%' OR
                  kategori LIKE '%$key_search%' OR
                  `status` LIKE '%$key_search%'
            ORDER BY kode_menu DESC
        ");
    }
}

if (isset($_POST["pesan"])) {
    $pesanan = tambah_data_pesanan();
    if ($pesanan > 0) {
        $_SESSION["notif-pesanan-baru"] = true; // Set notifikasi untuk admin
        echo "<script>alert('Pesanan Berhasil Dikirim!');</script>";
    } else {
        echo "<script>alert('Pesanan Gagal Dikirim!');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link rel="stylesheet" href="./src/css/bootstrap-5.2.0/css/bootstrap.min.css">
<link rel="stylesheet" href="./src/css/bootstrap-icons-1.8.3/bootstrap-icons.css">

<title>Beranda</title>

<style>

/* -------------------------------------------------
   1. GLOBAL BACKGROUND
--------------------------------------------------- */
body {
    background: linear-gradient(135deg, #0d0f22 0%, #1b1f3b 40%, #3f1e68 100%);
    background-attachment: fixed;
    margin: 0;
    font-family: 'Poppins', sans-serif;
    color: #fff;
    overflow-x: hidden;
}

/* Glass Utilities */
.glass {
    background: rgba(255,255,255,0.07);
    backdrop-filter: blur(15px);
    -webkit-backdrop-filter: blur(15px);
    border: 1px solid rgba(255,255,255,0.15);
    border-radius: 20px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.25);
}

/* -------------------------------------------------
   2. TOPBAR (Neon Gradient)
--------------------------------------------------- */
.topbar {
    position: fixed;
    top: 0;
    width: 100%;
    padding: 12px 25px;
    background: linear-gradient(90deg, #c471ed, #12c2e9);
    box-shadow: 0 4px 20px rgba(0,0,0,0.3);
    z-index: 100;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.topbar-title {
    font-size: 22px;
    font-weight: 700;
    letter-spacing: 1px;
}

/* -------------------------------------------------
   3. SIDEBAR GLASS SLIDING
--------------------------------------------------- */
.sidebar {
    position: fixed;
    left: -260px;
    top: 0;
    height: 100vh;
    width: 260px;
    padding: 30px 20px;
    transition: 0.4s;
    z-index: 99;
}

.sidebar.active {
    left: 0;
}

.sidebar a {
    display: block;
    padding: 12px;
    margin-bottom: 12px;
    font-size: 18px;
    color: #fff;
    text-decoration: none;
    border-radius: 12px;
    transition: 0.3s;
}

.sidebar a:hover {
    background: rgba(255,255,255,0.15);
    box-shadow: 0 4px 20px rgba(0,0,0,0.3);
}

/* -------------------------------------------------
   4. CONTENT WRAPPER
--------------------------------------------------- */
.main-content {
    margin-top: 90px;
    padding: 20px;
}

/* -------------------------------------------------
   5. BUTTON MODERN
--------------------------------------------------- */
.btn-modern {
    border: none;
    border-radius: 12px;
    padding: 10px 18px;
    font-weight: 600;
    background: linear-gradient(45deg, #12c2e9, #c471ed);
    color: #fff;
    transition: 0.3s;
}

.btn-modern:hover {
    box-shadow: 0 5px 25px rgba(198,113,238,0.6);
    transform: translateY(-3px);
}

/* -------------------------------------------------
   6. CARD MODERN GLASS
--------------------------------------------------- */
.card-glass {
    padding: 18px;
    border-radius: 18px;
    background: rgba(255,255,255,0.08);
    backdrop-filter: blur(12px);
    transition: 0.3s;
}

.card-glass:hover {
    background: rgba(255,255,255,0.13);
    transform: translateY(-5px);
    box-shadow: 0 12px 35px rgba(0,0,0,0.3);
}

</style>
</head>

<body>

<!-- TOPBAR -->
<div class="topbar">
    <div class="text-white topbar-title">
        <i id="toggleSidebar" class="bi bi-list" style="cursor:pointer; font-size: 28px;"></i>
        <span class="ms-3">WARMINDO BANG GAGA</span>
    </div>

    <a class="btn-modern" href="logout.php" onclick="return confirm('Ingin Logout?')">Logout</a>
</div>

<!-- SIDEBAR -->
<div id="sidebar" class="sidebar glass">
    <h4 class="mb-4">Menu</h4>
    <a href="index.php">🍜 Menu</a>
    <?php if (isset($_SESSION["akun-admin"])) : ?>
    <a href="index.php?pesanan">
        🧾 Pesanan
        <?php if (isset($_SESSION["notif-pesanan-baru"])) : ?>
            <span class="badge bg-danger rounded-pill ms-2">Baru</span>
        <?php endif; ?>
    </a>
    <a href="index.php?transaksi">💰 Transaksi</a>
    <?php endif; ?>
</div>

<!-- CONTENT -->
<div class="main-content">
    <div class="container">

        <div class="card-glass">
        <?php
        if (isset($_GET["pesanan"])) include "halaman/pesanan.php";
        else if (isset($_GET["transaksi"])) include "halaman/transaksi.php";
        else include "halaman/beranda.php";
        ?>
        </div>

    </div>
</div>

<script>
document.getElementById("toggleSidebar").onclick = function() {
    document.getElementById("sidebar").classList.toggle("active");
};
</script>

<?php if (isset($_SESSION["akun-admin"]) && isset($_SESSION["notif-pesanan-baru"])) : ?>
<script>
    // Buat objek audio
    const notifSound = new Audio('src/audio/notifikasi.mp3');

    // Putar suara notifikasi
    notifSound.play().catch(error => console.error("Gagal memutar suara:", error));
</script>
<?php endif; ?>

</body>
</html>
