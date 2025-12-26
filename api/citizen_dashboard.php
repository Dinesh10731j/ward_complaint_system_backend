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

// Get total complaints and count by status
$totalQuery = "SELECT COUNT(*) as total FROM complaints";
$pendingQuery = "SELECT COUNT(*) as pending FROM complaints WHERE status = 'pending'";
$inProgressQuery = "SELECT COUNT(*) as in_progress FROM complaints WHERE status = 'in_progress'";
$resolvedQuery = "SELECT COUNT(*) as resolved FROM complaints WHERE status = 'solved'";

$totalResult = $conn->query($totalQuery);
$pendingResult = $conn->query($pendingQuery);
$inProgressResult = $conn->query($inProgressQuery);
$resolvedResult = $conn->query($resolvedQuery);

if ($totalResult && $pendingResult && $inProgressResult && $resolvedResult) {
    $total = $totalResult->fetch_assoc()['total'];
    $pending = $pendingResult->fetch_assoc()['pending'];
    $inProgress = $inProgressResult->fetch_assoc()['in_progress'];
    $resolved = $resolvedResult->fetch_assoc()['resolved'];

    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'message' => 'Complaint statistics retrieved successfully',
        'data' => [
            'Total' => (int)$total,
            'Pending' => (int)$pending,
            'In Progress' => (int)$inProgress,
            'Resolved' => (int)$resolved
        ],
        'user' => $userData
    ]);
} else {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Failed to fetch complaint statistics: ' . $conn->error]);
}

$conn->close();
?>
