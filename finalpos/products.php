<?php
include 'includes/db_connect.php';
include 'includes/auth_check.php';
include 'includes/functions.php';
check_access(['Admin', 'Manager']);
include 'includes/header.php';
include 'includes/sidebar.php';

$products_result = $mysqli->query("SELECT p.product_id, p.product_name, c.category_name, p.mrp, p.tax_rate, rl.reorder_level FROM products p LEFT JOIN categories c ON p.category_id = c.category_id LEFT JOIN reorder_levels rl ON p.product_id = rl.product_id ORDER BY p.product_name ASC");
$products = $products_result->fetch_all(MYSQLI_ASSOC);

$categories_result = $mysqli->query("SELECT category_id, category_name FROM categories ORDER BY category_name ASC");
$categories = $categories_result->fetch_all(MYSQLI_ASSOC);
?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Product Management</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#product-modal" id="add-product-btn">Add Product</button>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-striped table-sm">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Product Name</th>
                    <th>Category</th>
                    <th>MRP</th>
                    <th>Tax Rate</th>
                    <th>Reorder Level</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                    <tr>
                        <td><?php echo $product['product_id']; ?></td>
                        <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                        <td><?php echo htmlspecialchars($product['category_name'] ?? 'N/A'); ?></td>
                        <td><?php echo number_format($product['mrp'], 2); ?></td>
                        <td><?php echo number_format($product['tax_rate'] * 100, 2); ?>%</td>
                        <td><?php echo $product['reorder_level'] ?? 'N/A'; ?></td>
                        <td>
                            <button class="btn btn-sm btn-info edit-product-btn" data-bs-toggle="modal" data-bs-target="#product-modal" data-product-id="<?php echo $product['product_id']; ?>">Edit</button>
                            <button class="btn btn-sm btn-danger delete-product-btn" data-product-id="<?php echo $product['product_id']; ?>">Delete</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="modal fade" id="product-modal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="product-modal-title">Add Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="product-form">
                        <input type="hidden" name="action" value="save_product">
                        <input type="hidden" name="product_id" id="product_id">
                        <div class="mb-3">
                            <label for="product_name" class="form-label">Product Name</label>
                            <input type="text" class="form-control" id="product_name" name="product_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="mrp" class="form-label">MRP</label>
                            <input type="number" step="0.01" class="form-control" id="mrp" name="mrp" required>
                        </div>
                        <div class="mb-3">
                            <label for="tax_rate" class="form-label">Tax Rate (e.g., 0.05 for 5%)</label>
                            <input type="number" step="0.01" class="form-control" id="tax_rate" name="tax_rate" required>
                        </div>
                        <div class="mb-3">
                            <label for="reorder_level" class="form-label">Reorder Level</label>
                            <input type="number" class="form-control" id="reorder_level" name="reorder_level" required>
                        </div>
                        <div class="mb-3">
                            <label for="category_id" class="form-label">Category</label>
                            <select class="form-select" id="category_id" name="category_id">
                                <option value="">No Category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['category_id']; ?>"><?php echo htmlspecialchars($category['category_name']); ?></option>
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
    const productModal = new bootstrap.Modal(document.getElementById('product-modal'));
    const productForm = document.getElementById('product-form');

    document.getElementById('add-product-btn').addEventListener('click', function() {
        productForm.reset();
        document.getElementById('product_id').value = 0;
        document.getElementById('product-modal-title').textContent = 'Add Product';
    });

    document.querySelectorAll('.edit-product-btn').forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.dataset.productId;
            fetch(`ajax_product_crud.php?action=get_product&product_id=${productId}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('product-modal-title').textContent = 'Edit Product';
                    document.getElementById('product_id').value = data.product_id;
                    document.getElementById('product_name').value = data.product_name;
                    document.getElementById('mrp').value = data.mrp;
                    document.getElementById('tax_rate').value = data.tax_rate;
                    document.getElementById('reorder_level').value = data.reorder_level;
                    document.getElementById('category_id').value = data.category_id;
                });
        });
    });

    productForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(productForm);
        fetch('ajax_product_crud.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                productModal.hide();
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        });
    });

    document.querySelectorAll('.delete-product-btn').forEach(button => {
        button.addEventListener('click', function() {
            if (confirm('Are you sure you want to delete this product?')) {
                const productId = this.dataset.productId;
                const formData = new FormData();
                formData.append('action', 'delete_product');
                formData.append('product_id', productId);

                fetch('ajax_product_crud.php', {
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