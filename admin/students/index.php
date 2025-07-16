<?php
$pageTitle = "Manage Students";
require_once __DIR__ . '/../../includes/header.php';

// Authorization: Allow only admin and staff
if (!isset($_SESSION['user_role_name']) || !in_array($_SESSION['user_role_name'], ['admin', 'staff'])) {
    $_SESSION['error_message'] = "You are not authorized to view this page.";
    header('Location: /' . $_SESSION['user_role_name'] . '/');
    exit();
}

// --- Pagination and Search Logic ---
define('STUDENTS_PER_PAGE', 10);
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * STUDENTS_PER_PAGE;
$search_query = $_GET['search'] ?? '';

$where_clause = 'WHERE ut.name = "student"';
$params = [];
$types = '';

if (!empty($search_query)) {
    $search_term = "%" . $search_query . "%";
    $where_clause .= " AND (u.name LIKE ? OR u.email LIKE ? OR s.roll_number LIKE ?)";
    $params = [$search_term, $search_term, $search_term];
    $types = 'sss';
}

// --- Get Total Students for Pagination ---
$total_sql = "SELECT COUNT(u.id) as total FROM users u JOIN user_types ut ON u.user_type_id = ut.id LEFT JOIN students s ON u.id = s.user_id $where_clause";
$stmt_total = $conn->prepare($total_sql);
if (!empty($params)) {
    $stmt_total->bind_param($types, ...$params);
}
$stmt_total->execute();
$total_result = $stmt_total->get_result()->fetch_assoc();
$total_students = $total_result['total'];
$total_pages = ceil($total_students / STUDENTS_PER_PAGE);
$stmt_total->close();

// --- Fetch Paginated Students ---
$students_sql = "SELECT u.id, u.name, u.email, s.roll_number, p.name as programme_name, b.name as batch_name, u.is_active, s.programme_id, s.batch_id
                 FROM users u
                 JOIN user_types ut ON u.user_type_id = ut.id
                 LEFT JOIN students s ON u.id = s.user_id
                 LEFT JOIN programmes p ON s.programme_id = p.id
                 LEFT JOIN batches b ON s.batch_id = b.id
                 $where_clause
                 ORDER BY u.name ASC
                 LIMIT ? OFFSET ?";

$students_params = $params;
$students_params[] = STUDENTS_PER_PAGE;
$students_params[] = $offset;
$students_types = $types . 'ii';

$stmt = $conn->prepare($students_sql);
$stmt->bind_param($students_types, ...$students_params);
$stmt->execute();
$students_result = $stmt->get_result();
$stmt->close();

// Fetch programmes and batches for dropdowns
$programmes_sql = "SELECT id, name FROM programmes WHERE is_active = TRUE ORDER BY name ASC";
$programmes_res = $conn->query($programmes_sql);
$programmes = $programmes_res->fetch_all(MYSQLI_ASSOC);

$batches_sql = "SELECT id, name, programme_id FROM batches WHERE is_active = TRUE ORDER BY name ASC";
$batches_res = $conn->query($batches_sql);
$batches = $batches_res->fetch_all(MYSQLI_ASSOC);

$success_message = $_SESSION['success_message'] ?? null;
$error_message = $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>

<div class="relative min-h-screen lg:flex">
    <?php
    $activePage = 'students';
    if ($_SESSION['user_role_name'] === 'admin') require_once __DIR__ . '/../../includes/admin_sidebar.php';
    else require_once __DIR__ . '/../../includes/staff_sidebar.php';
    ?>
    <div id="sidebar-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-20 hidden lg:hidden"></div>

    <div class="flex-1 flex flex-col relative z-0">
        <header class="flex justify-between items-center p-4 bg-white border-b lg:hidden">
            <button id="sidebar-toggle" class="text-gray-500 focus:outline-none">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
            <h2 class="text-xl font-semibold text-gray-800">Manage Students</h2>
        </header>

        <main class="flex-1 bg-gray-100 p-6">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-semibold text-gray-800 hidden lg:block">Manage Students</h2>
            </div>

            <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
                <form action="/admin/students/" method="GET" class="w-full md:w-1/3">
                    <div class="relative">
                        <input type="search" name="search" placeholder="Search by Name, Email, or Roll..." class="w-full pl-10 pr-4 py-2 border rounded-lg" value="<?php echo htmlspecialchars($search_query); ?>">
                        <svg class="w-5 h-5 text-gray-400 absolute top-1/2 left-3 -translate-y-1/2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                </form>
                <div class="flex gap-2 w-full md:w-auto">
                    <button id="add-student-btn" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-lg w-full md:w-auto">Add Student</button>
                    <button id="upload-csv-btn" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg w-full md:w-auto">Upload CSV</button>
                    <button id="archive-batch-btn" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-lg w-full md:w-auto">Archive/Delete</button>
                </div>
            </div>

            <?php if ($success_message): ?><div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded-md" role="alert">
                    <p><?php echo htmlspecialchars($success_message); ?></p>
                </div><?php endif; ?>
            <?php if ($error_message): ?><div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded-md" role="alert">
                    <p><?php echo htmlspecialchars($error_message); ?></p>
                </div><?php endif; ?>

            <div class="bg-white p-6 rounded-lg shadow-md overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Roll No.</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Programme</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if ($students_result->num_rows > 0): ?>
                            <?php while ($student = $students_result->fetch_assoc()): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($student['name']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($student['roll_number'] ?? 'N/A'); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($student['email']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($student['programme_name'] ?? 'N/A'); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap"><span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $student['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>"><?php echo $student['is_active'] ? 'Active' : 'Inactive'; ?></span></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button class="text-indigo-600 hover:text-indigo-900 edit-btn" data-id="<?php echo $student['id']; ?>" data-name="<?php echo htmlspecialchars($student['name']); ?>" data-email="<?php echo htmlspecialchars($student['email']); ?>" data-roll="<?php echo htmlspecialchars($student['roll_number'] ?? ''); ?>" data-programme-id="<?php echo $student['programme_id']; ?>" data-batch-id="<?php echo $student['batch_id']; ?>">Edit</button>
                                        <a href="/admin/students/process.php?action=reset_password&id=<?php echo $student['id']; ?>" class="ml-4 text-blue-600 hover:text-blue-900" onclick="return confirm('Are you sure you want to reset the password for this student? The new password will be their email address.');">ResetPassword</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center">No students found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <div class="mt-6 flex justify-between items-center">
                    <span class="text-sm text-gray-700">Showing <?php echo $offset + 1; ?> to <?php echo min($offset + STUDENTS_PER_PAGE, $total_students); ?> of <?php echo $total_students; ?> results</span>
                    <div class="flex">
                        <?php if ($page > 1): ?><a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search_query); ?>" class="px-4 py-2 mx-1 text-gray-700 bg-white rounded-md border hover:bg-gray-100">Previous</a><?php endif; ?>
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?><a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search_query); ?>" class="px-4 py-2 mx-1 <?php echo $i === $page ? 'text-white bg-indigo-600' : 'text-gray-700 bg-white'; ?> rounded-md border hover:bg-gray-100"><?php echo $i; ?></a><?php endfor; ?>
                        <?php if ($page < $total_pages): ?><a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search_query); ?>" class="px-4 py-2 mx-1 text-gray-700 bg-white rounded-md border hover:bg-gray-100">Next</a><?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Modals -->
<div id="add-modal" class="fixed z-50 inset-0 overflow-y-auto hidden">
    <div class="flex items-center justify-center min-h-screen">
        <div class="fixed inset-0 bg-gray-500 opacity-75"></div>
        <div class="bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:max-w-lg sm:w-full">
            <form action="/admin/students/process.php" method="POST"><input type="hidden" name="action" value="add">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Add New Student</h3>
                    <div class="space-y-4">
                        <div><label for="add_name" class="block text-sm font-medium">Full Name</label><input type="text" name="name" id="add_name" required class="mt-1 block w-full p-2 border rounded-md"></div>
                        <div><label for="add_email" class="block text-sm font-medium">Email</label><input type="email" name="email" id="add_email" required class="mt-1 block w-full p-2 border rounded-md"></div>
                        <div><label for="add_roll_number" class="block text-sm font-medium">Roll Number</label><input type="text" name="roll_number" id="add_roll_number" required class="mt-1 block w-full p-2 border rounded-md"></div>
                        <div><label for="add_programme_id" class="block text-sm font-medium">Programme</label><select name="programme_id" id="add_programme_id" required class="mt-1 block w-full p-2 border rounded-md"><?php foreach ($programmes as $p): ?><option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['name']); ?></option><?php endforeach; ?></select></div>
                        <div><label for="add_batch_id" class="block text-sm font-medium">Batch</label><select name="batch_id" id="add_batch_id" required class="mt-1 block w-full p-2 border rounded-md"></select></div>
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
            <form action="/admin/students/process.php" method="POST"><input type="hidden" name="action" value="edit"><input type="hidden" name="id" id="edit_id">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Edit Student</h3>
                    <div class="space-y-4">
                        <div><label for="edit_name" class="block text-sm font-medium">Full Name</label><input type="text" name="name" id="edit_name" required class="mt-1 block w-full p-2 border rounded-md"></div>
                        <div><label for="edit_email" class="block text-sm font-medium">Email</label><input type="email" name="email" id="edit_email" required class="mt-1 block w-full p-2 border rounded-md"></div>
                        <div><label for="edit_roll_number" class="block text-sm font-medium">Roll Number</label><input type="text" name="roll_number" id="edit_roll_number" required class="mt-1 block w-full p-2 border rounded-md"></div>
                        <div><label for="edit_programme_id" class="block text-sm font-medium">Programme</label><select name="programme_id" id="edit_programme_id" required class="mt-1 block w-full p-2 border rounded-md"><?php foreach ($programmes as $p): ?><option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['name']); ?></option><?php endforeach; ?></select></div>
                        <div><label for="edit_batch_id" class="block text-sm font-medium">Batch</label><select name="batch_id" id="edit_batch_id" required class="mt-1 block w-full p-2 border rounded-md"></select></div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse"><button type="submit" class="w-full sm:w-auto sm:ml-3 px-4 py-2 bg-indigo-600 text-white rounded-md">Update</button><button type="button" id="edit-cancel-btn" class="w-full sm:w-auto mt-3 sm:mt-0 px-4 py-2 bg-white border rounded-md">Cancel</button></div>
            </form>
        </div>
    </div>
</div>

<div id="upload-csv-modal" class="fixed z-50 inset-0 overflow-y-auto hidden">
    <div class="flex items-center justify-center min-h-screen">
        <div class="fixed inset-0 bg-gray-500 opacity-75"></div>
        <div class="bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:max-w-lg sm:w-full">
            <form action="/admin/students/process.php" method="POST" enctype="multipart/form-data"><input type="hidden" name="action" value="upload_csv">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Upload Students CSV</h3>
                    <div class="space-y-4">
                        <div><label for="csv_programme_id" class="block text-sm font-medium">Programme</label><select name="programme_id" id="csv_programme_id" required class="mt-1 block w-full p-2 border rounded-md"><?php foreach ($programmes as $p): ?><option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['name']); ?></option><?php endforeach; ?></select></div>
                        <div><label for="csv_batch_id" class="block text-sm font-medium">Batch</label><select name="batch_id" id="csv_batch_id" required class="mt-1 block w-full p-2 border rounded-md"></select></div>
                        <div><label for="csv_file" class="block text-sm font-medium">CSV File</label><input type="file" name="csv_file" id="csv_file" required accept=".csv" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"></div>
                        <p class="text-xs text-gray-500">CSV must have columns: name, roll, email</p>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse"><button type="submit" class="w-full sm:w-auto sm:ml-3 px-4 py-2 bg-green-600 text-white rounded-md">Upload</button><button type="button" id="upload-csv-cancel-btn" class="w-full sm:w-auto mt-3 sm:mt-0 px-4 py-2 bg-white border rounded-md">Cancel</button></div>
            </form>
        </div>
    </div>
</div>

<div id="archive-modal" class="fixed z-50 inset-0 overflow-y-auto hidden">
    <div class="flex items-center justify-center min-h-screen">
        <div class="fixed inset-0 bg-gray-500 opacity-75"></div>
        <div class="bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:max-w-lg sm:w-full">
            <form action="/admin/students/process.php" method="POST" onsubmit="return confirm('Are you sure? This action cannot be undone.');">
                <input type="hidden" name="action" value="archive_batch">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Archive/Delete Students by Batch</h3>
                    <div class="space-y-4">
                        <div>
                            <label for="archive_programme_id" class="block text-sm font-medium">Programme</label>
                            <select name="programme_id" id="archive_programme_id" required class="mt-1 block w-full p-2 border rounded-md">
                                <option value="">Select Programme</option>
                                <?php foreach ($programmes as $p): ?>
                                    <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="archive_batch_id" class="block text-sm font-medium">Batch</label>
                            <select name="batch_id" id="archive_batch_id" required class="mt-1 block w-full p-2 border rounded-md">
                                <option value="">Select Programme First</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium">Action</label>
                            <div class="mt-2 space-y-2">
                                <div class="flex items-center">
                                    <input id="action_deactivate" name="archive_action" type="radio" value="deactivate" required class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300">
                                    <label for="action_deactivate" class="ml-3 block text-sm font-medium text-gray-700">Deactivate Students (Prevent Login)</label>
                                </div>
                                <div class="flex items-center">
                                    <input id="action_delete" name="archive_action" type="radio" value="delete" required class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300">
                                    <label for="action_delete" class="ml-3 block text-sm font-medium text-gray-700">Permanently Delete Students</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse">
                    <button type="submit" class="w-full sm:w-auto sm:ml-3 px-4 py-2 bg-red-600 text-white rounded-md">Confirm</button>
                    <button type="button" id="archive-cancel-btn" class="w-full sm:w-auto mt-3 sm:mt-0 px-4 py-2 bg-white border rounded-md">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="/includes/sidebar_toggle_script.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const batches = <?php echo json_encode($batches); ?>;

        // --- Modal Logic ---
        const addModal = document.getElementById('add-modal');
        const editModal = document.getElementById('edit-modal');
        const uploadCsvModal = document.getElementById('upload-csv-modal');
        const archiveModal = document.getElementById('archive-modal'); // New modal

        document.getElementById('add-student-btn').addEventListener('click', () => addModal.classList.remove('hidden'));
        document.getElementById('add-cancel-btn').addEventListener('click', () => addModal.classList.add('hidden'));

        document.getElementById('upload-csv-btn').addEventListener('click', () => uploadCsvModal.classList.remove('hidden'));
        document.getElementById('upload-csv-cancel-btn').addEventListener('click', () => uploadCsvModal.classList.add('hidden'));

        document.getElementById('archive-batch-btn').addEventListener('click', () => archiveModal.classList.remove('hidden'));
        document.getElementById('archive-cancel-btn').addEventListener('click', () => archiveModal.classList.add('hidden'));


        document.getElementById('edit-cancel-btn').addEventListener('click', () => editModal.classList.add('hidden'));
        document.querySelectorAll('.edit-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.getElementById('edit_id').value = btn.dataset.id;
                document.getElementById('edit_name').value = btn.dataset.name;
                document.getElementById('edit_email').value = btn.dataset.email;
                document.getElementById('edit_roll_number').value = btn.dataset.roll;

                const programmeId = btn.dataset.programmeId;
                document.getElementById('edit_programme_id').value = programmeId;
                updateBatchDropdown('edit_batch_id', programmeId, btn.dataset.batchId);

                editModal.classList.remove('hidden');
            });
        });

        // --- Dynamic Batch Dropdowns ---
        function updateBatchDropdown(batchSelectId, programmeId, selectedBatchId = null) {
            const batchSelect = document.getElementById(batchSelectId);
            batchSelect.innerHTML = '<option value="">Select Batch</option>'; // Clear existing options and add a default

            const filteredBatches = batches.filter(b => b.programme_id == programmeId);
            filteredBatches.forEach(batch => {
                const option = document.createElement('option');
                option.value = batch.id;
                option.textContent = batch.name;
                if (selectedBatchId && batch.id == selectedBatchId) {
                    option.selected = true;
                }
                batchSelect.appendChild(option);
            });
        }

        document.getElementById('add_programme_id').addEventListener('change', function() {
            updateBatchDropdown('add_batch_id', this.value);
        });
        document.getElementById('edit_programme_id').addEventListener('change', function() {
            updateBatchDropdown('edit_batch_id', this.value);
        });
        document.getElementById('csv_programme_id').addEventListener('change', function() {
            updateBatchDropdown('csv_batch_id', this.value);
        });
        document.getElementById('archive_programme_id').addEventListener('change', function() {
            updateBatchDropdown('archive_batch_id', this.value);
        });

        // Initialize dropdowns
        updateBatchDropdown('add_batch_id', document.getElementById('add_programme_id').value);
        updateBatchDropdown('csv_batch_id', document.getElementById('csv_programme_id').value);
        // No need to initialize the archive dropdowns until a programme is selected
    });
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>