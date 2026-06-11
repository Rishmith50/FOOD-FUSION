<?php
$conn = new mysqli("localhost", "root", "Root@123", "foodfusion");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>