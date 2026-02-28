<?php
require_once __DIR__ . '/../config/database.php';

if (!isLoggedIn()) {
    redirect('auth/login.php');
}

$db = getDB();
$ticketId = (int)($_GET['id'] ?? 0);

if (!$ticketId) {
    redirect('user/dashboard.php');
}

$stmt = $db->prepare("
    SELECT t.*, u.full_name as user_name, u.email as user_email,
           tech.id as tech_id, tu.full_name as technician_name
    FROM tickets t
    JOIN users u ON t.user_id = u.id
    LEFT JOIN technicians tech ON t.technician_id = tech.id
    LEFT JOIN users tu ON tech.user_id = tu.id
    WHERE t.id = ?
");
$stmt->execute([$ticketId]);
$ticket = $stmt->fetch();

if (!$ticket) {
    setFlash('error', 'ไม่พบ Ticket นี้');
    redirect('user/dashboard.php');
}

if (!hasRole('admin') && !hasRole('technician') && $ticket['user_id'] != $_SESSION['user_id']) {
    setFlash('error', 'คุณไม่มีสิทธิ์เข้าถึง Ticket นี้');
    redirect('user/dashboard.php');
}

$stmt = $db->prepare("SELECT c.*, u.full_name, u.role FROM ticket_comments c JOIN users u ON c.user_id = u.id WHERE c.ticket_id = ? ORDER BY c.created_at ASC");
$stmt->execute([$ticketId]);
$comments = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment'])) {
    $comment = trim($_POST['comment']);
    if (!empty($comment)) {
        $stmt = $db->prepare("INSERT INTO ticket_comments (ticket_id, user_id, comment) VALUES (?, ?, ?)");
        $stmt->execute([$ticketId, $_SESSION['user_id'], $comment]);
        setFlash('success', 'เพิ่มความคิดเห็นสำเร็จ');
        redirect('user/view_ticket.php?id=' . $ticketId);
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="mb-6">
    <?php if (hasRole('admin')): ?>
    <a href="<?= BASE_URL ?>admin/tickets.php" class="inline-flex items-center gap-2 text-gray-500 hover:text-gray-700 transition">
    <?php elseif (hasRole('technician')): ?>
    <a href="<?= BASE_URL ?>technician/dashboard.php" class="inline-flex items-center gap-2 text-gray-500 hover:text-gray-700 transition">
    <?php else: ?>
    <a href="my_tickets.php" class="inline-flex items-center gap-2 text-gray-500 hover:text-gray-700 transition">
    <?php endif; ?>
        <i class="fas fa-arrow-left"></i> กลับ
    </a>
</div>

<!-- Ticket Details -->
<div class="bg-white rounded-xl border border-gray-100 mb-6">
    <div class="p-5 border-b border-gray-100 flex flex-wrap justify-between items-center gap-4">
        <h3 class="text-lg font-semibold text-gray-800 flex items-center gap-2">
            <i class="fas fa-ticket-alt text-blue-400"></i>
            Ticket #<?= $ticket['id'] ?> - <?= htmlspecialchars($ticket['title']) ?>
        </h3>
        <?php
        $statusClass = match($ticket['status']) {
            'open' => 'bg-blue-50 text-blue-600',
            'in-progress' => 'bg-amber-50 text-amber-600',
            'closed' => 'bg-green-50 text-green-600',
            default => ''
        };
        $statusText = match($ticket['status']) {
            'open' => 'รอดำเนินการ',
            'in-progress' => 'กำลังดำเนินการ',
            'closed' => 'เสร็จสิ้น',
            default => $ticket['status']
        };
        ?>
        <span class="px-3 py-1 rounded-full text-sm font-medium <?= $statusClass ?>"><?= $statusText ?></span>
    </div>
    <div class="p-6">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-6">
            <div>
                <p class="text-gray-400 text-sm mb-1">ผู้แจ้ง</p>
                <p class="font-semibold text-gray-700"><?= htmlspecialchars($ticket['user_name']) ?></p>
            </div>
            <div>
                <p class="text-gray-400 text-sm mb-1">หมวดหมู่</p>
                <p class="font-semibold text-gray-700"><?= htmlspecialchars($ticket['category'] ?? '-') ?></p>
            </div>
            <div>
                <p class="text-gray-400 text-sm mb-1">ความเร่งด่วน</p>
                <?php
                $priorityClass = match($ticket['priority']) {
                    'high' => 'bg-red-50 text-red-600',
                    'medium' => 'bg-amber-50 text-amber-600',
                    'low' => 'bg-gray-100 text-gray-600',
                    default => ''
                };
                $priorityText = match($ticket['priority']) {
                    'high' => 'สูง', 'medium' => 'ปานกลาง', 'low' => 'ต่ำ', default => $ticket['priority']
                };
                ?>
                <span class="px-2.5 py-1 rounded-full text-xs font-medium <?= $priorityClass ?>"><?= $priorityText ?></span>
            </div>
            <div>
                <p class="text-gray-400 text-sm mb-1">ช่างเทคนิค</p>
                <p class="font-semibold text-gray-700"><?= $ticket['technician_name'] ? htmlspecialchars($ticket['technician_name']) : '<span class="text-gray-400">ยังไม่มอบหมาย</span>' ?></p>
            </div>
        </div>
        
        <div class="grid grid-cols-2 md:grid-cols-3 gap-6 mb-6">
            <div>
                <p class="text-gray-400 text-sm mb-1">วันที่แจ้ง</p>
                <p class="text-gray-600"><?= date('d/m/Y H:i', strtotime($ticket['created_at'])) ?></p>
            </div>
            <div>
                <p class="text-gray-400 text-sm mb-1">อัพเดทล่าสุด</p>
                <p class="text-gray-600"><?= date('d/m/Y H:i', strtotime($ticket['updated_at'])) ?></p>
            </div>
            <?php if ($ticket['closed_at']): ?>
            <div>
                <p class="text-gray-400 text-sm mb-1">วันที่ปิด</p>
                <p class="text-gray-600"><?= date('d/m/Y H:i', strtotime($ticket['closed_at'])) ?></p>
            </div>
            <?php endif; ?>
        </div>
        
        <hr class="border-gray-100 mb-6">
        
        <h4 class="font-semibold text-gray-700 mb-3">รายละเอียดปัญหา</h4>
        <p class="text-gray-600 whitespace-pre-wrap leading-relaxed"><?= htmlspecialchars($ticket['description']) ?></p>
        
        <?php if ($ticket['image_path']): ?>
        <div class="mt-6">
            <h4 class="font-semibold text-gray-700 mb-3">รูปภาพแนบ</h4>
            <img src="<?= BASE_URL . $ticket['image_path'] ?>" class="max-w-sm rounded-lg shadow-md cursor-pointer hover:opacity-90 transition" alt="Attached image" onclick="openLightbox(this.src)">
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Comments -->
<div class="bg-white rounded-xl border border-gray-100">
    <div class="p-5 border-b border-gray-100">
        <h3 class="text-lg font-semibold text-gray-800 flex items-center gap-2">
            <i class="fas fa-comments text-gray-400"></i> ประวัติการดำเนินการ (<?= count($comments) ?>)
        </h3>
    </div>
    <div class="p-6">
        <div class="space-y-4">
            <?php if (empty($comments)): ?>
            <p class="text-gray-400 text-center py-4">ยังไม่มีความคิดเห็น</p>
            <?php else: ?>
            <?php foreach ($comments as $comment): ?>
            <div class="flex gap-4 pb-4 border-b border-gray-50">
                <div class="w-10 h-10 bg-blue-400 rounded-full flex items-center justify-center text-white font-semibold flex-shrink-0">
                    <?= strtoupper(substr($comment['full_name'], 0, 1)) ?>
                </div>
                <div class="flex-1">
                    <div class="flex flex-wrap justify-between items-center gap-2 mb-2">
                        <span class="font-semibold text-gray-700">
                            <?= htmlspecialchars($comment['full_name']) ?>
                            <?php if ($comment['role'] === 'technician'): ?>
                            <span class="ml-1 px-2 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-600">ช่าง</span>
                            <?php elseif ($comment['role'] === 'admin'): ?>
                            <span class="ml-1 px-2 py-0.5 rounded-full text-xs font-medium bg-green-50 text-green-600">แอดมิน</span>
                            <?php endif; ?>
                        </span>
                        <span class="text-gray-400 text-sm"><?= date('d/m/Y H:i', strtotime($comment['created_at'])) ?></span>
                    </div>
                    <p class="text-gray-600"><?= nl2br(htmlspecialchars($comment['comment'])) ?></p>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <?php if ($ticket['status'] !== 'closed'): ?>
        <form method="POST" class="mt-6 pt-6 border-t border-gray-100">
            <div class="mb-4">
                <label class="block text-gray-700 font-medium mb-2">เพิ่มความคิดเห็น</label>
                <textarea name="comment" class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent" rows="3" placeholder="พิมพ์ความคิดเห็น..."></textarea>
            </div>
            <button type="submit" class="bg-blue-400 hover:bg-blue-500 text-white px-5 py-2.5 rounded-lg font-medium transition flex items-center gap-2">
                <i class="fas fa-paper-plane"></i> ส่ง
            </button>
        </form>
        <?php endif; ?>
    </div>
</div>

<!-- Lightbox -->
<div id="lightbox" class="fixed inset-0 bg-black/80 z-50 hidden items-center justify-center" onclick="closeLightbox()">
    <img id="lightbox-img" src="" class="max-w-[90%] max-h-[90vh] rounded-lg">
</div>

<script>
function openLightbox(src) {
    document.getElementById('lightbox-img').src = src;
    document.getElementById('lightbox').classList.remove('hidden');
    document.getElementById('lightbox').classList.add('flex');
}
function closeLightbox() {
    document.getElementById('lightbox').classList.add('hidden');
    document.getElementById('lightbox').classList.remove('flex');
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
