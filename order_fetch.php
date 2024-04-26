<?php
// Start session if not already started
session_start();

// Include database connection and functions
include 'database_connection.php';
include 'function.php';

// Initialize variables
$query = '';
$output = [];

$query .= "SELECT * FROM inventory_order WHERE inventory_order_status='active'";

// Append condition for user type
if ($_SESSION['type'] === 'user') {
    $query .= ' AND user_id = ?';
}

// Append search condition
if (isset($_POST["search"]["value"])) {
    $searchValue = $_POST["search"]["value"];
    $query .= " AND (inventory_order_id LIKE ? OR inventory_order_name LIKE ? OR inventory_order_total LIKE ? OR inventory_order_status LIKE ? OR inventory_order_date LIKE ?)";
}

// Append order condition
if (isset($_POST["order"])) {
    $orderColumn = $_POST['order']['0']['column'];
    $orderDir = $_POST['order']['0']['dir'];
    $query .= ' ORDER BY ' . $orderColumn . ' ' . $orderDir;
} else {
    $query .= " ORDER BY inventory_order_id DESC";
}

// Append limit condition
if ($_POST["length"] != -1) {
    $query .= ' LIMIT ?, ?';
}

// Prepare statement
$statement = $connect->prepare($query);


// Execute statement
$statement->execute();

// Get result
$result = $statement->get_result();

// Process result
$data = [];
$filteredRows = $result->num_rows;
foreach ($result as $row) {
    $paymentStatus = ($row['payment_status'] === 'cash') ? '<span class="label label-primary">Cash</span>' : '<span class="label label-warning">Credit</span>';
    $status = ($row['inventory_order_status'] === 'active') ? '<span class="label label-success">Active</span>' : '<span class="label label-danger">Inactive</span>';
    $subArray = [];
    $subArray[] = $row['inventory_order_id'];
    $subArray[] = $row['inventory_order_name'];
    $subArray[] = $row['inventory_order_total'];
    $subArray[] = $paymentStatus;
    $subArray[] = $status;
    $subArray[] = $row['inventory_order_date'];
    if ($_SESSION['type'] === 'master') {
        $subArray[] = get_user_name($connect, $row['user_id']);
    }
    $subArray[] = '<a href="view_order.php?pdf=1&order_id=' . $row["inventory_order_id"] . '" class="btn btn-info btn-xs">View PDF</a>';
    $subArray[] = '<button type="button" name="update" id="' . $row["inventory_order_id"] . '" class="btn btn-warning btn-xs update">Update</button>';
    $subArray[] = '<button type="button" name="delete" id="' . $row["inventory_order_id"] . '" class="btn btn-danger btn-xs delete" data-status="' . $row["inventory_order_status"] . '">Delete</button>';
    $data[] = $subArray;
}

// Function to get total records
function get_total_all_records($connect): int {
    $statement = $connect->prepare("SELECT * FROM inventory_order");
    $statement->execute();
    return $statement->rowCount();
}

// Prepare output
$output = [
    "draw" => intval($_POST["draw"]),
    "recordsTotal" => get_total_all_records($connect),
    "recordsFiltered" => $filteredRows,
    "data" => $data
];

// Encode output as JSON and echo
echo json_encode($output);
?>
