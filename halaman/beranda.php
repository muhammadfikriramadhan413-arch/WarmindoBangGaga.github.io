<?php
// Ambil list kategori otomatis dari menu
$kategoriList = array_unique(array_column($menu, "kategori"));
sort($kategoriList);
?>

<style>
    .card {
        background-color: #f1e5e5ff;
        border-radius: 12px;    
        border: none;
        transition: transform 0.2s ease-in-out;
    }
    .card:hover {
        transform: scale(1.02);
        box-shadow: 0 6px 20px rgba(214, 209, 209, 1);
    }

    .category-btn {
        border-radius: 20px;
        padding: 6px 18px;
        font-weight: 600;
        cursor: pointer;
        transition: 0.2s;
    }
    .category-btn.active {
        background-color: #198754;
        color: white;
        box-shadow: 0 3px 10px rgba(0,0,0,0.15);
    }
</style>

<div class="container py-4">

    <!-- SEARCH -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <form action="index.php" method="GET" class="d-flex w-100 w-md-auto mb-2 mb-md-0">
            <input class="form-control me-2" type="search" autocomplete="off" name="key-search" placeholder="Cari menu...">
            <button class="btn btn-success" name="search">Cari</button>
        </form>

        <?php if (isset($_SESSION["akun-admin"])) { ?>
            <a class="btn btn-outline-success fw-bold" href="tambah.php">+ Tambah Menu</a>
        <?php } ?>
    </div>

    <!-- FORM PEMESANAN (WAJIB MEMBUNGKUS SEMUA QTY) -->
    <form action="index.php" method="POST">

        <!-- INPUT NAMA -->
        <div class="input-group mb-4">
            <input type="text" name="pelanggan" class="form-control" placeholder="Nama Pelanggan" required autocomplete="off">
            <button class="btn btn-primary" name="pesan" type="submit">Pesan</button>
        </div>

        <!-- KATEGORI -->
        <div class="d-flex gap-2 flex-wrap mt-2 mb-3">
            <button type="button" class="btn btn-outline-success category-btn active" data-category="all">Semua</button>

            <?php foreach ($kategoriList as $kat) { ?>
                <button type="button" class="btn btn-outline-success category-btn" data-category="<?= $kat; ?>">
                    <?= $kat; ?>
                </button>
            <?php } ?>
        </div>

        <!-- LIST MENU -->
        <div class="row mt-3">
            <?php 
            $i = 1;
            foreach ($menu as $m) { ?>
                <div class="col-md-4 col-sm-6 mb-4 menu-card" data-kategori="<?= $m['kategori']; ?>">
                    <div class="card shadow-sm h-100">

                        <img src="src/img/<?= $m["gambar"]; ?>" 
                             class="card-img-top rounded-top" 
                             alt="<?= $m["nama"]; ?>" 
                             style="object-fit: cover; height: 200px;">

                        <div class="card-body d-flex flex-column">

                            <h5 class="card-title text-primary"><?= $m["nama"]; ?></h5>

                            <!-- Hidden Kode Menu -->
                            <input type="hidden" name="kode_menu<?= $i; ?>" value="<?= $m["kode_menu"]; ?>">

                            <ul class="list-group list-group-flush mb-3">
                                <li class="list-group-item"><strong>Harga:</strong> Rp<?= $m["harga"]; ?></li>
                                <li class="list-group-item"><strong>Kategori:</strong> <?= $m["kategori"]; ?></li>
                                <li class="list-group-item"><strong>Status:</strong> <?= $m["status"]; ?></li>
                                <li class="list-group-item">
                                    <strong>Qty:</strong> 
                                    <input min="0" type="number" name="qty<?= $i; ?>" class="form-control mt-2" value="0">
                                </li>
                            </ul>

                            <?php if (isset($_SESSION["akun-admin"])) { ?>
                                <div class="d-flex justify-content-between mt-auto">
                                    <a class="btn btn-warning w-50 me-1" 
                                       href="edit.php?id_menu=<?= $m["id_menu"]; ?>">
                                        <i class="bi bi-pencil-fill"></i> Edit
                                    </a>

                                    <a class="btn btn-danger w-50 ms-1" 
                                       href="hapus.php?id_menu=<?= $m["id_menu"]; ?>" 
                                       onclick="return confirm('Ingin Menghapus Menu?')">
                                        <i class="bi bi-trash3-fill"></i> Hapus
                                    </a>
                                </div>
                            <?php } ?>

                        </div>
                    </div>
                </div>
            <?php $i++; } ?>
        </div>

    </form> <!-- END FORM -->

</div>

<!-- FILTER KATEGORI -->
<script>
    const buttons = document.querySelectorAll(".category-btn");
    const cards = document.querySelectorAll(".menu-card");

    buttons.forEach(btn => {
        btn.addEventListener("click", () => {
            buttons.forEach(b => b.classList.remove("active"));
            btn.classList.add("active");

            const kategori = btn.dataset.category;

            cards.forEach(card => {
                if (kategori === "all" || card.dataset.kategori === kategori) {
                    card.style.display = "block";
                } else {
                    card.style.display = "none";
                }
            });
        });
    });
</script>
