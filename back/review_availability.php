<?php
include_once("./session/session.php");
include_once "../connections/connection.php";

// Restrict access to Department Head
if ($_SESSION['role'] !== 'Department Head') {
    header("Location: ../unauthorized.php");
    exit();
}

// Fetch pending submissions
$query = "SELECT ppc.pending_id, f.firstname, f.lastname, f.faculty_id, ppc.subject_code, c.course_title, ppc.available_days, ppc.start_time, ppc.end_time, ppc.status
          FROM pending_preferred_courses ppc
          JOIN faculty f ON ppc.faculty_id = f.faculty_id
          JOIN courses c ON ppc.subject_code = c.subject_code
          WHERE ppc.status = 'Pending'";
$stmt = $conn->prepare($query);
$stmt->execute();
$submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch assigned courses for each faculty member
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

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pending_id = $_POST['pending_id'];
    $action = $_POST['action'];

    // Fetch the submission details
    $query = "SELECT faculty_id, subject_code, available_days, start_time, end_time FROM pending_preferred_courses WHERE pending_id = :pending_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':pending_id', $pending_id, PDO::PARAM_INT);
    $stmt->execute();
    $submission = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($action === 'accept') {
        // Update faculty_courses
        $query = "INSERT INTO faculty_courses (faculty_id, subject_code) 
                  VALUES (:faculty_id, :subject_code)
                  ON DUPLICATE KEY UPDATE subject_code = subject_code"; // Avoid duplicates
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':faculty_id', $submission['faculty_id'], PDO::PARAM_INT);
        $stmt->bindParam(':subject_code', $submission['subject_code'], PDO::PARAM_STR);
        $stmt->execute();

        // If part-time, update faculty table
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

        // Mark as accepted
        $query = "UPDATE pending_preferred_courses SET status = 'Accepted' WHERE pending_id = :pending_id";
    } else {
        // Mark as rejected
        $query = "UPDATE pending_preferred_courses SET status = 'Rejected' WHERE pending_id = :pending_id";
    }
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':pending_id', $pending_id, PDO::PARAM_INT);
    $stmt->execute();

    header("Location: review_availability.php"); // Refresh page
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Faculty Availability</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        th { background-color: #0a2d53; color: white; }
        tr:nth-child(even) { background-color: #f2f2f2; }
        button { padding: 5px 10px; margin: 0 5px; cursor: pointer; }
        .accept { background-color: #28a745; color: white; border: none; }
        .reject { background-color: #dc3545; color: white; border: none; }
    </style>
</head>
<body>
    <h1>Review Faculty Availability Submissions</h1>
    <table>
        <tr>
            <th>Faculty Name</th>
            <th>Requested Course</th>
            <th>Assigned Courses</th>
            <th>Available Days</th>
            <th>Start Time</th>
            <th>End Time</th>
            <th>Action</th>
        </tr>
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
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="pending_id" value="<?php echo $submission['pending_id']; ?>">
                        <button type="submit" name="action" value="accept" class="accept">Accept</button>
                        <button type="submit" name="action" value="reject" class="reject">Reject</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>