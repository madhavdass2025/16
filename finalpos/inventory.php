<?php
include 'includes/db_connect.php';
include 'includes/auth_check.php';
include 'includes/functions.php';
check_access(['Admin', 'Manager']);
include 'includes/header.php';
include 'includes/sidebar.php';

$inventory_result = $mysqli->query("SELECT p.product_name, sb.batch_number, sb.expiry_date, sb.current_qty, sb.purchase_price FROM stock_batches sb JOIN products p ON sb.product_id = p.product_id WHERE sb.current_qty > 0 ORDER BY p.product_name, sb.expiry_date ASC");
$inventory = $inventory_result->fetch_all(MYSQLI_ASSOC);
?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Inventory Management</h1>
    </div>

    <div class="table-responsive">
        <table class="table table-striped table-sm">
            <thead>
                <tr>
                    <th scope="col">Product</th>
                    <th scope="col">Batch Number</th>
                    <th scope="col">Expiry Date</th>
                    <th scope="col">Current Quantity</th>
                    <th scope="col">Purchase Price</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($inventory)): ?>
                    <?php foreach ($inventory as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                            <td><?php echo htmlspecialchars($item['batch_number']); ?></td>
                            <td><?php echo date("M d, Y", strtotime($item['expiry_date'])); ?></td>
                            <td><?php echo $item['current_qty']; ?></td>
                            <td><?php echo number_format($item['purchase_price'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center">No inventory found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

<?php
include 'includes/footer.php';
?>