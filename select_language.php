<?php
require_once 'config/database.php';

// Jika belum login, redirect ke login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Cek di database apakah user sudah punya prefered language
$stmt = $conn->prepare("SELECT prefered_language FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    if (!empty($row['prefered_language'])) {
        $_SESSION['language'] = $row['prefered_language'];
        header('Location: dashboard.php');
        exit;
    }
}
$stmt->close();

$error = '';

// Jika form disubmit
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['language'])) {
    $language = $_POST['language'];
    $available_langs = ['en', 'id', 'vi', 'ja'];
    
    if (in_array($language, $available_langs)) {
        $_SESSION['language'] = $language;
        
        $stmt = $conn->prepare("UPDATE users SET prefered_language = ? WHERE id = ?");
        $stmt->bind_param("si", $language, $_SESSION['user_id']);
        $stmt->execute();
        $stmt->close();
        
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Invalid language selected!';
    }
}

// ===== LOAD LANGUAGE UNTUK HALAMAN INI =====
require_once 'includes/lang.php';
require_once 'includes/functions.php';

// Load language
$lang = $_SESSION['language'] ?? 'en';
$lang_data = loadLanguage($lang);

// ===== INCLUDE HEADER TANPA NAVBAR =====
// Kita buat header sederhana tanpa navbar
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title><?php echo __('select_language'); ?> - <?php echo __('app_name'); ?></title>
    
    <link rel="icon" type="image/png" sizes="32x32" href="assets/images/logo4.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body style="background: linear-gradient(135deg, #f0f4ff 0%, #e8edf5 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px;">

<style>
/* ============================================================
   SELECT LANGUAGE PAGE - FULLY RESPONSIVE
   ============================================================ */

.language-page {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.language-card {
    background: white;
    border-radius: 20px;
    padding: 32px 28px;
    max-width: 520px;
    width: 100%;
    box-shadow: 0 20px 60px rgba(0,0,0,0.08);
    text-align: center;
}

.language-card .logo {
    margin-bottom: 16px;
}

.language-card .logo img {
    max-height: 55px;
    width: auto;
}

.language-card h3 {
    font-weight: 700;
    color: #1a1a2e;
    margin-bottom: 4px;
    font-size: 1.3rem;
}

.language-card .subtitle {
    color: #6b7280;
    font-size: 0.9rem;
    margin-bottom: 24px;
}

.language-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
    margin-bottom: 20px;
}

.language-option {
    padding: 14px 10px;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.25s ease;
    background: white;
    text-align: center;
}

.language-option:hover {
    border-color: #4a6cf7;
    background: #f8faff;
    transform: translateY(-2px);
}

.language-option.selected {
    border-color: #4a6cf7;
    background: #f0f4ff;
    box-shadow: 0 0 0 4px rgba(74, 108, 247, 0.1);
}

.language-option .flag {
    font-size: 1.8rem;
    display: block;
    margin-bottom: 2px;
}

.language-option .lang-code {
    font-size: 0.65rem;
    font-weight: 700;
    color: #6b7280;
    text-transform: uppercase;
    display: block;
    letter-spacing: 0.5px;
}

.language-option .lang-name {
    font-weight: 600;
    font-size: 0.85rem;
    color: #1a1a2e;
    display: block;
}

.language-option .lang-native {
    font-size: 0.7rem;
    color: #9ca3af;
    display: block;
}

.btn-continue {
    width: 100%;
    padding: 14px;
    background: linear-gradient(135deg, #4a6cf7 0%, #6a3de8 100%);
    color: white;
    border: none;
    border-radius: 12px;
    font-weight: 700;
    font-size: 1rem;
    transition: all 0.25s ease;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.btn-continue:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(74, 108, 247, 0.35);
}

.btn-continue:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

.btn-continue:active {
    transform: scale(0.97);
}

.btn-continue i {
    font-size: 1.1rem;
}

/* ============================================================
   RESPONSIVE
   ============================================================ */

/* Mobile Kecil (0 - 375px) */
@media (max-width: 375px) {
    .language-card {
        padding: 20px 14px;
        border-radius: 16px;
    }
    
    .language-card .logo img {
        max-height: 40px;
    }
    
    .language-card h3 {
        font-size: 1.1rem;
    }
    
    .language-card .subtitle {
        font-size: 0.8rem;
        margin-bottom: 16px;
    }
    
    .language-grid {
        gap: 8px;
    }
    
    .language-option {
        padding: 10px 6px;
        border-radius: 10px;
    }
    
    .language-option .flag {
        font-size: 1.4rem;
    }
    
    .language-option .lang-code {
        font-size: 0.55rem;
    }
    
    .language-option .lang-name {
        font-size: 0.7rem;
    }
    
    .language-option .lang-native {
        font-size: 0.6rem;
    }
    
    .btn-continue {
        padding: 12px;
        font-size: 0.85rem;
        border-radius: 10px;
    }
}

/* Mobile (376px - 575px) */
@media (min-width: 376px) and (max-width: 575.98px) {
    .language-card {
        padding: 24px 18px;
        border-radius: 18px;
    }
    
    .language-card .logo img {
        max-height: 45px;
    }
    
    .language-card h3 {
        font-size: 1.2rem;
    }
    
    .language-card .subtitle {
        font-size: 0.85rem;
        margin-bottom: 18px;
    }
    
    .language-grid {
        gap: 10px;
    }
    
    .language-option {
        padding: 12px 8px;
        border-radius: 12px;
    }
    
    .language-option .flag {
        font-size: 1.6rem;
    }
    
    .language-option .lang-code {
        font-size: 0.6rem;
    }
    
    .language-option .lang-name {
        font-size: 0.75rem;
    }
    
    .language-option .lang-native {
        font-size: 0.65rem;
    }
    
    .btn-continue {
        padding: 13px;
        font-size: 0.9rem;
    }
}

/* Tablet (576px - 768px) */
@media (min-width: 576px) and (max-width: 767.98px) {
    .language-card {
        padding: 28px 24px;
        max-width: 480px;
    }
    
    .language-card .logo img {
        max-height: 50px;
    }
    
    .language-grid {
        gap: 12px;
    }
    
    .language-option {
        padding: 14px 10px;
    }
}

/* Desktop (768px+) */
@media (min-width: 768px) {
    .language-card {
        padding: 40px 36px;
        max-width: 550px;
    }
    
    .language-card .logo img {
        max-height: 60px;
    }
    
    .language-card h3 {
        font-size: 1.4rem;
    }
    
    .language-grid {
        gap: 14px;
    }
    
    .language-option {
        padding: 18px 14px;
    }
    
    .language-option .flag {
        font-size: 2.2rem;
    }
    
    .language-option:hover {
        transform: translateY(-4px);
    }
}

/* ============================================================
   ANIMATIONS
   ============================================================ */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.language-card {
    animation: fadeInUp 0.5s ease forwards;
}

/* ============================================================
   TOUCH FEEDBACK
   ============================================================ */
.language-option,
.btn-continue {
    -webkit-tap-highlight-color: transparent;
    touch-action: manipulation;
}

.language-option:active {
    transform: scale(0.95);
}

.btn-continue:active {
    transform: scale(0.95);
}
</style>

<div class="language-page">
    <div class="language-card">
        <!-- Logo -->
        <div class="logo">
            <img src="assets/images/mon2.png" alt="Monexa" onerror="this.style.display='none'">
        </div>
        
        <h3><?php echo __('select_language'); ?></h3>
        <p class="subtitle"><?php echo __('choose_language'); ?></p>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <form method="POST" id="languageForm">
            <div class="language-grid">
                <!-- English -->
                <div class="language-option selected" data-lang="en" onclick="selectLanguage('en')">
                    <span class="flag">🇬🇧</span>
                    <span class="lang-code">EN</span>
                    <span class="lang-name">English</span>
                    <span class="lang-native">English</span>
                </div>
                
                <!-- Indonesian -->
                <div class="language-option" data-lang="id" onclick="selectLanguage('id')">
                    <span class="flag">🇮🇩</span>
                    <span class="lang-code">ID</span>
                    <span class="lang-name">Indonesian</span>
                    <span class="lang-native">Bahasa Indonesia</span>
                </div>
                
                <!-- Vietnamese -->
                <div class="language-option" data-lang="vi" onclick="selectLanguage('vi')">
                    <span class="flag">🇻🇳</span>
                    <span class="lang-code">VN</span>
                    <span class="lang-name">Vietnamese</span>
                    <span class="lang-native">Tiếng Việt</span>
                </div>
                
                <!-- Japanese -->
                <div class="language-option" data-lang="ja" onclick="selectLanguage('ja')">
                    <span class="flag">🇯🇵</span>
                    <span class="lang-code">JP</span>
                    <span class="lang-name">Japanese</span>
                    <span class="lang-native">日本語</span>
                </div>
            </div>
            
            <input type="hidden" name="language" id="selectedLanguage" value="en">
            
            <button type="submit" class="btn-continue" id="continueBtn">
                <i class="bi bi-arrow-right"></i> <?php echo __('continue'); ?>
            </button>
        </form>
    </div>
</div>

<script>
function selectLanguage(lang) {
    // Update hidden input
    document.getElementById('selectedLanguage').value = lang;
    
    // Update visual
    document.querySelectorAll('.language-option').forEach(el => {
        el.classList.remove('selected');
    });
    document.querySelector(`.language-option[data-lang="${lang}"]`).classList.add('selected');
}

// Submit on Enter key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        document.getElementById('languageForm').submit();
    }
});

// Touch feedback for mobile
document.querySelectorAll('.language-option').forEach(el => {
    el.addEventListener('touchstart', function() {
        this.style.transform = 'scale(0.95)';
    });
    el.addEventListener('touchend', function() {
        this.style.transform = '';
    });
});
</script>

</body>
</html>