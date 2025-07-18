<?php
$activePage = $activePage ?? '';
?>
<div id="sidebar" class="bg-white w-64 absolute inset-y-0 left-0 transform -translate-x-full lg:relative lg:translate-x-0 transition-transform duration-200 ease-in-out z-30 shadow-md">
    <div class="p-4 border-b">
        <h1 class="text-xl font-bold text-indigo-600">Admin Panel</h1>
        <p class="text-sm text-gray-500">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</p>
    </div>
    <nav class="mt-4">
        <a href="/admin/" class="flex items-center px-4 py-3 <?php echo ($activePage === 'dashboard') ? 'text-gray-700 bg-gray-200 font-bold' : 'text-gray-600 hover:bg-gray-200'; ?>">
            <svg class="h-6 w-6 mr-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7" />
            </svg>
            Dashboard
        </a>
        <a href="/admin/programmes/" class="flex items-center px-4 py-3 <?php echo ($activePage === 'programmes') ? 'text-gray-700 bg-gray-200 font-bold' : 'text-gray-600 hover:bg-gray-200'; ?>">
            <svg class="h-6 w-6 mr-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v11.494m-9-5.747h18" />
            </svg>
            Programmes
        </a>
        <a href="/admin/batches/" class="flex items-center px-4 py-3 <?php echo ($activePage === 'batches') ? 'text-gray-700 bg-gray-200 font-bold' : 'text-gray-600 hover:bg-gray-200'; ?>">
            <svg class="h-6 w-6 mr-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            Batches
        </a>
        <a href="/admin/students/" class="flex items-center px-4 py-3 <?php echo ($activePage === 'students') ? 'text-gray-700 bg-gray-200 font-bold' : 'text-gray-600 hover:bg-gray-200'; ?>">
            <svg class="h-6 w-6 mr-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
            Students
        </a>
        <a href="/admin/assignments/" class="flex items-center px-4 py-3 <?php echo ($activePage === 'assign_faculty') ? 'text-gray-700 bg-gray-200 font-bold' : 'text-gray-600 hover:bg-gray-200'; ?>">
            <svg class="h-6 w-6 mr-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 012-2h4a2 2 0 012 2v1m-4 0h4" />
            </svg>
            Assign Faculty
        </a>
        <a href="/admin/faculty/" class="flex items-center px-4 py-3 <?php echo ($activePage === 'faculty') ? 'text-gray-700 bg-gray-200 font-bold' : 'text-gray-600 hover:bg-gray-200'; ?>">
            <svg class="h-6 w-6 mr-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
            Faculty
        </a>
        <a href="/admin/roles/" class="flex items-center px-4 py-3 <?php echo ($activePage === 'roles') ? 'text-gray-700 bg-gray-200 font-bold' : 'text-gray-600 hover:bg-gray-200'; ?>">
            <svg class="h-6 w-6 mr-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.096 2.572-1.065z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            Manage Faculty Roles
        </a>
        <a href="/admin/users/" class="flex items-center px-4 py-3 <?php echo ($activePage === 'users') ? 'text-gray-700 bg-gray-200 font-bold' : 'text-gray-600 hover:bg-gray-200'; ?>">
            <svg class="h-6 w-6 mr-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M15 21a6 6 0 00-9-5.197m0 0A10.99 10.99 0 0112 5.196a10.99 10.99 0 017 9.604M15 21a6 6 0 00-9-5.197" />
            </svg>
            Users
        </a>
        <a href="/process_auth.php?logout=1" class="flex items-center px-4 py-3 text-gray-600 hover:bg-gray-200 mt-8">
            <svg class="h-6 w-6 mr-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
            </svg>
            Logout
        </a>
    </nav>
</div>