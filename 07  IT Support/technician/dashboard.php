<?php
require_once __DIR__ . '/../config/database.php';

if (!isLoggedIn() || !hasRole('technician')) {
    redirect('auth/login.php');
}

$db = getDB();
$techUserId = $_SESSION['user_id'];

// Get technician ID
$stmt = $db->prepare("SELECT id FROM technicians WHERE user_id = ?");
$stmt->execute([$techUserId]);
$tech = $stmt->fetch();
$techId = $tech['id'] ?? 0;

// Handle request ticket
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_ticket'])) {
    $ticketId = (int)$_POST['ticket_id'];
    
    // Check if already requested
    $stmt = $db->prepare("SELECT id FROM tickets WHERE id = ? AND requested_technician_id = ?");
    $stmt->execute([$ticketId, $techId]);
    if (!$stmt->fetch()) {
        $stmt = $db->prepare("UPDATE tickets SET requested_technician_id = ? WHERE id = ? AND technician_id IS NULL");
        $stmt->execute([$techId, $ticketId]);
        
        $stmt = $db->prepare("INSERT INTO ticket_comments (ticket_id, user_id, comment) VALUES (?, ?, ?)");
        $stmt->execute([$ticketId, $techUserId, 'ได้กดขอรับงานนี้ (รอแอดมินอนุมัติ)']);
        
        setFlash('success', 'ส่งคำขอรับงานแล้ว กรุณารอแอดมินอนุมัติ');
    }
    redirect('technician/dashboard.php');
}

// Get stats
$stmt = $db->prepare("SELECT status, COUNT(*) as count FROM tickets WHERE technician_id = ? GROUP BY status");
$stmt->execute([$techId]);
$statsRaw = $stmt->fetchAll();
$stats = ['open' => 0, 'in-progress' => 0, 'closed' => 0];
foreach ($statsRaw as $s) {
    $stats[$s['status']] = $s['count'];
}

// Get my assigned tickets
$stmt = $db->prepare("
    SELECT t.*, u.full_name as user_name
    FROM tickets t
    JOIN users u ON t.user_id = u.id
    WHERE t.technician_id = ? AND t.status != 'closed'
    ORDER BY CASE WHEN t.priority = 'high' THEN 1 WHEN t.priority = 'medium' THEN 2 ELSE 3 END, t.created_at ASC
");
$stmt->execute([$techId]);
$myTickets = $stmt->fetchAll();

// Get unassigned tickets
$stmt = $db->query("
    SELECT t.*, u.full_name as user_name, t.requested_technician_id
    FROM tickets t
    JOIN users u ON t.user_id = u.id
    WHERE t.technician_id IS NULL AND t.status = 'open'
    ORDER BY CASE WHEN t.priority = 'high' THEN 1 WHEN t.priority = 'medium' THEN 2 ELSE 3 END, t.created_at ASC
");
$unassignedTickets = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="mb-8">
    <h1 class="text-2xl font-bold text-gray-800">สวัสดี, <?= htmlspecialchars($_SESSION['full_name']) ?></h1>
    <p class="text-gray-400">งานที่ได้รับมอบหมาย</p>
</div>

<!-- Stats -->
<div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-8">
    <div class="bg-white rounded-xl border border-gray-100 p-5 flex items-center gap-4">
        <div class="w-14 h-14 bg-amber-50 rounded-lg flex items-center justify-center">
            <i class="fas fa-exclamation-circle text-xl text-amber-400"></i>
        </div>
        <div>
            <h3 class="text-2xl font-bold text-gray-800"><?= count($unassignedTickets) ?></h3>
            <p class="text-gray-400 text-sm">งานรอคนรับ</p>
        </div>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 p-5 flex items-center gap-4">
        <div class="w-14 h-14 bg-blue-50 rounded-lg flex items-center justify-center">
            <i class="fas fa-spinner text-xl text-blue-400"></i>
        </div>
        <div>
            <h3 class="text-2xl font-bold text-gray-800"><?= $stats['in-progress'] ?></h3>
            <p class="text-gray-400 text-sm">กำลังดำเนินการ</p>
        </div>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 p-5 flex items-center gap-4">
        <div class="w-14 h-14 bg-green-50 rounded-lg flex items-center justify-center">
            <i class="fas fa-check-circle text-xl text-green-400"></i>
        </div>
        <div>
            <h3 class="text-2xl font-bold text-gray-800"><?= $stats['closed'] ?></h3>
            <p class="text-gray-400 text-sm">เสร็จสิ้น</p>
        </div>
    </div>
</div>

<!-- Available Tickets -->
<?php if (!empty($unassignedTickets)): ?>
<div class="bg-white rounded-xl border border-gray-100 mb-6">
    <div class="p-5 border-b border-gray-100 bg-amber-50 rounded-t-xl">
        <h3 class="text-lg font-semibold text-amber-800 flex items-center gap-2">
            <i class="fas fa-inbox"></i> งานที่ยังไม่มีเจ้าของ (<?= count($unassignedTickets) ?>)
        </h3>
    </div>
    <div class="p-5">
        <div class="space-y-4">
            <?php foreach ($unassignedTickets as $ticket): ?>
            <div class="border border-amber-100 bg-amber-50/50 rounded-xl p-5">
                <div class="flex flex-wrap justify-between items-start gap-3 mb-3">
                    <div>
                        <span class="text-gray-400 text-sm">#<?= $ticket['id'] ?></span>
                        <h4 class="font-semibold text-gray-800"><?= htmlspecialchars($ticket['title']) ?></h4>
                    </div>
                    <?php
                    $pClass = match($ticket['priority']) { 'high' => 'bg-red-50 text-red-600', 'medium' => 'bg-amber-50 text-amber-600', 'low' => 'bg-gray-100 text-gray-600', default => '' };
                    $pText = match($ticket['priority']) { 'high' => 'สูง', 'medium' => 'ปานกลาง', 'low' => 'ต่ำ', default => $ticket['priority'] };
                    ?>
                    <span class="px-2.5 py-1 rounded-full text-xs font-medium <?= $pClass ?>"><?= $pText ?></span>
                </div>
                <div class="flex flex-wrap items-center gap-4 text-sm text-gray-500 mb-4">
                    <span><i class="fas fa-user mr-1"></i> <?= htmlspecialchars($ticket['user_name']) ?></span>
                    <span><i class="fas fa-folder mr-1"></i> <?= htmlspecialchars($ticket['category'] ?? '-') ?></span>
                    <span><i class="fas fa-clock mr-1"></i> <?= date('d/m/Y H:i', strtotime($ticket['created_at'])) ?></span>
                </div>
                <div class="flex gap-2">
                    <?php if ($ticket['requested_technician_id'] == $techId): ?>
                        <span class="bg-yellow-100 text-yellow-700 px-4 py-2 rounded-lg text-sm font-medium">
                            <i class="fas fa-clock mr-1"></i> รออนุมัติ
                        </span>
                    <?php elseif ($ticket['requested_technician_id']): ?>
                        <span class="bg-gray-100 text-gray-500 px-4 py-2 rounded-lg text-sm font-medium">
                            <i class="fas fa-lock mr-1"></i> ช่างอื่นขอแล้ว
                        </span>
                    <?php else: ?>
                        <form method="POST" class="inline">
                            <input type="hidden" name="request_ticket" value="1">
                            <input type="hidden" name="ticket_id" value="<?= $ticket['id'] ?>">
                            <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
                                <i class="fas fa-hand-paper mr-1"></i> ขอรับงาน
                            </button>
                        </form>
                    <?php endif; ?>
                    <a href="<?= BASE_URL ?>user/view_ticket.php?id=<?= $ticket['id'] ?>" class="bg-gray-100 hover:bg-gray-200 text-gray-600 px-4 py-2 rounded-lg text-sm font-medium transition">
                        <i class="fas fa-eye mr-1"></i> ดูรายละเอียด
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- My Tasks -->
<div class="bg-white rounded-xl border border-gray-100">
    <div class="p-5 border-b border-gray-100">
        <h3 class="text-lg font-semibold text-gray-800 flex items-center gap-2">
            <i class="fas fa-tasks text-gray-400"></i> งานของฉัน (<?= count($myTickets) ?>)
        </h3>
    </div>
    <div class="p-5">
        <?php if (empty($myTickets)): ?>
        <div class="text-center py-12 text-gray-400">
            <i class="fas fa-check-circle text-5xl mb-4 text-green-300"></i>
            <h3 class="text-gray-500 font-medium mb-2">ไม่มีงานค้าง</h3>
        </div>
        <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($myTickets as $ticket): ?>
            <div class="border border-gray-100 rounded-xl p-5 hover:shadow-md transition">
                <div class="flex flex-wrap justify-between items-start gap-3 mb-3">
                    <div>
                        <span class="text-gray-400 text-sm">#<?= $ticket['id'] ?></span>
                        <h4 class="font-semibold text-gray-800"><?= htmlspecialchars($ticket['title']) ?></h4>
                    </div>
                    <?php
                    $sClass = match($ticket['status']) { 'open' => 'bg-blue-50 text-blue-600', 'in-progress' => 'bg-amber-50 text-amber-600', default => '' };
                    $sText = match($ticket['status']) { 'open' => 'รอดำเนินการ', 'in-progress' => 'กำลังดำเนินการ', default => $ticket['status'] };
                    ?>
                    <span class="px-2.5 py-1 rounded-full text-xs font-medium <?= $sClass ?>"><?= $sText ?></span>
                </div>
                <div class="flex flex-wrap items-center gap-4 text-sm text-gray-500 mb-4">
                    <span><i class="fas fa-user mr-1"></i> <?= htmlspecialchars($ticket['user_name']) ?></span>
                    <span><i class="fas fa-folder mr-1"></i> <?= htmlspecialchars($ticket['category'] ?? '-') ?></span>
                    <span><i class="fas fa-clock mr-1"></i> <?= date('d/m/Y H:i', strtotime($ticket['created_at'])) ?></span>
                </div>
                <div class="flex gap-2">
                    <a href="update_ticket.php?id=<?= $ticket['id'] ?>" class="bg-blue-400 hover:bg-blue-500 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
                        <i class="fas fa-edit mr-1"></i> อัปเดต
                    </a>
                    <a href="<?= BASE_URL ?>user/view_ticket.php?id=<?= $ticket['id'] ?>" class="bg-gray-100 hover:bg-gray-200 text-gray-600 px-4 py-2 rounded-lg text-sm font-medium transition">
                        <i class="fas fa-eye mr-1"></i> ดูรายละเอียด
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
