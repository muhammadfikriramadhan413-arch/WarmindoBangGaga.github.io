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
 
    // Cek apakah ada gambar yang diupload
    if ($_FILES['gambar']['error'] === 4) { // 4 = UPLOAD_ERR_NO_FILE
        echo "<script>alert('Anda harus mengupload gambar!');</script>";
        return -1;
    }
 
    // Proses upload gambar
    $nama_gambar = upload_gambar($_FILES['gambar']);
    if (!$nama_gambar) {
        // upload_gambar() akan menampilkan alert jika gagal
        return -1;
    }
 
    // Generate kode menu
    $result = mysqli_query($koneksi, "SELECT MAX(id_menu) AS max_id FROM menu");
    $row = mysqli_fetch_assoc($result);
    $kode = $row['max_id'] == null ? 1 : $row['max_id'] + 1;
    $kode_menu = "MN" . $kode;
 
    // Menggunakan prepared statement untuk keamanan
    $stmt = mysqli_prepare($koneksi, "INSERT INTO menu (id_menu, kode_menu, nama, harga, gambar, kategori, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
    if ($stmt === false) {
        return -1; // Gagal mempersiapkan statement
    }
    mysqli_stmt_bind_param($stmt, "ississs", $kode, $kode_menu, $nama, $harga, $nama_gambar, $kategori, $status);
    mysqli_stmt_execute($stmt);
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

    $gambar_lama = htmlspecialchars($_POST["gambar-lama"]);

    // Cek apakah user mengupload gambar baru
    if ($_FILES['gambar']['error'] !== 4) { // 4 = UPLOAD_ERR_NO_FILE
        $gambar_baru = upload_gambar($_FILES['gambar']);
        if (!$gambar_baru) {
            return -1; // Gagal upload
        }
        // Hapus gambar lama jika ada
        if (file_exists("src/img/$gambar_lama")) {
            unlink("src/img/$gambar_lama");
        }
        $gambar = $gambar_baru;
    } else {
        $gambar = $gambar_lama;
    }

    // Menggunakan prepared statement untuk keamanan
    $stmt = mysqli_prepare($koneksi, "UPDATE menu SET kode_menu = ?, nama = ?, harga = ?, gambar = ?, kategori = ?, status = ? WHERE id_menu = ?");
    mysqli_stmt_bind_param($stmt, "ssisssi", $kode_menu, $nama, $harga, $gambar, $kategori, $status, $id_menu);
    mysqli_stmt_execute($stmt);
    return mysqli_affected_rows($koneksi);
}



// ==============================
// FUNCTION HAPUS MENU
// ==============================
function hapus_data_menu()
{
    global $koneksi;

    $id = $_GET["id_menu"];

    // Ambil nama gambar sebelum dihapus dari DB
    $stmt_select = mysqli_prepare($koneksi, "SELECT gambar FROM menu WHERE id_menu = ?");
    mysqli_stmt_bind_param($stmt_select, "i", $id);
    mysqli_stmt_execute($stmt_select);
    $result = mysqli_stmt_get_result($stmt_select);
    $data = mysqli_fetch_assoc($result);
    $gambar = $data['gambar'];

    // Hapus data dari database
    $stmt_delete = mysqli_prepare($koneksi, "DELETE FROM menu WHERE id_menu = ?");
    mysqli_stmt_bind_param($stmt_delete, "i", $id);
    mysqli_stmt_execute($stmt_delete);

    $affected_rows = mysqli_affected_rows($koneksi);

    // Jika data berhasil dihapus dari DB, hapus file gambarnya
    if ($affected_rows > 0 && $gambar && file_exists("src/img/$gambar")) {
        unlink("src/img/$gambar");
    }

    return $affected_rows;
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

// ==============================
// FUNCTION UPLOAD GAMBAR
// ==============================
function upload_gambar($file)
{
    $namaFile   = $file['name'];
    $ukuranFile = $file['size'];
    $tmpName    = $file['tmp_name'];

    // 1. Validasi Ekstensi
    $ekstensiValid  = ['jpg', 'jpeg', 'png', 'gif'];
    $ekstensiGambar = strtolower(pathinfo($namaFile, PATHINFO_EXTENSION));
    if (!in_array($ekstensiGambar, $ekstensiValid)) {
        echo "<script>alert('Format gambar harus JPG, JPEG, PNG, atau GIF!');</script>";
        return false;
    }

    // 2. Validasi Ukuran File (misal: maks 2MB)
    if ($ukuranFile > 2000000) {
        echo "<script>alert('Ukuran gambar terlalu besar! Maksimal 2MB.');</script>";
        return false;
    }

    // 3. Buat gambar dari file yang diunggah berdasarkan ekstensinya
    switch ($ekstensiGambar) {
        case 'jpg':
        case 'jpeg':
            $sumber = imagecreatefromjpeg($tmpName);
            break;
        case 'png':
            $sumber = imagecreatefrompng($tmpName);
            // Menjaga transparansi untuk PNG
            imagepalettetotruecolor($sumber);
            imagealphablending($sumber, true);
            imagesavealpha($sumber, true);
            break;
        case 'gif':
            $sumber = imagecreatefromgif($tmpName);
            break;
    }

    // 4. Generate nama file baru dengan ekstensi .webp
    $namaFileBaru = time() . '_' . uniqid() . '.webp';
    $tujuan = 'src/img/' . $namaFileBaru;

    // 5. Simpan gambar sebagai WebP dengan kualitas 80
    imagewebp($sumber, $tujuan, 80);
    imagedestroy($sumber);

    return $namaFileBaru; // Kembalikan nama file .webp yang baru
}

?>
