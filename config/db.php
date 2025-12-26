<?php
// backend/db.php

$host = 'localhost';     
$dbname = 'ward_complain';
$username = 'root';
$password = 'root';
$port = 3307;

// Create connection
$conn = new mysqli($host, $username, $password, $dbname, $port);

// Check connection
if ($conn->connect_error) {
    echo json_encode(['status'=>'error','message'=>'Database connection failed']);
    exit;
}

?>
