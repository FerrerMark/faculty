<?php
include_once("../connections/connection.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $request_id = filter_input(INPUT_POST, 'request_id', FILTER_VALIDATE_INT);
    $action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING);

    if (!$request_id) {
        header("Location: facultyrequests.php?success=false&error=Invalid request ID");
        exit();
    }

    if ($action !== "approve" && $action !== "reject") {
        header("Location: facultyrequests.php?success=false&error=Invalid action");
        exit();
    }

    // Determine the new status based on the action
    $new_status = $action === "approve" ? "Approved" : "Rejected";

    // Update the status in the faculty_requests table
    $sql = "UPDATE faculty_requests SET status = :status WHERE request_id = :request_id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':status', $new_status, PDO::PARAM_STR);
    $stmt->bindParam(':request_id', $request_id, PDO::PARAM_INT);

    if ($stmt->execute()) {
        $message = $action === "approve" ? "Request approved successfully" : "Request rejected successfully";
        header("Location: facultyrequests.php?success=true&message=" . urlencode($message));
    } else {
        $errorInfo = $stmt->errorInfo();
        header("Location: facultyrequests.php?success=false&error=" . urlencode($errorInfo[2]));
    }
    exit();
} else {
    // If accessed directly, redirect to the list page
    header("Location: facultyrequests.php");
    exit();
}
?>