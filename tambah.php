<?php
require_once __DIR__ . '/config/database.php';
require_once 'includes/auth_check.php';

// ===== LOAD LANGUAGE =====
require_once 'includes/lang.php';
require_once 'includes/functions.php';

// Load language
$lang = $_SESSION['language'] ?? 'en';
$lang_data = loadLanguage($lang);

include 'includes/header.php';

$message = '';
$message_type = '';

// Inisialisasi variabel untuk menghindari undefined variable
$jenis = '';
$kategori = '';
$jumlah = '';
$deskripsi = '';
$tanggal = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $jenis = $_POST['jenis'] ?? '';
    $kategori = $_POST['kategori'] ?? '';
    $jumlah = isset($_POST['jumlah']) ? str_replace('.', '', $_POST['jumlah']) : '';
    $deskripsi = $_POST['deskripsi'] ?? '';
    $tanggal = $_POST['tanggal'] ?? date('Y-m-d');
    
    if (empty($jenis) || empty($kategori) || empty($jumlah) || empty($tanggal)) {
        $message = __('error_required');
        $message_type = 'danger';
    } else {
        // ===== HAPUS: Cek transaksi tabungan (tidak lagi digunakan) =====
        // Semua transaksi di sini adalah transaksi biasa (bukan tabungan)
        $dari_tabungan = 0;
        $jenis_tabungan = null;
        $target_id = null;
        
        // Insert ke database
        $stmt = $conn->prepare("INSERT INTO catatan_keuangan 
            (user_id, jenis, kategori, jumlah, deskripsi, tanggal, dari_tabungan, jenis_tabungan, target_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issdssisi", $user_id, $jenis, $kategori, $jumlah, $deskripsi, $tanggal, $dari_tabungan, $jenis_tabungan, $target_id);
        
        if ($stmt->execute()) {
            $message = __('success_saved');
            $message_type = 'success';
            
            // Reset form setelah sukses
            $jenis = '';
            $kategori = '';
            $jumlah = '';
            $deskripsi = '';
            $tanggal = date('Y-m-d');
        } else {
            $message = 'Error: ' . $stmt->error;
            $message_type = 'danger';
        }
        $stmt->close();
    }
}

// Ambil data kategori (tanpa Tabungan)
$query_kategori = "SELECT * FROM kategori ORDER BY jenis, nama";
$result_kategori = $conn->query($query_kategori);
$kategori_pemasukan = [];
$kategori_pengeluaran = [];

if ($result_kategori) {
    while ($row = $result_kategori->fetch_assoc()) {
        // ===== HAPUS: Jangan tambahkan kategori Tabungan =====
        if ($row['jenis'] == 'pemasukan') {
            $kategori_pemasukan[] = $row;
        } else {
            $kategori_pengeluaran[] = $row;
        }
    }
}

// ===== HAPUS: Tidak menambahkan kategori Tabungan lagi =====
// $kategori_pengeluaran[] = ['nama' => __('savings'), 'jenis' => 'pengeluaran'];
// $kategori_pemasukan[] = ['nama' => __('savings'), 'jenis' => 'pemasukan'];
?>

<style>
.tambah-page .card {
    border: none;
    border-radius: 16px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
}

.tambah-page .card-header {
    background: linear-gradient(135deg, #4a6cf7 0%, #6a3de8 100%);
    color: white;
    border: none;
    padding: 16px 20px;
    border-radius: 16px 16px 0 0 !important;
}

.tambah-page .card-header h5 {
    margin: 0;
    font-weight: 700;
}

.tambah-page .form-control,
.tambah-page .form-select {
    border-radius: 10px;
    border: 2px solid #e5e7eb;
    padding: 10px 14px;
    font-size: 0.9rem;
    transition: all 0.2s ease;
}

.tambah-page .form-control:focus,
.tambah-page .form-select:focus {
    border-color: #4a6cf7;
    box-shadow: 0 0 0 4px rgba(74, 108, 247, 0.1);
}

.tambah-page .input-group-text {
    background: #f8fafc;
    border: 2px solid #e5e7eb;
    border-right: none;
    border-radius: 10px 0 0 10px;
    color: #6b7280;
}

.tambah-page .input-group .form-control {
    border-radius: 0 10px 10px 0;
}

.tambah-page .btn-submit {
    background: linear-gradient(135deg, #4a6cf7 0%, #6a3de8 100%);
    color: white;
    border: none;
    padding: 12px 32px;
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.95rem;
    transition: all 0.2s ease;
}

.tambah-page .btn-submit:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 16px rgba(74, 108, 247, 0.35);
    color: white;
}

.tambah-page .btn-submit:active {
    transform: scale(0.95);
}

.tambah-page .btn-back {
    padding: 12px 32px;
    border-radius: 10px;
    font-weight: 600;
}

@media (max-width: 575.98px) {
    .tambah-page .card-body {
        padding: 16px;
    }
    
    .tambah-page .btn-submit,
    .tambah-page .btn-back {
        width: 100%;
        justify-content: center;
    }
}
</style>

<div class="tambah-page">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5><i class="bi bi-plus-circle"></i> <?php echo __('add_transaction'); ?></h5>
                </div>
                <div class="card-body">
                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
                            <?php echo $message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label"><?php echo __('transaction_type'); ?> <span class="text-danger">*</span></label>
                                    <select name="jenis" id="jenis" class="form-select" required>
                                        <option value=""><?php echo __('select_type'); ?></option>
                                        <option value="pemasukan" <?php echo $jenis == 'pemasukan' ? 'selected' : ''; ?>><?php echo __('income_type'); ?></option>
                                        <option value="pengeluaran" <?php echo $jenis == 'pengeluaran' ? 'selected' : ''; ?>><?php echo __('expense_type'); ?></option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label"><?php echo __('transaction_category'); ?> <span class="text-danger">*</span></label>
                                    <select name="kategori" id="kategori" class="form-select" required>
                                        <option value=""><?php echo __('select_category'); ?></option>
                                        <?php foreach ($kategori_pemasukan as $kat): ?>
                                            <option value="<?php echo $kat['nama']; ?>" 
                                                    class="kategori-option pemasukan" 
                                                    style="<?php echo $jenis == 'pemasukan' ? '' : 'display:none;'; ?>"
                                                    <?php echo $kategori == $kat['nama'] ? 'selected' : ''; ?>>
                                                <?php echo translateCategory($kat['nama']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                        <?php foreach ($kategori_pengeluaran as $kat): ?>
                                            <option value="<?php echo $kat['nama']; ?>" 
                                                    class="kategori-option pengeluaran" 
                                                    style="<?php echo $jenis == 'pengeluaran' ? '' : 'display:none;'; ?>"
                                                    <?php echo $kategori == $kat['nama'] ? 'selected' : ''; ?>>
                                                <?php echo translateCategory($kat['nama']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label"><?php echo __('transaction_amount'); ?> <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><?php echo getCurrencySymbol($lang); ?></span>
                                        <input type="text" name="jumlah" id="jumlah" class="form-control" 
                                               placeholder="Contoh: 1000000" required 
                                               value="<?php echo $jumlah; ?>"
                                               onkeyup="formatRupiah(this)">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label"><?php echo __('transaction_date'); ?> <span class="text-danger">*</span></label>
                                    <input type="date" name="tanggal" class="form-control" 
                                           value="<?php echo $tanggal ?: date('Y-m-d'); ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label"><?php echo __('transaction_description'); ?></label>
                            <textarea name="deskripsi" class="form-control" rows="3" 
                                      placeholder="<?php echo __('transaction_description'); ?>"><?php echo htmlspecialchars($deskripsi); ?></textarea>
                        </div>
                        
                        <div class="d-flex gap-2 flex-wrap">
                            <button type="submit" class="btn-submit">
                                <i class="bi bi-save"></i> <?php echo __('save'); ?>
                            </button>
                            <a href="dashboard.php" class="btn btn-secondary btn-back">
                                <i class="bi bi-arrow-left"></i> <?php echo __('back'); ?>
                            </a>
                            <button type="reset" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-counterclockwise"></i> <?php echo __('reset'); ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('jenis').addEventListener('change', function() {
    const jenis = this.value;
    const options = document.querySelectorAll('.kategori-option');
    
    options.forEach(opt => {
        if (jenis === 'pemasukan' && opt.classList.contains('pemasukan')) {
            opt.style.display = '';
        } else if (jenis === 'pengeluaran' && opt.classList.contains('pengeluaran')) {
            opt.style.display = '';
        } else {
            opt.style.display = 'none';
        }
    });
    
    document.getElementById('kategori').value = '';
});

function formatRupiah(input) {
    let value = input.value.replace(/[^0-9]/g, '');
    if (value) {
        value = new Intl.NumberFormat('id-ID').format(value);
        input.value = value;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const jenis = document.getElementById('jenis').value;
    if (jenis) {
        document.getElementById('jenis').dispatchEvent(new Event('change'));
    }
});
</script>

<?php include 'includes/footer.php'; ?>