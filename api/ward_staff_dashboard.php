<?php
require_once __DIR__ . '/../config/middleware.php';
require_once "../config/headers.php";
require_once "../config/db.php";



try {
    // Helper function to run query safely
    function getCount($conn, $sql, $label) {
        $result = $conn->query($sql);

        if (!$result) {
            throw new Exception("Query failed for $label: " . $conn->error);
        }

        $row = $result->fetch_assoc();
        return (int)($row[array_key_first($row)] ?? 0);
    }

    // Queries
    $pending = getCount($conn, "SELECT COUNT(*) AS count FROM complaints WHERE status = 'pending'", "pending");
    $inprogress = getCount($conn, "SELECT COUNT(*) AS count FROM complaints WHERE status = 'in_progress'", "in_progress");
    $resolved = getCount($conn, "SELECT COUNT(*) AS count FROM complaints WHERE status = 'solved'", "resolved");
    $total_users = getCount($conn, "SELECT COUNT(*) AS count FROM users", "total_users");

    echo json_encode([
        "success" => true,
        "data" => [
            "pending" => $pending,
            "in_progress" => $inprogress,
            "resolved" => $resolved,
            "total_users" => $total_users
        ]
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
?>
