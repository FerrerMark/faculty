<?php
include_once "../connections/connection.php";
session_start();

$faculty_id = $_SESSION['id'];
$pending_id = $_POST['pending_id'];

$query = "DELETE FROM pending_deletions WHERE pending_id = :pending_id AND faculty_id = :faculty_id AND status = 'Pending'";
$stmt = $conn->prepare($query);
$stmt->bindParam(':pending_id', $pending_id, PDO::PARAM_INT);
$stmt->bindParam(':faculty_id', $faculty_id, PDO::PARAM_INT);
$stmt->execute();

$_SESSION['message'] = "Deletion request canceled.";
header("Location: ../frame/available.php");
exit();
?>