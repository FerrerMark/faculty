<?php
session_start();

if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

include_once("../connections/connection.php");

$faculty_id = $_SESSION['id'];
$sql = "SELECT f.role, f.departmentID, p.program_code 
        FROM faculty f 
        JOIN programs p ON f.departmentID = p.program_code 
        WHERE f.faculty_id = :faculty_id";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':faculty_id', $faculty_id, PDO::PARAM_INT);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user['role'] !== "Department Head") {
    die("Access denied: Only Department Heads can submit or cancel requests.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['request_id'])) {
        $request_id = filter_input(INPUT_POST, 'request_id', FILTER_VALIDATE_INT);

        if (!$request_id) {
            header("Location: ../frame/faculty_request_form.php?success=false&error=Invalid request ID");
            exit();
        }

        $sql = "SELECT status, department FROM faculty_requests 
                WHERE request_id = :request_id AND submitted_by = :faculty_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':request_id', $request_id, PDO::PARAM_INT);
        $stmt->bindParam(':faculty_id', $faculty_id, PDO::PARAM_INT);
        $stmt->execute();
        $request = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$request) {
            header("Location: ../frame/faculty_request_form.php?success=false&error=Request not found or unauthorized");
            exit();
        }

        if ($request['status'] !== "Pending") {
            header("Location: ../frame/faculty_request_form.php?success=false&error=Only Pending requests can be cancelled");
            exit();
        }

        if ($request['department'] !== $user['program_code']) {
            header("Location: ../frame/faculty_request_form.php?success=false&error=Unauthorized department");
            exit();
        }

        $sql = "UPDATE faculty_requests SET status = 'Cancelled' 
                WHERE request_id = :request_id AND submitted_by = :faculty_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':request_id', $request_id, PDO::PARAM_INT);
        $stmt->bindParam(':faculty_id', $faculty_id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            header("Location: ../frame/faculty_request_form.php?cancelled=true");
        } else {
            $errorInfo = $stmt->errorInfo();
            header("Location: ../frame/faculty_request_form.php?success=false&error=" . urlencode($errorInfo[2]));
        }
        exit();
    }

    $department = filter_input(INPUT_POST, 'department', FILTER_SANITIZE_STRING);
    $role = filter_input(INPUT_POST, 'role', FILTER_SANITIZE_STRING);
    $employment_status = filter_input(INPUT_POST, 'employment_status', FILTER_SANITIZE_STRING);
    $specialization = filter_input(INPUT_POST, 'specialization', FILTER_SANITIZE_STRING);
    $reason = filter_input(INPUT_POST, 'reason', FILTER_SANITIZE_STRING);
    $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
    $urgency = filter_input(INPUT_POST, 'urgency', FILTER_SANITIZE_STRING);
    $start_date = filter_input(INPUT_POST, 'start_date', FILTER_SANITIZE_STRING);
    $submitted_by = $faculty_id;

    if ($department !== $user['program_code']) {
        die("Error: You can only request faculty for your own department.");
    }

    if (empty($department) || empty($role) || empty($employment_status) || 
        empty($reason) || !$quantity || empty($urgency) || empty($start_date)) {
        header("Location: ../frame/faculty_request_form.php?success=false&error=All required fields must be filled.");
        exit();
    }

    $sql = "INSERT INTO faculty_requests (
                department, role, employment_status, specialization, 
                reason, quantity, urgency, start_date, submitted_by
            ) VALUES (:department, :role, :employment_status, :specialization, 
                      :reason, :quantity, :urgency, :start_date, :submitted_by)";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':department', $department, PDO::PARAM_STR);
    $stmt->bindParam(':role', $role, PDO::PARAM_STR);
    $stmt->bindParam(':employment_status', $employment_status, PDO::PARAM_STR);
    $stmt->bindParam(':specialization', $specialization, PDO::PARAM_STR);
    $stmt->bindParam(':reason', $reason, PDO::PARAM_STR);
    $stmt->bindParam(':quantity', $quantity, PDO::PARAM_INT);
    $stmt->bindParam(':urgency', $urgency, PDO::PARAM_STR);
    $stmt->bindParam(':start_date', $start_date, PDO::PARAM_STR);
    $stmt->bindParam(':submitted_by', $submitted_by, PDO::PARAM_INT);

    if ($stmt->execute()) {
        header("Location: ../frame/faculty_request_form.php?success=true");
    } else {
        $errorInfo = $stmt->errorInfo();
        header("Location: ../frame/faculty_request_form.php?success=false&error=" . urlencode($errorInfo[2]));
    }
}
?>
