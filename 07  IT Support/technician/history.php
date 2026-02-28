<?php
require_once __DIR__ . '/../config/database.php';

if (!isLoggedIn() || !hasRole('technician')) {
    redirect('auth/login.php');
}

$db = getDB();

$stmt = $db->prepare("SELECT id FROM technicians WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$tech = $stmt->fetch();
$techId = $tech['id'] ?? 0;

$stmt = $db->prepare("SELECT t.*, u.full_name as user_name FROM tickets t JOIN users u ON t.user_id = u.id WHERE t.technician_id = ? AND t.status = 'closed' ORDER BY t.closed_at DESC");
$stmt->execute([$techId]);
$tickets = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="mb-8">
    <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
        <i class="fas fa-history text-gray-400"></i> ประวัติการแก้ไข
    </h1>
    <p class="text-gray-400">Tickets ที่เสร็จสิ้นแล้ว</p>
</div>

<div class="bg-white rounded-xl border border-gray-100">
    <div class="p-5">
        <?php if (empty($tickets)): ?>
        <div class="text-center py-12 text-gray-400">
            <i class="fas fa-history text-5xl mb-4 opacity-50"></i>
            <h3 class="text-gray-500 font-medium">ยังไม่มีประวัติ</h3>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="text-left text-xs font-semibold text-gray-400 uppercase tracking-wide border-b border-gray-100">
                        <th class="pb-3">#</th>
                        <th class="pb-3">หัวข้อ</th>
                        <th class="pb-3">ผู้แจ้ง</th>
                        <th class="pb-3">วันที่แจ้ง</th>
                        <th class="pb-3">วันที่ปิด</th>
                        <th class="pb-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php foreach ($tickets as $ticket): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="py-4 text-gray-500">#<?= $ticket['id'] ?></td>
                        <td class="py-4 font-medium text-gray-700"><?= htmlspecialchars($ticket['title']) ?></td>
                        <td class="py-4 text-gray-500"><?= htmlspecialchars($ticket['user_name']) ?></td>
                        <td class="py-4 text-gray-500 text-sm"><?= date('d/m/Y H:i', strtotime($ticket['created_at'])) ?></td>
                        <td class="py-4 text-gray-500 text-sm"><?= $ticket['closed_at'] ? date('d/m/Y H:i', strtotime($ticket['closed_at'])) : '-' ?></td>
                        <td class="py-4">
                            <a href="<?= BASE_URL ?>user/view_ticket.php?id=<?= $ticket['id'] ?>" class="text-blue-400 hover:text-blue-500"><i class="fas fa-eye"></i></a>
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
