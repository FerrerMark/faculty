<?php
    session_start();
    session_unset();
    session_destroy();
    header("location: ../login.php");    
    // header("location: http://localhost/pref/logintestapi.php");

?>