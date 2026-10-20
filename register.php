<?php
require_once 'config/database.php';

// If already logged in, redirect to language selection
if (isset($_SESSION['user_id'])) {
    header('Location: select_language.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validations
    if (empty($username) || empty($email) || empty($password)) {
        $error = 'All fields are required!';
    } elseif (strlen($username) < 3) {
        $error = 'Username must be at least 3 characters!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address!';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters!';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match!';
    } else {
        // Check if username or email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = 'Username or email already exists!';
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // ===== PERBAIKAN: Insert user dengan prefered_language NULL =====
            $stmt = $conn->prepare("INSERT INTO users (username, email, password, prefered_language) VALUES (?, ?, ?, NULL)");
            $stmt->bind_param("sss", $username, $email, $hashed_password);
            
            if ($stmt->execute()) {
                $success = 'Registration successful! Please <a href="login.php">login</a>.';
            } else {
                $error = 'Error: ' . $stmt->error;
            }
        }
        $stmt->close();
    }
}

include 'includes/header.php';
?>

<style>
.register-page {
    min-height: calc(100vh - 100px);
    display: flex;
    align-items: center;
    padding: 30px 0;
}

.register-card {
    border: none;
    border-radius: 16px;
    box-shadow: 0 4px 24px rgba(0,0,0,0.06);
    overflow: hidden;
}

.register-card .card-header-custom {
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    padding: 24px 20px;
    text-align: center;
    border: none;
}

.register-card .card-header-custom img {
    max-height: 45px;
    margin-bottom: 8px;
}

.register-card .card-header-custom h4 {
    color: white;
    margin: 0;
    font-weight: 700;
    font-size: 1.1rem;
}

.register-card .card-body {
    padding: 28px 24px;
}

.register-card .form-control {
    border-radius: 10px;
    border: 2px solid #e5e7eb;
    padding: 10px 14px;
    font-size: 0.95rem;
    transition: all 0.2s ease;
}

.register-card .form-control:focus {
    border-color: #22c55e;
    box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.1);
}

.register-card .input-group-text {
    background: #f8fafc;
    border: 2px solid #e5e7eb;
    border-right: none;
    border-radius: 10px 0 0 10px;
    color: #9ca3af;
}

.register-card .input-group .form-control {
    border-radius: 0 10px 10px 0;
}

.register-card .btn-register {
    background: #22c55e;
    color: white;
    border: none;
    padding: 12px;
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.95rem;
    transition: all 0.2s ease;
    width: 100%;
}

.register-card .btn-register:hover {
    background: #16a34a;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(34, 197, 94, 0.3);
}

.register-card .btn-register:active {
    transform: scale(0.97);
}

.register-card .divider {
    display: flex;
    align-items: center;
    gap: 16px;
    margin: 20px 0;
    color: #d1d5db;
}

.register-card .divider::before,
.register-card .divider::after {
    content: '';
    flex: 1;
    height: 1px;
    background: #e5e7eb;
}

.register-card .btn-back {
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

.register-card .btn-back:hover {
    background: #f8fafc;
    border-color: #d1d5db;
    color: #1a1a2e;
    text-decoration: none;
}

.register-card .btn-back i {
    font-size: 1.1rem;
}

.register-card .footer-links {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 16px;
}

.register-card .footer-links a {
    color: #6b7280;
    text-decoration: none;
    font-size: 0.85rem;
    transition: all 0.2s ease;
}

.register-card .footer-links a:hover {
    color: #22c55e;
}

.password-strength {
    margin-top: 6px;
    height: 4px;
    border-radius: 4px;
    background: #e5e7eb;
    overflow: hidden;
    transition: all 0.3s ease;
}

.password-strength .bar {
    height: 100%;
    width: 0%;
    border-radius: 4px;
    transition: all 0.3s ease;
}

@media (max-width: 575.98px) {
    .register-page {
        padding: 20px 0;
        min-height: calc(100vh - 80px);
    }
    
    .register-card .card-body {
        padding: 20px 16px;
    }
    
    .register-card .card-header-custom {
        padding: 20px 16px;
    }
    
    .register-card .card-header-custom img {
        max-height: 36px;
    }
    
    .register-card .card-header-custom h4 {
        font-size: 1rem;
    }
    
    .register-card .footer-links {
        flex-direction: column;
        align-items: stretch;
        text-align: center;
    }
    
    .register-card .btn-back {
        justify-content: center;
    }
}

@media (min-width: 576px) and (max-width: 767px) {
    .register-card .card-body {
        padding: 24px 20px;
    }
}
</style>

<div class="register-page">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="register-card">
                    <div class="card-header-custom">
                        <img src="assets/images/logo-white.png" 
                             alt="Logo" 
                             onerror="this.style.display='none'">
                        <h4><i class="bi bi-person-plus"></i> Create Account</h4>
                    </div>
                    
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show">
                                <?php echo $error; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success alert-dismissible fade show">
                                <?php echo $success; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" id="registerForm">
                            <div class="mb-3">
                                <label class="form-label">Username</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                                    <input type="text" name="username" class="form-control" 
                                           placeholder="Enter username" required minlength="3">
                                </div>
                                <small class="text-muted">Minimum 3 characters</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                    <input type="email" name="email" class="form-control" 
                                           placeholder="Enter email" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                    <input type="password" name="password" id="password" class="form-control" 
                                           placeholder="Minimum 6 characters" required minlength="6">
                                </div>
                                <div class="password-strength">
                                    <div class="bar" id="strengthBar"></div>
                                </div>
                                <small class="text-muted">Minimum 6 characters</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Confirm Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                                    <input type="password" name="confirm_password" class="form-control" 
                                           placeholder="Re-enter password" required>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn-register">
                                <i class="bi bi-person-plus"></i> Sign Up
                            </button>
                        </form>
                        
                        <div class="divider">or</div>
                        
                        <div class="footer-links">
                            <a href="index.php" class="btn-back">
                                <i class="bi bi-arrow-left"></i> Back to Home
                            </a>
                            <a href="login.php">
                                Already have an account? <strong>Sign In</strong>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('password').addEventListener('input', function() {
    const password = this.value;
    const bar = document.getElementById('strengthBar');
    let strength = 0;
    
    if (password.length >= 6) strength += 20;
    if (password.length >= 10) strength += 20;
    if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength += 20;
    if (/\d/.test(password)) strength += 20;
    if (/[^a-zA-Z0-9]/.test(password)) strength += 20;
    
    bar.style.width = strength + '%';
    
    if (strength < 40) {
        bar.style.background = '#ef4444';
    } else if (strength < 70) {
        bar.style.background = '#f59e0b';
    } else {
        bar.style.background = '#22c55e';
    }
});
</script>

<?php include 'includes/footer.php'; ?>