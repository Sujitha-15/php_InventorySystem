<?php
require 'db.php';

$query = "
    SELECT
        header,
        COUNT(DISTINCT order_id) AS total_orders,
        SUM(CASE WHEN status = 'backorder' THEN quantity - allocation ELSE 0 END) AS backorders,
        SUM(CASE WHEN status = 'invalid' THEN 1 ELSE 0 END) AS invalid_orders,
        SUM(allocation) AS total_processed_quantity
    FROM orders
    GROUP BY header
";

$stmt = $pdo->query($query);
$customerReport = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Report</title>
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
            color: white;
        }

        .no-data {
            text-align: center;
            color: gray;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Customer Report</h1>

        <?php if (empty($customerReport)) { ?>
            <p class="no-data">No data available.</p>
        <?php } else { ?>
            <table>
                <tr>
                    <th>Header</th>
                    <th>Total Orders</th>
                    <th>Backorders</th>
                    <th>Total Processed Quantity</th>
                    <th>Invalid Orders</th> 
                </tr>
                <?php foreach ($customerReport as $customer) { ?>
                    <tr>
                        <td><?php echo $customer['header']; ?></td>
                        <td><?php echo $customer['total_orders']; ?></td>
                        <td><?php echo $customer['backorders']; ?></td>
                        <td><?php echo $customer['total_processed_quantity']; ?></td>
                        <td><?php echo $customer['invalid_orders']; ?></td>
                    </tr>
                <?php } ?>
            </table>
        <?php } ?>

        <a href="index.php">Back to order</a>
    </div>
</body>
</html>
