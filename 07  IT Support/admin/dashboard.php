<?php
require_once __DIR__ . '/../config/database.php';

if (!isLoggedIn() || !hasRole('admin')) {
    redirect('auth/login.php');
}

$db = getDB();

// Get stats
$stmt = $db->query("SELECT status, COUNT(*) as count FROM tickets GROUP BY status");
$statsRaw = $stmt->fetchAll();
$stats = ['open' => 0, 'in-progress' => 0, 'closed' => 0];
foreach ($statsRaw as $s) {
    $stats[$s['status']] = $s['count'];
}

$totalTickets = array_sum($stats);
$totalUsers = $db->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
$totalTechnicians = $db->query("SELECT COUNT(*) FROM technicians")->fetchColumn();

// Recent tickets
$stmt = $db->query("SELECT t.*, u.full_name as user_name FROM tickets t JOIN users u ON t.user_id = u.id ORDER BY t.created_at DESC LIMIT 5");
$recentTickets = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="mb-8">
    <h1 class="text-2xl font-bold text-gray-800">แดชบอร์ด</h1>
    <p class="text-gray-400">ภาพรวมระบบแจ้งปัญหา IT</p>
</div>

<!-- Stats -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-xl border border-gray-100 p-5 flex items-center gap-4 hover:shadow-md transition">
        <div class="w-14 h-14 bg-blue-50 rounded-lg flex items-center justify-center">
            <i class="fas fa-ticket-alt text-xl text-blue-400"></i>
        </div>
        <div>
            <h3 class="text-2xl font-bold text-gray-800"><?= $totalTickets ?></h3>
            <p class="text-gray-400 text-sm">Tickets ทั้งหมด</p>
        </div>
    </div>
    
    <div class="bg-white rounded-xl border border-gray-100 p-5 flex items-center gap-4 hover:shadow-md transition">
        <div class="w-14 h-14 bg-amber-50 rounded-lg flex items-center justify-center">
            <i class="fas fa-exclamation-circle text-xl text-amber-400"></i>
        </div>
        <div>
            <h3 class="text-2xl font-bold text-gray-800"><?= $stats['open'] ?></h3>
            <p class="text-gray-400 text-sm">รอมอบหมาย</p>
        </div>
    </div>
    
    <div class="bg-white rounded-xl border border-gray-100 p-5 flex items-center gap-4 hover:shadow-md transition">
        <div class="w-14 h-14 bg-indigo-50 rounded-lg flex items-center justify-center">
            <i class="fas fa-users text-xl text-indigo-400"></i>
        </div>
        <div>
            <h3 class="text-2xl font-bold text-gray-800"><?= $totalUsers ?></h3>
            <p class="text-gray-400 text-sm">ผู้ใช้งาน</p>
        </div>
    </div>
    
    <div class="bg-white rounded-xl border border-gray-100 p-5 flex items-center gap-4 hover:shadow-md transition">
        <div class="w-14 h-14 bg-green-50 rounded-lg flex items-center justify-center">
            <i class="fas fa-user-cog text-xl text-green-400"></i>
        </div>
        <div>
            <h3 class="text-2xl font-bold text-gray-800"><?= $totalTechnicians ?></h3>
            <p class="text-gray-400 text-sm">ช่างเทคนิค</p>
        </div>
    </div>
</div>

<!-- Chart & Recent -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Status Chart -->
    <div class="bg-white rounded-xl border border-gray-100 p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-6">สถานะ Tickets</h3>
        <div class="space-y-4">
            <div>
                <div class="flex justify-between text-sm mb-1">
                    <span class="text-gray-600">รอดำเนินการ</span>
                    <span class="font-medium"><?= $stats['open'] ?></span>
                </div>
                <div class="h-3 bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full bg-blue-400 rounded-full" style="width: <?= $totalTickets > 0 ? ($stats['open'] / $totalTickets * 100) : 0 ?>%"></div>
                </div>
            </div>
            <div>
                <div class="flex justify-between text-sm mb-1">
                    <span class="text-gray-600">กำลังดำเนินการ</span>
                    <span class="font-medium"><?= $stats['in-progress'] ?></span>
                </div>
                <div class="h-3 bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full bg-amber-400 rounded-full" style="width: <?= $totalTickets > 0 ? ($stats['in-progress'] / $totalTickets * 100) : 0 ?>%"></div>
                </div>
            </div>
            <div>
                <div class="flex justify-between text-sm mb-1">
                    <span class="text-gray-600">เสร็จสิ้น</span>
                    <span class="font-medium"><?= $stats['closed'] ?></span>
                </div>
                <div class="h-3 bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full bg-green-400 rounded-full" style="width: <?= $totalTickets > 0 ? ($stats['closed'] / $totalTickets * 100) : 0 ?>%"></div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Tickets -->
    <div class="bg-white rounded-xl border border-gray-100">
        <div class="p-5 border-b border-gray-100 flex justify-between items-center">
            <h3 class="text-lg font-semibold text-gray-800">Tickets ล่าสุด</h3>
            <a href="tickets.php" class="text-blue-400 hover:text-blue-500 text-sm font-medium">ดูทั้งหมด →</a>
        </div>
        <div class="p-5">
            <?php if (empty($recentTickets)): ?>
            <p class="text-gray-400 text-center py-4">ไม่มี Ticket</p>
            <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($recentTickets as $ticket): ?>
                <a href="tickets.php?id=<?= $ticket['id'] ?>" class="flex items-center justify-between p-3 rounded-lg hover:bg-gray-50 transition">
                    <div>
                        <p class="font-medium text-gray-700"><?= htmlspecialchars($ticket['title']) ?></p>
                        <p class="text-sm text-gray-400"><?= htmlspecialchars($ticket['user_name']) ?></p>
                    </div>
                    <?php
                    $sClass = match($ticket['status']) { 'open' => 'bg-blue-50 text-blue-600', 'in-progress' => 'bg-amber-50 text-amber-600', 'closed' => 'bg-green-50 text-green-600', default => '' };
                    $sText = match($ticket['status']) { 'open' => 'รอ', 'in-progress' => 'กำลัง', 'closed' => 'เสร็จ', default => $ticket['status'] };
                    ?>
                    <span class="px-2 py-1 rounded-full text-xs font-medium <?= $sClass ?>"><?= $sText ?></span>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
