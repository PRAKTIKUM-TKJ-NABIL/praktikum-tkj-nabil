<?php
// includes/header.php
require_once __DIR__ . '/lang.php';
require_once __DIR__ . '/functions.php';

// ===== LOAD LANGUAGE DARI SESSION =====
$lang = isset($_SESSION['language']) ? $_SESSION['language'] : 'en';
$lang_data = loadLanguage($lang);

// ===== SET CURRENCY =====
if (!isset($_SESSION['currency_symbol'])) {
    $_SESSION['currency_symbol'] = getCurrencySymbol($lang);
}
if (!isset($_SESSION['currency_code'])) {
    $_SESSION['currency_code'] = getCurrencyCode($lang);
}

// ===== FUNGSI __() =====
if (!function_exists('__')) {
    function __($key) {
        global $lang_data;
        return isset($lang_data[$key]) ? $lang_data[$key] : $key;
    }
}

// Set page title
$page_title = __('app_name');
$current_page = basename($_SERVER['PHP_SELF']);
switch($current_page) {
    case 'index.php': $page_title = __('app_name') . ' - Manage Your Money'; break;
    case 'dashboard.php': $page_title = __('dashboard') . ' - ' . __('app_name'); break;
    case 'tambah.php': $page_title = __('add') . ' - ' . __('app_name'); break;
    case 'edit.php': $page_title = __('edit') . ' - ' . __('app_name'); break;
    case 'laporan.php': $page_title = __('report') . ' - ' . __('app_name'); break;
    case 'kalkulator.php': $page_title = __('calculator') . ' - ' . __('app_name'); break;
    case 'target.php': $page_title = __('savings') . ' - ' . __('app_name'); break;
    case 'profile.php': $page_title = __('profile') . ' - ' . __('app_name'); break;
    case 'login.php': $page_title = __('login') . ' - ' . __('app_name'); break;
    case 'register.php': $page_title = __('register') . ' - ' . __('app_name'); break;
    case 'select_language.php': $page_title = __('select_language') . ' - ' . __('app_name'); break;
    default: $page_title = __('app_name') . ' - Manage Your Money';
}
?>

<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    
    <title><?php echo $page_title; ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="assets/images/logo4.png">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/images/logo4.png">
    <link rel="apple-touch-icon" sizes="180x180" href="assets/images/logo4.png">
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flag-icon-css@3.5.0/css/flag-icon.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php if (isset($_SESSION['user_id'])): ?>
    <!-- Top Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top" id="mainNavbar">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <img src="assets/images/logo4.png" 
                     alt="<?php echo __('app_name'); ?>" 
                     height="30" 
                     class="d-inline-block align-top me-2"
                     onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>💰</text></svg>'">
                <span id="navbarBrandText"><?php echo __('app_name'); ?></span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                            <i class="bi bi-house-fill"></i> <span class="nav-text" data-key="dashboard"><?php echo __('dashboard'); ?></span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'tambah.php' ? 'active' : ''; ?>" href="tambah.php">
                            <i class="bi bi-plus-circle-fill"></i> <span class="nav-text" data-key="add"><?php echo __('add'); ?></span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'target.php' ? 'active' : ''; ?>" href="target.php">
                            <i class="bi bi-piggy-bank-fill"></i> <span class="nav-text" data-key="savings"><?php echo __('savings'); ?></span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'laporan.php' ? 'active' : ''; ?>" href="laporan.php">
                            <i class="bi bi-file-earmark-text-fill"></i> <span class="nav-text" data-key="report"><?php echo __('report'); ?></span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'kalkulator.php' ? 'active' : ''; ?>" href="kalkulator.php">
                            <i class="bi bi-calculator-fill"></i> <span class="nav-text" data-key="calculator"><?php echo __('calculator'); ?></span>
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <?php 
                            if (isset($_SESSION['user_id'])) {
                                global $conn;
                                $stmt_avatar = $conn->prepare("SELECT profile_image FROM users WHERE id = ?");
                                $stmt_avatar->bind_param("i", $_SESSION['user_id']);
                                $stmt_avatar->execute();
                                $result_avatar = $stmt_avatar->get_result();
                                if ($row_avatar = $result_avatar->fetch_assoc()) {
                                    if (!empty($row_avatar['profile_image']) && file_exists("assets/images/profiles/" . $row_avatar['profile_image'])) {
                                        echo '<img src="assets/images/profiles/' . $row_avatar['profile_image'] . '" alt="Profile" class="rounded-circle" style="width: 28px; height: 28px; object-fit: cover; margin-right: 6px;">';
                                    } else {
                                        echo '<i class="bi bi-person-circle"></i> ';
                                    }
                                }
                                $stmt_avatar->close();
                            }
                            ?>
                            <?php echo $_SESSION['username']; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person"></i> <span data-key="profile"><?php echo __('profile'); ?></span></a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#logoutModal">
                                    <i class="bi bi-box-arrow-right text-danger"></i> <span class="text-danger" data-key="logout"><?php echo __('logout'); ?></span>
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Bottom Navigation (Mobile) -->
    <nav class="mobile-bottom-nav" id="mobileBottomNav">
        <a href="dashboard.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
            <i class="bi bi-house-fill"></i>
            <span data-key="dashboard"><?php echo __('dashboard'); ?></span>
        </a>
        <a href="tambah.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'tambah.php' ? 'active' : ''; ?>">
            <i class="bi bi-plus-circle-fill"></i>
            <span data-key="add"><?php echo __('add'); ?></span>
        </a>
        <a href="target.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'target.php' ? 'active' : ''; ?>">
            <i class="bi bi-piggy-bank-fill"></i>
            <span data-key="savings"><?php echo __('savings'); ?></span>
        </a>
        <a href="laporan.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'laporan.php' ? 'active' : ''; ?>">
            <i class="bi bi-file-earmark-text-fill"></i>
            <span data-key="report"><?php echo __('report'); ?></span>
        </a>
        <a href="kalkulator.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'kalkulator.php' ? 'active' : ''; ?>">
            <i class="bi bi-calculator-fill"></i>
            <span data-key="calculator"><?php echo __('calculator'); ?></span>
        </a>
    </nav>

    <!-- ===== MODAL LOGOUT ===== -->
    <div class="modal fade" id="logoutModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-box-arrow-right text-danger"></i> <?php echo __('logout_confirm_title'); ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center py-3">
                        <i class="bi bi-question-circle display-1 text-warning"></i>
                        <p class="mt-3 fs-5"><?php echo __('logout_confirm_message'); ?></p>
                        <p class="text-muted small"><?php echo __('logout_confirm_submessage'); ?></p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> <?php echo __('cancel'); ?>
                    </button>
                    <a href="logout.php" class="btn btn-danger">
                        <i class="bi bi-box-arrow-right"></i> <?php echo __('logout'); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Main Content -->
    <div class="container mt-4" style="<?php echo isset($_SESSION['user_id']) ? 'padding-top: 60px;' : ''; ?>">