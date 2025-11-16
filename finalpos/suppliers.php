<?php
include 'includes/db_connect.php';
include 'includes/auth_check.php';
include 'includes/functions.php';
check_access(['Admin', 'Manager']);
include 'includes/header.php';
include 'includes/sidebar.php';

// Fetch suppliers from the database
$suppliers_result = $mysqli->query("SELECT supplier_id, name, phone, gst_number FROM suppliers ORDER BY name ASC");
$suppliers = $suppliers_result->fetch_all(MYSQLI_ASSOC);
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Supplier Management</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#supplier-modal" id="add-supplier-btn">Add Supplier</button>
        </div>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-striped table-sm">
        <thead>
            <tr>
                <th scope="col">#</th>
                <th scope="col">Supplier Name</th>
                <th scope="col">Phone</th>
                <th scope="col">GST Number</th>
                <th scope="col">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($suppliers as $supplier): ?>
                <tr>
                    <td><?php echo $supplier['supplier_id']; ?></td>
                    <td><?php echo htmlspecialchars($supplier['name']); ?></td>
                    <td><?php echo htmlspecialchars($supplier['phone']); ?></td>
                    <td><?php echo htmlspecialchars($supplier['gst_number']); ?></td>
                    <td>
                        <button class="btn btn-sm btn-info edit-supplier-btn" data-bs-toggle="modal" data-bs-target="#supplier-modal" data-supplier-id="<?php echo $supplier['supplier_id']; ?>">Edit</button>
                        <button class="btn btn-sm btn-danger delete-supplier-btn" data-supplier-id="<?php echo $supplier['supplier_id']; ?>">Delete</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Supplier Modal -->
<div class="modal fade" id="supplier-modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="supplier-modal-title">Add Supplier</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="supplier-form">
                    <input type="hidden" name="action" value="save_supplier">
                    <input type="hidden" name="supplier_id" id="supplier_id">
                    <div class="mb-3">
                        <label for="supplier_name" class="form-label">Supplier Name</label>
                        <input type="text" class="form-control" id="supplier_name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone</label>
                        <input type="text" class="form-control" id="phone" name="phone">
                    </div>
                    <div class="mb-3">
                        <label for="gst_number" class="form-label">GST Number</label>
                        <input type="text" class="form-control" id="gst_number" name="gst_number">
                    </div>
                    <button type="submit" class="btn btn-primary">Save</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const supplierModal = new bootstrap.Modal(document.getElementById('supplier-modal'));
    const supplierForm = document.getElementById('supplier-form');

    document.getElementById('add-supplier-btn').addEventListener('click', function() {
        supplierForm.reset();
        document.getElementById('supplier_id').value = 0;
        document.getElementById('supplier-modal-title').textContent = 'Add Supplier';
    });

    document.querySelectorAll('.edit-supplier-btn').forEach(button => {
        button.addEventListener('click', function() {
            const supplierId = this.dataset.supplierId;
            fetch(`ajax_supplier_crud.php?action=get_supplier&supplier_id=${supplierId}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('supplier-modal-title').textContent = 'Edit Supplier';
                    document.getElementById('supplier_id').value = data.supplier_id;
                    document.getElementById('supplier_name').value = data.name;
                    document.getElementById('phone').value = data.phone;
                    document.getElementById('gst_number').value = data.gst_number;
                });
        });
    });

    supplierForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(supplierForm);
        fetch('ajax_supplier_crud.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                supplierModal.hide();
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        });
    });

    document.querySelectorAll('.delete-supplier-btn').forEach(button => {
        button.addEventListener('click', function() {
            if (confirm('Are you sure you want to delete this supplier?')) {
                const supplierId = this.dataset.supplierId;
                const formData = new FormData();
                formData.append('action', 'delete_supplier');
                formData.append('supplier_id', supplierId);

                fetch('ajax_supplier_crud.php', {
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