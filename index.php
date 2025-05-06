<?php
include_once("./back/index.php");
    $page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
    $role = $row['role'];
    $departmentId = $row['departmentID'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty</title>
    <link rel="stylesheet" href="./css/index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="https://bcp.edu.ph/images/logo300.png">
</head>
<body>
    <header>
        <div style="display: flex; align-items: center;">
            <span class="logo">
                <a href="?page=dashboard" onclick="loadFrame('dashboard', '<?php echo $role; ?>', '<?php echo $departmentId; ?>', event)">
                    <img src="./assets/logo300.png" alt="Logo" style="width:2rem;"/>
                </a>
            </span>
            <span class="header-title"><?php echo $departmentId; ?>&nbsp;Faculty</span>
        </div>
        <div class="header-icons">
            <i class="fa-solid fa-bell" onclick="toggleNotification()"></i>
            <a onclick="loadFrame('profile', '<?php echo $role; ?>', '<?php echo $departmentId; ?>', event)">
                <i class="fa-solid fa-user"></i>
            </a>
        </div>
        <i class="fa-solid fa-bars" onclick="showNav()"></i>
    </header>
    <div class="container">
        <aside class="sidebar">
            <div class="profile">
                <img src="https://bootdey.com/img/Content/avatar/avatar7.png" alt="">
            </div>
            <h3><?php echo $row['firstname'] . " " . $row['lastname']; ?></h3>
            <h4><?php echo "<span>" . $row['departmentID'] . "</span> " . $row['role']; ?></h4>
            <hr>
            <nav class="side_nav" id="navMenu">
                <?php if ($row['role'] == "Instructor") { ?>
                    <a href="?page=view_sched" onclick="loadFrame('view_sched', '<?php echo $role; ?>', '<?php echo $departmentId; ?>', event)" class="nav-item"><i class="fa-solid fa-calendar-days"></i>&nbsp;Schedule</a>
                    <a href="?page=available" onclick="loadFrame('available', '<?php echo $role; ?>', '<?php echo $departmentId; ?>', event)" class="nav-item"><i class="fa-solid fa-calendar-days"></i>&nbsp;My Courses</a>
                <?php } ?>

                <?php if (false) { ?>
                    <a href="#" onclick="loadFrame('faculty', '<?php echo $role; ?>', '<?php echo $departmentId; ?>', event)" class="nav-item"><span class="mdi--teacher"></span>&nbsp;Faculty</a>
                <?php } ?>
                    
                <a href="?page=programs" onclick="loadFrame('programs', '<?php echo $role; ?>', '<?php echo $departmentId; ?>', event)" class="nav-item"><span class="nrk--media-programguide-active"></span>&nbsp;Programs</a>

                <?php if ($role == "Department Head") { ?>
                    <a href="?page=faculty" onclick="loadFrame('faculty', '<?php echo $role; ?>', '<?php echo $departmentId; ?>', event)" class="nav-item"><span class="mdi--teacher"></span>&nbsp;Faculty</a>
                    <a href="?page=rooms" onclick="loadFrame('rooms', '<?php echo $role; ?>', '<?php echo $departmentId; ?>', event)" class="nav-item"><span class="guidance--conference-room"></span>&nbsp;Rooms</a>
                    <a href="?page=sections" onclick="loadFrame('sections', '<?php echo $role; ?>', '<?php echo $departmentId; ?>', event)" class="nav-item"><span class="ic--outline-hdr-auto"></span>&nbsp;Sections</a>

                    <a href="?page=view_sched" onclick="loadFrame('view_sched', '<?php echo $role; ?>', '<?php echo $departmentId; ?>', event)" class="nav-item"><i class="fa-solid fa-calendar-days"></i>&nbsp;Schedule</a>

                    <a href="?page=review_availability" onclick="loadFrame('review_availability', '<?php echo $role; ?>', '<?php echo $departmentId; ?>', event)" class="nav-item"><i class="fa-solid fa-calendar-days"></i>&nbsp;Faculty Course/s Request</a>
                    <a href="?page=available" onclick="loadFrame('available', '<?php echo $role; ?>', '<?php echo $departmentId; ?>', event)" class="nav-item"><i class="material-symbols--list-rounded"></i>&nbsp;My Courses</a>
                    <a href="?page=faculty_request_form" onclick="loadFrame('faculty_request_form', '<?php echo $role; ?>', '<?php echo $departmentId; ?>', event)" class="nav-item"><span class="fluent--send-24-filled"></span>&nbsp;Request Faculty</a>
                <?php } ?>
            </nav>
            <hr>
            <div class="logout-container">
                <button id="reportProblemBtn"><a href="#" class="nav-item"><i class="fa-solid fa-flag"></i>&nbsp;Report a problem</a></button>
                <button><a href="./back/logout.php" class="nav-item logout-button"><i class="fa-solid fa-arrow-left-long"></i>&nbsp; Logout</a></button>
            </div>
        </aside>
        <div style="width:100%;">
            <iframe id="frame" src="./frame/<?php echo $page; ?>.php?role=<?php echo $role; ?>&department=<?php echo $departmentId; ?>" width="100%" height="100%" title="nav"></iframe>
        </div>
    </div>

    <div id="reportProblemModal" class="modal">
        <div class="modal-content">
            <span class="close">×</span>
            <h2>To be continued...</h2>
        </div>
    </div>
    <div class="notification-popup" id="notificationPopup">
        <div class="notification-header">Notifications</div>
        <div class="notification-list" id="notificationList"></div>
    </div>

    <script src="scripts.js"></script>
    <script>
        
        var modal = document.getElementById("reportProblemModal");
        var btn = document.getElementById("reportProblemBtn");
        var span = document.getElementsByClassName("close")[0];

        btn.onclick = function() {
            modal.style.display = "block";
        };
        span.onclick = function() {
            modal.style.display = "none";
        };
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        };
    </script>

    <style>
        .modal { display: none; position: fixed; z-index: 1; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4); }
        .modal-content { background-color: #fefefe; margin: 15% auto; padding: 20px; border: 1px solid #888; border-radius: 8px; width: 80%; max-width: 500px; }
        .close { color: #aaa; float: right; font-size: 28px; font-weight: bold; }
        .close:hover, .close:focus { color: black; text-decoration: none; cursor: pointer; }
        form { display: flex; flex-direction: column; }
        label, input, select, textarea { margin-bottom: 10px; border-radius: 7px; }
        button { cursor: pointer; border: none; font-size: 1rem; background: none; }
        button#submitBtn { background: #5f6961a3; padding: 7px; border-radius: 9px; text-shadow: 2px 2px 20px #00000066; }
        .logout-container { display: flex; flex-direction: column; }
    </style>
</body>
</html>