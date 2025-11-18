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
$expiring_stock_query = $mysqli->query("SELECT p.name, sb.batch_number, sb.expiry_date FROM stock_batches sb JOIN products p ON sb.product_id = p.Mid WHERE sb.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 90 DAY) AND sb.current_qty > 0 ORDER BY sb.expiry_date ASC");
$expiring_stock = $expiring_stock_query->fetch_all(MYSQLI_ASSOC);

// Low Stock (at or below reorder level)
$low_stock_query = $mysqli->query("SELECT p.name, SUM(sb.current_qty) as total_qty, rl.reorder_level FROM products p JOIN stock_batches sb ON p.Mid = sb.product_id JOIN reorder_levels rl ON p.Mid = rl.product_id GROUP BY p.Mid, rl.reorder_level HAVING total_qty <= rl.reorder_level");
$low_stock = $low_stock_query->fetch_all(MYSQLI_ASSOC);
?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Dashboard</h1>
    </div>

    <!-- ... (rest of dashboard content is the same) -->
</main>

<?php
include 'includes/footer.php';
?>