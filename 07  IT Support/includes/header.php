<?php
require_once __DIR__ . '/../config/database.php';

$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IT Support Ticket System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Sarabun', 'sans-serif'],
                    },
                    colors: {
                        primary: {
                            50: '#EBF4FF',
                            100: '#D6E8FF',
                            200: '#B3D4FF',
                            300: '#8FB3DC',
                            400: '#6B9BD1',
                            500: '#5080B8',
                            600: '#3D6A9E',
                            700: '#2D5282',
                            800: '#1F3A5F',
                            900: '#12243D',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Sarabun', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="flex min-h-screen">
        <?php if (isLoggedIn()): ?>
        <!-- Sidebar -->
        <aside class="w-64 bg-white border-r border-gray-200 fixed left-0 top-0 bottom-0 overflow-y-auto z-40">
            <div class="p-6">
                <!-- Brand -->
                <div class="flex items-center gap-3 pb-6 border-b border-gray-200">
                    <i class="fas fa-headset text-3xl text-primary-400"></i>
                    <div>
                        <h2 class="text-lg font-bold text-gray-800">IT Support</h2>
                        <span class="text-xs text-gray-400">Ticket System</span>
                    </div>
                </div>
                
                <?php if (hasRole('admin')): ?>
                <!-- Admin Menu -->
                <p class="mt-6 mb-2 text-xs font-semibold text-gray-400 uppercase tracking-wide">แอดมิน</p>
                <nav class="space-y-1">
                    <a href="<?= BASE_URL ?>admin/dashboard.php" class="flex items-center gap-3 px-4 py-3 rounded-lg transition <?= $currentPage === 'dashboard' && strpos($_SERVER['PHP_SELF'], 'admin') ? 'bg-primary-400 text-white' : 'text-gray-600 hover:bg-gray-100' ?>">
                        <i class="fas fa-chart-pie w-5 text-center"></i> แดชบอร์ด
                    </a>
                    <a href="<?= BASE_URL ?>admin/tickets.php" class="flex items-center gap-3 px-4 py-3 rounded-lg transition <?= $currentPage === 'tickets' ? 'bg-primary-400 text-white' : 'text-gray-600 hover:bg-gray-100' ?>">
                        <i class="fas fa-ticket-alt w-5 text-center"></i> จัดการ Tickets
                    </a>
                    <a href="<?= BASE_URL ?>admin/users.php" class="flex items-center gap-3 px-4 py-3 rounded-lg transition <?= $currentPage === 'users' ? 'bg-primary-400 text-white' : 'text-gray-600 hover:bg-gray-100' ?>">
                        <i class="fas fa-users w-5 text-center"></i> จัดการผู้ใช้
                    </a>
                    <a href="<?= BASE_URL ?>admin/technicians.php" class="flex items-center gap-3 px-4 py-3 rounded-lg transition <?= $currentPage === 'technicians' ? 'bg-primary-400 text-white' : 'text-gray-600 hover:bg-gray-100' ?>">
                        <i class="fas fa-user-cog w-5 text-center"></i> จัดการช่าง
                    </a>
                </nav>
                
                <?php elseif (hasRole('technician')): ?>
                <!-- Technician Menu -->
                <p class="mt-6 mb-2 text-xs font-semibold text-gray-400 uppercase tracking-wide">ช่างเทคนิค</p>
                <nav class="space-y-1">
                    <a href="<?= BASE_URL ?>technician/dashboard.php" class="flex items-center gap-3 px-4 py-3 rounded-lg transition <?= $currentPage === 'dashboard' ? 'bg-primary-400 text-white' : 'text-gray-600 hover:bg-gray-100' ?>">
                        <i class="fas fa-tasks w-5 text-center"></i> งานของฉัน
                    </a>
                    <a href="<?= BASE_URL ?>technician/history.php" class="flex items-center gap-3 px-4 py-3 rounded-lg transition <?= $currentPage === 'history' ? 'bg-primary-400 text-white' : 'text-gray-600 hover:bg-gray-100' ?>">
                        <i class="fas fa-history w-5 text-center"></i> ประวัติการแก้ไข
                    </a>
                </nav>
                
                <?php else: ?>
                <!-- User Menu -->
                <p class="mt-6 mb-2 text-xs font-semibold text-gray-400 uppercase tracking-wide">เมนูหลัก</p>
                <nav class="space-y-1">
                    <a href="<?= BASE_URL ?>user/dashboard.php" class="flex items-center gap-3 px-4 py-3 rounded-lg transition <?= $currentPage === 'dashboard' ? 'bg-primary-400 text-white' : 'text-gray-600 hover:bg-gray-100' ?>">
                        <i class="fas fa-home w-5 text-center"></i> หน้าหลัก
                    </a>
                    <a href="<?= BASE_URL ?>user/create_ticket.php" class="flex items-center gap-3 px-4 py-3 rounded-lg transition <?= $currentPage === 'create_ticket' ? 'bg-primary-400 text-white' : 'text-gray-600 hover:bg-gray-100' ?>">
                        <i class="fas fa-plus-circle w-5 text-center"></i> แจ้งปัญหาใหม่
                    </a>
                    <a href="<?= BASE_URL ?>user/my_tickets.php" class="flex items-center gap-3 px-4 py-3 rounded-lg transition <?= $currentPage === 'my_tickets' ? 'bg-primary-400 text-white' : 'text-gray-600 hover:bg-gray-100' ?>">
                        <i class="fas fa-list w-5 text-center"></i> Tickets ของฉัน
                    </a>
                </nav>
                <?php endif; ?>
                
                <p class="mt-6 mb-2 text-xs font-semibold text-gray-400 uppercase tracking-wide">บัญชี</p>
                <nav class="space-y-1">
                    <a href="<?= BASE_URL ?>auth/logout.php" class="flex items-center gap-3 px-4 py-3 rounded-lg text-gray-600 hover:bg-red-50 hover:text-red-500 transition">
                        <i class="fas fa-sign-out-alt w-5 text-center"></i> ออกจากระบบ
                    </a>
                </nav>
            </div>
        </aside>
        <?php endif; ?>
        
        <!-- Main Content -->
        <main class="flex-1 p-8 <?= isLoggedIn() ? 'ml-64' : '' ?>">
            <?php if ($flash): ?>
            <div class="mb-6 px-4 py-3 rounded-lg flex items-center gap-3 <?= $flash['type'] === 'success' ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200' ?>">
                <i class="fas fa-<?= $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
                <?= htmlspecialchars($flash['message']) ?>
            </div>
            <?php endif; ?>
