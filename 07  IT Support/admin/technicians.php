<?php
require_once __DIR__ . '/../config/database.php';

if (!isLoggedIn() || !hasRole('admin')) {
    redirect('auth/login.php');
}

$db = getDB();
$error = '';

// Handle add technician
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_tech'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $full_name = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);
    $department = trim($_POST['department']);
    $specialization = trim($_POST['specialization']);
    
    // Check exists
    $stmt = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    if ($stmt->fetch()) {
        $error = 'ชื่อผู้ใช้หรืออีเมลมีอยู่แล้ว';
    } else {
        $db->beginTransaction();
        try {
            $stmt = $db->prepare("INSERT INTO users (username, email, password, full_name, phone, role) VALUES (?, ?, ?, ?, ?, 'technician')");
            $stmt->execute([$username, $email, $password, $full_name, $phone]);
            $userId = $db->lastInsertId();
            
            $stmt = $db->prepare("INSERT INTO technicians (user_id, department, specialization) VALUES (?, ?, ?)");
            $stmt->execute([$userId, $department, $specialization]);
            
            $db->commit();
            setFlash('success', 'เพิ่มช่างเทคนิคสำเร็จ');
            redirect('admin/technicians.php');
        } catch (Exception $e) {
            $db->rollBack();
            $error = 'เกิดข้อผิดพลาด';
        }
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $db->prepare("SELECT user_id FROM technicians WHERE id = ?");
    $stmt->execute([$id]);
    $tech = $stmt->fetch();
    if ($tech) {
        $db->prepare("DELETE FROM users WHERE id = ?")->execute([$tech['user_id']]);
    }
    setFlash('success', 'ลบช่างเทคนิคสำเร็จ');
    redirect('admin/technicians.php');
}

$technicians = $db->query("SELECT t.*, u.username, u.full_name, u.email, u.phone FROM technicians t JOIN users u ON t.user_id = u.id ORDER BY u.created_at DESC")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="flex flex-wrap justify-between items-center gap-4 mb-6">
    <h1 class="text-2xl font-bold text-gray-800">จัดการช่างเทคนิค</h1>
    <button onclick="openAddModal()" class="bg-blue-400 hover:bg-blue-500 text-white px-5 py-2.5 rounded-lg font-medium transition flex items-center gap-2">
        <i class="fas fa-plus"></i> เพิ่มช่าง
    </button>
</div>

<?php if ($error): ?>
<div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 flex items-center gap-3">
    <i class="fas fa-exclamation-circle"></i>
    <?= htmlspecialchars($error) ?>
</div>
<?php endif; ?>

<div class="bg-white rounded-xl border border-gray-100">
    <div class="p-5">
        <?php if (empty($technicians)): ?>
        <div class="text-center py-12 text-gray-400">
            <i class="fas fa-user-cog text-5xl mb-4 opacity-50"></i>
            <h3 class="text-gray-500 font-medium">ไม่มีช่างเทคนิค</h3>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="text-left text-xs font-semibold text-gray-400 uppercase tracking-wide border-b border-gray-100">
                        <th class="pb-3">#</th>
                        <th class="pb-3">ชื่อ-นามสกุล</th>
                        <th class="pb-3">อีเมล</th>
                        <th class="pb-3">แผนก</th>
                        <th class="pb-3">ความเชี่ยวชาญ</th>
                        <th class="pb-3">สถานะ</th>
                        <th class="pb-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php foreach ($technicians as $tech): ?>
                    <tr class="hover:bg-gray-50 transition">
                        <td class="py-4 text-gray-500"><?= $tech['id'] ?></td>
                        <td class="py-4 font-medium text-gray-700"><?= htmlspecialchars($tech['full_name']) ?></td>
                        <td class="py-4 text-gray-500"><?= htmlspecialchars($tech['email']) ?></td>
                        <td class="py-4 text-gray-500"><?= htmlspecialchars($tech['department'] ?? '-') ?></td>
                        <td class="py-4 text-gray-500"><?= htmlspecialchars($tech['specialization'] ?? '-') ?></td>
                        <td class="py-4">
                            <span class="px-2.5 py-1 rounded-full text-xs font-medium <?= $tech['is_available'] ? 'bg-green-50 text-green-600' : 'bg-gray-100 text-gray-500' ?>">
                                <?= $tech['is_available'] ? 'พร้อม' : 'ไม่ว่าง' ?>
                            </span>
                        </td>
                        <td class="py-4">
                            <a href="?delete=<?= $tech['id'] ?>" onclick="return confirm('ต้องการลบช่างนี้?')" class="text-red-400 hover:text-red-500"><i class="fas fa-trash"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Modal -->
<div id="addModal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center overflow-y-auto py-8">
    <div class="bg-white rounded-xl w-full max-w-lg mx-4 p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">เพิ่มช่างเทคนิค</h3>
        <form method="POST">
            <input type="hidden" name="add_tech" value="1">
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-1">ชื่อผู้ใช้</label>
                    <input type="text" name="username" class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 text-sm" required>
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-1">รหัสผ่าน</label>
                    <input type="password" name="password" class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 text-sm" required>
                </div>
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-medium mb-1">ชื่อ-นามสกุล</label>
                <input type="text" name="full_name" class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 text-sm" required>
            </div>
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-1">อีเมล</label>
                    <input type="email" name="email" class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 text-sm" required>
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-1">โทรศัพท์</label>
                    <input type="text" name="phone" class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 text-sm">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4 mb-5">
                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-1">แผนก</label>
                    <input type="text" name="department" class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 text-sm" placeholder="เช่น ฝ่ายซ่อมบำรุง">
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-1">ความเชี่ยวชาญ</label>
                    <input type="text" name="specialization" class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 text-sm" placeholder="เช่น เครือข่าย, ฮาร์ดแวร์">
                </div>
            </div>
            <div class="flex gap-3">
                <button type="submit" class="bg-blue-400 hover:bg-blue-500 text-white px-5 py-2.5 rounded-lg font-medium transition">เพิ่ม</button>
                <button type="button" onclick="closeAddModal()" class="bg-gray-100 hover:bg-gray-200 text-gray-600 px-5 py-2.5 rounded-lg font-medium transition">ยกเลิก</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddModal() {
    document.getElementById('addModal').classList.remove('hidden');
    document.getElementById('addModal').classList.add('flex');
}
function closeAddModal() {
    document.getElementById('addModal').classList.add('hidden');
    document.getElementById('addModal').classList.remove('flex');
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
