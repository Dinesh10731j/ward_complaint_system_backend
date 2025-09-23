<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");
require_once '../config/db.php';  // $conn is the MySQLi connection object
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['status' => 'error', 'message' => 'Only GET requests are allowed']);
    exit;
}
if (!isset($conn)) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection not established']);
    exit;
}

// Fetch all complaints
$result = $conn->query("SELECT id, name, ward, complaint, imageUrl, status, created_at FROM complaints ORDER BY created_at DESC");
if ($result) {
    $complaints = [];
    while ($row = $result->fetch_assoc()) {
        $complaints[] = $row;
    }
    echo json_encode(['status' => 'success', 'data' => $complaints]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to fetch complaints']);
}
$conn->close();

?>