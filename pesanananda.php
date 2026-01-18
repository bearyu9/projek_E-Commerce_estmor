<?php
include 'koneksi.php';

if (isset($_GET['hapus'])) {
    $id_hapus = (int)$_GET['hapus'];

    $queryHapus = "DELETE FROM pesanan WHERE id = $id_hapus";
    mysqli_query($koneksi, $queryHapus);
    
    header("Location: pesanananda.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_produk = isset($_POST['product_id']) ? mysqli_real_escape_string($koneksi, $_POST['product_id']) : '';
    $qty = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    $size = isset($_POST['size']) ? $_POST['size'] : 'L';
    
    $metode_bayar = isset($_POST['payment_method']) ? mysqli_real_escape_string($koneksi, $_POST['payment_method']) : 'qris';

    $cekStok = mysqli_query($koneksi, "SELECT * FROM produk WHERE kode_produk = '$id_produk'");
    
    if (mysqli_num_rows($cekStok) > 0) {
        $item = mysqli_fetch_assoc($cekStok);
        $stokTersedia = (int)$item['stok'];

        if ($stokTersedia >= $qty) {
            
            $hargaClean = (int)str_replace(['Rp', '.', ' '], '', $item['harga']);
            $diskon = isset($item['diskon']) ? $item['diskon'] : 0;
            
            if ($diskon > 0) {
                $potongan = ($hargaClean * $diskon) / 100;
                $hargaSatuanFix = $hargaClean - $potongan;
            } else {
                $hargaSatuanFix = $hargaClean;
            }

            $totalInt = $hargaSatuanFix * $qty;
            $totalFmt = 'Rp ' . number_format($totalInt, 0, ',', '.');

            $judul    = $item['judul'];
            $harga    = $item['harga'];
            $gambar   = $item['gambar'];
            $warna    = $item['warna'];
            $material = $item['material'];

            $queryInsert = "INSERT INTO pesanan (produk_id, judul, harga, gambar, warna, material, size, qty, total_harga, metode_pembayaran, tanggal) 
                            VALUES ('$id_produk', '$judul', '$harga', '$gambar', '$warna', '$material', '$size', '$qty', '$totalFmt', '$metode_bayar', NOW())";
            
            if (mysqli_query($koneksi, $queryInsert)) {

                $stokBaru = $stokTersedia - $qty;
                mysqli_query($koneksi, "UPDATE produk SET stok = $stokBaru WHERE kode_produk = '$id_produk'");
                
                $last_id = mysqli_insert_id($koneksi);

                header("Location: pembayaran.php?id=$last_id");
                exit;
            }
        } else {
            echo "<script>alert('Stok tidak mencukupi!'); window.location.href='index.html';</script>";
            exit;
        }
    }
}

$result = mysqli_query($koneksi, "SELECT * FROM pesanan ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Order - Estmore</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="css/pesanstyle.css">
    <style>
        .sticky-top {
            position: -webkit-sticky;
            position: sticky;
            top: 0;
            z-index: 1020; 
        }
        .order-card {
            transition: all 0.3s ease;
        }
        .order-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 1rem 3rem rgba(0,0,0,.175)!important;
        }
    </style>
</head>
<body>
    <div class="container-fluid p-0 wrapper">

        <div class="header-banner">
            <h1 class="header-title">Welcome to Estmore</h1>
        </div>
        
        <nav class="navbar navbar-expand-lg navbar-custom sticky-top shadow-sm">
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
                <span class="navbar-toggler-icon" style="filter: invert(1);"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item"><a class="nav-link" href="index.html">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="daftar.html">T-Shirt</a></li>
                    <li class="nav-item active"><a class="nav-link" href="pesanananda.php">My Order</a></li>
                    <li class="nav-item"><a class="nav-link" href="admin.html">About</a></li>
                </ul>
            </div>
        </nav>

        <div id="content" class="container mt-5 mb-5">
            <div class="row justify-content-center">
                <div class="col-md-9">
                    
                    <h4 class="text-center mb-4 text-uppercase" style="font-family: 'Didot', serif; letter-spacing: 2px;">
                        Riwayat Pesanan Anda
                    </h4>

                    <?php if (mysqli_num_rows($result) > 0): ?>
                        
                        <?php while($row = mysqli_fetch_assoc($result)): ?>
                            
                            <div class="card shadow-sm border-0 rounded-0 mb-4 order-card">
                                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="font-weight-bold text-dark d-block">
                                            <i class="fas fa-receipt mr-2"></i>Order ID: #<?= $row['id']; ?>
                                        </span>
                                        <span class="badge badge-light border mt-1">
                                            <?= strtoupper($row['metode_pembayaran']); ?>
                                        </span>
                                    </div>
                                    <small class="text-muted">
                                        <?= date('d M Y, H:i', strtotime($row['tanggal'])); ?>
                                    </small>
                                </div>
                                <div class="card-body p-4">
                                    <div class="row align-items-center">
                                        <div class="col-md-3 mb-3 mb-md-0">
                                            <img src="<?= $row['gambar']; ?>" class="img-fluid border rounded" alt="Product">
                                        </div>
                                        <div class="col-md-9">
                                            <h5 class="font-weight-bold text-uppercase"><?= $row['judul']; ?></h5>
                                            <p class="text-muted mb-2 small"><?= $row['warna']; ?> | <?= $row['material']; ?></p>
                                            
                                            <div class="row mt-3">
                                                <div class="col-4">
                                                    <small class="text-muted d-block">Size</small>
                                                    <span class="h6 font-weight-bold"><?= $row['size']; ?></span>
                                                </div>
                                                <div class="col-4">
                                                    <small class="text-muted d-block">Quantity</small>
                                                    <span class="h6 font-weight-bold"><?= $row['qty']; ?> pcs</span>
                                                </div>
                                                <div class="col-4 text-right">
                                                    <small class="text-muted d-block">Total</small>
                                                    <span class="h5 font-weight-bold text-dark"><?= $row['total_harga']; ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer bg-light border-0 d-flex justify-content-between align-items-center">
                                    
                                    <a href="pesanananda.php?hapus=<?= $row['id']; ?>" 
                                       class="btn btn-sm btn-outline-danger rounded-0"
                                       onclick="return confirm('Apakah Anda yakin ingin menghapus pesanan ini?');">
                                        <i class="fas fa-trash-alt mr-1"></i> Hapus
                                    </a>

                                    <a href="pembayaran.php?id=<?= $row['id']; ?>" class="btn btn-sm btn-dark rounded-0 px-3">
                                        Lihat Cara Bayar <i class="fas fa-arrow-right ml-1"></i>
                                    </a>
                                </div>
                            </div>

                        <?php endwhile; ?>

                        <div class="text-center mt-5">
                            <a href="daftar.html" class="btn btn-outline-dark px-5 py-2 rounded-0">Belanja Lagi</a>
                        </div>

                    <?php else: ?>
                        
                        <div class="text-center py-5">
                            <i class="fas fa-shopping-basket fa-4x text-muted mb-3 opacity-50"></i>
                            <h3 class="text-muted">Belum ada riwayat pesanan.</h3>
                            <p class="text-muted mb-4">Yuk, mulai belanja koleksi terbaik kami!</p>
                            <a href="daftar.html" class="btn btn-dark mt-2 rounded-0 px-4 py-2">Mulai Belanja</a>
                        </div>

                    <?php endif; ?>

                </div>
            </div>
        </div>

        <div id="footer">
            <footer class="footer-custom mt-auto">
                <div class="container-fluid px-5">
                    <div class="row align-items-center">
                        <div class="col-md-4 text-center text-md-left mb-4 mb-md-0">
                            <p class="footer-copyright">&copy; 2026, All right reserved.</p>
                            <h6 class="footer-brand">ESTMORE</h6>
                        </div>
                        <div class="col-md-4 text-center mb-4 mb-md-0">
                            <div class="d-flex justify-content-center">
                                <a href="#" class="social-btn">Instagram</a>
                                <a href="#" class="social-btn">Tiktok</a>
                                <a href="#" class="social-btn">Tokopedia</a>
                                <a href="#" class="social-btn">Shopee</a>
                            </div>
                        </div>
                        <div class="col-md-4 text-center text-md-right">
                            <div class="footer-address">
                                Jl. Ir. H. Djuanda No. 95 Ciputat<br>
                                Kota Tangerang Selatan, Indonesia
                            </div>
                        </div>
                    </div>
                </div>
            </footer>
        </div>

    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.9/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>