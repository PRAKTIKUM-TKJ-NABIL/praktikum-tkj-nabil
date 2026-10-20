<?php
require_once 'config/database.php';
require_once 'includes/auth_check.php';

// ===== LOAD LANGUAGE =====
require_once 'includes/lang.php';
require_once 'includes/functions.php';

// Load language
$lang = $_SESSION['language'] ?? 'en';
$lang_data = loadLanguage($lang);

include 'includes/header.php';

// ===== PROSES TAMBAH TARGET =====
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'tambah') {
    $nama_barang = trim($_POST['nama_barang']);
    $harga_target = str_replace('.', '', $_POST['harga_target']);
    $deskripsi = trim($_POST['deskripsi']);
    $target_tanggal = $_POST['target_tanggal'];
    
    if (empty($target_tanggal)) {
        $target_tanggal = null;
    }
    
    $gambar = '';
    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
        $target_dir = "assets/images/target/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION));
        $gambar = time() . '_' . uniqid() . '.' . $file_extension;
        $target_file = $target_dir . $gambar;
        
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (in_array($file_extension, $allowed_types)) {
            move_uploaded_file($_FILES['gambar']['tmp_name'], $target_file);
        }
    }
    
    if ($target_tanggal === null) {
        $stmt = $conn->prepare("INSERT INTO target_tabungan (user_id, nama_barang, harga_target, deskripsi, gambar) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("isdss", $user_id, $nama_barang, $harga_target, $deskripsi, $gambar);
    } else {
        $stmt = $conn->prepare("INSERT INTO target_tabungan (user_id, nama_barang, harga_target, deskripsi, gambar, target_tanggal) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isdsss", $user_id, $nama_barang, $harga_target, $deskripsi, $gambar, $target_tanggal);
    }
    
    if ($stmt->execute()) {
        $success = __('success_saved');
    } else {
        $error = "Error: " . $stmt->error;
    }
    $stmt->close();
}

// ===== PROSES UPDATE PROGRESS =====
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_progress') {
    $target_id = (int)$_POST['target_id'];
    $jumlah = str_replace('.', '', $_POST['jumlah']);
    $jenis = $_POST['jenis_progress'];
    $keterangan = trim($_POST['keterangan']);
    
    $stmt = $conn->prepare("SELECT * FROM target_tabungan WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $target_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $target = $result->fetch_assoc();
    $stmt->close();
    
    if ($target) {
        $jumlah_baru = $target['jumlah_terkumpul'];
        
        if ($jenis == 'tambah') {
            $jumlah_baru += $jumlah;
            
            $stmt_keuangan = $conn->prepare("INSERT INTO catatan_keuangan 
                (user_id, jenis, kategori, jumlah, deskripsi, tanggal, dari_tabungan, target_id, jenis_tabungan) 
                VALUES (?, 'pengeluaran', 'Tabungan', ?, ?, CURDATE(), 1, ?, 'ditabung')");
            $stmt_keuangan->bind_param("idsi", $user_id, $jumlah, $keterangan, $target_id);
            $stmt_keuangan->execute();
            $stmt_keuangan->close();
            
        } else {
            $jumlah_baru -= $jumlah;
            if ($jumlah_baru < 0) $jumlah_baru = 0;
            
            $stmt_keuangan = $conn->prepare("INSERT INTO catatan_keuangan 
                (user_id, jenis, kategori, jumlah, deskripsi, tanggal, dari_tabungan, target_id, jenis_tabungan) 
                VALUES (?, 'pemasukan', 'Tabungan', ?, ?, CURDATE(), 1, ?, 'diambil')");
            $stmt_keuangan->bind_param("idsi", $user_id, $jumlah, $keterangan, $target_id);
            $stmt_keuangan->execute();
            $stmt_keuangan->close();
        }
        
        $stmt = $conn->prepare("UPDATE target_tabungan SET jumlah_terkumpul = ? WHERE id = ? AND user_id = ?");
        $stmt->bind_param("dii", $jumlah_baru, $target_id, $user_id);
        $stmt->execute();
        $stmt->close();
        
        $stmt = $conn->prepare("INSERT INTO riwayat_tabungan (target_id, user_id, jenis, jumlah, keterangan) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iisds", $target_id, $user_id, $jenis, $jumlah, $keterangan);
        $stmt->execute();
        $stmt->close();
        
        if ($jumlah_baru >= $target['harga_target']) {
            $stmt = $conn->prepare("UPDATE target_tabungan SET status = 'tercapai' WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $target_id, $user_id);
            $stmt->execute();
            $stmt->close();
        }
        
        $success = __('success_saved');
    }
}

// ===== AMBIL DATA TARGET =====
$query_targets = "SELECT * FROM target_tabungan WHERE user_id = ? ORDER BY 
                  CASE WHEN status = 'aktif' THEN 0 ELSE 1 END, created_at DESC";
$stmt = $conn->prepare($query_targets);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result_targets = $stmt->get_result();
$stmt->close();

// ===== AMBIL RIWAYAT TABUNGAN =====
$query_riwayat = "SELECT r.*, t.nama_barang FROM riwayat_tabungan r 
                  JOIN target_tabungan t ON r.target_id = t.id 
                  WHERE r.user_id = ? 
                  ORDER BY r.created_at DESC LIMIT 20";
$stmt = $conn->prepare($query_riwayat);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result_riwayat = $stmt->get_result();
$stmt->close();

// ===== HITUNG TOTAL TABUNGAN =====
$query_total_tabungan = "SELECT SUM(jumlah_terkumpul) as total FROM target_tabungan WHERE user_id = ? AND status = 'aktif'";
$stmt = $conn->prepare($query_total_tabungan);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result_total = $stmt->get_result();
$total_tabungan = $result_total->fetch_assoc()['total'] ?? 0;
$stmt->close();
?>

<style>
/* ===== TARGET TABUNGAN STYLES ===== */
.target-card {
    border: none;
    border-radius: 16px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
    transition: all 0.3s ease;
    height: 100%;
    overflow: hidden;
    background: white;
}

.target-card:hover {
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.target-card .target-image {
    height: 180px;
    background: #f8fafc;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    position: relative;
}

.target-card .target-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.target-card .target-image .no-image {
    font-size: 3rem;
    color: #d1d5db;
}

.target-card .target-body {
    padding: 16px 18px 18px;
}

.target-card .target-body .target-name {
    font-weight: 700;
    font-size: 1rem;
    color: #1a1a2e;
    margin-bottom: 4px;
}

.target-card .target-body .target-price {
    color: #4a6cf7;
    font-weight: 600;
    font-size: 0.9rem;
}

.target-card .target-body .target-collected {
    color: #22c55e;
    font-weight: 600;
    font-size: 0.85rem;
}

.target-card .target-body .progress {
    height: 8px;
    border-radius: 10px;
    background: #e5e7eb;
    margin: 10px 0 8px;
    overflow: hidden;
}

.target-card .target-body .progress .progress-bar {
    border-radius: 10px;
    background: linear-gradient(90deg, #4a6cf7, #6a3de8);
    transition: width 0.5s ease;
}

.target-card .target-body .progress-text {
    font-size: 0.75rem;
    color: #6b7280;
    display: flex;
    justify-content: space-between;
}

.target-card .target-body .status-badge {
    font-size: 0.65rem;
    padding: 2px 10px;
    border-radius: 20px;
    font-weight: 600;
}

.target-card .target-body .status-badge.aktif {
    background: #dbeafe;
    color: #4a6cf7;
}

.target-card .target-body .status-badge.tercapai {
    background: #d1fae5;
    color: #22c55e;
}

.target-card .target-body .status-badge.gagal {
    background: #fee2e2;
    color: #ef4444;
}

.target-card .target-actions {
    padding: 12px 18px 18px;
    border-top: 1px solid #f0f0f0;
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.target-card .target-actions .btn-sm {
    font-size: 0.75rem;
    padding: 4px 12px;
    border-radius: 8px;
}

/* ===== MODAL STYLES ===== */
.modal-content {
    border-radius: 16px;
    border: none;
}

.modal-header {
    border-bottom: 2px solid #f0f0f0;
    padding: 18px 24px;
}

.modal-header .modal-title {
    font-weight: 700;
    font-size: 1.1rem;
}

.modal-body {
    padding: 24px;
}

.modal-body .form-control,
.modal-body .form-select {
    border-radius: 10px;
    border: 2px solid #e5e7eb;
    padding: 10px 14px;
    font-size: 0.9rem;
    transition: all 0.2s ease;
}

.modal-body .form-control:focus,
.modal-body .form-select:focus {
    border-color: #4a6cf7;
    box-shadow: 0 0 0 3px rgba(74, 108, 247, 0.1);
}

.modal-body .input-group-text {
    background: #f8fafc;
    border: 2px solid #e5e7eb;
    border-right: none;
    border-radius: 10px 0 0 10px;
}

.modal-body .input-group .form-control {
    border-radius: 0 10px 10px 0;
}

.modal-footer {
    border-top: 2px solid #f0f0f0;
    padding: 16px 24px;
}

/* ===== RIWAYAT ===== */
.history-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 14px;
    border-bottom: 1px solid #f5f5f5;
}

.history-item:last-child {
    border-bottom: none;
}

.history-item .icon {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
}

.history-item .icon.tambah {
    background: #d1fae5;
    color: #22c55e;
}

.history-item .icon.kurang {
    background: #fee2e2;
    color: #ef4444;
}

.history-item .detail {
    flex: 1;
}

.history-item .detail .title {
    font-weight: 600;
    font-size: 0.85rem;
    color: #1a1a2e;
}

.history-item .detail .sub {
    font-size: 0.75rem;
    color: #9ca3af;
}

.history-item .amount {
    font-weight: 700;
    font-size: 0.85rem;
}

.history-item .amount.tambah {
    color: #22c55e;
}

.history-item .amount.kurang {
    color: #ef4444;
}

/* ===== RESPONSIVE ===== */
@media (max-width: 575.98px) {
    .target-card .target-image {
        height: 140px;
    }
    
    .target-card .target-body {
        padding: 12px 14px 14px;
    }
    
    .target-card .target-body .target-name {
        font-size: 0.9rem;
    }
    
    .modal-body {
        padding: 16px;
    }
    
    .modal-header {
        padding: 14px 16px;
    }
}
</style>

<!-- ===== HEADER ===== -->
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h5 class="mb-0"><i class="bi bi-piggy-bank-fill"></i> <?php echo __('savings_title'); ?></h5>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">
        <i class="bi bi-plus-circle"></i> <?php echo __('add_target'); ?>
    </button>
</div>

<!-- ===== TARGET CARDS ===== -->
<div class="row g-3 mb-4">
    <?php if ($result_targets->num_rows > 0): ?>
        <?php while($target = $result_targets->fetch_assoc()): 
            $persentase = $target['harga_target'] > 0 ? ($target['jumlah_terkumpul'] / $target['harga_target'] * 100) : 0;
            $persentase = min($persentase, 100);
            $sisa = $target['harga_target'] - $target['jumlah_terkumpul'];
            $status_text = $target['status'] == 'aktif' ? __('target_active') : ($target['status'] == 'tercapai' ? __('target_achieved') : __('target_failed'));
        ?>
            <div class="col-12 col-sm-6 col-lg-4">
                <div class="target-card">
                    <div class="target-image">
                        <?php if (!empty($target['gambar']) && file_exists("assets/images/target/" . $target['gambar'])): ?>
                            <img src="assets/images/target/<?php echo $target['gambar']; ?>" alt="<?php echo htmlspecialchars($target['nama_barang']); ?>">
                        <?php else: ?>
                            <div class="no-image">🎯</div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="target-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="target-name"><?php echo htmlspecialchars($target['nama_barang']); ?></div>
                                <div>
                                    <span class="target-price"><?php echo formatCurrency($target['harga_target']); ?></span>
                                    <span class="target-collected ms-2">| <?php echo __('target_collected'); ?> <?php echo formatCurrency($target['jumlah_terkumpul']); ?></span>
                                </div>
                            </div>
                            <span class="status-badge <?php echo $target['status']; ?>">
                                <?php echo $status_text; ?>
                            </span>
                        </div>
                        
                        <div class="progress">
                            <div class="progress-bar" style="width: <?php echo $persentase; ?>%"></div>
                        </div>
                        <div class="progress-text">
                            <span><?php echo number_format($persentase, 1); ?>%</span>
                            <span><?php echo __('target_remaining'); ?>: <?php echo formatCurrency($sisa); ?></span>
                        </div>
                        
                        <?php if (!empty($target['deskripsi'])): ?>
                            <p class="text-muted small mt-1 mb-0"><?php echo htmlspecialchars($target['deskripsi']); ?></p>
                        <?php endif; ?>
                        
                        <?php if (!empty($target['target_tanggal'])): ?>
                            <small class="text-muted"><i class="bi bi-calendar"></i> <?php echo __('target_date'); ?>: <?php echo date('d/m/Y', strtotime($target['target_tanggal'])); ?></small>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($target['status'] == 'aktif'): ?>
                        <div class="target-actions">
                            <button class="btn btn-success btn-sm" onclick="openProgressModal(<?php echo $target['id']; ?>, 'tambah', '<?php echo addslashes($target['nama_barang']); ?>')">
                                <i class="bi bi-plus"></i> <?php echo __('add_progress'); ?>
                            </button>
                            <button class="btn btn-danger btn-sm" onclick="openProgressModal(<?php echo $target['id']; ?>, 'kurang', '<?php echo addslashes($target['nama_barang']); ?>')">
                                <i class="bi bi-dash"></i> <?php echo __('remove_progress'); ?>
                            </button>
                            <button class="btn btn-outline-danger btn-sm" onclick="if(confirm('<?php echo __('confirm_delete'); ?>')) location.href='hapus_target.php?id=<?php echo $target['id']; ?>'">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="col-12">
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="bi bi-piggy-bank-fill display-1 text-muted"></i>
                    <h5 class="mt-3"><?php echo __('no_savings_target'); ?></h5>
                    <p class="text-muted"><?php echo __('create_target'); ?></p>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambah">
                        <i class="bi bi-plus-circle"></i> <?php echo __('create_target'); ?>
                    </button>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- ===== RIWAYAT TABUNGAN ===== -->
<div class="card">
    <div class="card-header bg-light">
        <h6 class="mb-0"><i class="bi bi-clock-history"></i> <?php echo __('savings_history'); ?></h6>
    </div>
    <div class="card-body p-0">
        <?php if ($result_riwayat->num_rows > 0): ?>
            <div class="p-2">
                <?php while($row = $result_riwayat->fetch_assoc()): ?>
                    <div class="history-item">
                        <div class="icon <?php echo $row['jenis']; ?>">
                            <i class="bi bi-<?php echo $row['jenis'] == 'tambah' ? 'plus' : 'dash'; ?>"></i>
                        </div>
                        <div class="detail">
                            <div class="title"><?php echo htmlspecialchars($row['nama_barang']); ?></div>
                            <div class="sub">
                                <?php echo $row['keterangan'] ? htmlspecialchars($row['keterangan']) : __('no_data'); ?>
                                <span class="ms-2">• <?php echo date('d/m/Y H:i', strtotime($row['created_at'])); ?></span>
                            </div>
                        </div>
                        <div class="amount <?php echo $row['jenis']; ?>">
                            <?php echo $row['jenis'] == 'tambah' ? '+' : '-'; ?> <?php echo formatCurrency($row['jumlah']); ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="text-center text-muted py-4">
                <i class="bi bi-inbox fs-2 d-block"></i>
                <span><?php echo __('no_data'); ?></span>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ===== MODAL TAMBAH TARGET ===== -->
<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-plus-circle"></i> <?php echo __('add_target'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="tambah">
                <div class="modal-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    <?php if (isset($success)): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <!-- ===== NAMA BARANG ===== -->
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('target_name'); ?> <span class="text-danger">*</span></label>
                        <input type="text" name="nama_barang" class="form-control" placeholder="<?php echo __('target_name_placeholder'); ?>" required>
                    </div>
                    
                    <!-- ===== HARGA TARGET ===== -->
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('target_price'); ?> <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><?php echo getCurrencySymbol($lang); ?></span>
                            <input type="text" name="harga_target" class="form-control" placeholder="<?php echo __('target_price_placeholder'); ?>" required onkeyup="formatRupiah(this)">
                        </div>
                    </div>
                    
                    <!-- ===== UPLOAD GAMBAR ===== -->
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('target_image'); ?></label>
                        <input type="file" name="gambar" class="form-control" accept="image/*">
                        <small class="text-muted"><?php echo __('target_image_format'); ?></small>
                    </div>
                    
                    <!-- ===== DESKRIPSI ===== -->
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('target_description'); ?></label>
                        <textarea name="deskripsi" class="form-control" rows="2" placeholder="<?php echo __('target_description_placeholder'); ?>"></textarea>
                    </div>
                    
                    <!-- ===== TARGET TANGGAL ===== -->
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('target_date'); ?></label>
                        <input type="date" name="target_tanggal" class="form-control">
                        <small class="text-muted"><?php echo __('target_date_hint'); ?></small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('cancel'); ?></button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> <?php echo __('save'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ===== MODAL UPDATE PROGRESS ===== -->
<div class="modal fade" id="modalProgress" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="progressModalTitle"><?php echo __('update_progress'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="update_progress">
                <input type="hidden" name="target_id" id="progress_target_id">
                <input type="hidden" name="jenis_progress" id="progress_jenis">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('progress_amount'); ?> <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><?php echo getCurrencySymbol($lang); ?></span>
                            <input type="text" name="jumlah" id="progress_jumlah" class="form-control" placeholder="<?php echo __('progress_amount_placeholder'); ?>" required onkeyup="formatRupiah(this)">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('progress_note'); ?></label>
                        <input type="text" name="keterangan" class="form-control" placeholder="<?php echo __('progress_note_placeholder'); ?>" id="progress_keterangan">
                    </div>
                    <div id="progress_info" class="alert alert-info small"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('cancel'); ?></button>
                    <button type="submit" class="btn btn-primary" id="progress_submit_btn">
                        <i class="bi bi-save"></i> <?php echo __('save'); ?>
                    </button>
                </div>
            </form>
        </div>
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

function openProgressModal(targetId, jenis, namaBarang) {
    document.getElementById('progress_target_id').value = targetId;
    document.getElementById('progress_jenis').value = jenis;
    document.getElementById('progress_jumlah').value = '';
    document.getElementById('progress_keterangan').value = '';
    
    const title = document.getElementById('progressModalTitle');
    const info = document.getElementById('progress_info');
    const submitBtn = document.getElementById('progress_submit_btn');
    
    if (jenis == 'tambah') {
        title.innerHTML = '<i class="bi bi-plus-circle text-success"></i> <?php echo __('add_progress'); ?> - ' + namaBarang;
        info.innerHTML = '✅ <?php echo __('add_progress_info'); ?>';
        submitBtn.className = 'btn btn-success';
        submitBtn.innerHTML = '<i class="bi bi-plus"></i> <?php echo __('add_progress'); ?>';
    } else {
        title.innerHTML = '<i class="bi bi-dash-circle text-danger"></i> <?php echo __('remove_progress'); ?> - ' + namaBarang;
        info.innerHTML = '⚠️ <?php echo __('remove_progress_info'); ?>';
        submitBtn.className = 'btn btn-danger';
        submitBtn.innerHTML = '<i class="bi bi-dash"></i> <?php echo __('remove_progress'); ?>';
    }
    
    new bootstrap.Modal(document.getElementById('modalProgress')).show();
}
</script>

<?php include 'includes/footer.php'; ?>