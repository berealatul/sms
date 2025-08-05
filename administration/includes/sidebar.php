<?php $activePage = $activePage ?? ''; ?>
<div class="bg-white w-64 p-4 flex-shrink-0 border-r border-gray-200 lg:block hidden">
    <h1 class="text-xl font-bold text-indigo-600 mb-4">Admin Panel</h1>
    <nav>
        <a href="/administration/dashboard.php" class="flex items-center px-4 py-2 rounded-md <?php echo ($activePage === 'dashboard') ? 'bg-gray-200 text-gray-800' : 'text-gray-600 hover:bg-gray-100'; ?>">
            Dashboard
        </a>
        <a href="/administration/departments/" class="flex items-center px-4 py-2 mt-2 rounded-md <?php echo ($activePage === 'departments') ? 'bg-gray-200 text-gray-800' : 'text-gray-600 hover:bg-gray-100'; ?>">
            Departments
        </a>
        <a href="/administration/logout.php" class="flex items-center px-4 py-2 mt-8 rounded-md text-gray-600 hover:bg-gray-100">
            Logout
        </a>
    </nav>
</div>