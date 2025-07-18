<?php
$pageTitle = "Manage Faculty";
require_once __DIR__ . '/../../includes/header.php';

// Authorization: Allow only admin
if (!isset($_SESSION['user_role_name']) || $_SESSION['user_role_name'] !== 'admin') {
    $_SESSION['error_message'] = "You are not authorized to view this page.";
    header('Location: /');
    exit();
}

// Fetch all faculty members
$faculty_sql = "SELECT u.id, u.name, u.email, u.is_active, f.phone_number, f.specialization
                FROM users u
                JOIN faculty f ON u.id = f.user_id
                ORDER BY u.name ASC";
$faculty_result = $conn->query($faculty_sql);

// Get any session messages
$success_message = $_SESSION['success_message'] ?? null;
$error_message = $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>

<div class="relative min-h-screen lg:flex">
    <?php
    $activePage = 'faculty';
    require_once __DIR__ . '/../../includes/admin_sidebar.php';
    ?>
    <div id="sidebar-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-20 hidden lg:hidden"></div>
    <div class="flex-1 flex flex-col relative z-0">
        <header class="flex justify-between items-center p-4 bg-white border-b lg:hidden">
            <button id="sidebar-toggle" class="text-gray-500 focus:outline-none"><svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg></button>
            <h2 class="text-xl font-semibold text-gray-800">Manage Faculty</h2>
        </header>

        <main class="flex-1 bg-gray-100 p-6">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-semibold text-gray-800 hidden lg:block">Manage Faculty</h2>
                <button id="add-faculty-btn" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-lg">Add New Faculty</button>
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
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Specialization</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if ($faculty_result->num_rows > 0): ?>
                            <?php while ($faculty = $faculty_result->fetch_assoc()): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($faculty['name']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($faculty['email']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($faculty['specialization'] ?? 'N/A'); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap"><span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $faculty['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>"><?php echo $faculty['is_active'] ? 'Active' : 'Inactive'; ?></span></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button class="text-indigo-600 hover:text-indigo-900 edit-btn" data-id="<?php echo $faculty['id']; ?>" data-name="<?php echo htmlspecialchars($faculty['name']); ?>" data-email="<?php echo htmlspecialchars($faculty['email']); ?>" data-phone="<?php echo htmlspecialchars($faculty['phone_number'] ?? ''); ?>" data-specialization="<?php echo htmlspecialchars($faculty['specialization'] ?? ''); ?>">Edit</button>
                                        <a href="/admin/faculty/process.php?action=toggle_status&id=<?php echo $faculty['id']; ?>" class="ml-4 text-gray-600 hover:text-gray-900" onclick="return confirm('Are you sure you want to toggle the status for this user?');"><?php echo $faculty['is_active'] ? 'Deactivate' : 'Activate'; ?></a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center">No faculty found.</td>
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
            <form action="/admin/faculty/process.php" method="POST"><input type="hidden" name="action" value="add">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Add New Faculty</h3>
                    <div class="space-y-4">
                        <div><label for="add_name" class="block text-sm font-medium">Full Name</label><input type="text" name="name" id="add_name" required class="mt-1 block w-full p-2 border rounded-md"></div>
                        <div><label for="add_email" class="block text-sm font-medium">Email</label><input type="email" name="email" id="add_email" required class="mt-1 block w-full p-2 border rounded-md"></div>
                        <div><label for="add_password" class="block text-sm font-medium">Password</label><input type="password" name="password" id="add_password" required class="mt-1 block w-full p-2 border rounded-md"></div>
                        <div><label for="add_phone" class="block text-sm font-medium">Phone Number</label><input type="text" name="phone" id="add_phone" class="mt-1 block w-full p-2 border rounded-md"></div>
                        <div><label for="add_specialization" class="block text-sm font-medium">Specialization</label><input type="text" name="specialization" id="add_specialization" class="mt-1 block w-full p-2 border rounded-md"></div>
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
            <form action="/admin/faculty/process.php" method="POST"><input type="hidden" name="action" value="edit"><input type="hidden" name="id" id="edit_id">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Edit Faculty</h3>
                    <div class="space-y-4">
                        <div><label for="edit_name" class="block text-sm font-medium">Full Name</label><input type="text" name="name" id="edit_name" required class="mt-1 block w-full p-2 border rounded-md"></div>
                        <div><label for="edit_email" class="block text-sm font-medium">Email</label><input type="email" name="email" id="edit_email" required class="mt-1 block w-full p-2 border rounded-md"></div>
                        <div><label for="edit_password" class="block text-sm font-medium">New Password (optional)</label><input type="password" name="password" id="edit_password" class="mt-1 block w-full p-2 border rounded-md"></div>
                        <div><label for="edit_phone" class="block text-sm font-medium">Phone Number</label><input type="text" name="phone" id="edit_phone" class="mt-1 block w-full p-2 border rounded-md"></div>
                        <div><label for="edit_specialization" class="block text-sm font-medium">Specialization</label><input type="text" name="specialization" id="edit_specialization" class="mt-1 block w-full p-2 border rounded-md"></div>
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
        document.getElementById('add-faculty-btn').addEventListener('click', () => addModal.classList.remove('hidden'));
        document.getElementById('add-cancel-btn').addEventListener('click', () => addModal.classList.add('hidden'));
        document.getElementById('edit-cancel-btn').addEventListener('click', () => editModal.classList.add('hidden'));

        document.querySelectorAll('.edit-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.getElementById('edit_id').value = btn.dataset.id;
                document.getElementById('edit_name').value = btn.dataset.name;
                document.getElementById('edit_email').value = btn.dataset.email;
                document.getElementById('edit_phone').value = btn.dataset.phone;
                document.getElementById('edit_specialization').value = btn.dataset.specialization;
                editModal.classList.remove('hidden');
            });
        });
    });
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>