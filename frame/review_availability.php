<?php 
include_once "../connections/connection.php";
include_once "../comps/notif.php";

session_start();

if ($_SESSION['role'] !== 'Department Head') {
    header("Location: ../unauthorized.php");
    exit();
}

$query = "SELECT ppc.pending_id, f.firstname, f.lastname, f.faculty_id, ppc.subject_code, c.course_title, ppc.available_days, ppc.start_time, ppc.end_time, ppc.status
          FROM pending_preferred_courses ppc
          JOIN faculty f ON ppc.faculty_id = f.faculty_id
          JOIN courses c ON ppc.subject_code = c.subject_code
          WHERE ppc.status = 'Pending'";
$stmt = $conn->prepare($query);
$stmt->execute();
$submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

$assigned_courses = [];
foreach ($submissions as $submission) {
    $faculty_id = $submission['faculty_id'];
    $query = "SELECT c.course_title 
              FROM faculty_courses fc 
              JOIN courses c ON fc.subject_code = c.subject_code 
              WHERE fc.faculty_id = :faculty_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':faculty_id', $faculty_id, PDO::PARAM_INT);
    $stmt->execute();
    $assigned_courses[$faculty_id] = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pending_id = $_POST['pending_id'];
    $action = $_POST['action'];

    $query = "SELECT faculty_id, subject_code, available_days, start_time, end_time FROM pending_preferred_courses WHERE pending_id = :pending_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':pending_id', $pending_id, PDO::PARAM_INT);
    $stmt->execute();
    $submission = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($action === 'accept') {
        $query = "INSERT INTO faculty_courses (faculty_id, subject_code) 
                  VALUES (:faculty_id, :subject_code)
                  ON DUPLICATE KEY UPDATE subject_code = subject_code"; 
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':faculty_id', $submission['faculty_id'], PDO::PARAM_INT);
        $stmt->bindParam(':subject_code', $submission['subject_code'], PDO::PARAM_STR);
        $stmt->execute();

        $employment_query = "SELECT employment_status FROM faculty WHERE faculty_id = :faculty_id";
        $stmt = $conn->prepare($employment_query);
        $stmt->bindParam(':faculty_id', $submission['faculty_id'], PDO::PARAM_INT);
        $stmt->execute();
        if ($stmt->fetch(PDO::FETCH_ASSOC)['employment_status'] === 'Part-Time' && $submission['available_days']) {
            $query = "UPDATE faculty SET availability = :available_days, start_time = :start_time, end_time = :end_time 
                      WHERE faculty_id = :faculty_id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':faculty_id', $submission['faculty_id'], PDO::PARAM_INT);
            $stmt->bindParam(':available_days', $submission['available_days'], PDO::PARAM_STR);
            $stmt->bindParam(':start_time', $submission['start_time'], PDO::PARAM_STR);
            $stmt->bindParam(':end_time', $submission['end_time'], PDO::PARAM_STR);
            $stmt->execute();
        }

        $query = "UPDATE pending_preferred_courses SET status = 'Accepted' WHERE pending_id = :pending_id";
        $status = "accepted";
    } else {
        $query = "UPDATE pending_preferred_courses SET status = 'Rejected' WHERE pending_id = :pending_id";
        $status = "rejected";
    }
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':pending_id', $pending_id, PDO::PARAM_INT);
    $stmt->execute();

    header("Location: review_availability.php?$status"); 
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Faculty Availability</title>
    <link rel="stylesheet" href="../css/review_availability.css">
</head>
<body>
    <h1>Review Faculty Availability Submissions</h1>

    <!-- Toolbar -->
    <div class="actions-bar ">
        <div>
            <h6>Requested Courses</h6>
        </div>
        <div class="search-container">
            <input type="text" placeholder="Search programs..." class="search-box" id="searchBox" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
        </div>
    </div>

    <!-- Submissions Table -->
    <div class="table-wrapper">
        <table id="submissionsTable">
            <thead>
                <tr>
                    <th>Faculty Name</th>
                    <th>Requested Course</th>
                    <th>Assigned Courses</th>
                    <th>Available Days</th>
                    <th>Start Time</th>
                    <th>End Time</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($submissions)): ?>
                    <tr>
                        <td colspan="7" class="no-data">No pending submissions found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($submissions as $submission): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($submission['firstname'] . ' ' . $submission['lastname']); ?></td>
                            <td><?php echo htmlspecialchars($submission['course_title']); ?></td>
                            <td>
                                <?php 
                                $faculty_id = $submission['faculty_id'];
                                echo !empty($assigned_courses[$faculty_id]) 
                                    ? htmlspecialchars(implode(', ', $assigned_courses[$faculty_id])) 
                                    : 'None';
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($submission['available_days'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($submission['start_time'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($submission['end_time'] ?? 'N/A'); ?></td>
                            <td>
                                <form method="POST" class="action-form">
                                    <input type="hidden" name="pending_id" value="<?php echo $submission['pending_id']; ?>">
                                    <button type="submit" name="action" value="accept" class="accept">Accept</button>
                                    <button type="submit" name="action" value="reject" class="reject">Reject</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php
    if (isset($_GET['accepted'])) {
        showNotification("Course accepted successfully", "green");
    } else if (isset($_GET['rejected'])) {
        showNotification("Course rejected successfully", "red");
    }
    ?>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchBox'); 
        const table = document.getElementById('submissionsTable');
        const rows = table.querySelectorAll('tbody tr:not(.no-data)');

        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            let hasVisibleRows = false;

            rows.forEach(row => {
                const facultyName = row.cells[0].textContent.toLowerCase();
                const courseTitle = row.cells[1].textContent.toLowerCase();
                const assignedCourses = row.cells[2].textContent.toLowerCase();

                if (facultyName.includes(searchTerm) || courseTitle.includes(searchTerm) || assignedCourses.includes(searchTerm)) {
                    row.style.display = '';
                    hasVisibleRows = true;
                } else {
                    row.style.display = 'none';
                }
            });

            const noDataRow = table.querySelector('.no-data');
            if (noDataRow) {
                noDataRow.style.display = hasVisibleRows ? 'none' : '';
            }
        });
    });
    </script>
</body>
</html>