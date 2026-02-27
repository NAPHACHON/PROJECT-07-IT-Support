<?php
require_once __DIR__ . '/../config/database.php';

if (!isLoggedIn() || !hasRole('admin')) {
    redirect('auth/login.php');
}

$db = getDB();

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $db->prepare("DELETE FROM users WHERE id = ? AND role = 'user'")->execute([$id]);
    setFlash('success', 'ลบผู้ใช้สำเร็จ');
    redirect('admin/users.php');
}

$users = $db->query("SELECT * FROM users WHERE role = 'user' ORDER BY created_at DESC")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="flex flex-wrap justify-between items-center gap-4 mb-6">
    <h1 class="text-2xl font-bold text-gray-800">จัดการผู้ใช้งาน</h1>
</div>

<div class="bg-white rounded-xl border border-gray-100">
    <div class="p-5">
        <?php if (empty($users)): ?>
        <div class="text-center py-12 text-gray-400">
            <i class="fas fa-users text-5xl mb-4 opacity-50"></i>
            <h3 class="text-gray-500 font-medium">ไม่มีผู้ใช้</h3>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="text-left text-xs font-semibold text-gray-400 uppercase tracking-wide border-b border-gray-100">
                        <th class="pb-3">#</th>
                        <th class="pb-3">ชื่อผู้ใช้</th>
                        <th class="pb-3">ชื่อ-นามสกุล</th>
                        <th class="pb-3">อีเมล</th>
                        <th class="pb-3">โทรศัพท์</th>
                        <th class="pb-3">วันที่สมัคร</th>
                        <th class="pb-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php foreach ($users as $user): ?>
                    <tr class="hover:bg-gray-50 transition">
                        <td class="py-4 text-gray-500"><?= $user['id'] ?></td>
                        <td class="py-4 font-medium text-gray-700"><?= htmlspecialchars($user['username']) ?></td>
                        <td class="py-4 text-gray-600"><?= htmlspecialchars($user['full_name']) ?></td>
                        <td class="py-4 text-gray-500"><?= htmlspecialchars($user['email']) ?></td>
                        <td class="py-4 text-gray-500"><?= htmlspecialchars($user['phone'] ?? '-') ?></td>
                        <td class="py-4 text-gray-500 text-sm"><?= date('d/m/Y', strtotime($user['created_at'])) ?></td>
                        <td class="py-4">
                            <a href="?delete=<?= $user['id'] ?>" onclick="return confirm('ต้องการลบผู้ใช้นี้?')" class="text-red-400 hover:text-red-500"><i class="fas fa-trash"></i></a>
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
