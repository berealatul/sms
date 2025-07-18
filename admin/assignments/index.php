<?php
$pageTitle = "Assign Faculty";
require_once __DIR__ . '/../../includes/header.php';

// Authorization: Allow only admin and staff
if (!isset($_SESSION['user_role_name']) || !in_array($_SESSION['user_role_name'], ['admin', 'staff'])) {
    $_SESSION['error_message'] = "You are not authorized to view this page.";
    header('Location: /' . $_SESSION['user_role_name'] . '/');
    exit();
}

// Fetch active faculty members for the dropdown
$faculty_sql = "SELECT f.id, u.name 
                FROM faculty f 
                JOIN users u ON f.user_id = u.id 
                WHERE u.is_active = TRUE 
                ORDER BY u.name ASC";
$faculty_result = $conn->query($faculty_sql);
$faculties = $faculty_result->fetch_all(MYSQLI_ASSOC);

// Fetch association types for the dropdown
$assoc_types_sql = "SELECT id, name FROM association_types ORDER BY name ASC";
$assoc_types_result = $conn->query($assoc_types_sql);
$association_types = $assoc_types_result->fetch_all(MYSQLI_ASSOC);

// Get session messages
$success_message = $_SESSION['success_message'] ?? null;
$error_message = $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>

<div class="relative min-h-screen lg:flex">
    <?php
    // Include the correct sidebar based on the user's role
    $activePage = 'assign_faculty';
    if ($_SESSION['user_role_name'] === 'admin') {
        require_once __DIR__ . '/../../includes/admin_sidebar.php';
    } else {
        require_once __DIR__ . '/../../includes/staff_sidebar.php';
    }
    ?>

    <div id="sidebar-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-20 hidden lg:hidden"></div>

    <div class="flex-1 flex flex-col relative z-0">
        <header class="flex justify-between items-center p-4 bg-white border-b lg:hidden">
            <button id="sidebar-toggle" class="text-gray-500 focus:outline-none">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
            <h2 class="text-xl font-semibold text-gray-800">Assign Faculty</h2>
        </header>

        <main class="flex-1 bg-gray-100 p-6">
            <h2 class="text-2xl font-semibold text-gray-800 hidden lg:block">Assign Faculty to Students</h2>

            <div class="mt-6 bg-white p-8 rounded-lg shadow-md max-w-2xl mx-auto">
                <form action="/admin/assignments/process.php" method="POST">

                    <?php if ($success_message): ?>
                        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-md" role="alert">
                            <p><?php echo nl2br(htmlspecialchars($success_message)); ?></p>
                        </div>
                    <?php endif; ?>
                    <?php if ($error_message): ?>
                        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-md" role="alert">
                            <p><?php echo nl2br(htmlspecialchars($error_message)); ?></p>
                        </div>
                    <?php endif; ?>

                    <div class="space-y-6">
                        <div>
                            <label for="faculty_id" class="block text-sm font-medium text-gray-700">Select Faculty</label>
                            <select id="faculty_id" name="faculty_id" required class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm">
                                <option value="">-- Choose a Faculty Member --</option>
                                <?php foreach ($faculties as $faculty): ?>
                                    <option value="<?php echo $faculty['id']; ?>"><?php echo htmlspecialchars($faculty['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label for="roll_numbers" class="block text-sm font-medium text-gray-700">Student Roll Numbers</label>
                            <textarea id="roll_numbers" name="roll_numbers" rows="5" required class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm" placeholder="Enter roll numbers, separated by commas (e.g., CSM24001, CSM24004, ...)"></textarea>
                        </div>

                        <div>
                            <label for="association_type_id" class="block text-sm font-medium text-gray-700">Assign Role</label>
                            <select id="association_type_id" name="association_type_id" required class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm">
                                <option value="">-- Select a Role --</option>
                                <?php foreach ($association_types as $type): ?>
                                    <option value="<?php echo $type['id']; ?>"><?php echo htmlspecialchars($type['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mt-8 border-t pt-5">
                        <div class="flex justify-end">
                            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-lg">Assign Role</button>
                        </div>
                    </div>
                </form>
            </div>
        </main>
    </div>
</div>

<script src="/includes/sidebar_toggle_script.js"></script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>