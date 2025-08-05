<?php
$pageTitle = "Manage Departments";
require_once __DIR__ . '/../includes/header.php';

// Fetch all departments and their HODs
$sql = "SELECT d.id, d.name, d.code, d.is_active, u.name as hod_name, u.email as hod_email
        FROM departments d
        LEFT JOIN users u ON d.id = u.department_id AND u.role = 'hod'
        ORDER BY d.name ASC";
$departments_result = $conn->query($sql);

$success_message = $_SESSION['success_message'] ?? null;
$error_message = $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>

<div class="relative min-h-screen lg:flex">
    <?php
    $activePage = 'departments';
    require_once __DIR__ . '/../includes/sidebar.php';
    ?>
    <main class="flex-1 p-6 bg-gray-100">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-semibold text-gray-800">Manage Departments</h1>
            <button id="add-department-btn" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-lg shadow-md">
                Add New Department
            </button>
        </div>

        <?php if ($success_message): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded-md" role="alert">
                <p><?php echo htmlspecialchars($success_message, ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded-md" role="alert">
                <p><?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
        <?php endif; ?>

        <div class="bg-white p-6 rounded-lg shadow-md overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Department</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">HOD</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if ($departments_result->num_rows > 0): ?>
                        <?php while ($dept = $departments_result->fetch_assoc()): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($dept['name'], ENT_QUOTES, 'UTF-8'); ?> (<?php echo htmlspecialchars($dept['code'], ENT_QUOTES, 'UTF-8'); ?>)</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($dept['hod_name'] ?? 'Not Assigned', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $dept['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                        <?php echo $dept['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button class="text-indigo-600 hover:text-indigo-900 edit-btn"
                                        data-id="<?php echo $dept['id']; ?>"
                                        data-name="<?php echo htmlspecialchars($dept['name'], ENT_QUOTES, 'UTF-8'); ?>"
                                        data-code="<?php echo htmlspecialchars($dept['code'], ENT_QUOTES, 'UTF-8'); ?>"
                                        data-hod-name="<?php echo htmlspecialchars($dept['hod_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                        data-hod-email="<?php echo htmlspecialchars($dept['hod_email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">Edit</button>
                                    <a href="process.php?action=toggle_status&id=<?php echo $dept['id']; ?>" class="ml-4 text-gray-600 hover:text-gray-900" onclick="return confirm('Are you sure you want to toggle the status?');">
                                        <?php echo $dept['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center py-4">No departments found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<!-- Add Modal -->
<div id="add-modal" class="fixed z-50 inset-0 overflow-y-auto hidden">
    <div class="flex items-center justify-center min-h-screen">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75"></div>
        <div class="bg-white rounded-lg text-left overflow-hidden shadow-xl transform sm:max-w-lg sm:w-full">
            <form action="process.php" method="POST">
                <input type="hidden" name="action" value="add">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Add New Department</h3>
                    <div class="space-y-4">
                        <div><label class="block text-sm font-medium">Department Name</label><input type="text" name="name" required class="mt-1 block w-full p-2 border rounded-md"></div>
                        <div><label class="block text-sm font-medium">Department Code</label><input type="text" name="code" required class="mt-1 block w-full p-2 border rounded-md"></div>
                        <hr>
                        <p class="text-sm text-gray-600">Create an HOD account for this department.</p>
                        <div><label class="block text-sm font-medium">HOD Name</label><input type="text" name="hod_name" required class="mt-1 block w-full p-2 border rounded-md"></div>
                        <div><label class="block text-sm font-medium">HOD Email</label><input type="email" name="hod_email" required class="mt-1 block w-full p-2 border rounded-md"></div>
                        <div><label class="block text-sm font-medium">HOD Password (min 8 characters)</label><input type="password" name="hod_password" required class="mt-1 block w-full p-2 border rounded-md"></div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-white hover:bg-indigo-700 sm:ml-3 sm:w-auto">Save</button>
                    <button type="button" class="modal-cancel-btn mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-gray-700 hover:bg-gray-50 sm:mt-0 sm:w-auto">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div id="edit-modal" class="fixed z-50 inset-0 overflow-y-auto hidden">
    <div class="flex items-center justify-center min-h-screen">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75"></div>
        <div class="bg-white rounded-lg text-left overflow-hidden shadow-xl transform sm:max-w-lg sm:w-full">
            <form action="process.php" method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Edit Department</h3>
                    <div class="space-y-4">
                        <div><label class="block text-sm font-medium">Department Name</label><input type="text" name="name" id="edit_name" required class="mt-1 block w-full p-2 border rounded-md"></div>
                        <div><label class="block text-sm font-medium">Department Code</label><input type="text" name="code" id="edit_code" required class="mt-1 block w-full p-2 border rounded-md"></div>
                        <hr>
                        <p class="text-sm text-gray-600">Update HOD information. Leave password blank to keep it unchanged.</p>
                        <div><label class="block text-sm font-medium">HOD Name</label><input type="text" name="hod_name" id="edit_hod_name" required class="mt-1 block w-full p-2 border rounded-md"></div>
                        <div><label class="block text-sm font-medium">HOD Email</label><input type="email" name="hod_email" id="edit_hod_email" required class="mt-1 block w-full p-2 border rounded-md"></div>
                        <div><label class="block text-sm font-medium">New Password (min 8 characters)</label><input type="password" name="hod_password" class="mt-1 block w-full p-2 border rounded-md"></div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-white hover:bg-indigo-700 sm:ml-3 sm:w-auto">Update</button>
                    <button type="button" class="modal-cancel-btn mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-gray-700 hover:bg-gray-50 sm:mt-0 sm:w-auto">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>