document.addEventListener('DOMContentLoaded', function() {
    const addModal = document.getElementById('add-modal');
    const editModal = document.getElementById('edit-modal');

    const openAddModalBtn = document.getElementById('add-department-btn');
    const editBtns = document.querySelectorAll('.edit-btn');
    const cancelBtns = document.querySelectorAll('.modal-cancel-btn');

    if (openAddModalBtn) {
        openAddModalBtn.addEventListener('click', () => addModal.classList.remove('hidden'));
    }

    editBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            // Populate form fields from data attributes
            document.getElementById('edit_id').value = btn.dataset.id;
            document.getElementById('edit_name').value = btn.dataset.name;
            document.getElementById('edit_code').value = btn.dataset.code;
            document.getElementById('edit_hod_name').value = btn.dataset.hodName;
            document.getElementById('edit_hod_email').value = btn.dataset.hodEmail;
            editModal.classList.remove('hidden');
        });
    });

    cancelBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            if(addModal) addModal.classList.add('hidden');
            if(editModal) editModal.classList.add('hidden');
        });
    });
});