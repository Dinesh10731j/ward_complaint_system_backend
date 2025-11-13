<?php

header("Access-Control-Allow-Origin: http://127.0.0.1:5500");
header("Access-Control-Allow-Methods: DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

header("Content-Type: application/json");

require_once __DIR__ . '/../config/middleware.php';
require_once __DIR__ . '/../config/db.php';

// Authenticate user using JWT
$userData = authenticate();

// Allow only DELETE requests
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    echo json_encode(['status' => 'error', 'message' => 'Only DELETE requests are allowed']);
    exit;
}

// Read raw JSON input
$data = json_decode(file_get_contents("php://input"), true);

// Validate user_id
if (!isset($data['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'user_id is required']);
    exit;
}

$user_id = intval($data['user_id']);

// Prepare and execute delete query using MySQLi
$sql = "DELETE FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode(['status' => 'success', 'message' => 'User deleted successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'User not found']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to delete user']);
}

$stmt->close();
$conn->close();

?>
