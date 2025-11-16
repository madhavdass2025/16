<?php
include 'includes/db_connect.php';
include 'includes/auth_check.php';
include 'includes/functions.php';
check_access(['Admin', 'Manager', 'Cashier']);
include 'includes/header.php';
include 'includes/sidebar.php';

// Fetch customers from the database
$customers_result = $mysqli->query("SELECT customer_id, name, phone, address FROM customers ORDER BY name ASC");
$customers = $customers_result->fetch_all(MYSQLI_ASSOC);
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Customer Management</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#customer-modal" id="add-customer-btn">Add Customer</button>
        </div>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-striped table-sm">
        <thead>
            <tr>
                <th scope="col">#</th>
                <th scope="col">Name</th>
                <th scope="col">Phone</th>
                <th scope="col">Address</th>
                <th scope="col">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($customers as $customer): ?>
                <tr>
                    <td><?php echo $customer['customer_id']; ?></td>
                    <td><?php echo htmlspecialchars($customer['name']); ?></td>
                    <td><?php echo htmlspecialchars($customer['phone']); ?></td>
                    <td><?php echo htmlspecialchars($customer['address']); ?></td>
                    <td>
                        <button class="btn btn-sm btn-info edit-customer-btn" data-bs-toggle="modal" data-bs-target="#customer-modal" data-customer-id="<?php echo $customer['customer_id']; ?>">Edit</button>
                        <button class="btn btn-sm btn-danger delete-customer-btn" data-customer-id="<?php echo $customer['customer_id']; ?>">Delete</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Customer Modal -->
<div class="modal fade" id="customer-modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="customer-modal-title">Add Customer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="customer-form">
                    <input type="hidden" name="action" value="save_customer">
                    <input type="hidden" name="customer_id" id="customer_id">
                    <div class="mb-3">
                        <label for="customer_name" class="form-label">Name</label>
                        <input type="text" class="form-control" id="customer_name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone</label>
                        <input type="text" class="form-control" id="phone" name="phone">
                    </div>
                    <div class="mb-3">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control" id="address" name="address"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Save</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const customerModal = new bootstrap.Modal(document.getElementById('customer-modal'));
    const customerForm = document.getElementById('customer-form');

    document.getElementById('add-customer-btn').addEventListener('click', function() {
        customerForm.reset();
        document.getElementById('customer_id').value = 0;
        document.getElementById('customer-modal-title').textContent = 'Add Customer';
    });

    document.querySelectorAll('.edit-customer-btn').forEach(button => {
        button.addEventListener('click', function() {
            const customerId = this.dataset.customerId;
            fetch(`ajax_customer_crud.php?action=get_customer&customer_id=${customerId}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('customer-modal-title').textContent = 'Edit Customer';
                    document.getElementById('customer_id').value = data.customer_id;
                    document.getElementById('customer_name').value = data.name;
                    document.getElementById('phone').value = data.phone;
                    document.getElementById('address').value = data.address;
                });
        });
    });

    customerForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(customerForm);
        fetch('ajax_customer_crud.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                customerModal.hide();
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        });
    });

    document.querySelectorAll('.delete-customer-btn').forEach(button => {
        button.addEventListener('click', function() {
            if (confirm('Are you sure you want to delete this customer?')) {
                const customerId = this.dataset.customerId;
                const formData = new FormData();
                formData.append('action', 'delete_customer');
                formData.append('customer_id', customerId);

                fetch('ajax_customer_crud.php', {
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