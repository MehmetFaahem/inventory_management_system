<?php
// function.php

function fill_category_list(mysqli $connect): string
{
    $query = "
    SELECT * FROM category 
    WHERE category_status = 'active' 
    ORDER BY category_name ASC
    ";
    $statement = $connect->prepare($query);
    $statement->execute();
    $result = $statement->get_result();
    $output = '';
    while ($row = $result->fetch_assoc()) {
        $output .= '<option value="' . htmlspecialchars($row["category_id"]) . '">' . htmlspecialchars($row["category_name"]) . '</option>';
    }
    return $output;
}

function fill_brand_list(mysqli $connect, int $category_id): string
{
    $query = "SELECT * FROM brand 
    WHERE brand_status = 'active' 
    AND category_id = ?
    ORDER BY brand_name ASC";
    $statement = $connect->prepare($query);
    $statement->bind_param("i", $category_id);
    $statement->execute();
    $result = $statement->get_result();
    $output = '<option value="">Select Brand</option>';
    while ($row = $result->fetch_assoc()) {
        $output .= '<option value="' . htmlspecialchars($row["brand_id"]) . '">' . htmlspecialchars($row["brand_name"]) . '</option>';
    }
    return $output;
}

function get_user_name(mysqli $connect, int $user_id): ?string
{
    $query = "
    SELECT user_name FROM user_details WHERE user_id = ?
    ";
    $statement = $connect->prepare($query);
    $statement->bind_param("i", $user_id);
    $statement->execute();
    $result = $statement->get_result();
    $row = $result->fetch_assoc();
    return $row ? $row['user_name'] : null;
}

function fill_product_list(mysqli $connect): string
{
    $query = "
    SELECT * FROM product 
    WHERE product_status = 'active' 
    ORDER BY product_name ASC
    ";
    $statement = $connect->prepare($query);
    $statement->execute();
    $result = $statement->get_result();
    $output = '';
    while ($row = $result->fetch_assoc()) {
        $output .= '<option value="' . htmlspecialchars($row["product_id"]) . '">' . htmlspecialchars($row["product_name"]) . '</option>';
    }
    return $output;
}

function fetch_product_details(int $product_id, mysqli $connect): array
{
    $query = "
    SELECT * FROM product 
    WHERE product_id = ?";
    $statement = $connect->prepare($query);
    $statement->bind_param("i", $product_id);
    $statement->execute();
    $result = $statement->get_result();
    $row = $result->fetch_assoc();
    return $row ? $row : [];
}

function available_product_quantity(mysqli $connect, int $product_id): int
{
    $product_data = fetch_product_details($product_id, $connect);
    $query = "
    SELECT  inventory_order_product.quantity FROM inventory_order_product 
    INNER JOIN inventory_order ON inventory_order.inventory_order_id = inventory_order_product.inventory_order_id
    WHERE inventory_order_product.product_id = ? AND
    inventory_order.inventory_order_status = 'active'
    ";
    $statement = $connect->prepare($query);
    $statement->bind_param("i", $product_id);
    $statement->execute();
    $result = $statement->get_result();
    $total = 0;
    while ($row = $result->fetch_assoc()) {
        $total += $row['quantity'];
    }
    $available_quantity = intval($product_data['quantity']) - intval($total);
    if ($available_quantity == 0) {
        $update_query = "
        UPDATE product SET 
        product_status = 'inactive' 
        WHERE product_id = ?
        ";
        $statement = $connect->prepare($update_query);
        $statement->bind_param("i", $product_id);
        $statement->execute();
    }
    return $available_quantity;
}

function count_total_user(mysqli $connect): int {
    $query = "SELECT * FROM user_details WHERE user_status='Active'";
    $statement = $connect->prepare($query);
    $statement->execute();
    $result = $statement->get_result();
    return $result->num_rows;
}


function count_total_category(mysqli $connect): int
{
    $query = "
    SELECT * FROM category WHERE category_status='active'
    ";
    $statement = $connect->prepare($query);
    $statement->execute();
    $result = $statement->get_result();
    return $result->num_rows;
}

function count_total_brand(mysqli $connect): int
{
    $query = "
    SELECT * FROM brand WHERE brand_status='active'
    ";
    $statement = $connect->prepare($query);
    $statement->execute();
    $result = $statement->get_result();
    return $result->num_rows;
}

function count_total_product(mysqli $connect): int
{
    $query = "
    SELECT * FROM product WHERE product_status='active'
    ";
    $statement = $connect->prepare($query);
    $statement->execute();
    $result = $statement->get_result();
    return $result->num_rows;
}

function count_total_order_value(mysqli $connect): ?string
{
    if (isset($_SESSION['type'])) {
        $query = "
        SELECT sum(inventory_order_total) as total_order_value FROM inventory_order 
        WHERE inventory_order_status='active'
        ";
        if ($_SESSION['type'] == 'user') {
            $query .= ' AND user_id = ?';
        }
        $statement = $connect->prepare($query);
        if ($_SESSION['type'] == 'user') {
            $statement->bind_param("i", $_SESSION["user_id"]);
        }
        $statement->execute();
        $resultSet = $statement->get_result();
        $result = $resultSet->fetch_assoc();
        return $result ? number_format($result['total_order_value'], 2) : null;
    }
    return null;
}

function count_total_cash_order_value(mysqli $connect): ?string
{
    if (isset($_SESSION['type'])) {
        $query = "
        SELECT sum(inventory_order_total) as total_order_value FROM inventory_order 
        WHERE payment_status = 'cash' 
        AND inventory_order_status='active'
        ";
        if ($_SESSION['type'] == 'user') {
            $query .= ' AND user_id = ?';
        }
        $statement = $connect->prepare($query);
        if ($_SESSION['type'] == 'user') {
            $statement->bind_param("i", $_SESSION["user_id"]);
        }
        $statement->execute();
        $resultSet = $statement->get_result();
        $result = $resultSet->fetch_assoc();
        return $result ? number_format($result['total_order_value'], 2) : null;
    }
    return null;
}

function count_total_credit_order_value(mysqli $connect): ?string
{
    if (isset($_SESSION['type'])) {
        $query = "
        SELECT sum(inventory_order_total) as total_order_value FROM inventory_order WHERE payment_status = 'credit' AND inventory_order_status='active'
        ";
        if ($_SESSION['type'] == 'user') {
            $query .= ' AND user_id = ?';
        }
        $statement = $connect->prepare($query);
        if ($_SESSION['type'] == 'user') {
            $statement->bind_param("i", $_SESSION["user_id"]);
        }
        $statement->execute();
        $resultSet = $statement->get_result();
        $result = $resultSet->fetch_assoc();
        return $result ? number_format($result['total_order_value'], 2) : null;
    }
    return null;
}

function get_user_wise_total_order(mysqli $connect): string
{
    $query = '
    SELECT sum(inventory_order.inventory_order_total) as order_total, 
    SUM(CASE WHEN inventory_order.payment_status = "cash" THEN inventory_order.inventory_order_total ELSE 0 END) AS cash_order_total, 
    SUM(CASE WHEN inventory_order.payment_status = "credit" THEN inventory_order.inventory_order_total ELSE 0 END) AS credit_order_total, 
    user_details.user_name 
    FROM inventory_order 
    INNER JOIN user_details ON user_details.user_id = inventory_order.user_id 
    WHERE inventory_order.inventory_order_status = "active" GROUP BY inventory_order.user_id
    ';
    $statement = $connect->prepare($query);
    $statement->execute();
    $resultSet = $statement->get_result();
    $result = $resultSet->fetch_all(MYSQLI_ASSOC);
    $output = '
    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <tr>
                <th>User Name</th>
                <th>Total Order Value</th>
                <th>Total Cash Order</th>
                <th>Total Credit Order</th>
            </tr>
    ';

    $total_order = 0;
    $total_cash_order = 0;
    $total_credit_order = 0;
    foreach ($result as $row) {
        $userName = isset($row['user_name']) ? $row['user_name'] : 'Default'; // Set a default value if 'user_name' is not set

        $output .= '
        <tr>
            <td>' . htmlspecialchars($userName) . '</td>
            <td align="right">$ ' . number_format($row["order_total"], 2) . '</td>
            <td align="right">$ ' . number_format($row["cash_order_total"], 2) . '</td> 
            <td align="right">$ ' . number_format($row["credit_order_total"], 2) . '</td>
        </tr>
        ';

        $total_order += $row["order_total"];
        $total_cash_order += $row["cash_order_total"];
        $total_credit_order += $row["credit_order_total"];
    }

    $output .= '
    <tr>
        <td align="right"><b>Total</b></td>
        <td align="right"><b>$ ' . number_format($total_order, 2) . '</b></td>
        <td align="right"><b>$ ' . number_format($total_cash_order, 2) . '</b></td>
        <td align="right"><b>$ ' . number_format($total_credit_order, 2) . '</b></td>
    </tr></table></div>
    ';
    return $output;
}

?>
