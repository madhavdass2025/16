<?php
include 'includes/db_connect.php';
include 'includes/auth_check.php';
include 'includes/functions.php';
check_access(['Admin']);
include 'includes/header.php';
include 'includes/sidebar.php';

// Fetch users from the database
$users_result = $mysqli->query("SELECT u.user_id, u.name, u.email, r.role_name FROM users u LEFT JOIN roles r ON u.role_id = r.role_id ORDER BY u.name ASC");
$users = $users_result->fetch_all(MYSQLI_ASSOC);

// Fetch roles for the form
$roles_result = $mysqli->query("SELECT role_id, role_name FROM roles ORDER BY role_name ASC");
$roles = $roles_result->fetch_all(MYSQLI_ASSOC);
?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">User Management</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#user-modal" id="add-user-btn">Add User</button>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-striped table-sm">
            <thead>
                <tr>
                    <th scope="col">#</th>
                    <th scope="col">Name</th>
                    <th scope="col">Email</th>
                    <th scope="col">Role</th>
                    <th scope="col">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo $user['user_id']; ?></td>
                        <td><?php echo htmlspecialchars($user['name']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo htmlspecialchars($user['role_name'] ?? 'N/A'); ?></td>
                        <td>
                            <button class="btn btn-sm btn-info edit-user-btn" data-bs-toggle="modal" data-bs-target="#user-modal" data-user-id="<?php echo $user['user_id']; ?>">Edit</button>
                            <button class="btn btn-sm btn-danger delete-user-btn" data-user-id="<?php echo $user['user_id']; ?>">Delete</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- User Modal -->
    <div class="modal fade" id="user-modal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="user-modal-title">Add User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="user-form">
                        <input type="hidden" name="action" value="save_user">
                        <input type="hidden" name="user_id" id="user_id">
                        <div class="mb-3">
                            <label for="name" class="form-label">Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password">
                            <small class="form-text text-muted">Leave blank to keep the current password.</small>
                        </div>
                        <div class="mb-3">
                            <label for="role_id" class="form-label">Role</label>
                            <select class="form-select" id="role_id" name="role_id" required>
                                <?php foreach ($roles as $role): ?>
                                    <option value="<?php echo $role['role_id']; ?>"><?php echo htmlspecialchars($role['role_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Save</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const userModal = new bootstrap.Modal(document.getElementById('user-modal'));
    const userForm = document.getElementById('user-form');

    document.getElementById('add-user-btn').addEventListener('click', function() {
        userForm.reset();
        document.getElementById('user_id').value = 0;
        document.getElementById('user-modal-title').textContent = 'Add User';
    });

    document.querySelectorAll('.edit-user-btn').forEach(button => {
        button.addEventListener('click', function() {
            const userId = this.dataset.userId;
            fetch(`ajax_user_crud.php?action=get_user&user_id=${userId}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('user-modal-title').textContent = 'Edit User';
                    document.getElementById('user_id').value = data.user_id;
                    document.getElementById('name').value = data.name;
                    document.getElementById('email').value = data.email;
                    document.getElementById('role_id').value = data.role_id;
                });
        });
    });

    userForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(userForm);
        fetch('ajax_user_crud.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                userModal.hide();
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        });
    });

    document.querySelectorAll('.delete-user-btn').forEach(button => {
        button.addEventListener('click', function() {
            if (confirm('Are you sure you want to delete this user?')) {
                const userId = this.dataset.userId;
                const formData = new FormData();
                formData.append('action', 'delete_user');
                formData.append('user_id', userId);

                fetch('ajax_user_crud.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                });
            }
        });
    });
});
</script>

<?php
include 'includes/footer.php';
?>