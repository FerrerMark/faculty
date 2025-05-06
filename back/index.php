<?php
include_once("./session/session.php");
include_once("./connections/connection.php");
// session_start();
if (!isset($_SESSION['id'])) {
    header("Location: ./back/logout.php");
    exit(); 
}

$id = $_SESSION['id'];
$role = $_SESSION['role'];

try {
    $sql = "SELECT * FROM `faculty` WHERE `faculty_id` = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $_SESSION['role'] = $row['role']; 
        $departmentId = $row['departmentID'];
    } else {
        header("Location: ./back/logout.php");
        exit();
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

?>