<?php
$pageTitle = "Student Dashboard";
require_once __DIR__ . '/../includes/header.php';

// Authorization check for student - This is now the correct place for this logic
if (!isset($_SESSION['user_role_name']) || $_SESSION['user_role_name'] !== 'student') {
    // If not a student, redirect to login, which will then send them to their correct dashboard
    header('Location: /index.php');
    exit();
}
?>

<div class="relative min-h-screen lg:flex">
    <?php
    // Include the student-specific sidebar
    $activePage = 'dashboard';
    require_once __DIR__ . '/../includes/student_sidebar.php';
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
            <h2 class="text-xl font-semibold text-gray-800">Student Dashboard</h2>
        </header>

        <main class="flex-1 bg-gray-100 p-6">
            <h2 class="text-2xl font-semibold text-gray-800">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h2>
            <p class="mt-2 text-gray-600">This is your dashboard. Student-specific features will be added here.</p>
        </main>
    </div>
</div>

<script src="/includes/sidebar_toggle_script.js"></script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>