
<?php
include_once("./session/session.php");
include_once "../connections/connection.php";

if (isset($_GET["schedule_id"]) && isset($_GET["action"])) {
    $schedule_id = intval($_GET["schedule_id"]);
    $action = $_GET["action"];

    if ($action === "delete") {
        $stmt = $pdo->prepare("UPDATE schedules SET faculty_id = NULL WHERE schedule_id = :schedule_id");
        $stmt->bindParam(":schedule_id", $schedule_id, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            echo "Faculty assignment deleted successfully.";
            header("location:../frame/manual_scheduling.php?section_id=1&department=BSIT");

        } else {
            echo "Error: Unable to delete faculty assignment.";
        }
    } else {
        echo "Invalid action.";
    }
} else {
    echo "Missing parameters.";
}



?>