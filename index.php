<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Processing</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #ffff;
            padding: 40px;
            color: #333;
        }

        .container {
            max-width: 900px;
            margin:auto;
            background: #ffff;
            padding: 20px;
            border-radius: 20px;
        }

        h1 {
            text-align: center;
            color: #4CAF50;
            font-size: 2.2rem;
            margin-bottom: 20px;
        }

        h2 {
            color: #333;
            font-size: 1.6rem;
            margin-bottom: 15px;
        }

        form {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin: 15px 0 5px;
            font-weight: bold;
            color: #555;
        }

        input[type="text"], textarea, select {
            width: 100%;
            padding: 12px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 15px;
            font-size: 16px;
            box-sizing: border-box;
        }

        textarea {
            resize: vertical;
            height: 300px;
        }

        input[type="submit"] {
            background-color: #4CAF50;
            color: white;
            padding: 12px 20px;
            margin-top: 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }

        input[type="submit"]:hover {
            background-color: green;
        }

        select {
            padding: 12px;
            border-radius: 6px;
            width: 100%;
            font-size: 16px;
            border: 1px solid #ccc;
           }

        .report-dropdown {
            position: absolute;
            top: 40px;
            right: 30px;
        }

    </style>
</head>
<body>
    <div class="container">
        <h1>NEW ORDER</h1>
    <div class="report-dropdown">
            <h3>View Reports</h3>
            <form>
                <select id="report_type" name="report_type" onchange="location = this.value;">
                    <option value="" selected disabled>Select a report</option>
                    <option value="process_order.php">Orders List</option>
                    <option value="product_report.php">Product Report</option>
                    <option value="customer_report.php">Customer Report</option>
                </select>
            </form>
        </div>
        <form action="process_order.php" method="POST">
            <label for="json_input">Enter JSON Input:</label>
            <textarea id="json_input" name="json_input" required></textarea>

            <input type="submit" value="Process Order">
        </form>
        </div>
</body>
</html>
