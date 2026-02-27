<?php
require_once __DIR__ . '/../config/database.php';

// Redirect if already logged in
if (isLoggedIn()) {
    $role = $_SESSION['role'];
    if ($role === 'admin') {
        redirect('admin/dashboard.php');
    } elseif ($role === 'technician') {
        redirect('technician/dashboard.php');
    } else {
        redirect('user/dashboard.php');
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'กรุณากรอกชื่อผู้ใช้และรหัสผ่าน';
    } else {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            
            setFlash('success', 'เข้าสู่ระบบสำเร็จ ยินดีต้อนรับคุณ ' . $user['full_name']);
            
            if ($user['role'] === 'admin') {
                redirect('admin/dashboard.php');
            } elseif ($user['role'] === 'technician') {
                redirect('technician/dashboard.php');
            } else {
                redirect('user/dashboard.php');
            }
        } else {
            $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - IT Support</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>body { font-family: 'Sarabun', sans-serif; }</style>
</head>
<body class="bg-gradient-to-br from-slate-100 to-slate-200 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-8">
        <div class="text-center mb-8">
            <i class="fas fa-headset text-5xl text-blue-400 mb-4"></i>
            <h1 class="text-2xl font-bold text-gray-800">IT Support System</h1>
            <p class="text-gray-400">ระบบแจ้งปัญหา IT</p>
        </div>
        
        <?php if ($error): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 flex items-center gap-3">
            <i class="fas fa-exclamation-circle"></i>
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="mb-5">
                <label class="block text-gray-700 font-medium mb-2">ชื่อผู้ใช้ หรือ อีเมล</label>
                <input type="text" name="username" class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent transition" placeholder="กรอกชื่อผู้ใช้หรืออีเมล" required autofocus>
            </div>
            
            <div class="mb-6">
                <label class="block text-gray-700 font-medium mb-2">รหัสผ่าน</label>
                <input type="password" name="password" class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent transition" placeholder="กรอกรหัสผ่าน" required>
            </div>
            
            <button type="submit" class="w-full bg-blue-400 hover:bg-blue-500 text-white font-semibold py-3 px-6 rounded-lg transition flex items-center justify-center gap-2">
                <i class="fas fa-sign-in-alt"></i> เข้าสู่ระบบ
            </button>
        </form>
        
        <div class="text-center mt-6 text-gray-500">
            ยังไม่มีบัญชี? <a href="register.php" class="text-blue-400 hover:text-blue-500 font-medium">สมัครสมาชิก</a>
        </div>
    </div>
</body>
</html>
