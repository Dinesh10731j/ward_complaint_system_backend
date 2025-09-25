<?php
require_once __DIR__ . '/../config/middleware.php';
require_once __DIR__ . '/../config/headers.php';
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

// Fetch all complaints
$result = $conn->query("SELECT id, name, ward, complaint, imageUrl, status, created_at FROM complaints ORDER BY created_at DESC");
if ($result) {
    $complaints = [];
    while ($row = $result->fetch_assoc()) {
        $complaints[] = $row;
    }
    echo json_encode(['status' => 'success', 'data' => $complaints,'user'=>$userData]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to fetch complaints']);
}
$conn->close();

?>