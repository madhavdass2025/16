<?php
include 'includes/db_connect.php';
include 'includes/auth_check.php';
include 'includes/header.php';
include 'includes/sidebar.php';

// Today's Sales
$sales_query = $mysqli->query("SELECT SUM(net_amount) as total_sales FROM sales_invoices WHERE DATE(invoice_date) = CURDATE()");
$todays_sales = $sales_query->fetch_assoc()['total_sales'] ?? 0;

// Total Inventory Value
$inventory_query = $mysqli->query("SELECT SUM(current_qty * purchase_price) as total_value FROM stock_batches");
$inventory_value = $inventory_query->fetch_assoc()['total_value'] ?? 0;

// Pending Purchase Orders
$po_query = $mysqli->query("SELECT COUNT(*) as pending_count FROM purchase_orders WHERE status = 'Pending'");
$pending_pos = $po_query->fetch_assoc()['pending_count'] ?? 0;

// Expiring Stock (less than 90 days)
$expiring_stock_query = $mysqli->query("SELECT p.product_name, sb.batch_number, sb.expiry_date FROM stock_batches sb JOIN products p ON sb.product_id = p.product_id WHERE sb.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 90 DAY) AND sb.current_qty > 0 ORDER BY sb.expiry_date ASC");
$expiring_stock = $expiring_stock_query->fetch_all(MYSQLI_ASSOC);

// Low Stock (at or below reorder level)
$low_stock_query = $mysqli->query("SELECT p.product_name, SUM(sb.current_qty) as total_qty, rl.reorder_level FROM products p JOIN stock_batches sb ON p.product_id = sb.product_id JOIN reorder_levels rl ON p.product_id = rl.product_id GROUP BY p.product_id, rl.reorder_level HAVING total_qty <= rl.reorder_level");
$low_stock = $low_stock_query->fetch_all(MYSQLI_ASSOC);
?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Dashboard</h1>
    </div>

    <!-- KPIs -->
    <div class="row">
        <div class="col-md-4">
            <div class="card text-white bg-primary mb-3">
                <div class="card-header">Today's Sales</div>
                <div class="card-body">
                    <h5 class="card-title">$<?php echo number_format($todays_sales, 2); ?></h5>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-success mb-3">
                <div class="card-header">Inventory Value</div>
                <div class="card-body">
                    <h5 class="card-title">$<?php echo number_format($inventory_value, 2); ?></h5>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-warning mb-3">
                <div class="card-header">Pending POs</div>
                <div class="card-body">
                    <h5 class="card-title"><?php echo $pending_pos; ?></h5>
                </div>
            </div>
        </div>
    </div>

    <!-- Alerts -->
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-danger text-white">
                    Expiring Stock Alert (< 90 Days)
                </div>
                <div class="card-body">
                    <?php if (!empty($expiring_stock)): ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($expiring_stock as $item): ?>
                                <li class="list-group-item">
                                    <?php echo htmlspecialchars($item['product_name']); ?> (Batch: <?php echo htmlspecialchars($item['batch_number']); ?>) - Expires: <?php echo date("M d, Y", strtotime($item['expiry_date'])); ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="card-text">No products are expiring soon.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-info text-white">
                    Low Stock Alert
                </div>
                <div class="card-body">
                     <?php if (!empty($low_stock)): ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($low_stock as $item): ?>
                                <li class="list-group-item">
                                    <?php echo htmlspecialchars($item['product_name']); ?> - Current: <?php echo $item['total_qty']; ?>, Reorder at: <?php echo $item['reorder_level']; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="card-text">No products are below their reorder level.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<?php
include 'includes/footer.php';
?>