<?php
include 'includes/db_connect.php';
include 'includes/auth_check.php';
include 'includes/functions.php';
check_access(['Admin', 'Manager']);
include 'includes/header.php';
include 'includes/sidebar.php';

// Fetch categories from the database
$categories_result = $mysqli->query("SELECT category_id, category_name FROM categories ORDER BY category_name ASC");
$categories = $categories_result->fetch_all(MYSQLI_ASSOC);
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Category Management</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#category-modal" id="add-category-btn">Add Category</button>
        </div>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-striped table-sm">
        <thead>
            <tr>
                <th scope="col">#</th>
                <th scope="col">Category Name</th>
                <th scope="col">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($categories as $category): ?>
                <tr>
                    <td><?php echo $category['category_id']; ?></td>
                    <td><?php echo htmlspecialchars($category['category_name']); ?></td>
                    <td>
                        <button class="btn btn-sm btn-info edit-category-btn" data-bs-toggle="modal" data-bs-target="#category-modal" data-category-id="<?php echo $category['category_id']; ?>">Edit</button>
                        <button class="btn btn-sm btn-danger delete-category-btn" data-category-id="<?php echo $category['category_id']; ?>">Delete</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Category Modal -->
<div class="modal fade" id="category-modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="category-modal-title">Add Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="category-form">
                    <input type="hidden" name="action" value="save_category">
                    <input type="hidden" name="category_id" id="category_id">
                    <div class="mb-3">
                        <label for="category_name" class="form-label">Category Name</label>
                        <input type="text" class="form-control" id="category_name" name="category_name" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Save</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const categoryModal = new bootstrap.Modal(document.getElementById('category-modal'));
    const categoryForm = document.getElementById('category-form');

    document.getElementById('add-category-btn').addEventListener('click', function() {
        categoryForm.reset();
        document.getElementById('category_id').value = 0;
        document.getElementById('category-modal-title').textContent = 'Add Category';
    });

    document.querySelectorAll('.edit-category-btn').forEach(button => {
        button.addEventListener('click', function() {
            const categoryId = this.dataset.categoryId;
            fetch(`ajax_category_crud.php?action=get_category&category_id=${categoryId}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('category-modal-title').textContent = 'Edit Category';
                    document.getElementById('category_id').value = data.category_id;
                    document.getElementById('category_name').value = data.category_name;
                });
        });
    });

    categoryForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(categoryForm);
        fetch('ajax_category_crud.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                categoryModal.hide();
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        });
    });

    document.querySelectorAll('.delete-category-btn').forEach(button => {
        button.addEventListener('click', function() {
            if (confirm('Are you sure you want to delete this category?')) {
                const categoryId = this.dataset.categoryId;
                const formData = new FormData();
                formData.append('action', 'delete_category');
                formData.append('category_id', categoryId);

                fetch('ajax_category_crud.php', {
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