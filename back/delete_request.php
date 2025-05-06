<?php
include_once "../connections/connection.php";
session_start();

$faculty_id = $_SESSION['id'];
$subject_code = $_POST['subject_code'];

$query = "INSERT INTO pending_deletions (faculty_id, subject_code) VALUES (:faculty_id, :subject_code)";
$stmt = $conn->prepare($query);
$stmt->bindParam(':faculty_id', $faculty_id, PDO::PARAM_INT);
$stmt->bindParam(':subject_code', $subject_code, PDO::PARAM_STR);
$stmt->execute();

header("Location: ../frame/available.php");
exit();
?>