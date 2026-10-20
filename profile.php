<?php
require_once 'config/database.php';
require_once 'includes/auth_check.php';

// ===== LOAD LANGUAGE =====
require_once 'includes/lang.php';
require_once 'includes/functions.php';

// Load language
$lang = $_SESSION['language'] ?? 'en';
$lang_data = loadLanguage($lang);

// ============================================================
// PROSES UPDATE BAHASA - DILAKUKAN SEBELUM INCLUDE HEADER
// ============================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_language') {
    $language = $_POST['language'] ?? 'en';
    $available_langs = ['en', 'id', 'vi', 'ja'];
    
    if (in_array($language, $available_langs)) {
        $_SESSION['language'] = $language;
        $_SESSION['currency_symbol'] = getCurrencySymbol($language);
        $_SESSION['currency_code'] = getCurrencyCode($language);
        
        $stmt = $conn->prepare("UPDATE users SET prefered_language = ? WHERE id = ?");
        $stmt->bind_param("si", $language, $_SESSION['user_id']);
        $stmt->execute();
        $stmt->close();
        
        // Redirect setelah update bahasa
        header('Location: profile.php');
        exit;
    }
}

include 'includes/header.php';

$message = '';
$message_type = '';
$error = '';

// ===== AMBIL DATA USER =====
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// ===== HITUNG SALDO =====
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

$query_tabungan = "SELECT SUM(jumlah_terkumpul) as total FROM target_tabungan WHERE user_id = ? AND status = 'aktif'";
$stmt = $conn->prepare($query_tabungan);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result_tabungan = $stmt->get_result();
$total_tabungan = $result_tabungan->fetch_assoc()['total'] ?? 0;
$stmt->close();

$saldo = $total_pemasukan - $total_pengeluaran - $total_tabungan;

// ============================================================
// PROSES UPDATE PROFIL (NON-AJAX)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    // ===== UPDATE DATA DIRI =====
    if ($action == 'update_profile') {
        $full_name = trim($_POST['full_name']);
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        
        if (empty($full_name) || empty($username) || empty($email)) {
            $message = __('error_required');
            $message_type = 'danger';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = __('error_invalid');
            $message_type = 'danger';
        } else {
            $stmt = $conn->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
            $stmt->bind_param("ssi", $username, $email, $user_id);
            $stmt->execute();
            $result_check = $stmt->get_result();
            
            if ($result_check->num_rows > 0) {
                $message = 'Username or email already used by another user!';
                $message_type = 'danger';
            } else {
                $stmt_update = $conn->prepare("UPDATE users SET full_name = ?, username = ?, email = ? WHERE id = ?");
                $stmt_update->bind_param("sssi", $full_name, $username, $email, $user_id);
                
                if ($stmt_update->execute()) {
                    $_SESSION['username'] = $username;
                    $message = __('success_updated');
                    $message_type = 'success';
                    
                    $stmt_user = $conn->prepare("SELECT * FROM users WHERE id = ?");
                    $stmt_user->bind_param("i", $user_id);
                    $stmt_user->execute();
                    $result_user = $stmt_user->get_result();
                    $user = $result_user->fetch_assoc();
                    $stmt_user->close();
                } else {
                    $message = 'Error: ' . $stmt_update->error;
                    $message_type = 'danger';
                }
                $stmt_update->close();
            }
            $stmt->close();
        }
    }
    
    // ===== UPDATE PASSWORD =====
    elseif ($action == 'update_password') {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $message = __('error_required');
            $message_type = 'danger';
        } elseif (strlen($new_password) < 6) {
            $message = __('password_min');
            $message_type = 'danger';
        } elseif ($new_password !== $confirm_password) {
            $message = 'Passwords do not match!';
            $message_type = 'danger';
        } else {
            $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result_pass = $stmt->get_result();
            $user_pass = $result_pass->fetch_assoc();
            $stmt->close();
            
            if (password_verify($current_password, $user_pass['password'])) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt_update = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt_update->bind_param("si", $hashed_password, $user_id);
                
                if ($stmt_update->execute()) {
                    $message = 'Password updated successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Error: ' . $stmt_update->error;
                    $message_type = 'danger';
                }
                $stmt_update->close();
            } else {
                $message = 'Current password is incorrect!';
                $message_type = 'danger';
            }
        }
    }
    
    // ===== UPLOAD FOTO PROFIL =====
    elseif ($action == 'upload_photo') {
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
            $target_dir = "assets/images/profiles/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            $file_extension = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            if (!in_array($file_extension, $allowed_types)) {
                $message = 'Only JPG, PNG, GIF, and WEBP files are allowed!';
                $message_type = 'danger';
            } else {
                if (!empty($user['profile_image']) && file_exists($target_dir . $user['profile_image'])) {
                    unlink($target_dir . $user['profile_image']);
                }
                
                $new_filename = 'profile_' . $user_id . '_' . time() . '.' . $file_extension;
                $target_file = $target_dir . $new_filename;
                
                $image_info = getimagesize($_FILES['profile_image']['tmp_name']);
                $max_width = 500;
                $max_height = 500;
                
                if ($image_info[0] > $max_width || $image_info[1] > $max_height) {
                    $src = imagecreatefromstring(file_get_contents($_FILES['profile_image']['tmp_name']));
                    $width = $image_info[0];
                    $height = $image_info[1];
                    
                    $ratio = min($max_width / $width, $max_height / $height);
                    $new_width = round($width * $ratio);
                    $new_height = round($height * $ratio);
                    
                    $dst = imagecreatetruecolor($new_width, $new_height);
                    imagecopyresampled($dst, $src, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
                    
                    switch($file_extension) {
                        case 'jpg':
                        case 'jpeg':
                            imagejpeg($dst, $target_file, 85);
                            break;
                        case 'png':
                            imagepng($dst, $target_file, 8);
                            break;
                        case 'gif':
                            imagegif($dst, $target_file);
                            break;
                        case 'webp':
                            imagewebp($dst, $target_file, 85);
                            break;
                    }
                    
                    imagedestroy($src);
                    imagedestroy($dst);
                } else {
                    move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_file);
                }
                
                $stmt = $conn->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
                $stmt->bind_param("si", $new_filename, $user_id);
                
                if ($stmt->execute()) {
                    $message = 'Profile photo updated successfully!';
                    $message_type = 'success';
                    
                    $stmt_user = $conn->prepare("SELECT * FROM users WHERE id = ?");
                    $stmt_user->bind_param("i", $user_id);
                    $stmt_user->execute();
                    $result_user = $stmt_user->get_result();
                    $user = $result_user->fetch_assoc();
                    $stmt_user->close();
                } else {
                    $message = 'Error: ' . $stmt->error;
                    $message_type = 'danger';
                }
                $stmt->close();
            }
        } else {
            $message = 'Please select a file to upload!';
            $message_type = 'danger';
        }
    }
}

// Refresh user data setelah update (kecuali update language sudah redirect)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $message_type == 'success' && isset($_POST['action']) && $_POST['action'] != 'update_language') {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
}

// Set default full_name jika kosong
$display_name = !empty($user['full_name']) ? $user['full_name'] : $user['username'];
$profile_image = !empty($user['profile_image']) && file_exists('assets/images/profiles/' . $user['profile_image']) 
    ? 'assets/images/profiles/' . $user['profile_image'] 
    : 'assets/images/default-avatar.png';
?>

<style>
.profile-page .profile-header {
    background: linear-gradient(135deg, #4a6cf7 0%, #6a3de8 100%);
    border-radius: 16px;
    padding: 24px 20px;
    color: white;
    margin-bottom: 20px;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 16px;
}

.profile-page .avatar-wrapper {
    position: relative;
    flex-shrink: 0;
}

.profile-page .profile-avatar {
    width: 90px;
    height: 90px;
    border-radius: 50%;
    border: 4px solid rgba(255,255,255,0.8);
    object-fit: cover;
    box-shadow: 0 4px 16px rgba(0,0,0,0.2);
    background: #6b7280;
}

.profile-page .upload-btn-wrapper {
    position: absolute;
    bottom: 0;
    right: 0;
}

.profile-page .upload-btn-wrapper .btn-upload {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    border: 2px solid white;
    background: #4a6cf7;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    cursor: pointer;
    transition: all 0.2s ease;
}

.profile-page .upload-btn-wrapper .btn-upload:hover {
    transform: scale(1.1);
    background: #3a5de7;
}

.profile-page .upload-btn-wrapper input[type=file] {
    position: absolute;
    left: 0;
    top: 0;
    opacity: 0;
    width: 100%;
    height: 100%;
    cursor: pointer;
}

.profile-page .user-info {
    flex: 1;
    min-width: 0;
}

.profile-page .user-info .name {
    font-size: 1.2rem;
    font-weight: 700;
    margin: 0;
    word-break: break-word;
}

.profile-page .user-info .username {
    font-size: 0.85rem;
    opacity: 0.8;
}

.profile-page .user-info .badge-list {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-top: 6px;
}

.profile-page .user-info .badge-list .badge {
    font-size: 0.65rem;
    padding: 4px 10px;
    background: rgba(255,255,255,0.2);
    color: white;
    border: 1px solid rgba(255,255,255,0.1);
}

.profile-page .balance-card {
    background: rgba(255,255,255,0.15);
    backdrop-filter: blur(10px);
    border-radius: 12px;
    padding: 12px 18px;
    min-width: 120px;
    border: 1px solid rgba(255,255,255,0.1);
}

.profile-page .balance-card .label {
    font-size: 0.6rem;
    text-transform: uppercase;
    opacity: 0.8;
    letter-spacing: 0.5px;
}

.profile-page .balance-card .value {
    font-size: 1.1rem;
    font-weight: 700;
}

.profile-page .card {
    border: none;
    border-radius: 14px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.05);
    margin-bottom: 16px;
    height: 100%;
}

.profile-page .card-header {
    background: white;
    border-bottom: 2px solid #f5f5f5;
    padding: 14px 18px;
    font-weight: 700;
    font-size: 0.95rem;
    border-radius: 14px 14px 0 0 !important;
}

.profile-page .card-header i {
    margin-right: 8px;
    color: #4a6cf7;
}

.profile-page .card-body {
    padding: 18px;
}

.profile-page .form-control,
.profile-page .form-select {
    border-radius: 10px;
    border: 2px solid #e5e7eb;
    padding: 10px 14px;
    font-size: 0.9rem;
    transition: all 0.2s ease;
}

.profile-page .form-control:focus,
.profile-page .form-select:focus {
    border-color: #4a6cf7;
    box-shadow: 0 0 0 3px rgba(74, 108, 247, 0.1);
}

.profile-page .form-label {
    font-weight: 600;
    font-size: 0.85rem;
    margin-bottom: 4px;
}

.profile-page .btn-submit {
    background: linear-gradient(135deg, #4a6cf7 0%, #6a3de8 100%);
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.9rem;
    transition: all 0.2s ease;
    width: 100%;
}

.profile-page .btn-submit:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 16px rgba(74, 108, 247, 0.35);
    color: white;
}

.profile-page .btn-submit:active {
    transform: scale(0.97);
}

.profile-page .stat-box {
    padding: 12px 14px;
    border-radius: 10px;
    text-align: center;
}

.profile-page .stat-box .stat-label {
    font-size: 0.65rem;
    text-transform: uppercase;
    color: #6b7280;
    font-weight: 600;
    letter-spacing: 0.3px;
}

.profile-page .stat-box .stat-value {
    font-size: 0.95rem;
    font-weight: 700;
    margin-top: 2px;
}

@media (max-width: 575.98px) {
    .profile-page .profile-header {
        padding: 16px;
        flex-direction: column;
        text-align: center;
        gap: 12px;
        border-radius: 12px;
    }
    
    .profile-page .profile-avatar {
        width: 70px;
        height: 70px;
    }
    
    .profile-page .user-info .name {
        font-size: 1rem;
    }
    
    .profile-page .user-info .username {
        font-size: 0.8rem;
    }
    
    .profile-page .user-info .badge-list {
        justify-content: center;
    }
    
    .profile-page .balance-card {
        width: 100%;
        max-width: 200px;
        margin: 0 auto;
        padding: 10px 14px;
    }
    
    .profile-page .balance-card .value {
        font-size: 1rem;
    }
    
    .profile-page .card-body {
        padding: 14px;
    }
    
    .profile-page .card-header {
        padding: 12px 14px;
        font-size: 0.85rem;
    }
    
    .profile-page .stat-box .stat-value {
        font-size: 0.85rem;
    }
    
    .profile-page .stat-box .stat-label {
        font-size: 0.6rem;
    }
    
    .profile-page .form-control,
    .profile-page .form-select {
        font-size: 0.85rem;
        padding: 8px 12px;
    }
    
    .profile-page .btn-submit {
        font-size: 0.85rem;
        padding: 10px 16px;
    }
}

@media (min-width: 576px) and (max-width: 767.98px) {
    .profile-page .profile-header {
        padding: 20px;
        flex-wrap: wrap;
    }
    
    .profile-page .profile-avatar {
        width: 80px;
        height: 80px;
    }
    
    .profile-page .user-info .name {
        font-size: 1.1rem;
    }
    
    .profile-page .balance-card {
        margin-left: auto;
    }
}

@media (min-width: 768px) and (max-width: 991.98px) {
    .profile-page .profile-header {
        padding: 24px;
    }
    
    .profile-page .profile-avatar {
        width: 90px;
        height: 90px;
    }
}

@media (min-width: 992px) {
    .profile-page .profile-header {
        padding: 28px 32px;
    }
    
    .profile-page .profile-avatar {
        width: 110px;
        height: 110px;
    }
    
    .profile-page .user-info .name {
        font-size: 1.4rem;
    }
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(15px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.profile-page .fade-in {
    animation: fadeInUp 0.4s ease forwards;
}

.profile-page .fade-in:nth-child(2) { animation-delay: 0.05s; }
.profile-page .fade-in:nth-child(3) { animation-delay: 0.1s; }
.profile-page .fade-in:nth-child(4) { animation-delay: 0.15s; }
</style>

<div class="profile-page">
    <!-- ===== HEADER PROFIL ===== -->
    <div class="profile-header fade-in">
        <div class="avatar-wrapper">
            <img src="<?php echo $profile_image; ?>" 
                 alt="Profile" 
                 class="profile-avatar"
                 onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><rect width=%22100%22 height=%22100%22 fill=%22%236b7280%22/><text x=%2250%22 y=%2260%22 text-anchor=%22middle%22 font-size=%2240%22 fill=%22white%22>👤</text></svg>'">
            
            <form method="POST" enctype="multipart/form-data" class="upload-btn-wrapper">
                <input type="hidden" name="action" value="upload_photo">
                <div class="btn-upload" onclick="this.parentElement.querySelector('input[type=file]').click()">
                    <i class="bi bi-camera"></i>
                </div>
                <input type="file" name="profile_image" accept="image/*" onchange="this.form.submit()" style="display:none;">
            </form>
        </div>
        
        <div class="user-info">
            <div class="name"><?php echo htmlspecialchars($display_name); ?></div>
            <div class="username">@<?php echo htmlspecialchars($user['username']); ?></div>
            <div class="badge-list">
                <span class="badge">
                    <i class="bi bi-envelope"></i> <?php echo htmlspecialchars($user['email']); ?>
                </span>
                <span class="badge">
                    <i class="bi bi-globe"></i> <?php echo strtoupper($user['prefered_language'] ?? 'en'); ?>
                </span>
                <span class="badge">
                    <i class="bi bi-currency-dollar"></i> <?php echo getCurrencyCode($lang); ?>
                </span>
            </div>
        </div>
        
        <div class="balance-card">
            <div class="label"><?php echo __('total_balance'); ?></div>
            <div class="value"><?php echo formatCurrency($saldo); ?></div>
        </div>
    </div>

    <!-- ===== PESAN ===== -->
    <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show fade-in">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- ===== CONTENT ===== -->
    <div class="row g-3">
        <!-- Edit Profile -->
        <div class="col-12 col-md-6 fade-in">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-person"></i> <?php echo __('edit_profile'); ?>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="mb-3">
                            <label class="form-label"><?php echo __('full_name'); ?></label>
                            <input type="text" name="full_name" class="form-control" 
                                   value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" 
                                   placeholder="<?php echo __('full_name'); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label"><?php echo __('username'); ?> <span class="text-danger">*</span></label>
                            <input type="text" name="username" class="form-control" 
                                   value="<?php echo htmlspecialchars($user['username']); ?>" 
                                   required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label"><?php echo __('email'); ?> <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" 
                                   value="<?php echo htmlspecialchars($user['email']); ?>" 
                                   required>
                        </div>
                        
                        <button type="submit" class="btn-submit">
                            <i class="bi bi-save"></i> <?php echo __('save_changes'); ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Change Password -->
        <div class="col-12 col-md-6 fade-in" style="animation-delay: 0.05s;">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-lock"></i> <?php echo __('change_password'); ?>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="update_password">
                        
                        <div class="mb-3">
                            <label class="form-label"><?php echo __('current_password'); ?> <span class="text-danger">*</span></label>
                            <input type="password" name="current_password" class="form-control" 
                                   placeholder="<?php echo __('current_password'); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label"><?php echo __('new_password'); ?> <span class="text-danger">*</span></label>
                            <input type="password" name="new_password" class="form-control" 
                                   placeholder="<?php echo __('new_password'); ?>" required minlength="6">
                            <small class="text-muted"><?php echo __('password_min'); ?></small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label"><?php echo __('confirm_password'); ?> <span class="text-danger">*</span></label>
                            <input type="password" name="confirm_password" class="form-control" 
                                   placeholder="<?php echo __('confirm_password'); ?>" required>
                        </div>
                        
                        <button type="submit" class="btn-submit">
                            <i class="bi bi-key"></i> <?php echo __('update_password'); ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Language Settings -->
        <div class="col-12 col-md-6 fade-in" style="animation-delay: 0.1s;">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-globe2"></i> <?php echo __('language_settings'); ?>
                </div>
                <div class="card-body">
                    <form method="POST" id="languageForm">
                        <input type="hidden" name="action" value="update_language">
                        
                        <div class="mb-3">
                            <label class="form-label"><?php echo __('select_language'); ?></label>
                            <select name="language" id="languageSelect" class="form-select">
                                <option value="en" <?php echo ($user['prefered_language'] ?? 'en') == 'en' ? 'selected' : ''; ?>>🇬🇧 English (GBP)</option>
                                <option value="id" <?php echo ($user['prefered_language'] ?? 'en') == 'id' ? 'selected' : ''; ?>>🇮🇩 Indonesia (IDR)</option>
                                <option value="vi" <?php echo ($user['prefered_language'] ?? 'en') == 'vi' ? 'selected' : ''; ?>>🇻🇳 Tiếng Việt (VND)</option>
                                <option value="ja" <?php echo ($user['prefered_language'] ?? 'en') == 'ja' ? 'selected' : ''; ?>>🇯🇵 日本語 (JPY)</option>
                            </select>
                            <small class="text-muted">Currency will change based on language</small>
                        </div>
                        
                        
                    </form>
                </div>
            </div>
        </div>

        <!-- Statistics -->
        <div class="col-12 col-md-6 fade-in" style="animation-delay: 0.15s;">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-graph-up"></i> <?php echo __('account_stats'); ?>
                </div>
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="stat-box bg-success bg-opacity-10">
                                <div class="stat-label"><?php echo __('total_income'); ?></div>
                                <div class="stat-value text-success"><?php echo formatCurrency($total_pemasukan); ?></div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stat-box bg-danger bg-opacity-10">
                                <div class="stat-label"><?php echo __('total_expense'); ?></div>
                                <div class="stat-value text-danger"><?php echo formatCurrency($total_pengeluaran); ?></div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stat-box bg-warning bg-opacity-10">
                                <div class="stat-label"><?php echo __('total_savings'); ?></div>
                                <div class="stat-value text-warning"><?php echo formatCurrency($total_tabungan); ?></div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stat-box bg-info bg-opacity-10">
                                <div class="stat-label"><?php echo __('member_since'); ?></div>
                                <div class="stat-value text-info"><?php echo date('d M Y', strtotime($user['created_at'])); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Tambahkan auto-submit ketika bahasa berubah (opsional)
document.addEventListener('DOMContentLoaded', function() {
    const languageSelect = document.getElementById('languageSelect');
    if (languageSelect) {
        languageSelect.addEventListener('change', function() {
            // Auto submit form ketika bahasa berubah
            document.getElementById('languageForm').submit();
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>