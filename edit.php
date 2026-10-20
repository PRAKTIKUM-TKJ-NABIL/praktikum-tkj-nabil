<?php
require_once __DIR__ . '/config/database.php';
require_once 'includes/auth_check.php';
include 'includes/header.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: dashboard.php');
    exit;
}

// Ambil data yang akan diedit (dengan user_id untuk keamanan)
$query = "SELECT * FROM catatan_keuangan WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header('Location: dashboard.php');
    exit;
}

$data = $result->fetch_assoc();
$stmt->close();

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $jenis = $_POST['jenis'];
    $kategori = $_POST['kategori'];
    $jumlah = str_replace('.', '', $_POST['jumlah']);
    $deskripsi = $_POST['deskripsi'];
    $tanggal = $_POST['tanggal'];
    
    if (empty($jenis) || empty($kategori) || empty($jumlah) || empty($tanggal)) {
        $message = 'Semua field wajib diisi!';
        $message_type = 'danger';
    } else {
        $stmt = $conn->prepare("UPDATE catatan_keuangan SET jenis=?, kategori=?, jumlah=?, deskripsi=?, tanggal=? WHERE id=? AND user_id=?");
        $stmt->bind_param("ssdssii", $jenis, $kategori, $jumlah, $deskripsi, $tanggal, $id, $user_id);
        
        if ($stmt->execute()) {
            $message = 'Data berhasil diupdate!';
            $message_type = 'success';
            
            // Refresh data
            $query = "SELECT * FROM catatan_keuangan WHERE id = ? AND user_id = ?";
            $stmt2 = $conn->prepare($query);
            $stmt2->bind_param("ii", $id, $user_id);
            $stmt2->execute();
            $result2 = $stmt2->get_result();
            $data = $result2->fetch_assoc();
            $stmt2->close();
        } else {
            $message = 'Error: ' . $stmt->error;
            $message_type = 'danger';
        }
        $stmt->close();
    }
}

// ===== UPDATE TARGET TABUNGAN OTOMATIS =====
// Setelah transaksi pemasukan, tambahkan ke target aktif (opsional)
if ($jenis == 'pemasukan') {
    // Ambil target aktif dengan prioritas terendah
    $stmt_target = $conn->prepare("SELECT id FROM target_tabungan 
                                   WHERE user_id = ? AND status = 'aktif' 
                                   ORDER BY jumlah_terkumpul / harga_target ASC LIMIT 1");
    $stmt_target->bind_param("i", $user_id);
    $stmt_target->execute();
    $result_target = $stmt_target->get_result();
    
    if ($result_target->num_rows > 0) {
        $target = $result_target->fetch_assoc();
        $target_id = $target['id'];
        
        // Update target
        $stmt_update = $conn->prepare("UPDATE target_tabungan 
                                       SET jumlah_terkumpul = jumlah_terkumpul + ? 
                                       WHERE id = ? AND user_id = ?");
        $stmt_update->bind_param("dii", $jumlah, $target_id, $user_id);
        $stmt_update->execute();
        $stmt_update->close();
        
        // Simpan riwayat
        $stmt_riwayat = $conn->prepare("INSERT INTO riwayat_tabungan 
                                        (target_id, user_id, jenis, jumlah, keterangan) 
                                        VALUES (?, ?, 'tambah', ?, ?)");
        $keterangan = "Otomatis dari pemasukan: " . $deskripsi;
        $stmt_riwayat->bind_param("iids", $target_id, $user_id, $jumlah, $keterangan);
        $stmt_riwayat->execute();
        $stmt_riwayat->close();
    }
    $stmt_target->close();
}

// Ambil data kategori
$query_kategori = "SELECT * FROM kategori ORDER BY jenis, nama";
$result_kategori = $conn->query($query_kategori);
$kategori_pemasukan = [];
$kategori_pengeluaran = [];

if ($result_kategori) {
    while ($row = $result_kategori->fetch_assoc()) {
        if ($row['jenis'] == 'pemasukan') {
            $kategori_pemasukan[] = $row;
        } else {
            $kategori_pengeluaran[] = $row;
        }
    }
}
?>

<div class="card">
    <div class="card-header">
        <h5><i class="bi bi-pencil"></i> Edit Transaksi</h5>
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
                        <label class="form-label">Jenis Transaksi <span class="text-danger">*</span></label>
                        <select name="jenis" id="jenis" class="form-select" required>
                            <option value="pemasukan" <?php echo $data['jenis'] == 'pemasukan' ? 'selected' : ''; ?>>Pemasukan</option>
                            <option value="pengeluaran" <?php echo $data['jenis'] == 'pengeluaran' ? 'selected' : ''; ?>>Pengeluaran</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Kategori <span class="text-danger">*</span></label>
                        <select name="kategori" id="kategori" class="form-select" required>
                            <option value="">Pilih Kategori</option>
                            <?php foreach ($kategori_pemasukan as $kat): ?>
                                <option value="<?php echo $kat['nama']; ?>" 
                                        <?php echo $data['kategori'] == $kat['nama'] ? 'selected' : ''; ?>>
                                    <?php echo $kat['nama']; ?>
                                </option>
                            <?php endforeach; ?>
                            <?php foreach ($kategori_pengeluaran as $kat): ?>
                                <option value="<?php echo $kat['nama']; ?>" 
                                        <?php echo $data['kategori'] == $kat['nama'] ? 'selected' : ''; ?>>
                                    <?php echo $kat['nama']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Jumlah (Rp) <span class="text-danger">*</span></label>
                        <input type="text" name="jumlah" id="jumlah" class="form-control" 
                               value="<?php echo number_format($data['jumlah'], 0, ',', '.'); ?>" 
                               required onkeyup="formatRupiah(this)">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Tanggal <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal" class="form-control" 
                               value="<?php echo $data['tanggal']; ?>" required>
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Deskripsi</label>
                <textarea name="deskripsi" class="form-control" rows="3"><?php echo htmlspecialchars($data['deskripsi']); ?></textarea>
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-save"></i> Update
            </button>
            <a href="dashboard.php" class="btn btn-secondary">Kembali</a>
            <a href="hapus.php?id=<?php echo $id; ?>" class="btn btn-danger" 
               onclick="return confirm('Yakin ingin menghapus data ini?')">
                <i class="bi bi-trash"></i> Hapus
            </a>
        </form>
    </div>
</div>

<script>
function formatRupiah(input) {
    let value = input.value.replace(/[^0-9]/g, '');
    if (value) {
        value = new Intl.NumberFormat('id-ID').format(value);
        input.value = value;
    }
}
</script>

<?php include 'includes/footer.php'; ?>