document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const toggleButton = document.getElementById('sidebar-toggle');
    const overlay = document.getElementById('sidebar-overlay');
    const body = document.body;

    // Ensure all required elements are present on the page
    if (toggleButton && sidebar && overlay) {
        
        // Function to show the sidebar and lock background scroll
        const showSidebar = () => {
            sidebar.classList.remove('-translate-x-full');
            overlay.classList.remove('hidden');
            body.classList.add('overflow-hidden'); // Lock scroll
        };

        // Function to hide the sidebar and unlock background scroll
        const hideSidebar = () => {
            sidebar.classList.add('-translate-x-full');
            overlay.classList.add('hidden');
            body.classList.remove('overflow-hidden'); // Unlock scroll
        };

        // Add click listener to the main toggle button
        toggleButton.addEventListener('click', (e) => {
            e.stopPropagation(); // Prevents the event from bubbling up
            // Check if sidebar is currently hidden before showing
            if (sidebar.classList.contains('-translate-x-full')) {
                showSidebar();
            } else {
                hideSidebar();
            }
        });

        // Add click listener to the overlay to hide the sidebar
        overlay.addEventListener('click', hideSidebar);
    }
});
