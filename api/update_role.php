<?php
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

require_once "../config/headers.php";
require_once "../config/db.php";
require_once "../vendor/autoload.php";

use Dotenv\Dotenv;


$dotenv = Dotenv::createImmutable(__DIR__ . "/..");
$dotenv->load();
$jwtSecretKey = $_ENV['JWT_SECRET'];


if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Only POST requests are allowed']);
    exit;
}

$headers = getallheaders();
if (empty($headers['Authorization'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Missing Authorization header']);
    exit;
}

$jwt = str_replace('Bearer ', '', $headers['Authorization']);

try {
    $decoded = JWT::decode($jwt, new Key($jwtSecretKey, 'HS256'));
    $userData = (array) $decoded->data;
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Invalid or expired token']);
    exit;
}

if (($userData['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Access denied. Admins only.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['user_id'], $input['new_role'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'User ID and new role are required']);
    exit;
}

$user_id  = (int) $input['user_id'];
$new_role = strtolower(trim($input['new_role']));


$allowed_roles = ['citizen', 'admin', 'ward_staff'];

if (!in_array($new_role, $allowed_roles, true)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid role value']);
    exit;
}


$check = $conn->prepare("SELECT role FROM users WHERE id = ?");
$check->bind_param("i", $user_id);
$check->execute();
$result = $check->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'User not found']);
    exit;
}

$current_role = $result->fetch_assoc()['role'];

if ($current_role === $new_role) {
    http_response_code(200);
    echo json_encode([
        'status'  => 'info',
        'message' => 'User already has this role'
    ]);
    exit;
}


$update = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
$update->bind_param("si", $new_role, $user_id);
$update->execute();

if ($update->affected_rows === 1) {
    echo json_encode([
        'status'  => 'success',
        'message' => 'User role updated successfully'
    ]);
} else {
    echo json_encode([
        'status'  => 'error',
        'message' => 'Role update failed'
    ]);
}

$check->close();
$update->close();
$conn->close();
