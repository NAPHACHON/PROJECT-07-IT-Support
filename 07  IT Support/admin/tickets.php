<?php
require_once __DIR__ . '/../config/database.php';

if (!isLoggedIn() || !hasRole('admin')) {
    redirect('auth/login.php');
}

$db = getDB();

// Handle assign technician
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign'])) {
    $ticketId = (int)$_POST['ticket_id'];
    $techId = (int)$_POST['technician_id'];
    
    $stmt = $db->prepare("UPDATE tickets SET technician_id = ?, requested_technician_id = NULL, status = 'in-progress' WHERE id = ?");
    $stmt->execute([$techId, $ticketId]);
    
    $techName = $db->query("SELECT u.full_name FROM technicians t JOIN users u ON t.user_id = u.id WHERE t.id = $techId")->fetchColumn();
    $db->prepare("INSERT INTO ticket_comments (ticket_id, user_id, comment) VALUES (?, ?, ?)")
       ->execute([$ticketId, $_SESSION['user_id'], "มอบหมายงานให้: $techName"]);
    
    setFlash('success', 'มอบหมายงานสำเร็จ');
    redirect('admin/tickets.php');
}

// Get technicians
$technicians = $db->query("SELECT t.id, u.full_name, t.specialization FROM technicians t JOIN users u ON t.user_id = u.id WHERE t.is_available = 1")->fetchAll();

// Get tickets with requests
$query = "
    SELECT t.*, u.full_name as user_name, tu.full_name as technician_name,
           req_t.full_name as requester_name, t.requested_technician_id
    FROM tickets t
    JOIN users u ON t.user_id = u.id
    LEFT JOIN technicians tech ON t.technician_id = tech.id
    LEFT JOIN users tu ON tech.user_id = tu.id
    LEFT JOIN technicians rt ON t.requested_technician_id = rt.id
    LEFT JOIN users req_t ON rt.user_id = req_t.id
";

$statusFilter = $_GET['status'] ?? '';
if ($statusFilter && in_array($statusFilter, ['open', 'in-progress', 'closed'])) {
    $query .= " WHERE t.status = '$statusFilter'";
}
$query .= " ORDER BY t.requested_technician_id DESC, t.created_at DESC";

$tickets = $db->query($query)->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">จัดการ Tickets</h1>
</div>

<!-- Filter -->
<div class="bg-white rounded-xl border border-gray-100 p-4 mb-6">
    <div class="flex flex-wrap gap-2">
        <a href="tickets.php" class="px-4 py-2 rounded-lg text-sm font-medium transition <?= empty($statusFilter) ? 'bg-blue-400 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' ?>">ทั้งหมด</a>
        <a href="tickets.php?status=open" class="px-4 py-2 rounded-lg text-sm font-medium transition <?= $statusFilter === 'open' ? 'bg-blue-400 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' ?>">รอดำเนินการ</a>
        <a href="tickets.php?status=in-progress" class="px-4 py-2 rounded-lg text-sm font-medium transition <?= $statusFilter === 'in-progress' ? 'bg-blue-400 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' ?>">กำลังดำเนินการ</a>
        <a href="tickets.php?status=closed" class="px-4 py-2 rounded-lg text-sm font-medium transition <?= $statusFilter === 'closed' ? 'bg-blue-400 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' ?>">เสร็จสิ้น</a>
    </div>
</div>

<div class="bg-white rounded-xl border border-gray-100">
    <div class="p-5">
        <?php if (empty($tickets)): ?>
        <div class="text-center py-12 text-gray-400">
            <i class="fas fa-ticket-alt text-5xl mb-4 opacity-50"></i>
            <h3 class="text-gray-500 font-medium">ไม่พบ Ticket</h3>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="text-left text-xs font-semibold text-gray-400 uppercase tracking-wide border-b border-gray-100">
                        <th class="pb-3">#</th>
                        <th class="pb-3">หัวข้อ</th>
                        <th class="pb-3">ผู้แจ้ง</th>
                        <th class="pb-3">สถานะ</th>
                        <th class="pb-3">ช่างเทคนิค</th>
                        <th class="pb-3">คำขอรับงาน</th>
                        <th class="pb-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php foreach ($tickets as $ticket): ?>
                    <tr class="hover:bg-gray-50 transition <?= $ticket['requested_technician_id'] ? 'bg-blue-50/50' : '' ?>">
                        <td class="py-4 text-gray-500">#<?= $ticket['id'] ?></td>
                        <td class="py-4 font-medium text-gray-700"><?= htmlspecialchars($ticket['title']) ?></td>
                        <td class="py-4 text-gray-500"><?= htmlspecialchars($ticket['user_name']) ?></td>
                        <td class="py-4">
                            <?php
                            $sClass = match($ticket['status']) { 'open' => 'bg-blue-50 text-blue-600', 'in-progress' => 'bg-amber-50 text-amber-600', 'closed' => 'bg-green-50 text-green-600', default => '' };
                            $sText = match($ticket['status']) { 'open' => 'รอ', 'in-progress' => 'กำลัง', 'closed' => 'เสร็จ', default => $ticket['status'] };
                            ?>
                            <span class="px-2.5 py-1 rounded-full text-xs font-medium <?= $sClass ?>"><?= $sText ?></span>
                        </td>
                        <td class="py-4 text-gray-500"><?= $ticket['technician_name'] ?? '-' ?></td>
                        <td class="py-4">
                            <?php if ($ticket['requested_technician_id']): ?>
                                <span class="bg-blue-100 text-blue-700 px-2 py-1 rounded text-xs font-bold">
                                    <i class="fas fa-hand-paper mr-1"></i> <?= htmlspecialchars($ticket['requester_name']) ?> ขอรับงาน
                                </span>
                            <?php else: ?>
                                <span class="text-gray-300">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="py-4 text-right">
                            <a href="<?= BASE_URL ?>user/view_ticket.php?id=<?= $ticket['id'] ?>" class="text-gray-400 hover:text-blue-500 mr-2"><i class="fas fa-eye"></i></a>
                            <?php if ($ticket['status'] === 'open'): ?>
                            <button onclick="openAssignModal(<?= $ticket['id'] ?>, '<?= htmlspecialchars($ticket['title']) ?>', <?= $ticket['requested_technician_id'] ?? 'null' ?>)" class="text-green-500 hover:text-green-600 font-medium">
                                <i class="fas fa-user-plus"></i> จ่ายงาน
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Assign Modal -->
<div id="assignModal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center">
    <div class="bg-white rounded-xl w-full max-w-md mx-4 p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">มอบหมายงาน</h3>
        <p class="text-gray-500 mb-4" id="assignTicketTitle"></p>
        <form method="POST">
            <input type="hidden" name="assign" value="1">
            <input type="hidden" name="ticket_id" id="assignTicketId">
            <div class="mb-5">
                <label class="block text-gray-700 font-medium mb-2">เลือกช่างเทคนิค</label>
                <select name="technician_id" id="techSelect" class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 bg-white" required>
                    <option value="">-- เลือกช่าง --</option>
                    <?php foreach ($technicians as $tech): ?>
                    <option value="<?= $tech['id'] ?>"><?= htmlspecialchars($tech['full_name']) ?> (<?= htmlspecialchars($tech['specialization'] ?? '-') ?>)</option>
                    <?php endforeach; ?>
                </select>
                <p id="requestHint" class="hidden text-blue-500 text-sm mt-2"><i class="fas fa-info-circle"></i> ช่างคนนี้ขอรับงานนี้ไว้</p>
            </div>
            <div class="flex gap-3">
                <button type="submit" class="bg-blue-400 hover:bg-blue-500 text-white px-5 py-2.5 rounded-lg font-medium transition">ยืนยัน</button>
                <button type="button" onclick="closeAssignModal()" class="bg-gray-100 hover:bg-gray-200 text-gray-600 px-5 py-2.5 rounded-lg font-medium transition">ยกเลิก</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAssignModal(id, title, requestedTechId) {
    document.getElementById('assignTicketId').value = id;
    document.getElementById('assignTicketTitle').textContent = 'Ticket #' + id + ': ' + title;
    
    const select = document.getElementById('techSelect');
    const hint = document.getElementById('requestHint');
    
    if (requestedTechId) {
        select.value = requestedTechId;
        hint.classList.remove('hidden');
    } else {
        select.value = "";
        hint.classList.add('hidden');
    }
    
    document.getElementById('assignModal').classList.remove('hidden');
    document.getElementById('assignModal').classList.add('flex');
}
function closeAssignModal() {
    document.getElementById('assignModal').classList.add('hidden');
    document.getElementById('assignModal').classList.remove('flex');
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
