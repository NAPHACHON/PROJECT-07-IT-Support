<?php
require_once __DIR__ . '/../config/database.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('user/dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    
    if (empty($username) || empty($email) || empty($password) || empty($full_name)) {
        $error = 'กรุณากรอกข้อมูลให้ครบถ้วน';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'รูปแบบอีเมลไม่ถูกต้อง';
    } elseif (strlen($password) < 6) {
        $error = 'รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร';
    } elseif ($password !== $confirm_password) {
        $error = 'รหัสผ่านไม่ตรงกัน';
    } else {
        $db = getDB();
        
        $stmt = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        
        if ($stmt->fetch()) {
            $error = 'ชื่อผู้ใช้หรืออีเมลนี้มีอยู่ในระบบแล้ว';
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (username, email, password, full_name, phone, role) VALUES (?, ?, ?, ?, ?, 'user')");
            
            if ($stmt->execute([$username, $email, $hashedPassword, $full_name, $phone])) {
                setFlash('success', 'สมัครสมาชิกสำเร็จ! กรุณาเข้าสู่ระบบ');
                redirect('auth/login.php');
            } else {
                $error = 'เกิดข้อผิดพลาด กรุณาลองใหม่';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สมัครสมาชิก - IT Support</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>body { font-family: 'Sarabun', sans-serif; }</style>
</head>
<body class="bg-gradient-to-br from-slate-100 to-slate-200 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-8">
        <div class="text-center mb-6">
            <i class="fas fa-user-plus text-5xl text-blue-400 mb-4"></i>
            <h1 class="text-2xl font-bold text-gray-800">สมัครสมาชิก</h1>
            <p class="text-gray-400">สร้างบัญชีใหม่เพื่อแจ้งปัญหา IT</p>
        </div>
        
        <?php if ($error): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 flex items-center gap-3">
            <i class="fas fa-exclamation-circle"></i>
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="mb-4">
                <label class="block text-gray-700 font-medium mb-2">ชื่อผู้ใช้ <span class="text-red-500">*</span></label>
                <input type="text" name="username" class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent" placeholder="กรอกชื่อผู้ใช้" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 font-medium mb-2">ชื่อ-นามสกุล <span class="text-red-500">*</span></label>
                <input type="text" name="full_name" class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent" placeholder="กรอกชื่อ-นามสกุล" value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>" required>
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 font-medium mb-2">อีเมล <span class="text-red-500">*</span></label>
                <input type="email" name="email" class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent" placeholder="example@email.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 font-medium mb-2">เบอร์โทรศัพท์</label>
                <input type="tel" name="phone" class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent" placeholder="08x-xxx-xxxx" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 font-medium mb-2">รหัสผ่าน <span class="text-red-500">*</span></label>
                <input type="password" name="password" class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent" placeholder="อย่างน้อย 6 ตัวอักษร" required>
            </div>
            
            <div class="mb-6">
                <label class="block text-gray-700 font-medium mb-2">ยืนยันรหัสผ่าน <span class="text-red-500">*</span></label>
                <input type="password" name="confirm_password" class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent" placeholder="กรอกรหัสผ่านอีกครั้ง" required>
            </div>
            
            <button type="submit" class="w-full bg-blue-400 hover:bg-blue-500 text-white font-semibold py-3 px-6 rounded-lg transition flex items-center justify-center gap-2">
                <i class="fas fa-user-plus"></i> สมัครสมาชิก
            </button>
        </form>
        
        <div class="text-center mt-6 text-gray-500">
            มีบัญชีอยู่แล้ว? <a href="login.php" class="text-blue-400 hover:text-blue-500 font-medium">เข้าสู่ระบบ</a>
        </div>
    </div>
</body>
</html>
