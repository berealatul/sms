<?php
$pageTitle = "Manage Departments";
require_once __DIR__ . '/../includes/header.php';

// Fetch all departments
$departments_sql = "SELECT * FROM departments ORDER BY department_name ASC";
$departments_result = $conn->query($departments_sql);

$success_message = $_SESSION['success_message'] ?? null;
$error_message = $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>

<div class="relative min-h-screen lg:flex">
    <?php
    $activePage = 'departments';
    require_once __DIR__ . '/../includes/sidebar.php';
    ?>
    <div id="sidebar-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-20 hidden lg:hidden"></div>
    <div class="flex-1 flex flex-col relative z-0">
        <header class="flex justify-between items-center p-4 bg-white border-b lg:hidden">
            <button id="sidebar-toggle" class="text-gray-500 focus:outline-none"><svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg></button>
            <h2 class="text-xl font-semibold text-gray-800">Manage Departments</h2>
        </header>

        <main class="flex-1 bg-gray-100 p-6">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-semibold text-gray-800 hidden lg:block">Manage Departments</h2>
                <button id="add-department-btn" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-lg shadow-md">
                    Add New Department
                </button>
            </div>

            <?php if ($success_message): ?>
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded-md" role="alert">
                    <p><?php echo htmlspecialchars($success_message); ?></p>
                </div>
            <?php endif; ?>
            <?php if ($error_message): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded-md" role="alert">
                    <p><?php echo htmlspecialchars($error_message); ?></p>
                </div>
            <?php endif; ?>

            <div class="bg-white p-6 rounded-lg shadow-md overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Department</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">HOD</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">DB Name</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if ($departments_result->num_rows > 0): ?>
                            <?php while ($dept = $departments_result->fetch_assoc()): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($dept['department_name']); ?> (<?php echo htmlspecialchars($dept['department_code']); ?>)</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($dept['hod_name']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($dept['db_name']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $dept['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                            <?php echo $dept['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button class="text-indigo-600 hover:text-indigo-900 edit-btn"
                                            data-id="<?php echo $dept['id']; ?>"
                                            data-name="<?php echo htmlspecialchars($dept['department_name']); ?>"
                                            data-code="<?php echo htmlspecialchars($dept['department_code']); ?>"
                                            data-hod-name="<?php echo htmlspecialchars($dept['hod_name']); ?>"
                                            data-hod-email="<?php echo htmlspecialchars($dept['hod_email']); ?>"
                                            data-db-name="<?php echo htmlspecialchars($dept['db_name']); ?>">Edit</button>
                                        <a href="/superadmin/departments/process.php?action=toggle_status&id=<?php echo $dept['id']; ?>" class="ml-4 text-gray-600 hover:text-gray-900" onclick="return confirm('Are you sure you want to toggle the status of this department?');">
                                            <?php echo $dept['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">No departments found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</div>

<div id="add-modal" class="fixed z-50 inset-0 overflow-y-auto hidden">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity" aria-hidden="true">
            <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
        </div>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form action="/superadmin/departments/process.php" method="POST">
                <input type="hidden" name="action" value="add">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Add New Department</h3>
                    <div class="space-y-4">
                        <div><label for="add_department_name" class="block text-sm font-medium text-gray-700">Department Name</label><input type="text" name="department_name" id="add_department_name" required class="mt-1 block w-full p-2 border rounded-md"></div>
                        <div><label for="add_department_code" class="block text-sm font-medium text-gray-700">Department Code (e.g., CSE, ECE)</label><input type="text" name="department_code" id="add_department_code" required class="mt-1 block w-full p-2 border rounded-md"></div>
                        <div><label for="add_hod_name" class="block text-sm font-medium text-gray-700">HOD Name</label><input type="text" name="hod_name" id="add_hod_name" required class="mt-1 block w-full p-2 border rounded-md"></div>
                        <div><label for="add_hod_email" class="block text-sm font-medium text-gray-700">HOD Email</label><input type="email" name="hod_email" id="add_hod_email" required class="mt-1 block w-full p-2 border rounded-md"></div>
                        <div><label for="add_db_name" class="block text-sm font-medium text-gray-700">Database Name (e.g., sms_cse)</label><input type="text" name="db_name" id="add_db_name" required class="mt-1 block w-full p-2 border rounded-md"></div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 font-medium text-white hover:bg-indigo-700 sm:ml-3 sm:w-auto sm:text-sm">Save</button>
                    <button type="button" id="add-cancel-btn" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:w-auto sm:text-sm">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="edit-modal" class="fixed z-50 inset-0 overflow-y-auto hidden">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity" aria-hidden="true">
            <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
        </div>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form action="/superadmin/departments/process.php" method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Edit Department</h3>
                    <div class="space-y-4">
                        <div><label for="edit_department_name" class="block text-sm font-medium text-gray-700">Department Name</label><input type="text" name="department_name" id="edit_department_name" required class="mt-1 block w-full p-2 border rounded-md"></div>
                        <div><label for="edit_department_code" class="block text-sm font-medium text-gray-700">Department Code</label><input type="text" name="department_code" id="edit_department_code" required class="mt-1 block w-full p-2 border rounded-md"></div>
                        <div><label for="edit_hod_name" class="block text-sm font-medium text-gray-700">HOD Name</label><input type="text" name="hod_name" id="edit_hod_name" required class="mt-1 block w-full p-2 border rounded-md"></div>
                        <div><label for="edit_hod_email" class="block text-sm font-medium text-gray-700">HOD Email</label><input type="email" name="hod_email" id="edit_hod_email" required class="mt-1 block w-full p-2 border rounded-md"></div>
                        <div><label for="edit_db_name" class="block text-sm font-medium text-gray-700">Database Name</label><input type="text" name="db_name" id="edit_db_name" required class="mt-1 block w-full p-2 border rounded-md"></div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 font-medium text-white hover:bg-indigo-700 sm:ml-3 sm:w-auto sm:text-sm">Update</button>
                    <button type="button" id="edit-cancel-btn" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:w-auto sm:text-sm">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>
<button id="edit-department-btn-placeholder" class="hidden"></button>
<script src="/superadmin/includes/script.js"></script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>