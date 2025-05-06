<?php
include_once "../session/session.php";
include_once("../connections/connection.php");

if (!$conn) {
    die("Connection failed: ");    
}


$searchQuery = isset($_GET['search']) ? $_GET['search'] : '';

try {
    if ($searchQuery) {
        $sql = "SELECT * FROM programs WHERE program_code LIKE :searchQuery OR program_name LIKE :searchQuery OR college LIKE :searchQuery";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':searchQuery' => "%$searchQuery%"]);
    } else {
        $sql = "SELECT * FROM programs";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
    }
    
    $programs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = "Error: " . $e->getMessage();
}

$conn = null;
?>
