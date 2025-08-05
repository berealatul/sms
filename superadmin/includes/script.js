document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const toggleButton = document.getElementById('sidebar-toggle');
    const overlay = document.getElementById('sidebar-overlay');
    const body = document.body;

    if (toggleButton && sidebar && overlay) {
        const showSidebar = () => {
            sidebar.classList.remove('-translate-x-full');
            overlay.classList.remove('hidden');
            body.classList.add('overflow-hidden');
        };

        const hideSidebar = () => {
            sidebar.classList.add('-translate-x-full');
            overlay.classList.add('hidden');
            body.classList.remove('overflow-hidden');
        };

        toggleButton.addEventListener('click', (e) => {
            e.stopPropagation();
            if (sidebar.classList.contains('-translate-x-full')) {
                showSidebar();
            } else {
                hideSidebar();
            }
        });

        overlay.addEventListener('click', hideSidebar);
    }

    // Generic Modal Logic
    const handleModal = (modalId, openBtnId, cancelBtnId) => {
        const modal = document.getElementById(modalId);
        const openBtn = document.getElementById(openBtnId);
        const cancelBtn = document.getElementById(cancelBtnId);

        if (modal && openBtn && cancelBtn) {
            openBtn.addEventListener('click', () => modal.classList.remove('hidden'));
            cancelBtn.addEventListener('click', () => modal.classList.add('hidden'));
        }
    };

    // Initialize modals
    handleModal('add-modal', 'add-department-btn', 'add-cancel-btn');
    handleModal('edit-modal', 'edit-department-btn-placeholder', 'edit-cancel-btn'); // Placeholder for edit

    // Logic for Edit buttons
    const editModal = document.getElementById('edit-modal');
    if (editModal) {
        const editCancelBtn = document.getElementById('edit-cancel-btn');
        editCancelBtn.addEventListener('click', () => editModal.classList.add('hidden'));

        document.querySelectorAll('.edit-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                // Populate form fields from data attributes
                document.getElementById('edit_id').value = btn.dataset.id;
                document.getElementById('edit_department_name').value = btn.dataset.name;
                document.getElementById('edit_department_code').value = btn.dataset.code;
                document.getElementById('edit_hod_name').value = btn.dataset.hodName;
                document.getElementById('edit_hod_email').value = btn.dataset.hodEmail;
                document.getElementById('edit_db_name').value = btn.dataset.dbName;

                editModal.classList.remove('hidden');
            });
        });
    }
});