<?php
$servername = "mysql-container";  // MySQL container name
$username = "root";               // MySQL username
$password = "ems_sem";       // MySQL password
$dbname = "Lab3";        // MySQL database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
