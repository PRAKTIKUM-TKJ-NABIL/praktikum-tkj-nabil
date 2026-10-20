<?php
require_once 'config/database.php';

// If already logged in, check if they have preferred language
if (isset($_SESSION['user_id'])) {
    // Cek apakah user sudah punya prefered language
    $stmt = $conn->prepare("SELECT prefered_language FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    
    // ===== PERBAIKAN: Cek NULL atau empty =====
    if ($user['prefered_language'] === null || empty($user['prefered_language'])) {
        // Jika NULL atau empty, redirect ke select language
        header('Location: select_language.php');
        exit;
    } else {
        // Jika sudah punya, set session dan redirect ke dashboard
        $_SESSION['language'] = $user['prefered_language'];
        header('Location: dashboard.php');
        exit;
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = 'Username and password are required!';
    } else {
        $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                
                // Cek apakah user sudah punya prefered language
                $stmt_lang = $conn->prepare("SELECT prefered_language FROM users WHERE id = ?");
                $stmt_lang->bind_param("i", $user['id']);
                $stmt_lang->execute();
                $result_lang = $stmt_lang->get_result();
                $lang_data = $result_lang->fetch_assoc();
                $stmt_lang->close();
                
                // ===== PERBAIKAN: Cek NULL atau empty =====
                if ($lang_data['prefered_language'] !== null && !empty($lang_data['prefered_language'])) {
                    // Jika sudah punya bahasa, set session dan langsung ke dashboard
                    $_SESSION['language'] = $lang_data['prefered_language'];
                    header('Location: dashboard.php');
                    exit;
                }
                
                // Jika NULL atau empty, redirect ke halaman pilih bahasa
                header('Location: select_language.php');
                exit;
            } else {
                $error = 'Invalid password!';
            }
        } else {
            $error = 'Username or email not found!';
        }
        $stmt->close();
    }
}

include 'includes/header.php';
?>

<style>
.login-page {
    min-height: calc(100vh - 100px);
    display: flex;
    align-items: center;
    padding: 30px 0;
}

.login-card {
    border: none;
    border-radius: 16px;
    box-shadow: 0 4px 24px rgba(0,0,0,0.06);
    overflow: hidden;
}

.login-card .card-header-custom {
    background: linear-gradient(135deg, #4a6cf7 0%, #6a3de8 100%);
    padding: 24px 20px;
    text-align: center;
    border: none;
}

.login-card .card-header-custom img {
    max-height: 45px;
    margin-bottom: 8px;
}

.login-card .card-header-custom h4 {
    color: white;
    margin: 0;
    font-weight: 700;
    font-size: 1.1rem;
}

.login-card .card-body {
    padding: 28px 24px;
}

.login-card .form-control {
    border-radius: 10px;
    border: 2px solid #e5e7eb;
    padding: 10px 14px;
    font-size: 0.95rem;
    transition: all 0.2s ease;
}

.login-card .form-control:focus {
    border-color: #4a6cf7;
    box-shadow: 0 0 0 3px rgba(74, 108, 247, 0.1);
}

.login-card .input-group-text {
    background: #f8fafc;
    border: 2px solid #e5e7eb;
    border-right: none;
    border-radius: 10px 0 0 10px;
    color: #9ca3af;
}

.login-card .input-group .form-control {
    border-radius: 0 10px 10px 0;
}

.login-card .btn-login {
    background: #4a6cf7;
    color: white;
    border: none;
    padding: 12px;
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.95rem;
    transition: all 0.2s ease;
    width: 100%;
}

.login-card .btn-login:hover {
    background: #3a5de7;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(74, 108, 247, 0.3);
}

.login-card .btn-login:active {
    transform: scale(0.97);
}

.login-card .divider {
    display: flex;
    align-items: center;
    gap: 16px;
    margin: 20px 0;
    color: #d1d5db;
}

.login-card .divider::before,
.login-card .divider::after {
    content: '';
    flex: 1;
    height: 1px;
    background: #e5e7eb;
}

.login-card .btn-back {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: #6b7280;
    text-decoration: none;
    font-size: 0.9rem;
    font-weight: 500;
    transition: all 0.2s ease;
    padding: 8px 16px;
    border-radius: 8px;
    border: 2px solid #e5e7eb;
    background: transparent;
}

.login-card .btn-back:hover {
    background: #f8fafc;
    border-color: #d1d5db;
    color: #1a1a2e;
    text-decoration: none;
}

.login-card .btn-back i {
    font-size: 1.1rem;
}

.login-card .footer-links {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 16px;
}

.login-card .footer-links a {
    color: #6b7280;
    text-decoration: none;
    font-size: 0.85rem;
    transition: all 0.2s ease;
}

.login-card .footer-links a:hover {
    color: #4a6cf7;
}

@media (max-width: 575.98px) {
    .login-page {
        padding: 20px 0;
        min-height: calc(100vh - 80px);
    }
    
    .login-card .card-body {
        padding: 20px 16px;
    }
    
    .login-card .card-header-custom {
        padding: 20px 16px;
    }
    
    .login-card .card-header-custom img {
        max-height: 36px;
    }
    
    .login-card .card-header-custom h4 {
        font-size: 1rem;
    }
    
    .login-card .footer-links {
        flex-direction: column;
        align-items: stretch;
        text-align: center;
    }
    
    .login-card .btn-back {
        justify-content: center;
    }
}

@media (min-width: 576px) and (max-width: 767px) {
    .login-card .card-body {
        padding: 24px 20px;
    }
}
</style>

<div class="login-page">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="login-card">
                    <div class="card-header-custom">
                        <img src="assets/images/logo-white.png" 
                             alt="Logo" 
                             onerror="this.style.display='none'">
                        <h4><i class="bi bi-box-arrow-in-right"></i> Sign In</h4>
                    </div>
                    
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show">
                                <?php echo $error; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Username or Email</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                                    <input type="text" name="username" class="form-control" 
                                           placeholder="Enter username or email" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                    <input type="password" name="password" class="form-control" 
                                           placeholder="Enter password" required>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn-login">
                                <i class="bi bi-box-arrow-in-right"></i> Sign In
                            </button>
                        </form>
                        
                        <div class="divider">or</div>
                        
                        <div class="footer-links">
                            <a href="index.php" class="btn-back">
                                <i class="bi bi-arrow-left"></i> Back to Home
                            </a>
                            <a href="register.php">
                                Don't have an account? <strong>Sign Up</strong>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>