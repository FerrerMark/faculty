<?php
include_once "../session/session.php";

include_once "../connections/connection.php"; 

$role = $_GET['role'];

if (isset($_GET['action'])) {
    $action = $_GET['action'];

    if ($action === 'delete' && isset($_GET['program_code']) && $role === 'Dean') {
        $programCode = $_GET['program_code'];

        try {
            $stmt = $conn->prepare("DELETE FROM programs WHERE program_code = :program_code");
            $stmt->bindParam(':program_code', $programCode, PDO::PARAM_STR);
            $stmt->execute();
            header("Location: ../frame/programs.php?message=Program successfully deleted");
            exit;
        } catch (PDOException $e) {
            echo "Error deleting program: " . $e->getMessage();
        }
    } elseif ($action === 'edit' && isset($_POST['program_code']) && $role === 'Dean') {

        try {

            $programCode = $_POST['program_code'];
            $programName = $_POST['program_name'];
            $college = $_POST['college'];

            $stmt = $conn->prepare("UPDATE programs SET program_name = :program_name, college = :college WHERE program_code = :program_code");
    
            $stmt->bindParam(':program_code', $programCode, PDO::PARAM_STR);
            $stmt->bindParam(':program_name', $programName, PDO::PARAM_STR);
            $stmt->bindParam(':college', $college, PDO::PARAM_STR);
    
            $stmt->execute();
    
            header("Location: ../frame/programs.php?message=Program successfully updated&role=department%20head&department=$programCode");
            
            exit;
        } catch (PDOException $e) {
            echo "Error updating program: " . $e->getMessage();
        }
    } elseif ($action === 'add' && isset($_POST['programCode']) && $role === 'Dean') {

        try {

            $programCode = $_POST['programCode'];
            $programName = $_POST['program'];
            $college = $_POST['college'];

            $stmt = $conn->prepare("INSERT INTO programs (program_code, program_name, college) 
                                    VALUES (:program_code, :program_name, :college)");
    
            $stmt->bindParam(':program_code', $programCode, PDO::PARAM_STR);
            $stmt->bindParam(':program_name', $programName, PDO::PARAM_STR);
            $stmt->bindParam(':college', $college, PDO::PARAM_STR);
    
            $stmt->execute();

            $departmentID = $_SESSION['departmentID'];
            header("Location: ../frame/programs.php?message=Program successfully updated&role=Dean&department=". urldecode($departmentID));

            exit;
        } catch (PDOException $e) {
            echo "Error adding program: " . $e->getMessage();
        }
    } else {
        header("Location: ../frame/programs.php?error=Invalid action or missing parameters");
        exit;
    }
} else {
    header("Location: ../frame/programs.php?error=No action specified");
    exit;
}
?>
