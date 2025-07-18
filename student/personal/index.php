<?php
$pageTitle = "My Profile";
require_once __DIR__ . '/../../includes/header.php';

// Authorization check for student
if (!isset($_SESSION['user_role_name']) || $_SESSION['user_role_name'] !== 'student') {
    header('Location: /index.php');
    exit();
}

// Fetch student's current details using LEFT JOIN for resilience
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT u.name, u.email, s.roll_number, s.date_of_birth, s.current_address, s.permanent_address, s.phone_number_self, s.phone_number_guardian, p.name as programme_name, b.name as batch_name
                        FROM users u
                        LEFT JOIN students s ON u.id = s.user_id
                        LEFT JOIN programmes p ON s.programme_id = p.id
                        LEFT JOIN batches b ON s.batch_id = b.id
                        WHERE u.id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$student) {
    // This error is now much less likely to occur
    die("Error: Could not find your user account.");
}

// Get session messages
$success_message = $_SESSION['success_message'] ?? null;
$error_message = $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>

<div class="relative min-h-screen lg:flex">
    <?php
    $activePage = 'profile';
    require_once __DIR__ . '/../../includes/student_sidebar.php';
    ?>
    <div id="sidebar-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-20 hidden lg:hidden"></div>
    <div class="flex-1 flex flex-col relative z-0">
        <header class="flex justify-between items-center p-4 bg-white border-b lg:hidden">
            <button id="sidebar-toggle" class="text-gray-500 focus:outline-none"><svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg></button>
            <h2 class="text-xl font-semibold text-gray-800">My Profile</h2>
        </header>

        <main class="flex-1 bg-gray-100 p-6">
            <h2 class="text-2xl font-semibold text-gray-800 hidden lg:block">My Profile</h2>
            <div class="mt-6 bg-white p-8 rounded-lg shadow-md max-w-4xl mx-auto">

                <?php if ($success_message): ?><div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-md" role="alert">
                        <p><?php echo htmlspecialchars($success_message); ?></p>
                    </div><?php endif; ?>
                <?php if ($error_message): ?><div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-md" role="alert">
                        <p><?php echo htmlspecialchars($error_message); ?></p>
                    </div><?php endif; ?>

                <form action="/student/personal/process.php" method="POST">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="md:col-span-2">
                            <h3 class="text-lg font-medium text-gray-900">Personal & Academic Information</h3>
                            <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                                <div><span class="font-semibold">Name:</span> <?php echo htmlspecialchars($student['name']); ?></div>
                                <div><span class="font-semibold">Roll No:</span> <?php echo htmlspecialchars($student['roll_number'] ?? 'N/A'); ?></div>
                                <div><span class="font-semibold">Programme:</span> <?php echo htmlspecialchars($student['programme_name'] ?? 'N/A'); ?></div>
                                <div>
                                    <label for="date_of_birth" class="block text-sm font-medium">Date of Birth</label>
                                    <input type="date" id="date_of_birth" name="date_of_birth" value="<?php echo htmlspecialchars($student['date_of_birth'] ?? ''); ?>" class="mt-1 block w-full p-2 border rounded-md">
                                </div>
                            </div>
                        </div>

                        <div class="md:col-span-2">
                            <h3 class="mt-6 text-lg font-medium text-gray-900">Contact & Address Details</h3>
                        </div>
                        <div><label for="phone_self" class="block text-sm font-medium">Your Phone</label><input type="text" id="phone_self" name="phone_self" value="<?php echo htmlspecialchars($student['phone_number_self'] ?? ''); ?>" class="mt-1 block w-full p-2 border rounded-md"></div>
                        <div><label for="phone_guardian" class="block text-sm font-medium">Guardian's Phone</label><input type="text" id="phone_guardian" name="phone_guardian" value="<?php echo htmlspecialchars($student['phone_number_guardian'] ?? ''); ?>" class="mt-1 block w-full p-2 border rounded-md"></div>
                        <div class="md:col-span-2"><label for="current_address" class="block text-sm font-medium">Current Address</label><textarea id="current_address" name="current_address" rows="3" class="mt-1 block w-full p-2 border rounded-md"><?php echo htmlspecialchars($student['current_address'] ?? ''); ?></textarea></div>
                        <div class="md:col-span-2"><label for="permanent_address" class="block text-sm font-medium">Permanent Address</label><textarea id="permanent_address" name="permanent_address" rows="3" class="mt-1 block w-full p-2 border rounded-md"><?php echo htmlspecialchars($student['permanent_address'] ?? ''); ?></textarea></div>

                        <div class="md:col-span-2">
                            <h3 class="mt-6 text-lg font-medium text-gray-900">Account Security</h3>
                        </div>
                        <div><label for="email" class="block text-sm font-medium">Email Address</label><input type="email" id="email" name="email" value="<?php echo htmlspecialchars($student['email']); ?>" required class="mt-1 block w-full p-2 border rounded-md"></div>
                        <div><label for="password" class="block text-sm font-medium">New Password</label><input type="password" id="password" name="password" class="mt-1 block w-full p-2 border rounded-md" placeholder="Leave blank to keep current password"></div>
                    </div>

                    <div class="mt-8 border-t pt-5">
                        <div class="flex justify-end"><button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-lg">Update Profile</button></div>
                    </div>
                </form>
            </div>
        </main>
    </div>
</div>

<script src="/includes/sidebar_toggle_script.js"></script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>