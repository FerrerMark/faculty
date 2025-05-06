<?php
include_once("../session/session.php");

include_once "../connections/connection.php";
// include_once "../registrar/sync_fetch_section.php";
// include_once "../registrar/sync_fetch_schedule.php";


// $dep = isset($_GET['department']) ? htmlspecialchars($_GET['department']) : '';
$dep = $_SESSION['department'];
// $role = isset($_GET['role']) ? htmlspecialchars($_GET['role']) : '';
$role = $_SESSION['role'];

try {
    $stmt = $pdo->prepare("
        SELECT * FROM sections
        WHERE program_code = :department
        ORDER BY section_id DESC
    ");
    $stmt->bindParam(':department', $dep);
    $stmt->execute();
    $sections = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $sections = [];
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    if (isset($_POST['action']) && $_POST['action'] === 'add_section') {
        $program_code = $_POST['program_code'];
        $section_name = $_POST['section_name'];
        $year_level = $_POST['year_level'];
        $sem = $_POST['semestrial'];


        $sem = ($sem == "First") ? "1" : "2";

        $combined = $program_code . "-" . $year_level . $sem . $section_name;

        $sem = ($sem == "1") ? "First" : "Second";


        try {

            
            $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM sections WHERE section_name = :section_name AND program_code = :program_code");
            $checkStmt->bindParam(':section_name', $combined);
            $checkStmt->bindParam(':program_code', $program_code);
            $checkStmt->execute();
            $exists = $checkStmt->fetchColumn();

            if ($exists) {
                header("Location: ../frame/sections.php?department=$program_code&role=$role&error=duplicate_section");
                exit();
            }

            $insertStmt = $pdo->prepare("INSERT INTO sections (program_code, section_name, year_level, semester) VALUES (:program_code, :section_name, :year_level, :semester)");
            $insertStmt->bindParam(':program_code', $program_code);
            $insertStmt->bindParam(':section_name', $combined);
            $insertStmt->bindParam(':year_level', $year_level);
            $insertStmt->bindParam(':semester', $sem);
            $insertStmt->execute();

            header("Location: ../frame/sections.php?department=$program_code&role=$role&success=true");

        } catch (PDOException $e) {

            header("Location: ../frame/sections.php?department=$program_code&role=$role&error=db_error");
        }
    }else if($_GET['action'] === 'delete'){

        $section_id = $_POST['section_id'];
        $stmt = $conn->prepare('DELETE FROM sections Where section_id= :section_id');
        $stmt->bindParam(':section_id', $section_id);
        $stmt->execute();

        header("Location: ../frame/sections.php?department=$dep&role=$role");
        
    }
}


