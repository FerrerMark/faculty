<?php
include_once("./connections/connection.php"); 

include_once("./session/logsession.php");

if (isset($_POST['submit'])) {


    $username = $_POST['email'];
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = "Email and password are required.";
        exit();
    }

    $sql = "SELECT u.*, f.departmentID AS department 
            FROM `users` u 
            JOIN `faculty` f ON u.faculty_id = f.faculty_id 
            WHERE u.username = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$username]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row && password_verify($password, $row['password'])) {
        $_SESSION['id'] = $row['faculty_id'];
        $_SESSION['role'] = $row['role'];
        $_SESSION['department'] = $row['department'];

        header("Location: ./index.php");
        
        exit();
    } else {
        echo "Invalid username or password";
    }
}
?>
