<?php
require_once 'config/database.php';
require_once 'includes/auth_check.php';

// ===== LOAD LANGUAGE =====
require_once 'includes/lang.php';
require_once 'includes/functions.php';

// Cek bahasa
if (!isset($_SESSION['language'])) {
    $stmt = $conn->prepare("SELECT prefered_language FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        if (!empty($row['prefered_language'])) {
            $_SESSION['language'] = $row['prefered_language'];
            $_SESSION['currency_symbol'] = getCurrencySymbol($row['prefered_language']);
            $_SESSION['currency_code'] = getCurrencyCode($row['prefered_language']);
        } else {
            header('Location: select_language.php');
            exit;
        }
    }
    $stmt->close();
}

// Load language
$lang = $_SESSION['language'] ?? 'en';
$lang_data = loadLanguage($lang);

include 'includes/header.php';

// Hitung total pemasukan dan pengeluaran untuk user ini
$query_pemasukan = "SELECT SUM(jumlah) as total FROM catatan_keuangan WHERE user_id = ? AND jenis='pemasukan' AND dari_tabungan = 0";
$stmt = $conn->prepare($query_pemasukan);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result_pemasukan = $stmt->get_result();
$total_pemasukan = $result_pemasukan->fetch_assoc()['total'] ?? 0;
$stmt->close();

$query_pengeluaran = "SELECT SUM(jumlah) as total FROM catatan_keuangan WHERE user_id = ? AND jenis='pengeluaran' AND dari_tabungan = 0";
$stmt = $conn->prepare($query_pengeluaran);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result_pengeluaran = $stmt->get_result();
$total_pengeluaran = $result_pengeluaran->fetch_assoc()['total'] ?? 0;
$stmt->close();

// Hitung total tabungan
$query_total_tabungan = "SELECT SUM(jumlah_terkumpul) as total FROM target_tabungan WHERE user_id = ? AND status = 'aktif'";
$stmt = $conn->prepare($query_total_tabungan);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result_total_tabungan = $stmt->get_result();
$total_tabungan = $result_total_tabungan->fetch_assoc()['total'] ?? 0;
$stmt->close();

// Saldo = Pemasukan - Pengeluaran - Tabungan
$saldo = $total_pemasukan - $total_pengeluaran - $total_tabungan;

// Ambil data untuk chart (6 bulan terakhir)
$chart_data = [];
for ($i = 5; $i >= 0; $i--) {
    $bulan = date('Y-m', strtotime("-$i months"));
    $bulan_nama = date('M', strtotime("-$i months"));
    
    $stmt = $conn->prepare("SELECT SUM(jumlah) as total FROM catatan_keuangan 
                            WHERE user_id = ? AND jenis='pemasukan' 
                            AND dari_tabungan = 0 
                            AND DATE_FORMAT(tanggal, '%Y-%m') = ?");
    $stmt->bind_param("is", $user_id, $bulan);
    $stmt->execute();
    $result = $stmt->get_result();
    $pemasukan = $result->fetch_assoc()['total'] ?? 0;
    $stmt->close();
    
    $stmt = $conn->prepare("SELECT SUM(jumlah) as total FROM catatan_keuangan 
                            WHERE user_id = ? AND jenis='pengeluaran' 
                            AND dari_tabungan = 0 
                            AND DATE_FORMAT(tanggal, '%Y-%m') = ?");
    $stmt->bind_param("is", $user_id, $bulan);
    $stmt->execute();
    $result = $stmt->get_result();
    $pengeluaran = $result->fetch_assoc()['total'] ?? 0;
    $stmt->close();
    
    // Tabungan bulan ini
    $stmt = $conn->prepare("SELECT SUM(CASE WHEN jenis_tabungan = 'ditabung' THEN jumlah ELSE -jumlah END) as total 
                            FROM catatan_keuangan 
                            WHERE user_id = ? AND dari_tabungan = 1 
                            AND DATE_FORMAT(tanggal, '%Y-%m') = ?");
    $stmt->bind_param("is", $user_id, $bulan);
    $stmt->execute();
    $result = $stmt->get_result();
    $tabungan = $result->fetch_assoc()['total'] ?? 0;
    $stmt->close();
    
    $chart_data[] = [
        'bulan' => $bulan_nama,
        'pemasukan' => (float)$pemasukan,
        'pengeluaran' => (float)$pengeluaran,
        'tabungan' => (float)$tabungan
    ];
}

// Ambil data kategori untuk chart pie
$query_kategori = "SELECT kategori, jenis, SUM(jumlah) as total 
                   FROM catatan_keuangan 
                   WHERE user_id = ? AND jenis='pengeluaran' AND dari_tabungan = 0
                   GROUP BY kategori 
                   ORDER BY total DESC 
                   LIMIT 5";
$stmt = $conn->prepare($query_kategori);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result_kategori = $stmt->get_result();
$kategori_labels = [];
$kategori_data = [];
while ($row = $result_kategori->fetch_assoc()) {
    $kategori_labels[] = translateCategory($row['kategori']);
    $kategori_data[] = (float)$row['total'];
}
$stmt->close();

// Tambahkan kategori Tabungan
$kategori_labels[] = __('savings');
$kategori_data[] = (float)$total_tabungan;

// ===== AMBIL DATA TRANSAKSI HARI INI =====
$query = "SELECT * FROM catatan_keuangan 
          WHERE user_id = ? 
          AND tanggal = CURDATE() 
          ORDER BY tanggal DESC, created_at DESC 
          LIMIT 20";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();

// Total transaksi hari ini
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM catatan_keuangan WHERE user_id = ? AND tanggal = CURDATE()");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result_count = $stmt->get_result();
$total_transaksi_hari_ini = $result_count->fetch_assoc()['total'] ?? 0;
$stmt->close();

// Total transaksi keseluruhan (untuk card)
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM catatan_keuangan WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result_count_all = $stmt->get_result();
$total_transaksi = $result_count_all->fetch_assoc()['total'] ?? 0;
$stmt->close();

// Ambil jumlah target aktif
$query_target_aktif = "SELECT COUNT(*) as total FROM target_tabungan WHERE user_id = ? AND status = 'aktif'";
$stmt = $conn->prepare($query_target_aktif);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result_target = $stmt->get_result();
$total_target_aktif = $result_target->fetch_assoc()['total'] ?? 0;
$stmt->close();

// ===== HITUNG TOTAL PEMASUKAN & PENGELUARAN HARI INI =====
$query_pemasukan_hari_ini = "SELECT SUM(jumlah) as total FROM catatan_keuangan 
                             WHERE user_id = ? AND jenis='pemasukan' 
                             AND dari_tabungan = 0 AND tanggal = CURDATE()";
$stmt = $conn->prepare($query_pemasukan_hari_ini);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result_pemasukan_hari_ini = $stmt->get_result();
$total_pemasukan_hari_ini = $result_pemasukan_hari_ini->fetch_assoc()['total'] ?? 0;
$stmt->close();

$query_pengeluaran_hari_ini = "SELECT SUM(jumlah) as total FROM catatan_keuangan 
                               WHERE user_id = ? AND jenis='pengeluaran' 
                               AND dari_tabungan = 0 AND tanggal = CURDATE()";
$stmt = $conn->prepare($query_pengeluaran_hari_ini);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result_pengeluaran_hari_ini = $stmt->get_result();
$total_pengeluaran_hari_ini = $result_pengeluaran_hari_ini->fetch_assoc()['total'] ?? 0;
$stmt->close();
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- ===== SUMMARY CARDS ===== -->
<div class="row g-2 g-md-3 mb-3">
    <div class="col-6 col-md-3">
        <div class="summary-card bg-success fade-in-up">
            <i class="bi bi-arrow-up-circle card-icon"></i>
            <div class="card-label"><?php echo __('total_income'); ?></div>
            <div class="card-value"><?php echo formatCurrency($total_pemasukan); ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="summary-card bg-danger fade-in-up" style="animation-delay: 0.1s;">
            <i class="bi bi-arrow-down-circle card-icon"></i>
            <div class="card-label"><?php echo __('total_expense'); ?></div>
            <div class="card-value"><?php echo formatCurrency($total_pengeluaran); ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="summary-card bg-warning fade-in-up" style="animation-delay: 0.15s;">
            <i class="bi bi-piggy-bank-fill card-icon"></i>
            <div class="card-label"><?php echo __('total_savings'); ?></div>
            <div class="card-value"><?php echo formatCurrency($total_tabungan); ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="summary-card <?php echo $saldo >= 0 ? 'bg-info' : 'bg-danger'; ?> fade-in-up" style="animation-delay: 0.2s;">
            <i class="bi bi-wallet card-icon"></i>
            <div class="card-label"><?php echo __('total_balance'); ?></div>
            <div class="card-value"><?php echo formatCurrency($saldo); ?></div>
        </div>
    </div>
</div>

<!-- ===== RINGKASAN HARI INI ===== -->
<div class="row g-2 g-md-3 mb-3">
    <div class="col-12">
        <div class="card bg-info bg-opacity-10 border-info">
            <div class="card-body d-flex align-items-center justify-content-between flex-wrap">
                <div>
                    <h6 class="mb-1"><i class="bi bi-calendar-day"></i> <?php echo __('today_summary'); ?></h6>
                    <div class="d-flex flex-wrap gap-3">
                        <span><strong><?php echo __('income'); ?>:</strong> <span class="text-success"><?php echo formatCurrency($total_pemasukan_hari_ini); ?></span></span>
                        <span><strong><?php echo __('expense'); ?>:</strong> <span class="text-danger"><?php echo formatCurrency($total_pengeluaran_hari_ini); ?></span></span>
                        <span><strong><?php echo __('transactions'); ?>:</strong> <?php echo $total_transaksi_hari_ini; ?></span>
                    </div>
                </div>
                <span class="text-muted small"><?php echo date('d F Y'); ?></span>
            </div>
        </div>
    </div>
</div>

<!-- ===== RINGKASAN TARGET TABUNGAN ===== -->
<div class="row g-2 g-md-3 mb-3">
    <div class="col-12">
        <div class="card bg-warning bg-opacity-10 border-warning">
            <div class="card-body d-flex align-items-center justify-content-between flex-wrap">
                <div>
                    <h6 class="mb-1"><i class="bi bi-piggy-bank-fill"></i> <?php echo __('active_targets'); ?></h6>
                    <span class="fw-bold fs-5"><?php echo $total_target_aktif; ?> <?php echo __('target'); ?></span>
                    <?php if ($total_target_aktif > 0): ?>
                        <span class="text-muted ms-2">| <?php echo __('total_savings'); ?>: <?php echo formatCurrency($total_tabungan); ?></span>
                    <?php endif; ?>
                </div>
                <a href="target.php" class="btn btn-sm btn-warning">
                    <?php echo __('view_targets'); ?> <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- ===== CHARTS ===== -->
<div class="row g-2 g-md-3 mb-3">
    <div class="col-12 col-lg-8">
        <div class="card fade-in-up" style="animation-delay: 0.2s;">
            <div class="card-header bg-primary">
                <i class="bi bi-graph-up"></i> <?php echo __('trend_chart'); ?>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="trendChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-4">
        <div class="card fade-in-up" style="animation-delay: 0.3s;">
            <div class="card-header bg-danger">
                <i class="bi bi-pie-chart"></i> <?php echo __('expense_chart'); ?>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ===== TRANSAKSI HARI INI ===== -->
<div class="card fade-in-up" style="animation-delay: 0.4s;">
    <div class="card-header bg-primary d-flex justify-content-between align-items-center">
        <span><i class="bi bi-clock-history"></i> <?php echo __('today_transactions'); ?></span>
        <div>
            <span class="badge bg-light text-dark me-2"><?php echo $total_transaksi_hari_ini; ?> <?php echo __('transactions'); ?></span>
            <a href="tambah.php" class="btn btn-sm btn-light">
                <i class="bi bi-plus-circle"></i> <?php echo __('add'); ?>
            </a>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th><?php echo __('date'); ?></th>
                        <th><?php echo __('type'); ?></th>
                        <th><?php echo __('category'); ?></th>
                        <th><?php echo __('amount'); ?></th>
                        <th><?php echo __('description'); ?></th>
                        <th class="text-center"><?php echo __('actions'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): 
                            $jenis_label = $row['jenis'];
                            $kategori_label = $row['kategori'];
                            $deskripsi = $row['deskripsi'];
                            $badge_class = $row['jenis'] == 'pemasukan' ? 'bg-success' : 'bg-danger';
                            
                            if ($row['dari_tabungan'] == 1) {
                                if ($row['jenis_tabungan'] == 'ditabung') {
                                    $jenis_label = __('saved');
                                    $kategori_label = translateCategory($row['kategori']);
                                    $badge_class = 'bg-warning';
                                } else {
                                    $jenis_label = __('withdrawn');
                                    $kategori_label = translateCategory($row['kategori']);
                                    $badge_class = 'bg-info';
                                }
                            } else {
                                if ($row['jenis'] == 'pemasukan') {
                                    $jenis_label = __('income');
                                } else {
                                    $jenis_label = __('expense');
                                }
                                $kategori_label = translateCategory($row['kategori']);
                            }
                        ?>
                            <tr>
                                <td><small><?php echo date('d/m', strtotime($row['tanggal'])); ?></small></td>
                                <td>
                                    <span class="badge <?php echo $badge_class; ?>">
                                        <?php echo $jenis_label; ?>
                                    </span>
                                </td>
                                <td><small><?php echo htmlspecialchars($kategori_label); ?></small></td>
                                <td><small><?php echo formatCurrency($row['jumlah']); ?></small></td>
                                <td><small><?php echo htmlspecialchars($deskripsi) ?: '-'; ?></small></td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <a href="edit.php?id=<?php echo $row['id']; ?>" class="btn btn-warning">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="hapus.php?id=<?php echo $row['id']; ?>" 
                                           class="btn btn-danger" 
                                           onclick="return confirm('<?php echo __('confirm_delete'); ?>')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                <i class="bi bi-inbox fs-1 d-block text-muted"></i>
                                <span class="text-muted"><?php echo __('no_transactions_today'); ?></span>
                                <br>
                                <a href="tambah.php" class="btn btn-sm btn-primary mt-2">
                                    <i class="bi bi-plus-circle"></i> <?php echo __('add_transaction'); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- ===== FLOATING BUTTON KARAKTER ===== -->
<!-- ============================================ -->

<style>
.floating-btn {
    position: fixed;
    bottom: 90px;
    right: 20px;
    z-index: 999;
    width: 70px;
    height: 70px;
    border-radius: 50%;
    background: transparent;
    border: none;
    cursor: pointer;
    padding: 0;
    transition: all 0.3s ease;
    text-decoration: none;
    outline: none;
    animation: float 3s ease-in-out infinite;
}

.floating-btn:hover {
    animation-play-state: paused;
    transform: scale(1.08);
}

.floating-btn:active {
    transform: scale(0.92);
}

.floating-btn .btn-wrapper {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    background: linear-gradient(135deg, #4a6cf7 0%, #6a3de8 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 20px rgba(74, 108, 247, 0.35);
    transition: all 0.3s ease;
    padding: 8px;
    position: relative;
}

.floating-btn .btn-wrapper:hover {
    box-shadow: 0 6px 28px rgba(74, 108, 247, 0.5);
}

.floating-btn .btn-wrapper img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    border-radius: 50%;
    display: block;
}

.floating-btn .btn-wrapper .pulse-ring {
    position: absolute;
    width: 100%;
    height: 100%;
    border-radius: 50%;
    border: 3px solid rgba(74, 108, 247, 0.3);
    animation: pulse-ring 2s ease-out infinite;
    top: 0;
    left: 0;
}

.floating-btn .tooltip-text {
    position: absolute;
    right: 80px;
    top: 50%;
    transform: translateY(-50%);
    background: #1a1a2e;
    color: white;
    padding: 6px 16px;
    border-radius: 8px;
    font-size: 0.8rem;
    font-weight: 500;
    white-space: nowrap;
    opacity: 0;
    pointer-events: none;
    transition: all 0.3s ease;
    box-shadow: 0 2px 12px rgba(0,0,0,0.15);
}

.floating-btn .tooltip-text::after {
    content: '';
    position: absolute;
    right: -8px;
    top: 50%;
    transform: translateY(-50%);
    border-left: 8px solid #1a1a2e;
    border-top: 6px solid transparent;
    border-bottom: 6px solid transparent;
}

.floating-btn:hover .tooltip-text {
    opacity: 1;
    right: 85px;
}

.floating-btn .badge-notif {
    position: absolute;
    top: -6px;
    right: -6px;
    background: #ef4444;
    color: white;
    font-size: 0.65rem;
    font-weight: 700;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 3px solid white;
    box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3);
    animation: bounce-badge 2s ease-in-out infinite;
}

@keyframes pulse-ring {
    0% { transform: scale(1); opacity: 1; }
    100% { transform: scale(1.5); opacity: 0; }
}

@keyframes bounce-badge {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.15); }
}

@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-8px); }
}

@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.fade-in-up {
    opacity: 0;
    animation: fadeInUp 0.5s ease forwards;
}

.fade-in-up:nth-child(2) { animation-delay: 0.1s; }
.fade-in-up:nth-child(3) { animation-delay: 0.15s; }
.fade-in-up:nth-child(4) { animation-delay: 0.2s; }
.fade-in-up:nth-child(5) { animation-delay: 0.25s; }

@media (max-width: 575.98px) {
    .floating-btn {
        bottom: 75px;
        right: 14px;
        width: 56px;
        height: 56px;
    }
    .floating-btn .btn-wrapper { padding: 6px; }
    .floating-btn .tooltip-text { display: none; }
    .floating-btn .badge-notif {
        width: 20px;
        height: 20px;
        font-size: 0.55rem;
        top: -4px;
        right: -4px;
    }
}

@media (min-width: 768px) {
    .floating-btn {
        bottom: 30px;
        right: 30px;
        width: 76px;
        height: 76px;
    }
}

/* ===== MODAL PANDUAN ===== */
.panduan-modal .modal-content {
    border-radius: 16px;
    border: none;
    box-shadow: 0 20px 60px rgba(0,0,0,0.15);
}

.panduan-modal .modal-header {
    border-bottom: 2px solid #f0f0f0;
    padding: 20px 24px;
}

.panduan-modal .modal-header .modal-title {
    font-weight: 700;
    font-size: 1.2rem;
    color: #1a1a2e;
}

.panduan-modal .modal-header .modal-title i {
    color: #fbbf24;
    margin-right: 10px;
}

.panduan-modal .modal-body {
    padding: 24px;
    max-height: 70vh;
    overflow-y: auto;
}

.panduan-modal .modal-body .section-title {
    font-weight: 700;
    font-size: 1rem;
    color: #1a1a2e;
    margin: 20px 0 12px 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.panduan-modal .modal-body .section-title:first-child {
    margin-top: 0;
}

.panduan-modal .modal-body .section-title .num {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    background: #4a6cf7;
    color: white;
    border-radius: 50%;
    font-size: 0.8rem;
    font-weight: 700;
    flex-shrink: 0;
}

.panduan-modal .modal-body .tip-item {
    background: #f8fafc;
    padding: 14px 16px;
    border-radius: 10px;
    margin-bottom: 10px;
    border-left: 4px solid #4a6cf7;
}

.panduan-modal .modal-body .tip-item .tip-title {
    font-weight: 600;
    font-size: 0.9rem;
    color: #1a1a2e;
}

.panduan-modal .modal-body .tip-item .tip-desc {
    font-size: 0.85rem;
    color: #6b7280;
    margin: 4px 0 0 0;
    line-height: 1.5;
}

.panduan-modal .modal-body .tip-item.green {
    border-left-color: #22c55e;
}

.panduan-modal .modal-body .tip-item.orange {
    border-left-color: #f59e0b;
}

.panduan-modal .modal-body .tip-item.purple {
    border-left-color: #8b5cf6;
}

.panduan-modal .modal-body .tip-item.red {
    border-left-color: #ef4444;
}

.panduan-modal .modal-body .tip-item.pink {
    border-left-color: #ec4899;
}

.panduan-modal .modal-body .tip-item.teal {
    border-left-color: #14b8a6;
}

.panduan-modal .modal-footer {
    border-top: 2px solid #f0f0f0;
    padding: 16px 24px;
}

.panduan-modal .modal-footer .btn-close-modal {
    background: #4a6cf7;
    color: white;
    border: none;
    padding: 10px 32px;
    border-radius: 10px;
    font-weight: 600;
    transition: all 0.2s ease;
}

.panduan-modal .modal-footer .btn-close-modal:hover {
    background: #3a5de7;
}

.panduan-modal .modal-body::-webkit-scrollbar {
    width: 4px;
}

.panduan-modal .modal-body::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

.panduan-modal .modal-body::-webkit-scrollbar-thumb {
    background: #d1d5db;
    border-radius: 10px;
}

.panduan-modal .modal-body::-webkit-scrollbar-thumb:hover {
    background: #9ca3af;
}
</style>

<!-- ===== FLOATING BUTTON KARAKTER ===== -->
<button class="floating-btn" data-bs-toggle="modal" data-bs-target="#panduanModal" aria-label="Panduan Keuangan">
    <div class="btn-wrapper">
        <img src="assets/images/karakter.png" 
             alt="Karakter Panduan" 
             onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><rect width=%22100%22 height=%22100%22 fill=%22%234a6cf7%22 rx=%2250%22/><text x=%2250%22 y=%2260%22 text-anchor=%22middle%22 font-size=%2240%22>💡</text></svg>'">
        <span class="pulse-ring"></span>
    </div>
    <span class="tooltip-text">💡 <?php echo __('panduan_keuangan'); ?></span>
    <span class="badge-notif">?</span>
</button>

<!-- ===== MODAL PANDUAN ===== -->
<div class="modal fade panduan-modal" id="panduanModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-lightbulb-fill"></i> <?php echo __('panduan_keuangan'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p style="color: #6b7280; font-size: 0.95rem; line-height: 1.6; margin-bottom: 20px;">
                    <?php echo __('panduan_intro'); ?>
                </p>

                <!-- ===== 1. BUAT ANGGARAN ===== -->
                <div class="section-title">
                    <span class="num">1</span> <?php echo __('panduan_anggaran'); ?>
                </div>
                <div class="tip-item">
                    <div class="tip-title"><?php echo __('panduan_catat_pemasukan'); ?></div>
                    <p class="tip-desc"><?php echo __('panduan_catat_pemasukan_desc'); ?></p>
                </div>
                <div class="tip-item green">
                    <div class="tip-title"><?php echo __('panduan_alokasi_kebutuhan'); ?></div>
                    <p class="tip-desc"><?php echo __('panduan_alokasi_kebutuhan_desc'); ?></p>
                </div>
                <div class="tip-item orange">
                    <div class="tip-title"><?php echo __('panduan_sisihkan_tabungan'); ?></div>
                    <p class="tip-desc"><?php echo __('panduan_sisihkan_tabungan_desc'); ?></p>
                </div>

                <!-- ===== 2. CATAT PENGELUARAN ===== -->
                <div class="section-title">
                    <span class="num">2</span> <?php echo __('panduan_catat_pengeluaran'); ?>
                </div>
                <div class="tip-item">
                    <div class="tip-title"><?php echo __('panduan_disiplin_mencatat'); ?></div>
                    <p class="tip-desc"><?php echo __('panduan_disiplin_mencatat_desc'); ?></p>
                </div>
                <div class="tip-item red">
                    <div class="tip-title"><?php echo __('panduan_evaluasi'); ?></div>
                    <p class="tip-desc"><?php echo __('panduan_evaluasi_desc'); ?></p>
                </div>

                <!-- ===== 3. ATURAN 50/30/20 ===== -->
                <div class="section-title">
                    <span class="num">3</span> <?php echo __('panduan_aturan_50_30_20'); ?>
                </div>
                <div class="tip-item green">
                    <div class="tip-title"><?php echo __('panduan_50_persen'); ?></div>
                    <p class="tip-desc"><?php echo __('panduan_50_persen_desc'); ?></p>
                </div>
                <div class="tip-item orange">
                    <div class="tip-title"><?php echo __('panduan_30_persen'); ?></div>
                    <p class="tip-desc"><?php echo __('panduan_30_persen_desc'); ?></p>
                </div>
                <div class="tip-item purple">
                    <div class="tip-title"><?php echo __('panduan_20_persen'); ?></div>
                    <p class="tip-desc"><?php echo __('panduan_20_persen_desc'); ?></p>
                </div>

                <!-- ===== 4. KELOLA TABUNGAN ===== -->
                <div class="section-title">
                    <span class="num">4</span> <?php echo __('panduan_kelola_tabungan'); ?>
                </div>
                <div class="tip-item teal">
                    <div class="tip-title"><?php echo __('panduan_target_tabungan'); ?></div>
                    <p class="tip-desc"><?php echo __('panduan_target_tabungan_desc'); ?></p>
                </div>
                <div class="tip-item pink">
                    <div class="tip-title"><?php echo __('panduan_pantau'); ?></div>
                    <p class="tip-desc"><?php echo __('panduan_pantau_desc'); ?></p>
                </div>
                <div class="tip-item purple">
                    <div class="tip-title"><?php echo __('panduan_konsisten'); ?></div>
                    <p class="tip-desc"><?php echo __('panduan_konsisten_desc'); ?></p>
                </div>

                <!-- ===== 5. TIPS TAMBAHAN ===== -->
                <div class="section-title">
                    <span class="num">5</span> <?php echo __('panduan_tips_tambahan'); ?>
                </div>
                <div class="tip-item">
                    <div class="tip-title"><?php echo __('panduan_tujuan_keuangan'); ?></div>
                    <p class="tip-desc"><?php echo __('panduan_tujuan_keuangan_desc'); ?></p>
                </div>
                <div class="tip-item green">
                    <div class="tip-title"><?php echo __('panduan_review'); ?></div>
                    <p class="tip-desc"><?php echo __('panduan_review_desc'); ?></p>
                </div>
                <div class="tip-item red">
                    <div class="tip-title"><?php echo __('panduan_dana_darurat'); ?></div>
                    <p class="tip-desc"><?php echo __('panduan_dana_darurat_desc'); ?></p>
                </div>
                <div class="tip-item orange">
                    <div class="tip-title"><?php echo __('panduan_gunakan_aplikasi'); ?></div>
                    <p class="tip-desc"><?php echo __('panduan_gunakan_aplikasi_desc'); ?></p>
                </div>

                <div style="background: #f0f4ff; padding: 16px 20px; border-radius: 12px; margin-top: 20px;">
                    <p style="margin: 0; color: #4a6cf7; font-weight: 600; font-size: 0.9rem;">
                        💡 <strong><?php echo __('panduan_penutup'); ?></strong>
                    </p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-close-modal" data-bs-dismiss="modal">
                    <i class="bi bi-check2"></i> <?php echo __('panduan_mengerti'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ===== CHARTS SCRIPT ===== -->
<script>
const ctx1 = document.getElementById('trendChart').getContext('2d');
const chartData = <?php echo json_encode($chart_data); ?>;

new Chart(ctx1, {
    type: 'line',
    data: {
        labels: chartData.map(item => item.bulan),
        datasets: [
            {
                label: '<?php echo __('income'); ?>',
                data: chartData.map(item => item.pemasukan),
                borderColor: '#22c55e',
                backgroundColor: 'rgba(34, 197, 94, 0.1)',
                borderWidth: 2,
                tension: 0.4,
                fill: true,
                pointRadius: 3
            },
            {
                label: '<?php echo __('expense'); ?>',
                data: chartData.map(item => item.pengeluaran),
                borderColor: '#ef4444',
                backgroundColor: 'rgba(239, 68, 68, 0.1)',
                borderWidth: 2,
                tension: 0.4,
                fill: true,
                pointRadius: 3
            },
            {
                label: '<?php echo __('savings'); ?>',
                data: chartData.map(item => item.tabungan),
                borderColor: '#f59e0b',
                backgroundColor: 'rgba(245, 158, 11, 0.1)',
                borderWidth: 2,
                tension: 0.4,
                fill: true,
                pointRadius: 3,
                borderDash: [5, 5]
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'top', labels: { font: { size: 11 }, boxWidth: 12 } }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    font: { size: 10 },
                    callback: function(value) {
                        if (value >= 1000000) return (value/1000000).toFixed(1) + 'M';
                        if (value >= 1000) return (value/1000).toFixed(0) + 'K';
                        return value;
                    }
                }
            },
            x: { ticks: { font: { size: 10 } } }
        }
    }
});

const ctx2 = document.getElementById('categoryChart').getContext('2d');
const kategoriLabels = <?php echo json_encode($kategori_labels); ?>;
const kategoriData = <?php echo json_encode($kategori_data); ?>;
const colors = ['#ef4444', '#3b82f6', '#22c55e', '#f59e0b', '#8b5cf6', '#ec4899'];

new Chart(ctx2, {
    type: 'doughnut',
    data: {
        labels: kategoriLabels.length ? kategoriLabels : ['<?php echo __('no_data'); ?>'],
        datasets: [{
            data: kategoriLabels.length ? kategoriData : [1],
            backgroundColor: kategoriLabels.length ? colors.slice(0, kategoriLabels.length) : ['#e9ecef'],
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'bottom', labels: { font: { size: 10 }, boxWidth: 12, padding: 8 } }
        },
        cutout: '60%'
    }
});
</script>

<?php include 'includes/footer.php'; ?>