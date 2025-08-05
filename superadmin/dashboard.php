<?php
$pageTitle = "Superadmin Dashboard";
require_once __DIR__ . '/includes/header.php';

// Fetch stats for the dashboard
$total_departments = $conn->query("SELECT COUNT(*) as count FROM departments")->fetch_assoc()['count'];
$active_departments = $conn->query("SELECT COUNT(*) as count FROM departments WHERE is_active = 1")->fetch_assoc()['count'];
$total_superadmins = $conn->query("SELECT COUNT(*) as count FROM users WHERE role_id = (SELECT id FROM roles WHERE name = 'superadmin')")->fetch_assoc()['count'];

?>

<div class="relative min-h-screen lg:flex">
    <?php
    $activePage = 'dashboard';
    require_once __DIR__ . '/includes/sidebar.php';
    ?>

    <div id="sidebar-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-20 hidden lg:hidden"></div>

    <div class="flex-1 flex flex-col relative z-0">
        <header class="flex justify-between items-center p-4 bg-white border-b lg:hidden">
            <button id="sidebar-toggle" class="text-gray-500 focus:outline-none">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
            <h2 class="text-xl font-semibold text-gray-800">Dashboard</h2>
        </header>

        <main class="flex-1 bg-gray-100 p-6">
            <h2 class="text-2xl font-semibold text-gray-800 hidden lg:block">Dashboard Overview</h2>
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
                    <h3 class="text-gray-600">Super Administrators</h3>
                    <p class="text-3xl font-bold text-red-600"><?php echo $total_superadmins; ?></p>
                </div>
            </div>

            <div class="mt-8 bg-white p-6 rounded-lg shadow-md">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">System Management</h3>
                <p class="text-gray-600">Use the sidebar to navigate to the departments management section to add, edit, or update the status of academic departments.</p>
            </div>
        </main>
    </div>
</div>

<script src="/superadmin/includes/script.js"></script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>