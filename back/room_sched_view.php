<?php
include_once("./session/session.php");
include_once "../connections/connection.php";

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'delete') {
    if (isset($_GET['room_id'], $_GET['day_of_week'], $_GET['start_time'])) {
        $room_id = intval($_GET['room_id']);
        $day_of_week = $_GET['day_of_week'];
        $start_time = $_GET['start_time'];

        try {
            // Debugging output (remove after testing)
            // var_dump($_GET);

            // Prepare DELETE statement
            $deleteStmt = $pdo->prepare("DELETE FROM room_assignments WHERE room_id = :room_id AND day_of_week = :day_of_week AND start_time = :start_time");
            $deleteStmt->bindParam(':room_id', $room_id, PDO::PARAM_INT);
            $deleteStmt->bindParam(':day_of_week', $day_of_week, PDO::PARAM_STR);
            $deleteStmt->bindParam(':start_time', $start_time, PDO::PARAM_STR);

            if ($deleteStmt->execute()) {
                header("Location: room_sched_view.php?room_id=$room_id&refresh=" . time());
exit;

exit;

            } else {
                echo "<script>alert('Error: Could not delete the record.');</script>";
            }
        } catch (PDOException $e) {
            echo "<script>alert('Database Error: " . $e->getMessage() . "');</script>";
        }
    } else {
        echo "<script>alert('Error: Missing required parameters.');</script>";
    }
}
?>
