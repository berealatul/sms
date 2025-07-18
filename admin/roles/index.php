<?php
$pageTitle = "Manage Roles";
require_once __DIR__ . '/../../includes/header.php';

// Authorization: Allow only admin
if (!isset($_SESSION['user_role_name']) || $_SESSION['user_role_name'] !== 'admin') {
    $_SESSION['error_message'] = "You are not authorized to view this page.";
    header('Location: /');
    exit();
}

// Fetch all association types
$roles_sql = "SELECT id, name FROM association_types ORDER BY name ASC";
$roles_result = $conn->query($roles_sql);

// Get any session messages
$success_message = $_SESSION['success_message'] ?? null;
$error_message = $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>

<div class="relative min-h-screen lg:flex">
    <?php
    $activePage = 'roles';
    require_once __DIR__ . '/../../includes/admin_sidebar.php';
    ?>
    <div id="sidebar-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-20 hidden lg:hidden"></div>
    <div class="flex-1 flex flex-col relative z-0">
        <header class="flex justify-between items-center p-4 bg-white border-b lg:hidden">
            <button id="sidebar-toggle" class="text-gray-500 focus:outline-none"><svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg></button>
            <h2 class="text-xl font-semibold text-gray-800">Manage Roles</h2>
        </header>

        <main class="flex-1 bg-gray-100 p-6">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-semibold text-gray-800 hidden lg:block">Manage Faculty Roles</h2>
                <button id="add-role-btn" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-lg">Add New Role</button>
            </div>

            <?php if ($success_message): ?><div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded-md">
                    <p><?php echo htmlspecialchars($success_message); ?></p>
                </div><?php endif; ?>
            <?php if ($error_message): ?><div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded-md">
                    <p><?php echo htmlspecialchars($error_message); ?></p>
                </div><?php endif; ?>

            <div class="bg-white p-6 rounded-lg shadow-md overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Role Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if ($roles_result->num_rows > 0): ?>
                            <?php while ($role = $roles_result->fetch_assoc()): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($role['name']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button class="text-indigo-600 hover:text-indigo-900 edit-btn" data-id="<?php echo $role['id']; ?>" data-name="<?php echo htmlspecialchars($role['name']); ?>">Edit</button>
                                        <a href="/admin/roles/process.php?action=delete&id=<?php echo $role['id']; ?>" class="ml-4 text-red-600 hover:text-red-900" onclick="return confirm('Are you sure you want to delete this role?');">Delete</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="2" class="px-6 py-4 text-center">No roles found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</div>

<div id="add-modal" class="fixed z-50 inset-0 overflow-y-auto hidden">
    <div class="flex items-center justify-center min-h-screen">
        <div class="fixed inset-0 bg-gray-500 opacity-75"></div>
        <div class="bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:max-w-lg sm:w-full">
            <form action="/admin/roles/process.php" method="POST"><input type="hidden" name="action" value="add">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Add New Role</h3>
                    <div class="space-y-4">
                        <div><label for="add_name" class="block text-sm font-medium">Role Name</label><input type="text" name="name" id="add_name" required class="mt-1 block w-full p-2 border rounded-md"></div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse"><button type="submit" class="w-full sm:w-auto sm:ml-3 px-4 py-2 bg-indigo-600 text-white rounded-md">Save</button><button type="button" id="add-cancel-btn" class="w-full sm:w-auto mt-3 sm:mt-0 px-4 py-2 bg-white border rounded-md">Cancel</button></div>
            </form>
        </div>
    </div>
</div>

<div id="edit-modal" class="fixed z-50 inset-0 overflow-y-auto hidden">
    <div class="flex items-center justify-center min-h-screen">
        <div class="fixed inset-0 bg-gray-500 opacity-75"></div>
        <div class="bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:max-w-lg sm:w-full">
            <form action="/admin/roles/process.php" method="POST"><input type="hidden" name="action" value="edit"><input type="hidden" name="id" id="edit_id">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Edit Role</h3>
                    <div class="space-y-4">
                        <div><label for="edit_name" class="block text-sm font-medium">Role Name</label><input type="text" name="name" id="edit_name" required class="mt-1 block w-full p-2 border rounded-md"></div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse"><button type="submit" class="w-full sm:w-auto sm:ml-3 px-4 py-2 bg-indigo-600 text-white rounded-md">Update</button><button type="button" id="edit-cancel-btn" class="w-full sm:w-auto mt-3 sm:mt-0 px-4 py-2 bg-white border rounded-md">Cancel</button></div>
            </form>
        </div>
    </div>
</div>

<script src="/includes/sidebar_toggle_script.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const addModal = document.getElementById('add-modal');
        const editModal = document.getElementById('edit-modal');
        document.getElementById('add-role-btn').addEventListener('click', () => addModal.classList.remove('hidden'));
        document.getElementById('add-cancel-btn').addEventListener('click', () => addModal.classList.add('hidden'));
        document.getElementById('edit-cancel-btn').addEventListener('click', () => editModal.classList.add('hidden'));

        document.querySelectorAll('.edit-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.getElementById('edit_id').value = btn.dataset.id;
                document.getElementById('edit_name').value = btn.dataset.name;
                editModal.classList.remove('hidden');
            });
        });
    });
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>