<?php
$pageTitle = "Admin Dashboard";
require_once __DIR__ . '/includes/header.php';

// Fetch stats for the dashboard
$total_departments = $conn->query("SELECT COUNT(*) as count FROM departments")->fetch_assoc()['count'];
$active_departments = $conn->query("SELECT COUNT(*) as count FROM departments WHERE is_active = 1")->fetch_assoc()['count'];
$total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
?>

<div class="relative min-h-screen lg:flex">
    <?php
    $activePage = 'dashboard';
    require_once __DIR__ . '/includes/sidebar.php';
    ?>
    <main class="flex-1 p-6 bg-gray-100">
        <h1 class="text-2xl font-semibold text-gray-800">Dashboard Overview</h1>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mt-6">
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h3 class="text-gray-600">Total Departments</h3>
                <p class="text-3xl font-bold text-indigo-600"><?php echo $total_departments; ?></p>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h3 class="text-gray-600">Active Departments</h3>
                <p class="text-3xl font-bold text-green-600"><?php echo $active_departments; ?></p>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h3 class="text-gray-600">Total Users (All Roles)</h3>
                <p class="text-3xl font-bold text-red-600"><?php echo $total_users; ?></p>
            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>