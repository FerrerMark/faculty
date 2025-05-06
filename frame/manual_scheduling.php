<?php
include_once("../session/session.php");
include_once "../connections/connection.php";

if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['schedule_id'])) {
    $schedule_id = intval($_GET['schedule_id']);
    $section_id = isset($_GET['section_id']) ? intval($_GET['section_id']) : 1;
    $stmt = $pdo->prepare("UPDATE schedules SET faculty_id = NULL WHERE schedule_id = :schedule_id");
    $stmt->bindParam(':schedule_id', $schedule_id, PDO::PARAM_INT);
    
    if ($stmt->execute()) {
        header("Location: manual_scheduling.php?section_id=$section_id");
        exit;
    } else {
        die("Error: Unable to delete faculty assignment.");
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['schedule_id']) && isset($_POST['faculty_id'])) {
    $schedule_id = intval($_POST['schedule_id']);
    $faculty_id = intval($_POST['faculty_id']);

    $scheduleStmt = $pdo->prepare("
        SELECT subject_code, day_of_week, start_time, end_time, section_id
        FROM section_schedules
        WHERE schedule_id = :schedule_id
    ");
    $scheduleStmt->bindParam(':schedule_id', $schedule_id);
    $scheduleStmt->execute();
    $schedule = $scheduleStmt->fetch(PDO::FETCH_ASSOC);

    if ($schedule) {
        $checkStmt = $pdo->prepare("
            SELECT schedule_id FROM schedules 
            WHERE section_id = :section_id 
            AND subject_code = :subject_code 
            AND day_of_week = :day_of_week 
            AND start_time = :start_time 
            AND end_time = :end_time
        ");
        $checkStmt->bindParam(':section_id', $schedule['section_id']);
        $checkStmt->bindParam(':subject_code', $schedule['subject_code']);
        $checkStmt->bindParam(':day_of_week', $schedule['day_of_week']);
        $checkStmt->bindParam(':start_time', $schedule['start_time']);
        $checkStmt->bindParam(':end_time', $schedule['end_time']);
        $checkStmt->execute();
        $existingSchedule = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if ($existingSchedule) {
            $updateStmt = $pdo->prepare("
                UPDATE schedules 
                SET faculty_id = :faculty_id 
                WHERE schedule_id = :schedule_id
            ");
            $updateStmt->bindParam(':faculty_id', $faculty_id);
            $updateStmt->bindParam(':schedule_id', $existingSchedule['schedule_id']);
            $result = $updateStmt->execute();
        } else {
            $insertStmt = $pdo->prepare("
                INSERT INTO schedules (faculty_id, subject_code, section_id, day_of_week, start_time, end_time)
                VALUES (:faculty_id, :subject_code, :section_id, :day_of_week, :start_time, :end_time)
            ");
            $insertStmt->bindParam(':faculty_id', $faculty_id);
            $insertStmt->bindParam(':subject_code', $schedule['subject_code']);
            $insertStmt->bindParam(':section_id', $schedule['section_id']);
            $insertStmt->bindParam(':day_of_week', $schedule['day_of_week']);
            $insertStmt->bindParam(':start_time', $schedule['start_time']);
            $insertStmt->bindParam(':end_time', $schedule['end_time']);
            $result = $insertStmt->execute();
        }

        if ($result) {
            echo "Faculty assigned successfully";
        } else {
            echo "Error assigning faculty";
        }
    } else {
        echo "Schedule not found";
    }
    exit; 
}

if (isset($_GET['action']) && $_GET['action'] === 'fetch_available_faculty' && isset($_GET['schedule_id'])) {
    header('Content-Type: application/json');
    $schedule_id = intval($_GET['schedule_id']);

    $scheduleStmt = $pdo->prepare("
        SELECT subject_code, day_of_week, start_time, end_time, section_id
        FROM section_schedules
        WHERE schedule_id = :schedule_id
    ");
    $scheduleStmt->bindParam(':schedule_id', $schedule_id);
    $scheduleStmt->execute();
    $schedule = $scheduleStmt->fetch(PDO::FETCH_ASSOC);

    if (!$schedule) {
        echo json_encode([]);
        exit;
    }

    $subject_code = $schedule['subject_code'];
    $day_of_week = $schedule['day_of_week'];
    $start_time = $schedule['start_time'];
    $end_time = $schedule['end_time'];

    $facultyStmt = $pdo->prepare("
        SELECT 
            f.faculty_id,
            CONCAT(f.firstname, ' ', COALESCE(f.middlename, ''), ' ', f.lastname) AS name
        FROM faculty f
        INNER JOIN faculty_courses fc ON f.faculty_id = fc.faculty_id
        WHERE 
            fc.subject_code = :subject_code
            AND FIND_IN_SET(:day_of_week, f.availability) > 0
            AND f.start_time <= :start_time
            AND f.end_time >= :end_time
            AND NOT EXISTS (
                SELECT 1
                FROM schedules s
                WHERE s.faculty_id = f.faculty_id
                AND s.day_of_week = :day_of_week
                AND (
                    (s.start_time < :end_time AND s.end_time > :start_time)
                )
            )
    ");
    $facultyStmt->bindParam(':subject_code', $subject_code);
    $facultyStmt->bindParam(':day_of_week', $day_of_week);
    $facultyStmt->bindParam(':start_time', $start_time);
    $facultyStmt->bindParam(':end_time', $end_time);
    $facultyStmt->execute();
    $availableFaculty = $facultyStmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($availableFaculty);
    exit;
}

$sectionStmt = $pdo->query("SELECT section_id, section_name FROM sections ORDER BY section_name");
$sections = $sectionStmt->fetchAll(PDO::FETCH_ASSOC);

$section_id = isset($_GET['section_id']) ? intval($_GET['section_id']) : 0;

$stmt = $pdo->prepare("
    SELECT 
        ss.schedule_id,
        ss.section_id, 
        ss.subject_code, 
        ss.day_of_week, 
        ss.start_time, 
        ss.end_time, 
        ss.semester, 
        ss.program_code, 
        ss.section_name,
        ra.room_id,
        r.room_no,
        f.firstname, 
        f.middlename, 
        f.lastname,
        s.schedule_id AS faculty_schedule_id
    FROM section_schedules ss
    LEFT JOIN room_assignments ra ON ss.section_id = ra.section_id 
        AND ss.subject_code = ra.subject_code 
        AND ss.day_of_week = ra.day_of_week 
        AND ss.start_time = ra.start_time 
        AND ss.end_time = ra.end_time
    LEFT JOIN rooms r ON ra.room_id = r.room_id
    LEFT JOIN schedules s ON ss.section_id = s.section_id 
        AND ss.subject_code = s.subject_code 
        AND ss.day_of_week = s.day_of_week
        AND ss.start_time = s.start_time 
        AND ss.end_time = s.end_time
    LEFT JOIN faculty f ON s.faculty_id = f.faculty_id
    WHERE ss.section_id = :section_id
    ORDER BY FIELD(ss.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'), 
             ss.start_time, 
             ss.subject_code
");
$stmt->bindParam(':section_id', $section_id);
$stmt->execute();
$scheduleData = $stmt->fetchAll(PDO::FETCH_ASSOC);

$startTime = strtotime('06:00 AM');
$endTime = strtotime('09:00 PM');
$interval = 60 * 60; 
$time_slots = [];

while ($startTime <= $endTime) {
    $current_time = date('H:i:s', $startTime);
    $next_time = date('H:i:s', $startTime + $interval);
    
    $has_schedule = false;
    foreach ($scheduleData as $schedule) {
        if (strtotime($schedule['start_time']) <= strtotime($current_time) &&
            strtotime($schedule['end_time']) > strtotime($current_time)) {
            $has_schedule = true;
            break;
        }
    }
    
    if ($has_schedule) {
        $time_slots[] = [
            'start' => $current_time,
            'end' => $next_time
        ];
    }
    
    $startTime += $interval;
}

$days_of_week = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

function formatTime($time) {
    return date('h:i A', strtotime($time));
}

function getAvailableFaculty($pdo, $schedule) {
    $subject_code = $schedule['subject_code'];
    $day_of_week = $schedule['day_of_week'];
    $start_time = $schedule['start_time'];
    $end_time = $schedule['end_time'];

    $facultyStmt = $pdo->prepare("
        SELECT 
            CONCAT(f.firstname, ' ', COALESCE(f.middlename, ''), ' ', f.lastname) AS name
        FROM faculty f
        INNER JOIN faculty_courses fc ON f.faculty_id = fc.faculty_id
        WHERE 
            fc.subject_code = :subject_code
            AND FIND_IN_SET(:day_of_week, f.availability) > 0
            AND f.start_time <= :start_time
            AND f.end_time >= :end_time
            AND NOT EXISTS (
                SELECT 1
                FROM schedules s
                WHERE s.faculty_id = f.faculty_id
                AND s.day_of_week = :day_of_week
                AND (
                    (s.start_time < :end_time AND s.end_time > :start_time)
                )
            )
    ");
    $facultyStmt->bindParam(':subject_code', $subject_code);
    $facultyStmt->bindParam(':day_of_week', $day_of_week);
    $facultyStmt->bindParam(':start_time', $start_time);
    $facultyStmt->bindParam(':end_time', $end_time);
    $facultyStmt->execute();
    return $facultyStmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manual Scheduling</title>
    <link rel="stylesheet" href="../css/manual_scheduling.css">
</head>
<body>

<h2>Schedule</h2>

<form method="GET">
    <label for="section_id"><strong>Select Section:</strong></label>
    <select name="section_id" id="section_id" required>
        <option value="">-- Select Section --</option>
        <?php foreach ($sections as $section): ?>
            <option value="<?= $section['section_id'] ?>" <?= ($section_id == $section['section_id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($section['section_name']) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <button type="submit">Load Schedule</button>
</form>

<?php if ($section_id > 0): ?>
    <table>
        <tr>
            <th>Time Slot</th>
            <?php foreach ($days_of_week as $day_name): ?>
                <th><?= $day_name ?></th>
            <?php endforeach; ?>
        </tr>

        <?php
        $current_schedules = array_fill(0, count($days_of_week), null);
        
        foreach ($time_slots as $index => $slot):
            $slot_start = $slot['start'];
            $slot_end = $slot['end'];
            $has_content = false;
            
            foreach ($days_of_week as $day_name) {
                foreach ($scheduleData as $schedule) {
                    if ($schedule['day_of_week'] == $day_name &&
                        $schedule['start_time'] <= $slot_start &&
                        $schedule['end_time'] > $slot_start) {
                        $has_content = true;
                        break 2;
                    }
                }
            }
            
            if ($has_content):
        ?>
            <tr>
                <td><?= formatTime($slot_start) ?> - <?= formatTime($slot_end) ?></td>
                <?php foreach ($days_of_week as $day_index => $day_name):
                    $cell_content = "";
                    $cell_style = ""; 
                    foreach ($scheduleData as $schedule):
                        if ($schedule['day_of_week'] == $day_name &&
                            $schedule['start_time'] <= $slot_start &&
                            $schedule['end_time'] > $slot_start):

                            $teacher_name = trim("{$schedule['firstname']} {$schedule['middlename']} {$schedule['lastname']}");
                            $teacher_display = !empty($teacher_name) ? $teacher_name : "<small style='color: gray;'>No Faculty</small>";
                            $room_display = !empty($schedule['room_no']) ? $schedule['room_no'] : "<small style='color: gray;'>No Room</small>";

                            $action_button = !empty($teacher_name) ? 
                                "<button class='delete-btn' onclick='deleteFaculty({$schedule['faculty_schedule_id']})'>×</button>" :
                                "<button class='add-btn' onclick='addFaculty({$schedule['schedule_id']})'>+</button>";

                            $available_faculty = !empty($teacher_name) ? '' : getAvailableFaculty($pdo, $schedule);
                            $faculty_display = '';
                            if (empty($teacher_name)) {
                                if (!empty($available_faculty)) {
                                    $faculty_names = implode('<br>', array_column($available_faculty, 'name'));
                                    $faculty_display = "<h6>Available</h6><div class='scrolling-faculty'><span>$faculty_names</span></div>";
                                } else {
                                    $faculty_display = "<small style='color: gray;'>No Faculty Available</small>";
                                }
                            }

                            $new_content = "<strong style='color: black;'>{$schedule['subject_code']}</strong><br>" .
                                           "<small>Teacher: $teacher_display</small><br>" .
                                           "<small>Room: $room_display</small><br>" .
                                           $faculty_display .
                                           $action_button;

                            if (!empty($teacher_name)) {
                                $cell_style = "style='background-color: #90ee90a8;'"; 
                            } else {
                                $cell_style = "style='background-color: #f08080d9;'"; 
                            }

                            if ($current_schedules[$day_index] !== $new_content):
                                $current_schedules[$day_index] = $new_content;
                                $cell_content = $new_content;
                                $row_span = ceil((strtotime($schedule['end_time']) - strtotime($slot_start)) / $interval);
                            endif;
                            break;
                        endif;
                    endforeach;

                    if (empty($cell_content) && $current_schedules[$day_index] === null):
                        echo "<td><small style='color: gray;'>-</small></td>";
                    elseif (!empty($cell_content)):
                        echo "<td rowspan='$row_span' $cell_style>$cell_content</td>";
                    endif;

                    if (isset($schedule) && strtotime($slot_end) >= strtotime($schedule['end_time'])):
                        $current_schedules[$day_index] = null;
                    endif;
                endforeach; ?>
            </tr>
        <?php 
            endif;
        endforeach; 
        ?>
    </table>
<?php else: ?>
    <p>Please select a section to view the schedule.</p>
<?php endif; ?>

<div id="addModalForSchedule" class="modal">
    <div class="modal-content">
        <span class="close-modal">×</span>
        <h3>Select Faculty</h3>
        <form id="assignFacultyForm">
            <input type="hidden" id="schedule_id" name="schedule_id">
            <label for="faculty_id">Faculty:</label>
            <select id="faculty_id" name="faculty_id" required>
                <option value="">-- Select Faculty --</option>
            </select>
            <br><br>
            <button type="submit">Assign Faculty</button>
        </form>
    </div>
</div>

<script>
function deleteFaculty(scheduleId) {
    if (confirm('Are you sure you want to remove the faculty from this schedule?')) {
        window.location.href = `manual_scheduling.php?section_id=<?php echo $section_id; ?>&schedule_id=${scheduleId}&action=delete`;
    }
}

function addFaculty(scheduleId) {
    document.getElementById('schedule_id').value = scheduleId;
    document.getElementById('addModalForSchedule').style.display = 'block';
    
    fetch(`manual_scheduling.php?action=fetch_available_faculty&schedule_id=${scheduleId}`)
        .then(response => response.json())
        .then(data => {
            const facultyDropdown = document.getElementById('faculty_id');
            facultyDropdown.innerHTML = '<option value="">-- Select Faculty --</option>';
            data.forEach(faculty => {
                facultyDropdown.innerHTML += `<option value="${faculty.faculty_id}">${faculty.name}</option>`;
            });
        })
        .catch(error => console.error('Error fetching faculty:', error));
}

document.querySelector('.close-modal').addEventListener('click', function() {
    document.getElementById('addModalForSchedule').style.display = 'none';
});

document.getElementById('assignFacultyForm').addEventListener('submit', function(event) {
    event.preventDefault();
    const formData = new FormData(this);
    
    fetch('manual_scheduling.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(result => {
        alert(result);
        document.getElementById('addModalForSchedule').style.display = 'none';
        location.reload();
    })
    .catch(error => console.error('Error assigning faculty:', error));
});
</script>

</body>
</html>