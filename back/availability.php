<?php
include_once "../connections/connection.php";
session_start();

$faculty_id = $_SESSION['id'];
$employment_status_query = "SELECT employment_status FROM faculty WHERE faculty_id = :faculty_id";
$stmt = $conn->prepare($employment_status_query);
$stmt->bindParam(':faculty_id', $faculty_id, PDO::PARAM_INT);
$stmt->execute();
$employment_status = $stmt->fetch(PDO::FETCH_ASSOC)['employment_status'];

$assigned_courses_query = "SELECT subject_code FROM faculty_courses WHERE faculty_id = :faculty_id";
$stmt = $conn->prepare($assigned_courses_query);
$stmt->bindParam(':faculty_id', $faculty_id, PDO::PARAM_INT);
$stmt->execute();
$assigned_courses = $stmt->fetchAll(PDO::FETCH_COLUMN);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_course'])) {
    $subject_code = $_POST['subject_code'];

    $query = "DELETE FROM faculty_courses WHERE faculty_id = :faculty_id AND subject_code = :subject_code";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':faculty_id', $faculty_id, PDO::PARAM_INT);
    $stmt->bindParam(':subject_code', $subject_code, PDO::PARAM_STR);
    $stmt->execute();

    header("Location: ../frame/available.php?delete_success");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_request'])) {
    $pending_id = $_POST['pending_id'];

    $query = "DELETE FROM pending_preferred_courses WHERE faculty_id = :faculty_id AND pending_id = :pending_id AND status = 'Pending'";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':faculty_id', $faculty_id, PDO::PARAM_INT);
    $stmt->bindParam(':pending_id', $pending_id, PDO::PARAM_INT);
    $stmt->execute();

    header("Location: ../frame/available.php?cancel_success=1");
    exit();
}

$available_days = ($employment_status === 'Part-Time' && isset($_POST['available_days'])) ? implode(',', $_POST['available_days']) : null;
$start_time = ($employment_status === 'Part-Time' && isset($_POST['start_time'])) ? $_POST['start_time'] : null;
$end_time = ($employment_status === 'Part-Time' && isset($_POST['end_time'])) ? $_POST['end_time'] : null;
$courses = isset($_POST['courses']) && is_array($_POST['courses']) ? $_POST['courses'] : [];

if (count($courses) === 0) {
    $_SESSION['message'] = "Please select at least one course.";
    header("Location: ../frame/available.php");
    exit();
}

$duplicate_courses = [];
$new_courses = array_diff($courses, $assigned_courses);
if (!empty($new_courses)) {
    $placeholders = implode(',', array_fill(0, count($new_courses), '?'));
    $query = "SELECT subject_code FROM pending_preferred_courses 
              WHERE faculty_id = ? AND subject_code IN ($placeholders) 
              AND status = 'Pending'";
    $stmt = $conn->prepare($query);
    $params = array_merge([$faculty_id], array_values($new_courses));
    $stmt->execute($params);
    $existing_pending = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($existing_pending)) {
        $duplicate_courses = $existing_pending;
        $_SESSION['message'] = "You have already submitted the following course(s) for approval: " . implode(', ', $duplicate_courses) . ". Duplicates are not allowed.";
        header("Location: ../frame/available.php");
        exit();
    }

    foreach ($new_courses as $subject_code) {
        $query = "INSERT INTO pending_preferred_courses (faculty_id, subject_code, available_days, start_time, end_time, status) 
                  VALUES (:faculty_id, :subject_code, :available_days, :start_time, :end_time, 'Pending')";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':faculty_id', $faculty_id, PDO::PARAM_INT);
        $stmt->bindParam(':subject_code', $subject_code, PDO::PARAM_STR);
        $stmt->bindParam(':available_days', $available_days, PDO::PARAM_STR);
        $stmt->bindParam(':start_time', $start_time, PDO::PARAM_STR);
        $stmt->bindParam(':end_time', $end_time, PDO::PARAM_STR);
        $stmt->execute();
    }
}

header("Location: ../frame/available.php?request_success");
exit();
?>