<?php


header('Content-Type: application/json');

require_once '../config/db.php'; // $conn

$data = json_decode(file_get_contents('php://input'), true);

$name = trim($data['name'] ?? '');
$ward = trim($data['ward'] ?? '');
$complaint = trim($data['complaint'] ?? '');

// Validate
if (empty($name) || empty($ward) || empty($complaint)) {
    echo json_encode(['status' => 'error', 'message' => 'All fields are required']);
    exit;
}

// Default status = pending
$status = 'pending';

// Insert complaint
$stmt = $conn->prepare("INSERT INTO complaints (name, ward, complaint, status) VALUES (?, ?, ?, ?)");
if (!$stmt) {
    echo json_encode(['status' => 'error', 'message' => 'DB error: ' . $conn->error]);
    exit;
}

$stmt->bind_param("ssss", $name, $ward, $complaint, $status);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Complaint submitted', 'complaint_id' => $stmt->insert_id]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to submit complaint']);
}

$stmt->close();
$conn->close();
