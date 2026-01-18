<?php
include 'koneksi.php';

$id_pesanan = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (isset($_POST['konfirmasi_bayar'])) {
    $updateQuery = "UPDATE pesanan SET status = 'Lunas' WHERE id = $id_pesanan";
    
    try {
        mysqli_query($koneksi, $updateQuery);
        header("Location: pembayaran.php?id=$id_pesanan&status=success");
        exit;
    } catch (mysqli_sql_exception $e) {
        die("Error Database: Kolom 'status' belum dibuat di tabel pesanan. <br>Pesan Error: " . $e->getMessage());
    }
}

$query = mysqli_query($koneksi, "SELECT * FROM pesanan WHERE id = $id_pesanan");
$data = mysqli_fetch_assoc($query);

if (!$data) {
    header("Location: pesanananda.php");
    exit;
}

$status_sekarang = $data['status'] ?? 'Pending'; 

$rekening = [
    'bca'     => [
        'bank' => 'Bank BCA', 
        'norek' => '8880-1234-5678', 
        'an' => 'ESTMORE OFFICIAL', 
        'logo' => 'https://upload.wikimedia.org/wikipedia/commons/5/5c/Bank_Central_Asia.svg'
    ],
    'mandiri' => [
        'bank' => 'Bank Mandiri', 
        'norek' => '100-00-1234567-8', 
        'an' => 'ESTMORE OFFICIAL', 
        'logo' => 'https://upload.wikimedia.org/wikipedia/commons/a/ad/Bank_Mandiri_logo_2016.svg'
    ],
    'bri'     => [
        'bank' => 'Bank BRI', 
        'norek' => '5555-01-000123-50-5', 
        'an' => 'ESTMORE OFFICIAL', 
        'logo' => 'https://upload.wikimedia.org/wikipedia/commons/6/68/BANK_BRI_logo.svg'
    ],
    'seabank' => [
        'bank' => 'SeaBank', 
        'norek' => '9012-3456-7890', 
        'an' => 'ESTMORE OFFICIAL', 
        'logo' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/8/88/SeaBank.png/1200px-SeaBank.png'
    ],
    'qris'    => [
        'bank' => 'QRIS', 
        'norek' => 'Scan Code', 
        'an' => 'Estmore Store', 
        'logo' => ''
    ]
];

$metode = strtolower($data['metode_pembayaran']);
$infoBayar = isset($rekening[$metode]) ? $rekening[$metode] : $rekening['qris'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran - Order #<?= $data['id']; ?></title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        body { background-color: #f4f6f9; font-family: 'Arial', sans-serif; }
        .card { border: none; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .status-badge { font-size: 0.9rem; padding: 5px 15px; border-radius: 20px; }
        .bg-gradient-dark { background: linear-gradient(45deg, #1a1a1a, #333); color: white; }
        .qris-container { border: 2px dashed #ccc; padding: 20px; border-radius: 10px; display: inline-block; }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            
            <a href="pesanananda.php" class="text-muted mb-3 d-block"><i class="fas fa-arrow-left"></i> Kembali ke Pesanan Saya</a>

            <div class="card overflow-hidden">
                <div class="card-header bg-gradient-dark text-center py-4">
                    <h5 class="mb-0 font-weight-bold">INVOICE #<?= $data['id']; ?></h5>
                    <small>Tanggal: <?= date('d M Y, H:i', strtotime($data['tanggal'])); ?></small>
                </div>

                <div class="card-body p-4">
                    
                    <?php if ($status_sekarang == 'Lunas'): ?>
                        
                        <div class="text-center py-4">
                            <div class="mb-3">
                                <i class="fas fa-check-circle text-success" style="font-size: 5rem;"></i>
                            </div>
                            <h3 class="text-success font-weight-bold">Pembayaran Berhasil!</h3>
                            <p class="text-muted">Terima kasih, pesanan Anda sedang kami proses.</p>
                            
                            <hr>
                            <div class="alert alert-light border">
                                <strong>Metode Bayar:</strong> <?= strtoupper($metode); ?><br>
                                <strong>Total:</strong> <?= $data['total_harga']; ?>
                            </div>

                            <a href="pesanananda.php" class="btn btn-dark btn-block rounded-pill mt-4">Lihat Riwayat Pesanan</a>
                        </div>

                    <?php else: ?>

                        <div class="text-center mb-4">
                            <p class="text-muted mb-1">Total Tagihan:</p>
                            <h2 class="font-weight-bold text-dark"><?= $data['total_harga']; ?></h2>
                            <span class="badge badge-warning status-badge mt-2">Menunggu Pembayaran</span>
                        </div>

                        <hr>

                        <div class="payment-instruction mt-4">
                            <h6 class="font-weight-bold mb-3 text-center">Silakan Transfer ke:</h6>
                            
                            <div class="text-center bg-light p-3 rounded mb-3">
                                <?php if($infoBayar['logo'] && $metode != 'qris'): ?>
                                    <img src="<?= $infoBayar['logo']; ?>" style="height: 30px;" class="mb-2" alt="<?= $infoBayar['bank']; ?>">
                                <?php endif; ?>
                                
                                <?php if($metode == 'qris'): ?>
                                    <div class="qris-container bg-white mt-2">
                                        <img src="image/qr-code.png" width="200" alt="QRIS Code">
                                    </div>
                                    <p class="small text-muted mt-2">Scan QR code di atas menggunakan E-Wallet Anda.</p>
                                <?php else: ?>
                                    <h5 class="font-weight-bold mb-0 mt-2"><?= $infoBayar['bank']; ?></h5>
                                    <p class="mb-0 text-muted small">Atas Nama: <?= $infoBayar['an']; ?></p>
                                    
                                    <div class="d-flex justify-content-center align-items-center mt-3">
                                        <h4 class="mb-0 mr-2 text-primary border-bottom border-primary" id="text-salin"><?= $infoBayar['norek']; ?></h4>
                                        <button class="btn btn-sm btn-outline-secondary" onclick="copyToClipboard('<?= $infoBayar['norek']; ?>')"><i class="far fa-copy"></i> Salin</button>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="alert alert-info small">
                                <i class="fas fa-info-circle mr-1"></i> 
                                Pastikan nominal transfer sesuai dengan total tagihan.
                            </div>
                        </div>

                        <form method="POST" class="mt-4">
                            <button type="submit" name="konfirmasi_bayar" class="btn btn-success btn-block py-3 font-weight-bold shadow" onclick="return confirm('Apakah Anda yakin sudah melakukan pembayaran?')">
                                <i class="fas fa-check-circle mr-2"></i> SAYA SUDAH BAYAR
                            </button>
                        </form>

                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function copyToClipboard(text) {
        var dummy = document.createElement("textarea");
        document.body.appendChild(dummy);
        dummy.value = text;
        dummy.select();
        document.execCommand("copy");
        document.body.removeChild(dummy);
        alert("Nomor rekening berhasil disalin!");
    }
</script>

</body>
</html>