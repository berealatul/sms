<?php
$activePage = $activePage ?? '';
?>
<div id="sidebar" class="bg-white w-64 absolute inset-y-0 left-0 transform -translate-x-full lg:relative lg:translate-x-0 transition-transform duration-200 ease-in-out z-30 shadow-md">
    <div class="p-4 border-b">
        <h1 class="text-xl font-bold text-teal-600">Faculty Panel</h1>
        <p class="text-sm text-gray-500">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</p>
    </div>
    <nav class="mt-4">
        <a href="/faculty/" class="flex items-center px-4 py-3 <?php echo ($activePage === 'dashboard') ? 'text-gray-700 bg-gray-200 font-bold' : 'text-gray-600 hover:bg-gray-200'; ?>">
            <svg class="h-6 w-6 mr-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7" />
            </svg>
            Dashboard
        </a>
        <a href="#" class="flex items-center px-4 py-3 text-gray-600 hover:bg-gray-200">
            <svg class="h-6 w-6 mr-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1z" />
            </svg>
            My Students
        </a>
        <a href="/process_auth.php?logout=1" class="flex items-center px-4 py-3 text-gray-600 hover:bg-gray-200 mt-8">
            <svg class="h-6 w-6 mr-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
            </svg>
            Logout
        </a>
    </nav>
</div>