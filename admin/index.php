<?php
$pageTitle = "Admin Dashboard";
require_once __DIR__ . '/../includes/header.php';

// Authorization check for admin
if (!isset($_SESSION['user_role_name']) || $_SESSION['user_role_name'] !== 'admin') {
    header('Location: /index.php');
    exit();
}
?>

<div class="relative min-h-screen lg:flex">
    <?php
    // Include the admin-specific sidebar
    $activePage = 'dashboard';
    require_once __DIR__ . '/../includes/admin_sidebar.php';
    ?>

    <!-- Sidebar Overlay -->
    <div id="sidebar-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-20 hidden lg:hidden"></div>

    <!-- Main Content -->
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
                    <h3 class="text-gray-600">Total Students</h3>
                    <p class="text-3xl font-bold text-indigo-600">5</p>
                </div>
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <h3 class="text-gray-600">Total Faculty</h3>
                    <p class="text-3xl font-bold text-indigo-600">2</p>
                </div>
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <h3 class="text-gray-600">Active Programs</h3>
                    <p class="text-3xl font-bold text-indigo-600">3</p>
                </div>
            </div>
            <div class="mt-8 grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Student Enrollment Trends</h3>
                    <div class="h-64"><canvas id="enrollmentChart"></canvas></div>
                </div>
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Recent User Activity</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm text-left text-gray-500">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3">User</th>
                                    <th scope="col" class="px-6 py-3">Role</th>
                                    <th scope="col" class="px-6 py-3">Last Login</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="bg-white border-b">
                                    <td class="px-6 py-4 font-medium text-gray-900">Admin User</td>
                                    <td class="px-6 py-4">admin</td>
                                    <td class="px-6 py-4">Just now</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="/includes/sidebar_toggle_script.js"></script>
<script>
    const ctx = document.getElementById('enrollmentChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            datasets: [{
                label: 'New Students',
                data: [12, 19, 3, 5, 2, 3],
                backgroundColor: 'rgba(79, 70, 229, 0.2)',
                borderColor: 'rgba(79, 70, 229, 1)',
                borderWidth: 2,
                tension: 0.4
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true
                }
            },
            responsive: true,
            maintainAspectRatio: false
        }
    });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>