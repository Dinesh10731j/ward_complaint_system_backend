<?php
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
require_once "../config/headers.php";
require_once "../config/db.php";
require_once "../vendor/autoload.php";
use Dotenv\Dotenv;

// ✅ Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . "/..");
$dotenv->load();
$jwtSecretKey = $_ENV['JWT_SECRET'];

// ✅ Handle OPTIONS request (CORS preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ✅ Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Only POST requests are allowed']);
    exit;
}

// ✅ Get Authorization header
$headers = getallheaders();
if (!isset($headers['Authorization'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Missing Authorization header']);
    exit;
}

$authHeader = $headers['Authorization'];
$jwt = str_replace('Bearer ', '', $authHeader);

// ✅ Decode JWT
try {
    $decoded = JWT::decode($jwt, new Key($jwtSecretKey, 'HS256'));
    $userData = (array) $decoded->data; // extract payload data
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Invalid or expired token']);
    exit;
}

// ✅ Check if user is admin
if (!isset($userData['role']) || $userData['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Access denied. Admins only.']);
    exit;
}

// ✅ Parse JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['user_id']) || !isset($input['new_role'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'User ID and new role are required']);
    exit;
}

$user_id = intval($input['user_id']);
$new_role = mysqli_real_escape_string($conn, trim($input['new_role']));

// ✅ Allowed roles
$allowed_roles = ['admin', 'ward_staff', 'citizen'];
if (!in_array($new_role, $allowed_roles)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid role value']);
    exit;
}

// ✅ Update role in database
$stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database prepare error: ' . $conn->error]);
    exit;
}

$stmt->bind_param("si", $new_role, $user_id);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    echo json_encode(['status' => 'success', 'message' => 'User role updated successfully']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'User not found or role unchanged']);
}

$stmt->close();
$conn->close();
?>
