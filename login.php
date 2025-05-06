<?php
    include_once("./back/login.php");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Login</title>
    <link rel="stylesheet" href="./css/login.css">
</head>
<body style="">
    <div class="login-container">
        <div class="form-container">
            <div class="logo">
                <img src="./assets/logo300.png" alt="University Logo">
            </div>
            <h1>Faculty Login</h1>
            <form action="" method="POST">
                <label class="label" for="email">Username:</label>
                        <input type="text" id="email" name="email" required autocomplete="username"></input>
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required autocomplete="current-password">
                
                <input type="submit" value="Login" name="submit">
            </form>
            <div class="forgot-password">
            <a href="forgot_password.php">Forgot your password?</a> <br>
            </div>
        </div>
    </div>
</body>
</html>
