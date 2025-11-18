<?php
include 'includes/db_connect.php';
include 'includes/auth_check.php';

if (!isset($_GET['invoice_id'])) {
    die("Invoice ID is required.");
}
$invoice_id = (int)$_GET['invoice_id'];

// Fetch invoice details
$invoice_query = $mysqli->prepare("
    SELECT si.*, c.name as customer_name, c.phone as customer_phone
    FROM sales_invoices si
    JOIN customers c ON si.customer_id = c.customer_id
    WHERE si.invoice_id = ?
");
$invoice_query->bind_param("i", $invoice_id);
$invoice_query->execute();
$invoice = $invoice_query->get_result()->fetch_assoc();

// Fetch invoice items
$items_query = $mysqli->prepare("
    SELECT p.name, ii.quantity_sold, ii.selling_price_at_sale
    FROM invoice_items ii
    JOIN stock_batches sb ON ii.batch_id = sb.batch_id
    JOIN products p ON sb.product_id = p.Mid
    WHERE ii.invoice_id = ?
");
$items_query->bind_param("i", $invoice_id);
$items_query->execute();
$items = $items_query->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Print Bill - <?php echo htmlspecialchars($invoice['invoice_number']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="card">
            <div class="card-body">
                <h2 class="text-center">Tax Invoice</h2>
                <hr>
                <div class="row">
                    <div class="col-6">
                        <strong>Invoice #:</strong> <?php echo htmlspecialchars($invoice['invoice_number']); ?><br>
                        <strong>Date:</strong> <?php echo date("d-m-Y H:i", strtotime($invoice['invoice_date'])); ?>
                    </div>
                    <div class="col-6 text-end">
                        <strong>To:</strong><br>
                        <?php echo htmlspecialchars($invoice['customer_name']); ?><br>
                        <?php echo htmlspecialchars($invoice['customer_phone']); ?>
                    </div>
                </div>
                <hr>
                <table class="table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Item</th>
                            <th>Qty</th>
                            <th>Rate</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i = 1; foreach ($items as $item): ?>
                            <tr>
                                <td><?php echo $i++; ?></td>
                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                <td><?php echo $item['quantity_sold']; ?></td>
                                <td><?php echo number_format($item['selling_price_at_sale'], 2); ?></td>
                                <td><?php echo number_format($item['quantity_sold'] * $item['selling_price_at_sale'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="4" class="text-end">Grand Total:</th>
                            <th><?php echo number_format($invoice['net_amount'], 2); ?></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        <div class="text-center mt-3 no-print">
            <button class="btn btn-primary" onclick="window.print()">Print Bill</button>
            <a href="sales.php" class="btn btn-secondary">New Sale</a>
        </div>
    </div>
</body>
</html>
