<?php
$pageTitle = "Manage Users";
require_once __DIR__ . '/../../includes/header.php';

// Authorization: Allow only admin
if (!isset($_SESSION['user_role_name']) || $_SESSION['user_role_name'] !== 'admin') {
    $_SESSION['error_message'] = "You are not authorized to view this page.";
    header('Location: /');
    exit();
}

// --- Pagination and Search Logic ---
define('USERS_PER_PAGE', 50);

$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * USERS_PER_PAGE;
$search_query = $_GET['search'] ?? '';

$where_clause = '';
$params = [];
$types = '';

if (!empty($search_query)) {
    $search_term = "%" . $search_query . "%";
    $where_clause = "WHERE u.name LIKE ? OR u.email LIKE ?";
    $params = [$search_term, $search_term];
    $types = 'ss';
}

// --- Get Total Users for Pagination ---
$total_users_sql = "SELECT COUNT(u.id) as total FROM users u $where_clause";
$stmt_total = $conn->prepare($total_users_sql);
if (!empty($params)) {
    $stmt_total->bind_param($types, ...$params);
}
$stmt_total->execute();
$total_users_result = $stmt_total->get_result()->fetch_assoc();
$total_users = $total_users_result['total'];
$total_pages = ceil($total_users / USERS_PER_PAGE);
$stmt_total->close();


// --- Fetch Paginated Users ---
$users_sql = "SELECT u.id, u.name, u.email, u.is_active, ut.name as role_name, u.user_type_id
              FROM users u
              JOIN user_types ut ON u.user_type_id = ut.id
              $where_clause
              ORDER BY u.name ASC
              LIMIT ? OFFSET ?";

$params[] = USERS_PER_PAGE;
$params[] = $offset;
$types .= 'ii';

$stmt = $conn->prepare($users_sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$users_result = $stmt->get_result();
$stmt->close();


// Fetch all user types for the dropdowns in the modals
$user_types_sql = "SELECT id, name FROM user_types ORDER BY name ASC";
$user_types_result = $conn->query($user_types_sql);
$user_types = [];
if ($user_types_result->num_rows > 0) {
    while ($row = $user_types_result->fetch_assoc()) {
        $user_types[] = $row;
    }
}

// Get any session messages
$success_message = $_SESSION['success_message'] ?? null;
$error_message = $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>

<div class="relative min-h-screen lg:flex">
    <?php
    $activePage = 'users';
    require_once __DIR__ . '/../../includes/admin_sidebar.php';
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
            <h2 class="text-xl font-semibold text-gray-800">Manage Users</h2>
        </header>

        <main class="flex-1 bg-gray-100 p-6">
            <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
                <h2 class="text-2xl font-semibold text-gray-800 hidden lg:block">Manage Users</h2>

                <!-- Server-side Search Form -->
                <form action="/admin/users/" method="GET" class="w-full md:w-1/3">
                    <div class="relative">
                        <input type="search" name="search" placeholder="Search user by name or email..."
                            class="w-full pl-10 pr-4 py-2 border rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            value="<?php echo htmlspecialchars($search_query); ?>">
                        <svg class="w-5 h-5 text-gray-400 absolute top-1/2 left-3 transform -translate-y-1/2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                </form>

                <button id="add-user-btn" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-lg shadow-md transition duration-300 w-full md:w-auto flex-shrink-0">
                    Add New User
                </button>
            </div>

            <!-- Session Messages -->
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

            <!-- Users Table -->
            <div class="bg-white p-6 rounded-lg shadow-md overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="users-table-body" class="bg-white divide-y divide-gray-200">
                        <?php if ($users_result->num_rows > 0): ?>
                            <?php while ($user = $users_result->fetch_assoc()): ?>
                                <tr class="user-row">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 user-name"><?php echo htmlspecialchars($user['name']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 user-email"><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars(ucfirst($user['role_name'])); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <?php if ($user['is_active']): ?>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Active</span>
                                        <?php else: ?>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button class="text-indigo-600 hover:text-indigo-900 edit-btn"
                                            data-id="<?php echo $user['id']; ?>"
                                            data-name="<?php echo htmlspecialchars($user['name']); ?>"
                                            data-email="<?php echo htmlspecialchars($user['email']); ?>"
                                            data-role-id="<?php echo $user['user_type_id']; ?>">Edit</button>
                                        <a href="/admin/users/process.php?action=toggle_status&id=<?php echo $user['id']; ?>" class="ml-4 text-gray-600 hover:text-gray-900" onclick="return confirm('Are you sure you want to toggle the status for this user?');">
                                            <?php echo $user['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">No users found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <!-- Pagination Controls -->
                <div class="mt-6 flex justify-between items-center">
                    <span class="text-sm text-gray-700">
                        Showing <?php echo $offset + 1; ?> to <?php echo min($offset + USERS_PER_PAGE, $total_users); ?> of <?php echo $total_users; ?> results
                    </span>
                    <div class="flex">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search_query); ?>" class="px-4 py-2 mx-1 text-gray-700 bg-white rounded-md border hover:bg-gray-100">Previous</a>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search_query); ?>" class="px-4 py-2 mx-1 <?php echo $i === $page ? 'text-white bg-indigo-600' : 'text-gray-700 bg-white'; ?> rounded-md border hover:bg-gray-100"><?php echo $i; ?></a>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search_query); ?>" class="px-4 py-2 mx-1 text-gray-700 bg-white rounded-md border hover:bg-gray-100">Next</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Add/Edit User Modals (remain unchanged) -->
<!-- ... -->
<div id="add-modal" class="fixed z-50 inset-0 overflow-y-auto hidden">
    <div class="flex items-center justify-center min-h-screen">
        <div class="fixed inset-0 transition-opacity" aria-hidden="true">
            <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
        </div>
        <div class="bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:max-w-lg sm:w-full">
            <form action="/admin/users/process.php" method="POST">
                <input type="hidden" name="action" value="add">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Add New User</h3>
                    <div class="space-y-4">
                        <div>
                            <label for="add_name" class="block text-sm font-medium text-gray-700">Full Name</label>
                            <input type="text" name="name" id="add_name" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 sm:text-sm">
                        </div>
                        <div>
                            <label for="add_email" class="block text-sm font-medium text-gray-700">Email Address</label>
                            <input type="email" name="email" id="add_email" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 sm:text-sm">
                        </div>
                        <div>
                            <label for="add_password" class="block text-sm font-medium text-gray-700">Password</label>
                            <input type="password" name="password" id="add_password" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 sm:text-sm">
                        </div>
                        <div>
                            <label for="add_user_type_id" class="block text-sm font-medium text-gray-700">Role</label>
                            <select name="user_type_id" id="add_user_type_id" required class="mt-1 block w-full px-3 py-2 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 sm:text-sm">
                                <?php foreach ($user_types as $type): ?>
                                    <option value="<?php echo $type['id']; ?>"><?php echo htmlspecialchars(ucfirst($type['name'])); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 sm:ml-3 sm:w-auto sm:text-sm">Save User</button>
                    <button type="button" id="add-cancel-btn" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:w-auto sm:text-sm">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>
<div id="edit-modal" class="fixed z-50 inset-0 overflow-y-auto hidden">
    <div class="flex items-center justify-center min-h-screen">
        <div class="fixed inset-0 transition-opacity" aria-hidden="true">
            <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
        </div>
        <div class="bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:max-w-lg sm:w-full">
            <form action="/admin/users/process.php" method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Edit User</h3>
                    <div class="space-y-4">
                        <div>
                            <label for="edit_name" class="block text-sm font-medium text-gray-700">Full Name</label>
                            <input type="text" name="name" id="edit_name" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 sm:text-sm">
                        </div>
                        <div>
                            <label for="edit_email" class="block text-sm font-medium text-gray-700">Email Address</label>
                            <input type="email" name="email" id="edit_email" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 sm:text-sm">
                        </div>
                        <div>
                            <label for="edit_password" class="block text-sm font-medium text-gray-700">New Password (optional)</label>
                            <input type="password" name="password" id="edit_password" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 sm:text-sm" placeholder="Leave blank to keep current password">
                        </div>
                        <div>
                            <label for="edit_user_type_id" class="block text-sm font-medium text-gray-700">Role</label>
                            <select name="user_type_id" id="edit_user_type_id" required class="mt-1 block w-full px-3 py-2 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 sm:text-sm">
                                <?php foreach ($user_types as $type): ?>
                                    <option value="<?php echo $type['id']; ?>"><?php echo htmlspecialchars(ucfirst($type['name'])); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 sm:ml-3 sm:w-auto sm:text-sm">Update User</button>
                    <button type="button" id="edit-cancel-btn" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:w-auto sm:text-sm">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="/includes/sidebar_toggle_script.js"></script>
<script>
    // Modal logic remains the same
    document.addEventListener('DOMContentLoaded', function() {
        const addModal = document.getElementById('add-modal');
        const editModal = document.getElementById('edit-modal');

        const addBtn = document.getElementById('add-user-btn');
        const addCancelBtn = document.getElementById('add-cancel-btn');
        addBtn.addEventListener('click', () => addModal.classList.remove('hidden'));
        addCancelBtn.addEventListener('click', () => addModal.classList.add('hidden'));

        const editCancelBtn = document.getElementById('edit-cancel-btn');
        const editBtns = document.querySelectorAll('.edit-btn');
        editBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                document.getElementById('edit_id').value = btn.dataset.id;
                document.getElementById('edit_name').value = btn.dataset.name;
                document.getElementById('edit_email').value = btn.dataset.email;
                document.getElementById('edit_user_type_id').value = btn.dataset.roleId;
                editModal.classList.remove('hidden');
            });
        });
        editCancelBtn.addEventListener('click', () => editModal.classList.add('hidden'));
    });
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>