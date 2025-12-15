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
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['status' => 'error', 'message' => 'Only POST requests are allowed']);
    exit;
}

// === 1️⃣ Total Complaints ===
$total = 0;
$totalQuery = "SELECT COUNT(*) AS total FROM complaints";
$totalResult = $conn->query($totalQuery);
if ($totalResult && $totalResult->num_rows > 0) {
    $row = $totalResult->fetch_assoc();
    $total = (int)$row['total'];
}

// === 2️⃣ Status Counts ===
$statusData = [
    'pending' => 0,
    'in_progress' => 0,
    'solved' => 0
];

$statusQuery = "
    SELECT 
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending,
        SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) AS in_progress,
        SUM(CASE WHEN status = 'solved' THEN 1 ELSE 0 END) AS solved
    FROM complaints
";
$statusResult = $conn->query($statusQuery);
if ($statusResult && $statusResult->num_rows > 0) {
    $row = $statusResult->fetch_assoc();
    $statusData = [
        'pending' => (int)$row['pending'],
        'in_progress' => (int)$row['in_progress'],
        'solved' => (int)$row['solved']
    ];
}

// === 3️⃣ Monthly Complaint Count ===
$monthly = array_fill(1, 12, 0);
$monthlyQuery = "
    SELECT 
        MONTH(created_at) AS month, 
        COUNT(*) AS total 
    FROM complaints 
    GROUP BY MONTH(created_at)
    ORDER BY MONTH(created_at)
";
$monthlyResult = $conn->query($monthlyQuery);
if ($monthlyResult && $monthlyResult->num_rows > 0) {
    while ($row = $monthlyResult->fetch_assoc()) {
        $month = (int)$row['month'];
        $monthly[$month] = (int)$row['total'];
    }
}

// === 4️⃣ JSON Response ===
echo json_encode([
    'status' => 'success',
    'data' => [
        'total' => $total,
        'pending' => $statusData['pending'],
        'in_progress' => $statusData['in_progress'],
        'solved' => $statusData['solved'],
        'monthly' => array_values($monthly)
    ]
]);

$conn->close();
?>
