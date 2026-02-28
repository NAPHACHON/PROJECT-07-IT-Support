<?php
require_once __DIR__ . '/../config/database.php';

if (!isLoggedIn() || hasRole('admin')) {
    redirect('auth/login.php');
}

$db = getDB();
$userId = $_SESSION['user_id'];
$statusFilter = $_GET['status'] ?? '';

$query = "SELECT t.*, tech.id as tech_id, u.full_name as technician_name FROM tickets t LEFT JOIN technicians tech ON t.technician_id = tech.id LEFT JOIN users u ON tech.user_id = u.id WHERE t.user_id = ?";
$params = [$userId];

if ($statusFilter && in_array($statusFilter, ['open', 'in-progress', 'closed'])) {
    $query .= " AND t.status = ?";
    $params[] = $statusFilter;
}
$query .= " ORDER BY t.created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$tickets = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="flex flex-wrap justify-between items-center gap-4 mb-6">
    <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
        <i class="fas fa-list text-gray-400"></i> Tickets ของฉัน
    </h1>
    <a href="create_ticket.php" class="bg-blue-400 hover:bg-blue-500 text-white px-5 py-2.5 rounded-lg font-medium transition flex items-center gap-2">
        <i class="fas fa-plus"></i> แจ้งปัญหาใหม่
    </a>
</div>

<!-- Filter -->
<div class="bg-white rounded-xl border border-gray-100 p-4 mb-6">
    <div class="flex flex-wrap gap-2">
        <a href="my_tickets.php" class="px-4 py-2 rounded-lg text-sm font-medium transition <?= empty($statusFilter) ? 'bg-blue-400 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' ?>">ทั้งหมด</a>
        <a href="my_tickets.php?status=open" class="px-4 py-2 rounded-lg text-sm font-medium transition <?= $statusFilter === 'open' ? 'bg-blue-400 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' ?>">รอดำเนินการ</a>
        <a href="my_tickets.php?status=in-progress" class="px-4 py-2 rounded-lg text-sm font-medium transition <?= $statusFilter === 'in-progress' ? 'bg-blue-400 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' ?>">กำลังดำเนินการ</a>
        <a href="my_tickets.php?status=closed" class="px-4 py-2 rounded-lg text-sm font-medium transition <?= $statusFilter === 'closed' ? 'bg-blue-400 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' ?>">เสร็จสิ้น</a>
    </div>
</div>

<div class="bg-white rounded-xl border border-gray-100">
    <div class="p-5">
        <?php if (empty($tickets)): ?>
        <div class="text-center py-12 text-gray-400">
            <i class="fas fa-ticket-alt text-5xl mb-4 opacity-50"></i>
            <h3 class="text-gray-500 font-medium mb-2">ไม่พบ Ticket</h3>
            <p>ไม่มี Ticket ที่ตรงกับตัวกรอง</p>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="text-left text-xs font-semibold text-gray-400 uppercase tracking-wide border-b border-gray-100">
                        <th class="pb-3">#</th>
                        <th class="pb-3">หัวข้อ</th>
                        <th class="pb-3">หมวดหมู่</th>
                        <th class="pb-3">ความเร่งด่วน</th>
                        <th class="pb-3">สถานะ</th>
                        <th class="pb-3">ช่างเทคนิค</th>
                        <th class="pb-3">วันที่แจ้ง</th>
                        <th class="pb-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php foreach ($tickets as $ticket): ?>
                    <tr class="hover:bg-gray-50 transition">
                        <td class="py-4 text-gray-500">#<?= $ticket['id'] ?></td>
                        <td class="py-4 font-medium text-gray-700"><?= htmlspecialchars($ticket['title']) ?></td>
                        <td class="py-4 text-gray-500"><?= htmlspecialchars($ticket['category'] ?? '-') ?></td>
                        <td class="py-4">
                            <?php
                            $pClass = match($ticket['priority']) { 'high' => 'bg-red-50 text-red-600', 'medium' => 'bg-amber-50 text-amber-600', 'low' => 'bg-gray-100 text-gray-600', default => '' };
                            $pText = match($ticket['priority']) { 'high' => 'สูง', 'medium' => 'ปานกลาง', 'low' => 'ต่ำ', default => $ticket['priority'] };
                            ?>
                            <span class="px-2.5 py-1 rounded-full text-xs font-medium <?= $pClass ?>"><?= $pText ?></span>
                        </td>
                        <td class="py-4">
                            <?php
                            $sClass = match($ticket['status']) { 'open' => 'bg-blue-50 text-blue-600', 'in-progress' => 'bg-amber-50 text-amber-600', 'closed' => 'bg-green-50 text-green-600', default => '' };
                            $sText = match($ticket['status']) { 'open' => 'รอดำเนินการ', 'in-progress' => 'กำลังดำเนินการ', 'closed' => 'เสร็จสิ้น', default => $ticket['status'] };
                            ?>
                            <span class="px-2.5 py-1 rounded-full text-xs font-medium <?= $sClass ?>"><?= $sText ?></span>
                        </td>
                        <td class="py-4 text-gray-500"><?= $ticket['technician_name'] ? htmlspecialchars($ticket['technician_name']) : '-' ?></td>
                        <td class="py-4 text-gray-500 text-sm"><?= date('d/m/Y H:i', strtotime($ticket['created_at'])) ?></td>
                        <td class="py-4">
                            <a href="view_ticket.php?id=<?= $ticket['id'] ?>" class="inline-flex items-center gap-1 text-blue-400 hover:text-blue-500 font-medium text-sm">
                                <i class="fas fa-eye"></i> ดู
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
