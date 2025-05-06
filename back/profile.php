<?php
    include_once("../connections/connection.php");
    include_once("./session/session.php");

    $sql = "SELECT * FROM `faculty` WHERE `faculty_id` = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$_SESSION['id']]);
    $row = $stmt->fetch();


    $_SESSION['departmentID'] = $row['departmentID'];

?>