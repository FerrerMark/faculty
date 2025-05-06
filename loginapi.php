<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json");

session_start();
include_once("connections/connection.php");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST['username']) && isset($_POST['password'])) {

        $username = $_POST['username'];
        $password = $_POST['password'];

        $username = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
        
        $sql = "SELECT faculty_id, role, password FROM `users` WHERE username = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$username]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            
            if (password_verify($password, $row['password'])) {
               
                session_regenerate_id(true);

                $_SESSION['id'] = $row['faculty_id'];
                $_SESSION['role'] = $row['role'];

                header("location: https://faculty.schoolmanagementsystem2.com/");
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Missing username or password']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit();
}
?>
