<?php
header("Access-Control-Allow-Origin: http://127.0.0.1:5500"); 
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}


header("Content-Type: application/json");
require_once __DIR__ . '/../config/middleware.php';
require_once __DIR__ . '/../config/db.php';

$userData = authenticate();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['status' => 'error', 'message' => 'Only GET requests are allowed']);
    exit;
}

if (!isset($conn)) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection not established']);
    exit;
}

$result = $conn->query("SELECT id, name, ward, complaint, imageUrl, status, created_at 
                        FROM complaints ORDER BY created_at DESC");

if ($result) {
    $complaints = [];
    while ($row = $result->fetch_assoc()) {
        $complaints[] = $row;
    }

    echo json_encode([
        'status' => 'success',
        'data' => $complaints,
        'user' => $userData
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to fetch complaints']);
}

$conn->close();
?>
