<?php
require_once __DIR__ . '/../config/database.php';

if (!isLoggedIn() || hasRole('admin')) {
    redirect('auth/login.php');
}

$db = getDB();
$userId = $_SESSION['user_id'];

// Get user's tickets stats
$stmt = $db->prepare("SELECT status, COUNT(*) as count FROM tickets WHERE user_id = ? GROUP BY status");
$stmt->execute([$userId]);
$statsRaw = $stmt->fetchAll();

$stats = ['open' => 0, 'in-progress' => 0, 'closed' => 0];
foreach ($statsRaw as $s) {
    $stats[$s['status']] = $s['count'];
}

// Get recent tickets
$stmt = $db->prepare("
    SELECT t.*, tech.id as tech_id, u.full_name as technician_name
    FROM tickets t
    LEFT JOIN technicians tech ON t.technician_id = tech.id
    LEFT JOIN users u ON tech.user_id = u.id
    WHERE t.user_id = ?
    ORDER BY t.created_at DESC
    LIMIT 5
");
$stmt->execute([$userId]);
$recentTickets = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="flex flex-wrap justify-between items-center gap-4 mb-8">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">สวัสดี, <?= htmlspecialchars($_SESSION['full_name']) ?></h1>
        <p class="text-gray-400">ยินดีต้อนรับสู่ระบบแจ้งปัญหา IT</p>
    </div>
    <a href="create_ticket.php" class="bg-blue-400 hover:bg-blue-500 text-white px-5 py-2.5 rounded-lg font-medium transition flex items-center gap-2">
        <i class="fas fa-plus"></i> แจ้งปัญหาใหม่
    </a>
</div>

<!-- Stats -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-xl border border-gray-100 p-5 flex items-center gap-4 hover:shadow-md transition">
        <div class="w-14 h-14 bg-blue-50 rounded-lg flex items-center justify-center">
            <i class="fas fa-ticket-alt text-xl text-blue-400"></i>
        </div>
        <div>
            <h3 class="text-2xl font-bold text-gray-800"><?= array_sum($stats) ?></h3>
            <p class="text-gray-400 text-sm">Tickets ทั้งหมด</p>
        </div>
    </div>
    
    <div class="bg-white rounded-xl border border-gray-100 p-5 flex items-center gap-4 hover:shadow-md transition">
        <div class="w-14 h-14 bg-amber-50 rounded-lg flex items-center justify-center">
            <i class="fas fa-exclamation-circle text-xl text-amber-400"></i>
        </div>
        <div>
            <h3 class="text-2xl font-bold text-gray-800"><?= $stats['open'] ?></h3>
            <p class="text-gray-400 text-sm">รอดำเนินการ</p>
        </div>
    </div>
    
    <div class="bg-white rounded-xl border border-gray-100 p-5 flex items-center gap-4 hover:shadow-md transition">
        <div class="w-14 h-14 bg-indigo-50 rounded-lg flex items-center justify-center">
            <i class="fas fa-spinner text-xl text-indigo-400"></i>
        </div>
        <div>
            <h3 class="text-2xl font-bold text-gray-800"><?= $stats['in-progress'] ?></h3>
            <p class="text-gray-400 text-sm">กำลังดำเนินการ</p>
        </div>
    </div>
    
    <div class="bg-white rounded-xl border border-gray-100 p-5 flex items-center gap-4 hover:shadow-md transition">
        <div class="w-14 h-14 bg-green-50 rounded-lg flex items-center justify-center">
            <i class="fas fa-check-circle text-xl text-green-400"></i>
        </div>
        <div>
            <h3 class="text-2xl font-bold text-gray-800"><?= $stats['closed'] ?></h3>
            <p class="text-gray-400 text-sm">เสร็จสิ้น</p>
        </div>
    </div>
</div>

<!-- Recent Tickets -->
<div class="bg-white rounded-xl border border-gray-100">
    <div class="flex justify-between items-center p-5 border-b border-gray-100">
        <h3 class="text-lg font-semibold text-gray-800 flex items-center gap-2">
            <i class="fas fa-history text-gray-400"></i> Tickets ล่าสุด
        </h3>
        <a href="my_tickets.php" class="text-blue-400 hover:text-blue-500 text-sm font-medium">ดูทั้งหมด →</a>
    </div>
    <div class="p-5">
        <?php if (empty($recentTickets)): ?>
        <div class="text-center py-12 text-gray-400">
            <i class="fas fa-ticket-alt text-5xl mb-4 opacity-50"></i>
            <h3 class="text-gray-500 font-medium mb-2">ยังไม่มี Ticket</h3>
            <p class="mb-4">คุณยังไม่เคยแจ้งปัญหา IT</p>
            <a href="create_ticket.php" class="inline-flex items-center gap-2 bg-blue-400 hover:bg-blue-500 text-white px-5 py-2.5 rounded-lg font-medium transition">
                <i class="fas fa-plus"></i> แจ้งปัญหาใหม่
            </a>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="text-left text-xs font-semibold text-gray-400 uppercase tracking-wide">
                        <th class="pb-3">#</th>
                        <th class="pb-3">หัวข้อ</th>
                        <th class="pb-3">หมวดหมู่</th>
                        <th class="pb-3">สถานะ</th>
                        <th class="pb-3">วันที่แจ้ง</th>
                        <th class="pb-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php foreach ($recentTickets as $ticket): ?>
                    <tr class="hover:bg-gray-50 transition">
                        <td class="py-3 text-gray-500">#<?= $ticket['id'] ?></td>
                        <td class="py-3 font-medium text-gray-700"><?= htmlspecialchars($ticket['title']) ?></td>
                        <td class="py-3 text-gray-500"><?= htmlspecialchars($ticket['category'] ?? '-') ?></td>
                        <td class="py-3">
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
                            <span class="px-2.5 py-1 rounded-full text-xs font-medium <?= $statusClass ?>"><?= $statusText ?></span>
                        </td>
                        <td class="py-3 text-gray-500 text-sm"><?= date('d/m/Y H:i', strtotime($ticket['created_at'])) ?></td>
                        <td class="py-3">
                            <a href="view_ticket.php?id=<?= $ticket['id'] ?>" class="text-blue-400 hover:text-blue-500">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
