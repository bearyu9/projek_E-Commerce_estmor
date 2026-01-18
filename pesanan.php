<?php
include 'koneksi.php';

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

$id_produk = isset($_GET['id']) ? mysqli_real_escape_string($koneksi, $_GET['id']) : 'black';

$query = mysqli_query($koneksi, "SELECT * FROM produk WHERE kode_produk = '$id_produk'");

if (mysqli_num_rows($query) > 0) {
    $data = mysqli_fetch_assoc($query);
} else {
    $queryDefault = mysqli_query($koneksi, "SELECT * FROM produk WHERE kode_produk = 'black'");
    $data = mysqli_fetch_assoc($queryDefault);
}

$stokTersedia = (int)$data['stok'];

$qty_sekarang = isset($_GET['qty']) ? (int)$_GET['qty'] : 1;
if ($qty_sekarang < 1) $qty_sekarang = 1;
if ($qty_sekarang > $stokTersedia) $qty_sekarang = ($stokTersedia > 0 ? 1 : 0);

$hargaAsli = (int)str_replace(['Rp', '.', ' '], '', $data['harga']);
$persenDiskon = isset($data['diskon']) ? $data['diskon'] : 0;
$hargaFinal = $hargaAsli;

if ($persenDiskon > 0) {
    $potongan = ($hargaAsli * $persenDiskon) / 100;
    $hargaFinal = $hargaAsli - $potongan;
}
$tampilHargaFinal = 'Rp ' . number_format($hargaFinal, 0, ',', '.');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beli <?= $data['judul']; ?> - Estmore</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="css/pesanstyle.css">
    <style>
        .payment-option {
            cursor: pointer;
            border: 1px solid #ddd;
            transition: all 0.2s;
            background: #fff;
        }
        .payment-option:hover {
            border-color: #333;
            background-color: #f8f9fa;
        }
        .payment-option.active {
            border-color: #000;
            background-color: #e9ecef;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container-fluid p-0 wrapper">

        <div id="header">
            <div class="header-banner">
                <h1 class="header-title">Welcome to Estmore</h1>
            </div>
            
            <nav class="navbar navbar-expand-lg navbar-custom sticky-top">
                <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
                    <span class="navbar-toggler-icon" style="filter: invert(1);"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav mx-auto">
                        <li class="nav-item"><a class="nav-link" href="index.html">Home</a></li>
                        <li class="nav-item active"><a class="nav-link" href="daftar.html">T-Shirt</a></li>
                        <li class="nav-item"><a class="nav-link" href="pesanananda.php">My Order</a></li>
                        <li class="nav-item"><a class="nav-link" href="admin.html">About</a></li>
                    </ul>
                </div>
            </nav>
        </div>

        <div id="content" class="container mt-5 mb-5">
            <div class="row">
                <div class="col-md-7 mb-4">
                    <div class="product-image-container">
                        <img src="<?= $data['gambar']; ?>" alt="<?= $data['judul']; ?>" class="img-fluid w-100">
                    </div>
                </div>

                <div class="col-md-5">
                    <h2 class="product-title mb-2"><?= $data['judul']; ?></h2>
                    <div class="mb-4">
                        <?php if ($persenDiskon > 0): ?>
                            <small class="text-muted"><del><?= $data['harga']; ?></del></small>
                            <h4 class="product-price text-danger d-inline ml-2">
                                <?= $tampilHargaFinal; ?>
                            </h4>
                            <span class="badge badge-danger ml-2">Hemat <?= $persenDiskon; ?>%</span>
                        <?php else: ?>
                            <h4 class="product-price"><?= $data['harga']; ?></h4>
                        <?php endif; ?>
                    </div>

                    <form action="pesanananda.php" method="POST" id="formBeli">
                        
                        <div class="form-group mb-4">
                            <label class="d-block font-weight-bold mb-2">Size:</label>
                            <div class="btn-group-toggle size-selector" data-toggle="buttons">
                                <?php 
                                $sizes = ['S', 'M', 'L', 'XL', 'XXL'];
                                foreach ($sizes as $s) {
                                    $active_class = ($s == 'L') ? 'active' : '';
                                    $checked = ($s == 'L') ? 'checked' : '';
                                    echo "<label class='btn btn-size $active_class'><input type='radio' name='size' value='$s' $checked autocomplete='off'> $s</label>";
                                }
                                ?>
                            </div>
                        </div>

                        <div class="form-group mb-4">
                            <label class="d-block font-weight-bold mb-2">Quantity:</label>
                            <div class="input-group input-group-sm" style="width: 120px;">
                                <div class="input-group-prepend">
                                    <button type="button" class="btn btn-qty" id="btn-minus" <?= $stokTersedia == 0 ? 'disabled' : '' ?>>-</button>
                                </div>
                                <input type="text" class="form-control text-center font-weight-bold" id="quantity" name="quantity" value="<?= $qty_sekarang; ?>" readonly style="background-color: white;" autocomplete="off">
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-qty" id="btn-plus" <?= $stokTersedia == 0 ? 'disabled' : '' ?>>+</button>
                                </div>
                            </div>

                            <?php if ($stokTersedia > 5): ?>
                                <small class="text-success font-weight-bold mt-2 d-block"><i class="fas fa-check-circle"></i> Ready Stock (<?= $stokTersedia; ?>)</small>
                            <?php elseif ($stokTersedia > 0): ?>
                                <small class="text-warning font-weight-bold mt-2 d-block"><i class="fas fa-exclamation-circle"></i> Segera Habis! Sisa <?= $stokTersedia; ?> pcs</small>
                            <?php else: ?>
                                <small class="text-danger font-weight-bold mt-2 d-block"><i class="fas fa-times-circle"></i> Stok Habis (Sold Out)</small>
                            <?php endif; ?>
                        </div>

                        <div class="action-buttons mb-4">
                            <input type="hidden" name="product_id" value="<?= $data['kode_produk']; ?>">
                            <input type="hidden" name="payment_method" id="input-payment-method" value="">
                            
                            <?php if ($stokTersedia > 0): ?>
                                <button type="button" id="btn-buy-trigger" class="btn btn-dark btn-block py-3 text-uppercase font-weight-bold">Buy It Now</button>
                            <?php else: ?>
                                <button type="button" class="btn btn-secondary btn-block py-3 text-uppercase font-weight-bold" disabled style="cursor: not-allowed;">Stok Habis</button>
                            <?php endif; ?>
                        </div>
                    </form>

                    <div class="product-description mt-4">
                        <p class="mb-1"><strong>Estmore Series</strong></p>
                        <p class="mb-1">Warna: <?= $data['warna']; ?></p>
                        <p class="mb-1">Material: <?= $data['material']; ?></p>
                        <br>
                        <p class="small text-muted"><strong>Penting! MOHON DI BACA</strong><br>Komplain Barang Mohon Kirimkan Video Unboxing Paket Dari Awal. Jika Tidak Ada Video Kami Tidak Respon Untuk Komplain.</p>
                    </div>
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
                                <a href="https://www.instagram.com/estmor.co?utm_source=ig_web_button_share_sheet&igsh=ZDNlZDc0MzIxNw==" class="social-btn">Instagram</a>
                                <a href="https://www.tiktok.com/@estmorofficial?_r=1&_t=ZS-937LgCtEajU" class="social-btn">Tiktok</a>
                                <a href="https://tk.tokopedia.com/ZS5EVSkVu/" class="social-btn">Tokopedia</a>
                                <a href="https://id.shp.ee/UdCca8h" class="social-btn">Shopee</a>
                            </div>
                        </div>
                        <div class="col-md-4 text-center text-md-right">
                            <div class="footer-address">Jl. Ir. H. Djuanda No. 95 Ciputat<br>Kota Tangerang Selatan, Indonesia</div>
                        </div>
                    </div>
                </div>
            </footer>
        </div>

    </div>

    <div class="modal fade" id="buyModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content p-4">
                <div class="modal-header border-0 pb-0 justify-content-center">
                    <h5 class="modal-title font-weight-bold">Konfirmasi Pesanan</h5>
                </div>
                <div class="modal-body">
                    <h6 class="font-weight-bold text-center mb-3"><?= $data['judul']; ?></h6>
                    
                    <div class="d-flex justify-content-center mb-4 bg-light py-2 rounded">
                         <div class="px-3 border-right">
                             <small class="text-muted d-block">Size</small>
                             <span id="modal-size-display" class="font-weight-bold h5">L</span>
                         </div>
                         <div class="px-3">
                             <small class="text-muted d-block">Qty</small>
                             <span id="modal-qty-display" class="font-weight-bold h5">1</span>
                         </div>
                    </div>

                    <p class="font-weight-bold mb-2 small text-muted text-uppercase">Transfer Bank</p>
                    <div class="payment-list mb-3">
                        <div class="payment-option p-2 mb-2 rounded d-flex align-items-center justify-content-between" data-value="bca">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-university mr-2 text-secondary"></i>
                                <span class="font-weight-bold small">Bank BCA</span>
                            </div>
                            <img src="https://upload.wikimedia.org/wikipedia/commons/5/5c/Bank_Central_Asia.svg" height="15">
                        </div>

                        <div class="payment-option p-2 mb-2 rounded d-flex align-items-center justify-content-between" data-value="bni">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-university mr-2 text-secondary"></i>
                                <span class="font-weight-bold small">Bank BNI</span>
                            </div>
                            <img src="https://upload.wikimedia.org/wikipedia/id/5/55/BNI_logo.svg" height="15">
                        </div>

                        <div class="payment-option p-2 mb-2 rounded d-flex align-items-center justify-content-between" data-value="mandiri">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-university mr-2 text-secondary"></i>
                                <span class="font-weight-bold small">Bank Mandiri</span>
                            </div>
                            <img src="https://upload.wikimedia.org/wikipedia/commons/a/ad/Bank_Mandiri_logo_2016.svg" height="15">
                        </div>

                        <div class="payment-option p-2 mb-2 rounded d-flex align-items-center justify-content-between" data-value="bri">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-university mr-2 text-secondary"></i>
                                <span class="font-weight-bold small">Bank BRI</span>
                            </div>
                            <img src="https://upload.wikimedia.org/wikipedia/commons/6/68/BANK_BRI_logo.svg" height="15">
                        </div>

                        <div class="payment-option p-2 mb-2 rounded d-flex align-items-center justify-content-between" data-value="seabank">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-university mr-2 text-secondary"></i>
                                <span class="font-weight-bold small">Seabank</span>
                            </div>
                            <span class="badge badge-warning text-white">SEABANK</span>
                        </div>
                    </div>

                    <p class="font-weight-bold mb-2 small text-muted text-uppercase">E-Wallet / QRIS</p>
                    <div class="payment-option p-2 mb-2 rounded d-flex align-items-center justify-content-between" data-value="qris">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-qrcode mr-2 text-secondary"></i>
                            <span class="font-weight-bold small">Scan QRIS</span>
                        </div>
                        <i class="fas fa-qrcode"></i>
                    </div>
                    
                    <div id="payment-error" class="text-danger small mb-2 text-center" style="display:none;">Silakan pilih metode pembayaran!</div>
                    <p class="small text-muted text-center mt-3">Stok akan diamankan setelah Anda menekan tombol confirm.</p>
                </div>
                <div class="modal-footer justify-content-center border-0 pt-0">
                    <button type="button" class="btn btn-outline-dark px-4" data-dismiss="modal">CANCEL</button>
                    <button type="button" id="btn-confirm-buy" class="btn btn-dark px-4 ml-2">CONFIRM</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.9/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <script>
        const maxStock = <?= $stokTersedia; ?>;

        document.addEventListener('DOMContentLoaded', function() {
            const qtyInput = document.getElementById('quantity');
            const btnMinus = document.getElementById('btn-minus');
            const btnPlus = document.getElementById('btn-plus');
            
            btnMinus.addEventListener('click', function() {
                let currentVal = parseInt(qtyInput.value) || 0;
                if (currentVal > 1) qtyInput.value = currentVal - 1;
            });

            btnPlus.addEventListener('click', function() {
                let currentVal = parseInt(qtyInput.value) || 0;
                if (currentVal < maxStock) qtyInput.value = currentVal + 1;
            });
            
            const sizeLabels = document.querySelectorAll('.size-selector label');
            sizeLabels.forEach(label => {
                label.addEventListener('click', function() {
                    sizeLabels.forEach(l => l.classList.remove('active'));
                    this.classList.add('active');
                });
            });

            const btnBuyTrigger = document.getElementById('btn-buy-trigger');
            const btnConfirmBuy = document.getElementById('btn-confirm-buy');
            const formBeli = document.getElementById('formBeli');
            const paymentOptions = document.querySelectorAll('.payment-option');
            const paymentInput = document.getElementById('input-payment-method');
            const paymentError = document.getElementById('payment-error');
            
            paymentOptions.forEach(opt => {
                opt.addEventListener('click', function() {
                    paymentOptions.forEach(o => o.classList.remove('active'));
                    this.classList.add('active');
                    paymentInput.value = this.getAttribute('data-value');
                    paymentError.style.display = 'none';
                });
            });

            if (btnBuyTrigger) {
                btnBuyTrigger.addEventListener('click', function() {
                    let selectedSize = document.querySelector('input[name="size"]:checked').value;
                    let selectedQty = document.getElementById('quantity').value;

                    document.getElementById('modal-size-display').innerText = selectedSize;
                    document.getElementById('modal-qty-display').innerText = selectedQty;
                    
                    paymentOptions.forEach(o => o.classList.remove('active'));
                    paymentInput.value = "";
                    paymentError.style.display = 'none';

                    $('#buyModal').modal('show');
                });
            }

            if (btnConfirmBuy) {
                btnConfirmBuy.addEventListener('click', function() {
                    if (paymentInput.value === "") {
                        paymentError.style.display = 'block';
                    } else {
                        formBeli.submit();
                    }
                });
            }
        });
    </script>
</body>
</html>