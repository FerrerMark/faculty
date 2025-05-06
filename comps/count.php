<?php

// include_once "../session/session.php";

// session_start();

$department = $_SESSION['department'];

include_once "../connections/connection.php";

$faculty_count = "SELECT COUNT(*) AS total_faculty FROM faculty";
    $stmt = $pdo->prepare($faculty_count);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $faculty_count = $result['total_faculty'] ?? 0;
// echo "<br>facultyCount: " . $facultyCount;

$sectionCountQuery = "SELECT COUNT(*) as count FROM sections";
$sectionCountResult = $conn->query($sectionCountQuery);
$sectionCountData = $sectionCountResult->fetch(PDO::FETCH_ASSOC);
$sectionCount = $sectionCountData['count']; 
// echo "<br>sectionsCount: " . $sectionCount;

$depfacultycount = "SELECT COUNT(*) as count FROM faculty WHERE departmentID = :department";
$bsitCountStmt = $conn->prepare($depfacultycount);
$bsitCountStmt->execute([':department' => $department]);
$depfacultyCountData = $bsitCountStmt->fetch(PDO::FETCH_ASSOC);
$depfacultycount = $depfacultyCountData['count'];

// echo $depfacultycount;

include_once "../connections/regconnection.php";

$studentsCountQuery = "SELECT COUNT(*) as count FROM students";
$studentsCountResult = $regconn->query($studentsCountQuery);
$studentsCountData = $studentsCountResult->fetch(PDO::FETCH_ASSOC);
$studentsCount = $studentsCountData['count']; 
// echo "<br>studentsCount: " . $studentsCount;

$depStudentsCount = "SELECT COUNT(*) as count FROM students WHERE department = :department";
$bsitCountStmt = $regconn->prepare($depStudentsCount);
$bsitCountStmt->execute([':department' => $department]);
$bsitCountData = $bsitCountStmt->fetch(PDO::FETCH_ASSOC);
$depStudentsCount = $bsitCountData['count']; 
// echo "<br>$department students: " . $bsitStudentsCount;
