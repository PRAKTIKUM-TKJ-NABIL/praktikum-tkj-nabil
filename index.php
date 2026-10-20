<?php
// index.php - Landing Page (Full English)
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';

// Jika sudah login, redirect ke select language
if (isset($_SESSION['user_id'])) {
    header('Location: select_language.php');
    exit;
}

// Load language English for landing page
$_SESSION['language'] = 'en';
require_once 'includes/lang.php';
require_once 'includes/functions.php';
$lang_data = loadLanguage('en');

// ===== CEK APAKAH FUNGSI __() TERSEDIA =====
if (!function_exists('__')) {
    function __($key) {
        global $lang_data;
        return isset($lang_data[$key]) ? $lang_data[$key] : $key;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>Monexa - Manage Your Money</title>
    
    <link rel="icon" type="image/png" sizes="32x32" href="assets/images/logo4.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }
    
    body {
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        background: #fafafa;
        color: #1a1a2e;
        min-height: 100vh;
    }
    
    .landing-page {
        min-height: 100vh;
        display: flex;
        align-items: center;
        padding: 40px 0;
    }
    
    .landing-page .container {
        max-width: 1100px;
        margin: 0 auto;
        padding: 0 20px;
    }
    
    .hero-section {
        padding: 20px 0;
    }
    
    .hero-section .brand {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 28px;
    }
    
    .hero-section .brand img {
        height: 42px;
        width: auto;
    }
    
    .hero-section .brand h1 {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1a1a2e;
        margin: 0;
        letter-spacing: -0.5px;
    }
    
    .hero-section .brand h1 span {
        color: #4a6cf7;
    }
    
    .hero-section .tagline {
        font-size: 2.2rem;
        font-weight: 700;
        line-height: 1.2;
        margin-bottom: 16px;
        color: #1a1a2e;
        letter-spacing: -0.5px;
    }
    
    .hero-section .tagline .highlight {
        color: #4a6cf7;
        position: relative;
    }
    
    .hero-section .tagline .highlight::after {
        content: '';
        position: absolute;
        bottom: 2px;
        left: 0;
        right: 0;
        height: 8px;
        background: rgba(74, 108, 247, 0.15);
        border-radius: 4px;
        z-index: -1;
    }
    
    .hero-section .description {
        font-size: 1.05rem;
        color: #6b7280;
        line-height: 1.7;
        max-width: 480px;
        margin-bottom: 28px;
    }
    
    .hero-section .actions {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        margin-bottom: 40px;
    }
    
    .hero-section .actions .btn-primary-custom {
        background: #4a6cf7;
        color: white;
        padding: 12px 32px;
        border-radius: 10px;
        font-weight: 600;
        font-size: 0.95rem;
        border: none;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s ease;
    }
    
    .hero-section .actions .btn-primary-custom:hover {
        background: #3a5de7;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(74, 108, 247, 0.3);
        text-decoration: none;
        color: white;
    }
    
    .hero-section .actions .btn-outline-custom {
        background: transparent;
        color: #4a6cf7;
        padding: 12px 32px;
        border-radius: 10px;
        font-weight: 600;
        font-size: 0.95rem;
        border: 2px solid #e5e7eb;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s ease;
    }
    
    .hero-section .actions .btn-outline-custom:hover {
        border-color: #4a6cf7;
        background: rgba(74, 108, 247, 0.04);
        text-decoration: none;
        color: #4a6cf7;
    }
    
    .features {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
        padding-top: 8px;
    }
    
    .features .feature-item {
        background: white;
        padding: 18px 16px;
        border-radius: 12px;
        border: 1px solid #f0f0f0;
        transition: all 0.2s ease;
        text-align: center;
    }
    
    .features .feature-item:hover {
        border-color: #e0e0e0;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    }
    
    .features .feature-item .icon {
        font-size: 1.8rem;
        color: #4a6cf7;
        display: block;
        margin-bottom: 6px;
    }
    
    .features .feature-item .label {
        font-size: 0.8rem;
        font-weight: 600;
        color: #374151;
        margin: 0;
    }
    
    .features .feature-item .desc {
        font-size: 0.7rem;
        color: #9ca3af;
        margin: 2px 0 0 0;
    }
    
    .info-card {
        background: white;
        border-radius: 16px;
        padding: 32px 28px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.04);
        border: 1px solid #f0f0f0;
    }
    
    .info-card .card-title {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #9ca3af;
        font-weight: 600;
        margin-bottom: 20px;
    }
    
    .info-card .benefit-list {
        list-style: none;
        padding: 0;
        margin: 0 0 24px 0;
    }
    
    .info-card .benefit-list li {
        display: flex;
        align-items: flex-start;
        gap: 14px;
        padding: 12px 0;
        border-bottom: 1px solid #f5f5f5;
    }
    
    .info-card .benefit-list li:last-child {
        border-bottom: none;
    }
    
    .info-card .benefit-list .check {
        color: #4a6cf7;
        font-size: 1.1rem;
        flex-shrink: 0;
        margin-top: 2px;
    }
    
    .info-card .benefit-list .text strong {
        display: block;
        font-size: 0.9rem;
        font-weight: 600;
        color: #1a1a2e;
    }
    
    .info-card .benefit-list .text small {
        display: block;
        font-size: 0.8rem;
        color: #9ca3af;
    }
    
    .info-card .btn-start {
        display: block;
        width: 100%;
        padding: 14px;
        text-align: center;
        background: #1a1a2e;
        color: white;
        border-radius: 10px;
        font-weight: 600;
        font-size: 0.95rem;
        text-decoration: none;
        transition: all 0.2s ease;
        border: none;
    }
    
    .info-card .btn-start:hover {
        background: #2d2d44;
        color: white;
        text-decoration: none;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(26, 26, 46, 0.15);
    }
    
    .info-card .btn-start i {
        margin-right: 8px;
    }
    
    .info-card .footer-text {
        text-align: center;
        margin-top: 16px;
        font-size: 0.75rem;
        color: #9ca3af;
    }
    
    .info-card .footer-text i {
        margin-right: 4px;
    }
    
    /* Responsive */
    @media (max-width: 767px) {
        .landing-page {
            padding: 20px 0;
            align-items: flex-start;
        }
        
        .hero-section .brand img {
            height: 34px;
        }
        
        .hero-section .brand h1 {
            font-size: 1.2rem;
        }
        
        .hero-section .tagline {
            font-size: 1.6rem;
        }
        
        .hero-section .description {
            font-size: 0.95rem;
            max-width: 100%;
        }
        
        .hero-section .actions {
            flex-direction: column;
        }
        
        .hero-section .actions .btn-primary-custom,
        .hero-section .actions .btn-outline-custom {
            width: 100%;
            justify-content: center;
            padding: 12px 20px;
        }
        
        .features {
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
        }
        
        .features .feature-item {
            padding: 14px 12px;
        }
        
        .features .feature-item .icon {
            font-size: 1.4rem;
        }
        
        .features .feature-item .label {
            font-size: 0.7rem;
        }
        
        .features .feature-item .desc {
            display: none;
        }
        
        .info-card {
            padding: 24px 20px;
            margin-top: 20px;
        }
    }
    
    @media (min-width: 768px) and (max-width: 991px) {
        .hero-section .tagline {
            font-size: 1.8rem;
        }
    }
    
    @media (min-width: 992px) {
        .hero-section .tagline {
            font-size: 2.6rem;
        }
        
        .info-card {
            padding: 36px 32px;
        }
    }
    
    @keyframes fadeUp {
        from {
            opacity: 0;
            transform: translateY(12px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .animate {
        opacity: 0;
        animation: fadeUp 0.5s ease forwards;
    }
    
    .delay-1 { animation-delay: 0.05s; }
    .delay-2 { animation-delay: 0.1s; }
    .delay-3 { animation-delay: 0.15s; }
    .delay-4 { animation-delay: 0.2s; }
    .delay-5 { animation-delay: 0.25s; }
    
    .footer-landing {
        text-align: center;
        padding: 20px 0;
        color: #9ca3af;
        font-size: 0.8rem;
        border-top: 1px solid #f0f0f0;
        margin-top: 20px;
    }
    </style>
</head>
<body>

<div class="landing-page">
    <div class="container">
        <div class="row align-items-center g-4">
            <!-- Left Column -->
            <div class="col-lg-6">
                <div class="hero-section">
                    <!-- Brand -->
                    <div class="brand animate delay-1">
                        <img src="assets/images/mon2.png" alt="Monexa" onerror="this.style.display='none'">
                    </div>

                    <!-- Tagline -->
                    <h2 class="tagline animate delay-2">
                        <?php echo __('landing_title'); ?><br>
                        <span class="highlight">without the hassle</span>
                    </h2>

                    <!-- Description -->
                    <p class="description animate delay-3">
                        <?php echo __('landing_subtitle'); ?>
                    </p>

                    <!-- Buttons -->
                    <div class="actions animate delay-4">
                        <a href="register.php" class="btn-primary-custom">
                            <i class="bi bi-person-plus"></i>
                            <?php echo __('landing_btn_start'); ?>
                        </a>
                        <a href="login.php" class="btn-outline-custom">
                            <i class="bi bi-box-arrow-in-right"></i>
                            <?php echo __('landing_btn_login'); ?>
                        </a>
                    </div>

                    <!-- Features dengan Bootstrap Icons -->
                    <div class="features animate delay-5">
                        <div class="feature-item">
                            <span class="icon"><i class="bi bi-graph-up-arrow"></i></span>
                            <p class="label"><?php echo __('landing_feature_1'); ?></p>
                            <p class="desc"><?php echo __('landing_feature_1_desc'); ?></p>
                        </div>
                        <div class="feature-item">
                            <span class="icon"><i class="bi bi-graph-down-arrow"></i></span>
                            <p class="label"><?php echo __('landing_feature_2'); ?></p>
                            <p class="desc"><?php echo __('landing_feature_2_desc'); ?></p>
                        </div>
                        <div class="feature-item">
                            <span class="icon"><i class="bi bi-pie-chart-fill"></i></span>
                            <p class="label"><?php echo __('landing_feature_3'); ?></p>
                            <p class="desc"><?php echo __('landing_feature_3_desc'); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column - Card -->
            <div class="col-lg-6">
                <div class="info-card animate delay-4">
                    <div class="card-title"><?php echo __('landing_why_choose'); ?></div>

                    <ul class="benefit-list">
                        <li>
                            <span class="check"><i class="bi bi-check-circle-fill"></i></span>
                            <div class="text">
                                <strong><?php echo __('landing_easy'); ?></strong>
                                <small><?php echo __('landing_easy_desc'); ?></small>
                            </div>
                        </li>
                        <li>
                            <span class="check"><i class="bi bi-check-circle-fill"></i></span>
                            <div class="text">
                                <strong><?php echo __('landing_complete'); ?></strong>
                                <small><?php echo __('landing_complete_desc'); ?></small>
                            </div>
                        </li>
                        <li>
                            <span class="check"><i class="bi bi-check-circle-fill"></i></span>
                            <div class="text">
                                <strong><?php echo __('landing_calculator'); ?></strong>
                                <small><?php echo __('landing_calculator_desc'); ?></small>
                            </div>
                        </li>
                        <li>
                            <span class="check"><i class="bi bi-check-circle-fill"></i></span>
                            <div class="text">
                                <strong><?php echo __('landing_secure'); ?></strong>
                                <small><?php echo __('landing_secure_desc'); ?></small>
                            </div>
                        </li>
                        <li>
                            <span class="check"><i class="bi bi-check-circle-fill"></i></span>
                            <div class="text">
                                <strong><?php echo __('landing_anywhere'); ?></strong>
                                <small><?php echo __('landing_anywhere_desc'); ?></small>
                            </div>
                        </li>
                    </ul>

                    <a href="register.php" class="btn-start">
                        <i class="bi bi-arrow-right"></i> <?php echo __('landing_start'); ?>
                    </a>

                    <div class="footer-text">
                        <i class="bi bi-shield-check"></i> <?php echo __('landing_free'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<footer class="footer-landing">
    <div class="container">
        &copy; <?php echo date('Y'); ?> <?php echo __('app_name'); ?>. <?php echo __('all_rights_reserved'); ?>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>