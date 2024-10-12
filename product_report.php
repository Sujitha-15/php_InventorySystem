<?php
require 'db.php';

$query = "
    SELECT
        p.product_name,
        COALESCE(SUM(o.quantity), 0) AS total_orders,
        COALESCE(SUM(CASE WHEN o.status = 'backorder' THEN o.quantity - o.allocation ELSE 0 END), 0) AS backorders,
        p.stock AS remaining_stock
    FROM products p
    LEFT JOIN orders o ON o.product_name = p.product_name
    GROUP BY p.product_name, p.stock
";

$stmt = $pdo->query($query);
$productReport = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }

        .container {
            width: 80%;
            margin: auto;
            background: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        h1 {
            text-align: center;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
        }

        th {
            background-color: #4CAF50;
        }

        .no-data {
            text-align: center;
            color: gray;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Product Report</h1>

        <?php if (empty($productReport)) { ?>
            <p class="no-data">No data available.</p>
        <?php } else { ?>
            <table>
                <tr>
                    <th>Product</th>
                    <th>Total Orders</th>
                    <th>Backorders</th>
                    <th>Remaining Stock</th>
                </tr>
                <?php foreach ($productReport as $product) { ?>
                    <tr>
                        <td><?php echo $product['product_name']; ?></td>
                        <td><?php echo $product['total_orders']; ?></td>
                        <td><?php echo $product['backorders']; ?></td>
                        <td><?php echo $product['remaining_stock']; ?></td>
                    </tr>
                <?php } ?>
            </table>
        <?php } ?>
        <a href="index.php">Back to order</a>
    </div>
</body>
</html>
