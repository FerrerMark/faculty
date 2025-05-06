<?php  
include_once "../session/session.php";
include_once "../connections/connection.php";

$program_code = $_GET['program_code'] ?? '';

if (empty($program_code)) {
    die("Program code is required to view sections.");
}

try {

    $stmt = $pdo->prepare("SELECT * FROM programs WHERE program_code = :program_code");
    $stmt->execute(['program_code' => $program_code]);
    $program = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$program) {
        die("Invalid program code.");
    }

    $stmt = $pdo->prepare("SELECT * FROM sections WHERE program_code = :program_code ORDER BY year_section");
    $stmt->execute(['program_code' => $program_code]);
    $sections = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        if ($_POST['action'] === 'add_section') { 
            $program_code = $_POST['program_code'] ?? '';
            $year_section = $_POST['year_section'] ?? '';
        
            if (empty($program_code) || empty($year_section)) {
                die("Program code and Year/Section are required.");
            }
        
            $stmt = $pdo->prepare("INSERT INTO sections (program_code, year_section) VALUES (:program_code, :year_section)");
            $stmt->execute([
                ':program_code' => $program_code,
                ':year_section' => $year_section
            ]);
        
            header("Location: /facultyloading/frame/class.php?program_code=" . urlencode($program_code));
            exit;
        }
        

        if ($_POST['action'] === 'edit_class') {
            $sectionId = $_POST['section_id'] ?? '';
            $yearSection = $_POST['year_section'] ?? '';

            if (empty($sectionId) || empty($yearSection)) {
                die("Section ID and Year/Section are required.");
            }

            $stmt = $pdo->prepare("UPDATE sections SET year_section = :year_section WHERE id = :section_id");
            $stmt->execute([
                ':section_id' => $sectionId,
                ':year_section' => $yearSection
            ]);

            header("Location: /facultyloading/frame/class.php?program_code=" . urlencode($program_code));
            exit;
        }

        if ($_POST['action'] === 'delete_section') { 
            $sectionId = $_POST['section_id'] ?? '';
        
            if (empty($sectionId)) {
                die("Section ID is required.");
            }
        
            $stmt = $pdo->prepare("DELETE FROM sections WHERE id = :section_id");
            $stmt->execute([
                ':section_id' => $sectionId
            ]);
        
            header("Location: /facultyloading/frame/class.php?program_code=" . urlencode($program_code));
            exit;
        }
        
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

?>
