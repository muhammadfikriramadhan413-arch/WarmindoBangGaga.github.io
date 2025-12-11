<?php
// Ambil data pesanan + semua kolom transaksi (t.*) + nama menu
$menu = ambil_data("
    SELECT 
        p.*,
        m.nama AS nama_menu,
        t.* 
    FROM pesanan p
    JOIN menu m ON p.kode_menu = m.kode_menu
    LEFT JOIN transaksi t ON t.kode_pesanan = p.kode_pesanan
    ORDER BY p.id_pesanan DESC
");

// kemungkinan nama kolom yang menyimpan nama pelanggan
$kemungkinanKolom = ['nama_pelanggan', 'pelanggan', 'nama', 'nama_pemesan'];

// fungsi helper untuk ambil nama pelanggan dari baris (jika ada)
function ambilNamaPelangganDariRow($row, $kemungkinanKolom) {
    foreach ($kemungkinanKolom as $k) {
        if (isset($row[$k]) && strlen(trim($row[$k])) > 0) {
            return $row[$k];
        }
    }
    return "-";
}
?>

<table class="table table-bordered table-hover" style="margin-top: 100px;">
    <tr class="text-bg-success">
        <th>No</th>
        <th>Kode Pesanan</th>
        <th>Nama Pelanggan</th>
        <th>Nama Menu</th>
        <th>Qty</th>
    </tr>

    <?php $i = 1; foreach ($menu as $m) { 
        $nama_pelanggan = ambilNamaPelangganDariRow($m, $kemungkinanKolom);
    ?>
        <tr style="background-color: white;">
            <td><?= $i; ?></td>
            <td><?= htmlspecialchars($m["kode_pesanan"]); ?></td>
            <td><?= htmlspecialchars($nama_pelanggan); ?></td>
            <td><?= htmlspecialchars($m["nama_menu"]); ?></td>
            <td><?= htmlspecialchars($m["qty"]); ?></td>
        </tr>
    <?php $i++; } ?>

</table>
