<?php

include_once "../back/viewfacultysched.php";

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Schedule</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/viewfacultysched.css">
</head>
<body>

    <h2>Faculty Schedule</h2>

    <?php if (!empty($schedules)): ?>
        <div class="faculty-info">
            <h4 class="text-center">
                <?php echo htmlspecialchars($schedules[0]['firstname']) . " " . 
                           htmlspecialchars($schedules[0]['middlename']) . " " . 
                           htmlspecialchars($schedules[0]['lastname']); ?>
            </h4>
            <p><strong>Role:</strong> <?php echo htmlspecialchars($schedules[0]['role']); ?></p>
            <p><strong>College:</strong> <?php echo htmlspecialchars($schedules[0]['college']); ?></p>
            <p><strong>Employment Status:</strong> <?php echo htmlspecialchars($schedules[0]['employment_status']); ?></p>
            <p><strong>Phone:</strong> <?php echo htmlspecialchars($schedules[0]['phone_no']); ?></p>
            <p><strong>Department:</strong> <?php echo htmlspecialchars($schedules[0]['department']); ?></p>
            <p><strong>Max Weekly Hours:</strong> <?php echo htmlspecialchars($schedules[0]['max_weekly_hours']); ?> hours</p>
        </div>

        <table class="table table-bordered table-striped">
            <thead class="table-primary">
                <tr>
                    <th>Section</th>
                    <th>Day</th>
                    <th>Time Slot</th>
                    <th>Subject Code</th>
                    <th>Program</th>
                    <th>Year Level</th>
                    <th>Semester</th>
                    <th>Room</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($schedules as $schedule): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($schedule['section_name']); ?></td>
                        <td><?php echo htmlspecialchars($schedule['day_of_week']); ?></td>
                        <td><?php echo htmlspecialchars($schedule['time_slot']); ?></td>
                        <td><?php echo htmlspecialchars($schedule['subject_code']); ?></td>
                        <td><?php echo htmlspecialchars($schedule['department'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($schedule['year_level']); ?></td>
                        <td><?php echo htmlspecialchars($schedule['semester']); ?></td>
                        <td><?php echo htmlspecialchars($schedule['room_id'] ?? 'N/A'); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="text-center text-danger">No schedule found for this faculty member.</p>
    <?php endif; ?>


    <div class="text-center mt-4">
        <a href="../frame/faculty.php?department=<?php echo $_GET['department']; ?>" class="btn btn-secondary">Back to Faculty List</a>
    </div>


</body>
</html>