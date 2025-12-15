<?php
header("Access-Control-Allow-Origin: http://127.0.0.1:5500"); 
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

header("Content-Type: application/json");
require_once __DIR__ . '/../config/middleware.php';
require_once __DIR__ . '/../config/db.php';

$userData = authenticate(); 

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Only POST requests are allowed']);
    exit;
}

// Get POST data (JSON)
$data = json_decode(file_get_contents('php://input'), true);
$id = trim($data['id'] ?? '');
$status = trim($data['status'] ?? '');

if (empty($id) || empty($status)) {
    echo json_encode(['status' => 'error', 'message' => 'ID and status are required']);
    exit;
}

// Use MySQLi prepared statement
$stmt = $conn->prepare("UPDATE complaints SET status = ? WHERE id = ?");
if (!$stmt) {
    echo json_encode(['status' => 'error', 'message' => 'Prepare failed: ' . $conn->error]);
    exit;
}

$stmt->bind_param("si", $status, $id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode(['status' => 'success', 'message' => 'Complaint status updated successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No complaint found with this ID']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Execute failed: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
