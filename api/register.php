<?php
require_once "../config/headers.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Only POST requests are allowed']);
    exit;
}

require_once '../config/db.php';  // $conn is the MySQLi connection object

if (!isset($conn)) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection not established']);
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

// Update complaint status
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
    echo json_encode(['status' => 'error', 'message' => 'Failed to update complaint: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
