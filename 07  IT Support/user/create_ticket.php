<?php
require_once __DIR__ . '/../config/database.php';

if (!isLoggedIn() || hasRole('admin')) {
    redirect('auth/login.php');
}

$db = getDB();
$error = '';

$uploadDir = __DIR__ . '/../uploads/tickets/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $priority = $_POST['priority'] ?? 'medium';
    $description = trim($_POST['description'] ?? '');
    
    if (empty($title) || empty($description)) {
        $error = 'กรุณากรอกหัวข้อและรายละเอียดปัญหา';
    } else {
        $imagePath = null;
        
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['image'];
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            
            if (!in_array($file['type'], $allowedTypes)) {
                $error = 'รองรับเฉพาะไฟล์รูปภาพ (JPG, PNG, GIF, WEBP)';
            } elseif ($file['size'] > 5 * 1024 * 1024) {
                $error = 'ขนาดไฟล์ต้องไม่เกิน 5MB';
            } else {
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = uniqid('ticket_') . '.' . $ext;
                $targetPath = $uploadDir . $filename;
                
                if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                    $imagePath = 'uploads/tickets/' . $filename;
                }
            }
        }
        
        if (empty($error)) {
            $stmt = $db->prepare("INSERT INTO tickets (user_id, title, category, priority, description, image_path) VALUES (?, ?, ?, ?, ?, ?)");
            
            if ($stmt->execute([$_SESSION['user_id'], $title, $category, $priority, $description, $imagePath])) {
                $ticketId = $db->lastInsertId();
                setFlash('success', 'แจ้งปัญหาสำเร็จ! Ticket #' . $ticketId);
                redirect('user/view_ticket.php?id=' . $ticketId);
            } else {
                $error = 'เกิดข้อผิดพลาด กรุณาลองใหม่';
            }
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="mb-6">
    <a href="dashboard.php" class="inline-flex items-center gap-2 text-gray-500 hover:text-gray-700 transition">
        <i class="fas fa-arrow-left"></i> กลับ
    </a>
</div>

<div class="max-w-2xl">
    <div class="bg-white rounded-xl border border-gray-100">
        <div class="p-5 border-b border-gray-100">
            <h3 class="text-lg font-semibold text-gray-800 flex items-center gap-2">
                <i class="fas fa-plus-circle text-blue-400"></i> แจ้งปัญหาใหม่
            </h3>
        </div>
        <div class="p-6">
            <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 flex items-center gap-3">
                <i class="fas fa-exclamation-circle"></i>
                <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="mb-5">
                    <label class="block text-gray-700 font-medium mb-2">หัวข้อปัญหา <span class="text-red-500">*</span></label>
                    <input type="text" name="title" class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent" placeholder="เช่น อินเทอร์เน็ตใช้งานไม่ได้" value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" required>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-5">
                    <div>
                        <label class="block text-gray-700 font-medium mb-2">หมวดหมู่</label>
                        <select name="category" class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent bg-white">
                            <option value="">-- เลือกหมวดหมู่ --</option>
                            <option value="network" <?= ($_POST['category'] ?? '') === 'network' ? 'selected' : '' ?>>เครือข่าย / อินเทอร์เน็ต</option>
                            <option value="hardware" <?= ($_POST['category'] ?? '') === 'hardware' ? 'selected' : '' ?>>ฮาร์ดแวร์ / อุปกรณ์</option>
                            <option value="software" <?= ($_POST['category'] ?? '') === 'software' ? 'selected' : '' ?>>ซอฟต์แวร์ / โปรแกรม</option>
                            <option value="printer" <?= ($_POST['category'] ?? '') === 'printer' ? 'selected' : '' ?>>เครื่องพิมพ์</option>
                            <option value="email" <?= ($_POST['category'] ?? '') === 'email' ? 'selected' : '' ?>>อีเมล</option>
                            <option value="other" <?= ($_POST['category'] ?? '') === 'other' ? 'selected' : '' ?>>อื่นๆ</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 font-medium mb-2">ความเร่งด่วน</label>
                        <select name="priority" class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent bg-white">
                            <option value="low" <?= ($_POST['priority'] ?? '') === 'low' ? 'selected' : '' ?>>ต่ำ</option>
                            <option value="medium" <?= ($_POST['priority'] ?? 'medium') === 'medium' ? 'selected' : '' ?>>ปานกลาง</option>
                            <option value="high" <?= ($_POST['priority'] ?? '') === 'high' ? 'selected' : '' ?>>สูง</option>
                        </select>
                    </div>
                </div>
                
                <div class="mb-5">
                    <label class="block text-gray-700 font-medium mb-2">รายละเอียดปัญหา <span class="text-red-500">*</span></label>
                    <textarea name="description" class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent" rows="5" placeholder="อธิบายปัญหาที่พบอย่างละเอียด..." required><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                </div>
                
                <div class="mb-6">
                    <label class="block text-gray-700 font-medium mb-2">แนบรูปภาพ (ถ้ามี)</label>
                    <label class="flex flex-col items-center justify-center border-2 border-dashed border-gray-200 rounded-lg p-8 cursor-pointer hover:border-blue-400 hover:bg-blue-50/30 transition">
                        <i class="fas fa-cloud-upload-alt text-4xl text-gray-300 mb-3"></i>
                        <p class="text-gray-500 mb-1">คลิกเพื่อเลือกไฟล์</p>
                        <span class="text-xs text-gray-400">รองรับ: JPG, PNG, GIF, WEBP (ไม่เกิน 5MB)</span>
                        <input type="file" name="image" accept="image/*" class="hidden" onchange="previewImage(this)">
                        <div id="preview" class="mt-4"></div>
                    </label>
                </div>
                
                <div class="flex gap-3">
                    <button type="submit" class="bg-blue-400 hover:bg-blue-500 text-white px-6 py-3 rounded-lg font-medium transition flex items-center gap-2">
                        <i class="fas fa-paper-plane"></i> ส่งแจ้งปัญหา
                    </button>
                    <a href="dashboard.php" class="bg-gray-100 hover:bg-gray-200 text-gray-600 px-6 py-3 rounded-lg font-medium transition">ยกเลิก</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function previewImage(input) {
    const preview = document.getElementById('preview');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.innerHTML = `<img src="${e.target.result}" class="max-w-xs rounded-lg shadow-md mt-2" alt="Preview">`;
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
