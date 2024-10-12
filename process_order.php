<?php
require 'db.php';

$error = '';
$success = '';
$ordersProcessed = [];
$allOrders = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jsonInput = trim($_POST['json_input']);

    // Validate the JSON input
    if (empty($jsonInput)) {
        $error = 'Input cannot be empty.';
    } else {
        $decodedInput = json_decode($jsonInput, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $error = 'Invalid JSON format.';
        } else {
            $stmt = $pdo->query("SELECT SUM(stock) AS total_stock FROM products");
            $totalStock = $stmt->fetchColumn();

            if ($totalStock === 0) {
                $error = "All inventory is currently zero. Unable to process orders.";
            } else {
                foreach ($decodedInput as $order) {
                    $header = $order['Header'] ;
                    $lines = $order['Lines'];
                    $validOrder = true;
                    $orderDetails = [];

                    foreach ($lines as $line) {
                        $product = $line['Product'];
                        $quantity = $line['Quantity'];

                        $stmt = $pdo->prepare("SELECT stock FROM products WHERE product_name = :product");
                        $stmt->execute(['product' => $product]);
                        $productData = $stmt->fetch();

                        if ($productData) {
                            $remainingStock = $productData['stock'];

                            
                            if ($totalStock === 0 || $remainingStock === 0) {
                                $validOrder = false;
                                $status = 'invalid';
                                $allocation = 0;
                                $backorderedQty = 0;
                                $error .= "Order for $header: Product $product cannot be processed, as stock is depleted.<br>";

                                $insertInvalidStmt = $pdo->prepare("INSERT INTO orders (header, product_name, quantity, allocation, remaining_stock, backordered_quantity, status) 
                                    VALUES (:header, :product_name, :quantity, :allocation, :remaining_stock, :backordered_quantity, 'invalid')");
                                $insertInvalidStmt->execute([
                                    'header' => $header,
                                    'product_name' => $product,
                                    'quantity' => $quantity,
                                    'allocation' => $allocation,
                                    'remaining_stock' => $remainingStock,
                                    'backordered_quantity' => $backorderedQty
                                ]);
                                break;
                            }

                            
                            if ($quantity <= 0) {
                                $validOrder = false;
                                $status = 'invalid';
                                $allocation = 0;
                                $error .= "Invalid order for $header: Product $product has a quantity of 0.<br>";

                                $insertInvalidStmt = $pdo->prepare("INSERT INTO orders (header, product_name, quantity, allocation, remaining_stock, backordered_quantity, status) 
                                    VALUES (:header, :product_name, :quantity, :allocation, :remaining_stock, 0, 'invalid')");
                                $insertInvalidStmt->execute([
                                    'header' => $header,
                                    'product_name' => $product,
                                    'quantity' => $quantity,
                                    'allocation' => $allocation,
                                    'remaining_stock' => $remainingStock
                                ]);

                                break;
                            }

                            if ($remainingStock >= $quantity) {
                                $status = 'valid';
                                $allocation = $quantity;
                                $remainingStock -= $quantity;

                                $updateStmt = $pdo->prepare("UPDATE products SET stock = :stock WHERE product_name = :product");
                                $updateStmt->execute(['stock' => $remainingStock, 'product' => $product]);
                            } else {
                                $status = 'backorder';
                                $allocation = $remainingStock;
                                $backorderedQty = $quantity - $remainingStock;
                                $remainingStock = 0;

                                $updateStmt = $pdo->prepare("UPDATE products SET stock = :stock WHERE product_name = :product");
                                $updateStmt->execute(['stock' => $remainingStock, 'product' => $product]);
                            }

                            $backorderedQty = $status === 'backorder' ? $quantity - $allocation : 0;

                            $insertStmt = $pdo->prepare("INSERT INTO orders (header, product_name, quantity, allocation, remaining_stock, backordered_quantity, status) 
                                VALUES (:header, :product_name, :quantity, :allocation, :remaining_stock, :backordered_quantity, :status)");
                            $insertStmt->execute([
                                'header' => $header,
                                'product_name' => $product,
                                'quantity' => $quantity,
                                'allocation' => $allocation,
                                'remaining_stock' => $remainingStock,
                                'backordered_quantity' => $backorderedQty,
                                'status' => $status
                            ]);

                            $orderId = $pdo->lastInsertId();

                            $orderDetails[] = [
                                'order_id' => $orderId,
                                'header' => $header,
                                'product' => $product,
                                'quantity' => $quantity,
                                'allocation' => $allocation,
                                'remaining_stock' => $remainingStock,
                                'backordered_quantity' => $backorderedQty,
                                'status' => $status
                            ];
                        } else {
                            $validOrder = false;
                            $status = 'invalid';
                            $error .= "Invalid order for $header: Product $product does not exist.<br>";
                            break;
                        }
                    }

                    if ($validOrder) {
                        $success .= "Order processed successfully for $header with status: $status.<br>";
                        $ordersProcessed = array_merge($ordersProcessed, $orderDetails);
                    } else {
                        $error .= "Order failed for $header with status: invalid.<br>";
                    }

                    
                    $stmt = $pdo->query("SELECT SUM(stock) AS total_stock FROM products");
                    $totalStock = $stmt->fetchColumn();
                    if ($totalStock === 0) {
                        $error .= "All inventory is now zero. Halting further processing.<br>";
                        break;
                    }
                }
            }
        }
    }
}

$stmt = $pdo->query("SELECT * FROM orders");
$allOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order List</title>
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
        }

        h1 {
            text-align: center;
        }

        .error {
            color: red;
        }

        .success {
            color: green;
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

        .back-button {
            display: inline;
            margin: 20px auto;
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            text-align: center;
            border-radius: 5px;
        }

        .back-button:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Order Processing Report</h1>

        <?php if ($error) { ?>
            <div class="error"><?php echo $error; ?></div>
        <?php } ?>

        <?php if ($success) { ?>
            <div class="success"><?php echo $success; ?></div>
        <?php } ?>

        <?php if (!empty($ordersProcessed)) { ?>
            <h2>Processed Orders Details</h2>
            <table>
                <tr>
                    <th>Order ID</th>
                    <th>Header</th>
                    <th>Product</th>
                    <th>Quantity Ordered</th>
                    <th>Allocation</th>
                    <th>Remaining Stock</th>
                    <th>Backordered Quantity</th>
                    <th>Status</th>
                </tr>
                <?php foreach ($ordersProcessed as $order) { ?>
                    <tr>
                        <td><?php echo $order['order_id']; ?></td>
                        <td><?php echo $order['header']; ?></td>
                        <td><?php echo $order['product']; ?></td>
                        <td><?php echo $order['quantity']; ?></td>
                        <td><?php echo $order['allocation']; ?></td>
                        <td><?php echo $order['remaining_stock']; ?></td>
                        <td><?php echo $order['backordered_quantity']; ?></td>
                        <td style="color:<?php echo $order['status'] == 'invalid' ? 'red' : ($order['status'] == 'backorder' ? 'orange' : 'green'); ?>">
                            <?php echo $order['status']; ?>
                        </td>
                    </tr>
                <?php } ?>
            </table>
        <?php } ?>

        <h2>All Orders</h2>
        <table>
            <tr>
                <th>Order ID</th>
                <th>Header</th>
                <th>Product Name</th>
                <th>Quantity</th>
                <th>Allocation</th>
                <th>Remaining Stock</th>
                <th>Backordered Quantity</th>
                <th>Status</th>
            </tr>
            <?php foreach ($allOrders as $order) { ?>
                <tr>
                    <td><?php echo $order['order_id']; ?></td>
                    <td><?php echo $order['header']; ?></td>
                    <td><?php echo $order['product_name']; ?></td>
                    <td><?php echo $order['quantity']; ?></td>
                    <td><?php echo $order['allocation']; ?></td>
                    <td><?php echo $order['remaining_stock']; ?></td>
                    <td><?php echo $order['backordered_quantity']; ?></td>
                    <td style="color:<?php echo $order['status'] == 'invalid' ? 'red' : ($order['status'] == 'backorder' ? 'orange' : 'green'); ?>">
                        <?php echo $order['status']; ?>
                    </td>
                </tr>
            <?php } ?>
        </table>
    </div>
</body>
</html>
