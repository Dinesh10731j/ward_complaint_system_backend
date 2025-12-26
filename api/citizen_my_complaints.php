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

// Verify JWT token
$userData = authenticate();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Only GET requests are allowed']);
    exit;
}

if (!isset($conn)) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database connection not established']);
    exit;
}

// Get user_id from JWT token (support nested `data` payload as object or array)
$userId = null;
if (isset($userData['data'])) {
    if (is_array($userData['data']) && isset($userData['data']['id'])) {
        $userId = intval($userData['data']['id']);
    } elseif (is_object($userData['data']) && isset($userData['data']->id)) {
        $userId = intval($userData['data']->id);
    }
}

if (!$userId && isset($userData['id'])) {
    $userId = intval($userData['id']);
}

if (!$userId) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'user_id not found in token']);
    exit;
}

// Get complaints for specific user
$query = "SELECT id, name, ward, complaint, imageUrl, status, created_at 
          FROM complaints 
          WHERE id = ?
          ORDER BY created_at DESC";

$stmt = $conn->prepare($query);

if (!$stmt) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Failed to prepare query: ' . $conn->error]);
    exit;
}

$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result) {
    $complaints = [];
    while ($row = $result->fetch_assoc()) {
        $complaints[] = $row;
    }

    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'message' => 'User complaints retrieved successfully',
        'user_id' => $userId,
        'total_complaints' => count($complaints),
        'data' => $complaints,
        'user' => $userData
    ]);
} else {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Failed to fetch complaints: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?>
