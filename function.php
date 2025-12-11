<?php

$koneksi = mysqli_connect("localhost", "root", "", "kedai");


// ==============================
// FUNCTION REGISTER
// ==============================
function register_akun()
{
    global $koneksi;

    $username = htmlspecialchars($_POST["username"]);
    $password = md5(htmlspecialchars($_POST["password"]));
    $konfirmasi_password = md5(htmlspecialchars($_POST["konfirmasi-password"]));

    // Cek username
    $cek_username = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM user WHERE username = '$username'"));

    if ($cek_username != null) {
        echo "<script>alert('Username sudah ada!');</script>";
        return -1;
    }
    if ($password !== $konfirmasi_password) {
        echo "<script>alert('Password tidak sesuai!');</script>";
        return -1;
    }

    mysqli_query($koneksi, "INSERT INTO user VALUES ('', '$username', '$password')");
    return mysqli_affected_rows($koneksi);
}



// ==============================
// FUNCTION LOGIN
// ==============================
function login_akun()
{
    global $koneksi;

    $username = htmlspecialchars($_POST["username"]);
    $password = md5(htmlspecialchars($_POST["password"]));

    $cek_admin = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM admin WHERE username = '$username' AND password = '$password'"));
    $cek_user  = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM user WHERE username = '$username' AND password = '$password'"));

    if (!$cek_admin && !$cek_user) return false;

    if ($cek_user) {
        $_SESSION["akun-user"] = [
            "username" => $username,
            "password" => $password
        ];
    }

    if ($cek_admin) {
        $_SESSION["akun-admin"] = [
            "username" => $username,
            "password" => $password
        ];
    }

    header("Location: index.php");
    exit;
}



// ==============================
// FUNCTION AMBIL DATA
// ==============================
function ambil_data($query)
{
    global $koneksi;

    $db = [];
    $run = mysqli_query($koneksi, $query);

    while ($row = mysqli_fetch_assoc($run)) {
        $db[] = $row;
    }

    return $db;
}



// ==============================
// FUNCTION TAMBAH MENU
// ==============================
function tambah_data_menu()
{
    global $koneksi;

    $nama     = htmlspecialchars($_POST["nama"]);
    $harga    = (int) $_POST["harga"];
    $kategori = htmlspecialchars($_POST["kategori"]);
    $status   = htmlspecialchars($_POST["status"]);
    $gambar   = $_FILES["gambar"]["name"];

    // Format gambar
    $format = ["jpg", "jpeg", "png", "gif"];
    $ext = strtolower(pathinfo($gambar, PATHINFO_EXTENSION));

    if (!in_array($ext, $format)) {
        echo "<script>alert('File bukan gambar!');</script>";
        return -1;
    }

    // Upload gambar
    $nama_gambar = uniqid() . ".$ext";
    move_uploaded_file($_FILES["gambar"]["tmp_name"], "src/img/$nama_gambar");

    // Generate kode menu
    $kode = ambil_data("SELECT MAX(SUBSTR(kode_menu, 3)) AS kode FROM menu")[0]["kode"];
    $kode = $kode == null ? 1 : $kode + 1;
    $kode_menu = "MN" . $kode;

    mysqli_query($koneksi, "INSERT INTO menu VALUES ('$kode', '$kode_menu', '$nama', $harga, '$nama_gambar', '$kategori', '$status')");
    return mysqli_affected_rows($koneksi);
}



// ==============================
// FUNCTION EDIT MENU
// ==============================
function edit_data_menu()
{
    global $koneksi;

    $id_menu   = $_POST["id_menu"];
    $nama      = htmlspecialchars($_POST["nama"]);
    $harga     = (int) $_POST["harga"];
    $kategori  = htmlspecialchars($_POST["kategori"]);
    $status    = htmlspecialchars($_POST["status"]);
    $kode_menu = htmlspecialchars($_POST["kode_menu"]);

    $gambar_lama = $_POST["gambar-lama"];
    $gambar_baru = $_FILES["gambar"]["name"];

    if ($gambar_baru) {
        $ext = strtolower(pathinfo($gambar_baru, PATHINFO_EXTENSION));
        $format = ["jpg", "jpeg", "png", "gif"];

        if (!in_array($ext, $format)) {
            echo "<script>alert('Format gambar tidak valid!');</script>";
            return -1;
        }

        move_uploaded_file($_FILES["gambar"]["tmp_name"], "src/img/$gambar_baru");
        unlink("src/img/$gambar_lama");
        $gambar = $gambar_baru;
    } else {
        $gambar = $gambar_lama;
    }

    mysqli_query($koneksi, "UPDATE menu SET 
        kode_menu = '$kode_menu',
        nama      = '$nama',
        harga     = $harga,
        gambar    = '$gambar',
        kategori  = '$kategori',
        status    = '$status'
        WHERE id_menu = $id_menu
    ");

    return mysqli_affected_rows($koneksi);
}



// ==============================
// FUNCTION HAPUS MENU
// ==============================
function hapus_data_menu()
{
    global $koneksi;

    $id = $_GET["id_menu"];

    $gambar = ambil_data("SELECT gambar FROM menu WHERE id_menu = $id")[0]["gambar"];
    if (file_exists("src/img/$gambar")) unlink("src/img/$gambar");

    mysqli_query($koneksi, "DELETE FROM menu WHERE id_menu = $id");
    return mysqli_affected_rows($koneksi);
}



// ==============================
// FUNCTION TAMBAH PESANAN
// ==============================
function tambah_data_pesanan()
{
    global $koneksi;

    $pelanggan = htmlspecialchars($_POST["pelanggan"]);
    $kode_pesanan = uniqid();

    $list_pesanan = [];

    // Loop semua input POST
    foreach ($_POST as $key => $value) {

        // Jika nama input mengandung qty dan diikuti angka
        if (preg_match('/^qty([0-9]+)$/', $key, $match)) {

            $index = $match[1];
            $qty   = (int)$value;

            if ($qty > 0) {
                // Ambil kode menu sesuai index qty
                $kode_menu = $_POST["kode_menu" . $index];

                $list_pesanan[] = [
                    "kode_menu" => $kode_menu,
                    "qty"       => $qty
                ];
            }
        }
    }

    // Validasi
    if (count($list_pesanan) === 0) {
        echo "<script>alert('Anda belum memesan menu apa pun!');</script>";
        return -1;
    }

    // Insert pesanan
    foreach ($list_pesanan as $p) {
        mysqli_query($koneksi, "
            INSERT INTO pesanan VALUES (
                '',
                '$kode_pesanan',
                '{$p['kode_menu']}',
                {$p['qty']}
            )
        ");
    }

    // Insert transaksi
    mysqli_query($koneksi, "
        INSERT INTO transaksi VALUES (
            '',
            '$kode_pesanan',
            '$pelanggan',
            NOW()
        )
    ");

    return mysqli_affected_rows($koneksi);
}



// ==============================
// FUNCTION HAPUS PESANAN
// ==============================
function hapus_data_pesanan()
{
    global $koneksi;

    $kode = $_GET["kode_pesanan"];

    mysqli_query($koneksi, "DELETE FROM transaksi WHERE kode_pesanan = '$kode'");
    mysqli_query($koneksi, "DELETE FROM pesanan WHERE kode_pesanan = '$kode'");

    return mysqli_affected_rows($koneksi);
}

function ambil_pesanan_lengkap() {
    global $koneksi;

    $query = "
        SELECT 
            transaksi.pelanggan,
            pesanan.kode_pesanan,
            pesanan.kode_menu,
            pesanan.qty,
            menu.nama,
            menu.harga
        FROM pesanan 
        JOIN transaksi ON transaksi.kode_pesanan = pesanan.kode_pesanan
        JOIN menu ON menu.kode_menu = pesanan.kode_menu
        ORDER BY pesanan.kode_pesanan DESC
    ";

    return ambil_data($query);
}


?>
