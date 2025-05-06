<?php
include_once("./session/session.php");
include_once "../connections/connection.php";


$faculty_id = $_GET['id'];

try{

    $stmt = $conn->prepare("SELECT section.section_name, 
                               CONCAT(f.firstname, ' ', f.lastname) AS teacher, 
                               sched.*,f.*
                            FROM sections section
                            JOIN schedules sched ON section.section_id = sched.section_id
                            JOIN faculty f ON f.faculty_id = sched.faculty_id
                            WHERE f.faculty_id = :faculty_id");




    $stmt->bindParam(':faculty_id', $faculty_id);
    $stmt->execute();

    $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    

}catch(PDOException $e){
    echo "db_error: ".$e->getMessage();
}

?>