<?php
require_once __DIR__ . '/../config/database.php';

if (!isLoggedIn() || !hasRole('technician')) {
    redirect('auth/login.php');
}

$db = getDB();
$ticketId = (int)($_GET['id'] ?? 0);

// Get technician ID
$stmt = $db->prepare("SELECT id FROM technicians WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$tech = $stmt->fetch();
$techId = $tech['id'] ?? 0;

// Get ticket
$stmt = $db->prepare("SELECT t.*, u.full_name as user_name FROM tickets t JOIN users u ON t.user_id = u.id WHERE t.id = ? AND t.technician_id = ?");
$stmt->execute([$ticketId, $techId]);
$ticket = $stmt->fetch();

if (!$ticket) {
    setFlash('error', 'ไม่พบ Ticket หรือคุณไม่ได้รับมอบหมายงานนี้');
    redirect('technician/dashboard.php');
}

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newStatus = $_POST['status'] ?? $ticket['status'];
    $comment = trim($_POST['comment'] ?? '');
    
    // Update status
    if ($newStatus !== $ticket['status']) {
        $closedAt = $newStatus === 'closed' ? date('Y-m-d H:i:s') : null;
        $stmt = $db->prepare("UPDATE tickets SET status = ?, closed_at = ? WHERE id = ?");
        $stmt->execute([$newStatus, $closedAt, $ticketId]);
    }
    
    // Add comment
    if (!empty($comment)) {
        $stmt = $db->prepare("INSERT INTO ticket_comments (ticket_id, user_id, comment) VALUES (?, ?, ?)");
        $stmt->execute([$ticketId, $_SESSION['user_id'], $comment]);
    }
    
    setFlash('success', 'อัปเดต Ticket สำเร็จ');
    redirect('technician/dashboard.php');
}

include __DIR__ . '/../includes/header.php';
?>

<div class="mb-6">
    <a href="dashboard.php" class="inline-flex items-center gap-2 text-gray-500 hover:text-gray-700 transition">
        <i class="fas fa-arrow-left"></i> กลับ
    </a>
</div>

<div class="max-w-2xl">
    <!-- Ticket Info -->
    <div class="bg-white rounded-xl border border-gray-100 mb-6">
        <div class="p-5 border-b border-gray-100">
            <h3 class="text-lg font-semibold text-gray-800">
                <i class="fas fa-ticket-alt text-blue-400 mr-2"></i>
                Ticket #<?= $ticket['id'] ?> - <?= htmlspecialchars($ticket['title']) ?>
            </h3>
        </div>
        <div class="p-5">
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <p class="text-gray-400 text-sm">ผู้แจ้ง</p>
                    <p class="font-medium text-gray-700"><?= htmlspecialchars($ticket['user_name']) ?></p>
                </div>
                <div>
                    <p class="text-gray-400 text-sm">หมวดหมู่</p>
                    <p class="font-medium text-gray-700"><?= htmlspecialchars($ticket['category'] ?? '-') ?></p>
                </div>
            </div>
            <div>
                <p class="text-gray-400 text-sm mb-1">รายละเอียดปัญหา</p>
                <p class="text-gray-600 whitespace-pre-wrap"><?= htmlspecialchars($ticket['description']) ?></p>
            </div>
            <?php if ($ticket['image_path']): ?>
            <div class="mt-4">
                <img src="<?= BASE_URL . $ticket['image_path'] ?>" class="max-w-xs rounded-lg shadow-md" alt="Image">
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Update Form -->
    <div class="bg-white rounded-xl border border-gray-100">
        <div class="p-5 border-b border-gray-100">
            <h3 class="text-lg font-semibold text-gray-800">
                <i class="fas fa-edit text-gray-400 mr-2"></i> อัปเดตสถานะ
            </h3>
        </div>
        <div class="p-5">
            <form method="POST">
                <div class="mb-5">
                    <label class="block text-gray-700 font-medium mb-2">สถานะ</label>
                    <select name="status" class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent bg-white">
                        <option value="open" <?= $ticket['status'] === 'open' ? 'selected' : '' ?>>รอดำเนินการ</option>
                        <option value="in-progress" <?= $ticket['status'] === 'in-progress' ? 'selected' : '' ?>>กำลังดำเนินการ</option>
                        <option value="closed" <?= $ticket['status'] === 'closed' ? 'selected' : '' ?>>เสร็จสิ้น (ปิด Ticket)</option>
                    </select>
                </div>
                
                <div class="mb-5">
                    <label class="block text-gray-700 font-medium mb-2">เพิ่มความคิดเห็น / บันทึกการแก้ไข</label>
                    <textarea name="comment" class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent" rows="4" placeholder="รายละเอียดการดำเนินการ..."></textarea>
                </div>
                
                <div class="flex gap-3">
                    <button type="submit" class="bg-blue-400 hover:bg-blue-500 text-white px-6 py-3 rounded-lg font-medium transition flex items-center gap-2">
                        <i class="fas fa-save"></i> บันทึก
                    </button>
                    <a href="dashboard.php" class="bg-gray-100 hover:bg-gray-200 text-gray-600 px-6 py-3 rounded-lg font-medium transition">ยกเลิก</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
