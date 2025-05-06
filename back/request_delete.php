<?php
include_once "../connections/connection.php";
session_start();

$faculty_id = $_SESSION['id'];
$selected_courses = $_POST['courses'] ?? [];

$query = "SELECT subject_code FROM faculty_courses WHERE faculty_id = :faculty_id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':faculty_id', $faculty_id, PDO::PARAM_INT);
$stmt->execute();
$current_courses = $stmt->fetchAll(PDO::FETCH_COLUMN);

foreach ($selected_courses as $subject_code) {
    if (!in_array($subject_code, $current_courses)) {
        $query = "INSERT INTO pending_preferred_courses (faculty_id, subject_code, status, submission_date) 
                  VALUES (:faculty_id, :subject_code, 'Pending', NOW())";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':faculty_id', $faculty_id, PDO::PARAM_INT);
        $stmt->bindParam(':subject_code', $subject_code, PDO::PARAM_STR);
        $stmt->execute();
    }
}

$_SESSION['message'] = "Your course preferences have been submitted for review.";
header("Location: ../frame/available.php");
exit();
?>