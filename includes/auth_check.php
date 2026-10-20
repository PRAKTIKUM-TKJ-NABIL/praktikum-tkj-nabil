<?php
// includes/auth_check.php
// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Ambil data user
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// ===== PERBAIKAN: Cek bahasa dengan benar =====
if (!isset($_SESSION['language'])) {
    global $conn;
    $stmt = $conn->prepare("SELECT prefered_language FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    // Cek apakah prefered_language ada (tidak NULL)
    if ($row && $row['prefered_language'] !== null && !empty($row['prefered_language'])) {
        $_SESSION['language'] = $row['prefered_language'];
    } else {
        // Jika NULL atau empty, redirect ke select_language
        header('Location: select_language.php');
        exit;
    }
}
?>