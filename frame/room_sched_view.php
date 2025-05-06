<?php
include_once("../session/session.php");
include_once "../connections/connection.php";

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'delete') {
    $room_id = intval($_GET['room_id']);
    $day_of_week = $_GET['day_of_week'];
    $start_time = $_GET['start_time'];

    $deleteStmt = $pdo->prepare("
        DELETE FROM room_assignments 
        WHERE room_id = :room_id 
        AND day_of_week = :day_of_week 
        AND start_time = :start_time
    ");
    $deleteStmt->bindParam(':room_id', $room_id, PDO::PARAM_INT);
    $deleteStmt->bindParam(':day_of_week', $day_of_week);
    $deleteStmt->bindParam(':start_time', $start_time);

    if ($deleteStmt->execute()) {
        header("Location: " . $_SERVER['PHP_SELF'] . "?room_id=$room_id");
        exit;
    } else {
        echo "Error deleting room assignment";
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['section_id']) && isset($_POST['subject_code'])) {
    $room_id = intval($_POST['room_id']);
    $section_id = intval($_POST['section_id']);
    $subject_code = $_POST['subject_code'];
    $day_of_week = $_POST['day_of_week'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];

    $insertStmt = $pdo->prepare("
        INSERT INTO room_assignments (section_id, subject_code, day_of_week, start_time, end_time, room_id)
        VALUES (:section_id, :subject_code, :day_of_week, :start_time, :end_time, :room_id)
    ");
    $insertStmt->bindParam(':section_id', $section_id);
    $insertStmt->bindParam(':subject_code', $subject_code);
    $insertStmt->bindParam(':day_of_week', $day_of_week);
    $insertStmt->bindParam(':start_time', $start_time);
    $insertStmt->bindParam(':end_time', $end_time);
    $insertStmt->bindParam(':room_id', $room_id);

    if ($insertStmt->execute()) {
        echo "Room assigned successfully";
    } else {
        echo "Error assigning room";
    }
    exit;
}

$roomStmt = $pdo->query("SELECT room_id, room_no FROM rooms ORDER BY room_no");
$rooms = $roomStmt->fetchAll(PDO::FETCH_ASSOC);

$room_id = isset($_GET['room_id']) ? intval($_GET['room_id']) : 0;

$stmt = $pdo->prepare("
    SELECT 
        ra.room_id,
        r.room_no,
        ra.day_of_week,
        ra.start_time,
        ra.end_time,
        ra.subject_code,
        ra.section_id,
        s.section_name,
        CONCAT(f.firstname, ' ', COALESCE(f.middlename, ''), ' ', f.lastname) AS faculty_name
    FROM room_assignments ra
    JOIN rooms r ON ra.room_id = r.room_id
    JOIN sections s ON ra.section_id = s.section_id
    LEFT JOIN schedules sch ON ra.section_id = sch.section_id 
        AND ra.subject_code = sch.subject_code 
        AND ra.day_of_week = sch.day_of_week 
        AND ra.start_time = sch.start_time 
        AND ra.end_time = sch.end_time
    LEFT JOIN faculty f ON sch.faculty_id = f.faculty_id
    WHERE ra.room_id = :room_id
    ORDER BY FIELD(ra.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'), 
             ra.start_time
");
$stmt->bindParam(':room_id', $room_id);
$stmt->execute();
$scheduleData = $stmt->fetchAll(PDO::FETCH_ASSOC);

$startTime = strtotime('06:00 AM');
$endTime = strtotime('09:00 PM');
$interval = 30 * 60; // 30 minutes
$time_slots = [];

while ($startTime < $endTime) {
    $current_time = date('H:i:s', $startTime);
    $next_time = date('H:i:s', $startTime + $interval);
    $time_slots[] = [
        'start' => $current_time,
        'end' => $next_time
    ];
    $startTime += $interval;
}

$days_of_week = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

// Function to fetch available courses and sections, including faculty
function getAvailableCourses($pdo, $room_id, $day_of_week, $start_time, $end_time) {
    $stmt = $pdo->prepare("
        SELECT 
            ss.subject_code,
            ss.section_id,
            s.section_name,
            CONCAT(f.firstname, ' ', COALESCE(f.middlename, ''), ' ', f.lastname) AS faculty_name,
            ss.start_time,
            ss.end_time
        FROM section_schedules ss
        JOIN sections s ON ss.section_id = s.section_id
        LEFT JOIN schedules sch ON ss.section_id = sch.section_id 
            AND ss.subject_code = sch.subject_code 
            AND ss.day_of_week = sch.day_of_week 
            AND ss.start_time = sch.start_time 
            AND ss.end_time = sch.end_time
        LEFT JOIN faculty f ON sch.faculty_id = f.faculty_id
        WHERE 
            ss.day_of_week = :day_of_week
            AND ss.start_time < :end_time
            AND ss.end_time > :start_time
            AND NOT EXISTS (
                SELECT 1 
                FROM room_assignments ra 
                WHERE ra.section_id = ss.section_id 
                AND ra.subject_code = ss.subject_code 
                AND ra.day_of_week = ss.day_of_week 
                AND ra.start_time = ss.start_time 
                AND ra.end_time = ss.end_time
            )
            AND (sch.faculty_id IS NULL OR NOT EXISTS (
                SELECT 1 
                FROM schedules s2
                JOIN room_assignments ra2 ON s2.section_id = ra2.section_id
                WHERE s2.faculty_id = sch.faculty_id
                AND s2.day_of_week = :day_of_week
                AND s2.start_time < :end_time
                AND s2.end_time > :start_time
                AND ra2.room_id != :room_id
            ))
        LIMIT 5
    ");
    $stmt->bindParam(':day_of_week', $day_of_week);
    $stmt->bindParam(':start_time', $start_time);
    $stmt->bindParam(':end_time', $end_time);
    $stmt->bindParam(':room_id', $room_id, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function formatTime($time) {
    return date('h:i A', strtotime($time));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Room Schedule</title>
    <link rel="stylesheet" href="../css/room_sched_view.css">
</head>
<body>

<h2>Room Schedule</h2>

<form method="GET">
    <label for="room_id"><strong>Select Room:</strong></label>
    <select name="room_id" id="room_id" required>
        <option value="">-- Select Room --</option>
        <?php foreach ($rooms as $room): ?>
            <option value="<?= $room['room_id'] ?>" <?= ($room_id == $room['room_id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($room['room_no']) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <button type="submit">Load Schedule</button>
</form>

<?php if ($room_id > 0): ?>
    <table>
        <tr>
            <th>Time Slot</th>
            <?php foreach ($days_of_week as $day_name): ?>
                <th><?= $day_name ?></th>
            <?php endforeach; ?>
        </tr>

        <?php
        $occupied_slots = array_fill_keys($days_of_week, []);
        foreach ($scheduleData as $schedule) {
            $day = $schedule['day_of_week'];
            $start = strtotime($schedule['start_time']);
            $end = strtotime($schedule['end_time']);
            $current = $start;
            while ($current < $end) {
                $time_key = date('H:i:s', $current);
                $occupied_slots[$day][$time_key] = [
                    'start_time' => $schedule['start_time'],
                    'end_time' => $schedule['end_time'],
                    'section_name' => $schedule['section_name'],
                    'subject_code' => $schedule['subject_code'],
                    'faculty_name' => $schedule['faculty_name'], // Add faculty_name to the slot data
                    'room_id' => $schedule['room_id'],
                    'is_start' => ($current == $start)
                ];
                $current += $interval;
            }
        }

        foreach ($time_slots as $slot):
            $slot_start = $slot['start'];
            $slot_end = $slot['end'];
        ?>
            <tr>
                <td><?= formatTime($slot_start) ?> - <?= formatTime($slot_end) ?></td>
                <?php foreach ($days_of_week as $day_name):
                    $cell_content = "<small class='empty'>-</small>";
                    $cell_class = "";
                    $action_button = "";
                    $rowspan = 1;

                    if (isset($occupied_slots[$day_name][$slot_start])) {
                        $slot_data = $occupied_slots[$day_name][$slot_start];
                        if ($slot_data['is_start']) {
                            $cell_content = "<strong>{$slot_data['section_name']}</strong><br>" .
                                            "<small>{$slot_data['subject_code']}</small><br>" .
                                            "<small>Faculty: " . ($slot_data['faculty_name'] ?? 'Not assigned') . "</small>";
                            $cell_class = "class='occupied'";
                            $action_button = "<button class='delete-btn' onclick='deleteRoomAssignment({$slot_data['room_id']}, \"{$day_name}\", \"{$slot_data['start_time']}\")'>×</button>";
                            
                            $duration = (strtotime($slot_data['end_time']) - strtotime($slot_data['start_time'])) / 60;
                            $rowspan = ceil($duration / 30);
                        } else {
                            echo "<!-- Spanned slot -->"; // Placeholder to maintain table structure
                            continue; // Skip rendering for non-start slots within rowspan
                        }
                    } else {
                        $available_courses = getAvailableCourses($pdo, $room_id, $day_name, $slot_start, $slot_end);
                        if (!empty($available_courses)) {
                            $available_list = implode('<br>', array_map(function($course) {
                                return "{$course['section_name']} - {$course['subject_code']}" . 
                                       ($course['faculty_name'] ? " (Faculty: {$course['faculty_name']})" : "");
                            }, $available_courses));
                            $cell_content = "<h6>Available Courses</h6><div class='scrolling-available'><span class='available'>$available_list</span></div>";
                            $action_button = "<button class='add-btn' onclick='openAddModal($room_id, \"$day_name\", \"{$slot_start}\", \"{$slot_end}\")'>+</button>";
                        } else {
                            $cell_content = "<small class='empty'>No available courses</small>";
                            $action_button = "";
                        }
                    }
                ?>
                    <td <?php if ($rowspan > 1) echo "rowspan='$rowspan'"; ?> <?= $cell_class ?>>
                        <?= $cell_content ?>
                        <?= $action_button ?>
                    </td>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; ?>
    </table>
<?php else: ?>
    <p>Please select a room to view the schedule.</p>
<?php endif; ?>

<div id="addRoomSectionModal" class="modal">
    <div class="modal-content">
        <span class="close-modal" onclick="closeAddModal()">×</span>
        <h3>Assign Course to Room</h3>
        <div id="courseList" class="course-list"></div>
        <input type="hidden" id="modalRoomId">
        <input type="hidden" id="modalDayOfWeek">
        <input type="hidden" id="modalStartTime">
        <input type="hidden" id="modalEndTime">
    </div>
</div>

<script>
function deleteRoomAssignment(roomId, dayOfWeek, startTime) {
    if (confirm('Are you sure you want to remove the room from this schedule?')) {
        window.location.href = `?room_id=${roomId}&day_of_week=${dayOfWeek}&start_time=${startTime}&action=delete`;
    }
}
function openAddModal(roomId, dayOfWeek, startTime, endTime) {
    document.getElementById('addRoomSectionModal').style.display = 'block';
    document.getElementById('modalRoomId').value = roomId;
    document.getElementById('modalDayOfWeek').value = dayOfWeek;
    document.getElementById('modalStartTime').value = startTime;
    document.getElementById('modalEndTime').value = endTime;

    const courses = <?php
        $all_available = [];
        foreach ($days_of_week as $day) {
            foreach ($time_slots as $slot) {
                $all_available["$day-{$slot['start']}-{$slot['end']}"] = getAvailableCourses($pdo, $room_id, $day, $slot['start'], $slot['end']);
            }
        }
        echo json_encode($all_available);
    ?>;
    
    const key = `${dayOfWeek}-${startTime}-${endTime}`;
    const availableCourses = courses[key] || [];
    const courseList = document.getElementById('courseList');
    courseList.innerHTML = '';

    if (availableCourses.length > 0) {
        availableCourses.forEach(course => {
            const div = document.createElement('div');
            div.className = 'course-item';
            const facultyDisplay = course.faculty_name ? ` (Faculty: ${course.faculty_name})` : ' (No faculty yet)';
            div.innerHTML = `${course.section_name} - ${course.subject_code}${facultyDisplay}`;
            div.onclick = () => assignRoom(course.section_id, course.subject_code, course.start_time, course.end_time);
            courseList.appendChild(div);
        });
    } else {
        courseList.innerHTML = '<p>No available courses</p>';
    }
}

function closeAddModal() {
    document.getElementById('addRoomSectionModal').style.display = 'none';
}

function assignRoom(sectionId, subjectCode, startTime, endTime) {
    if (confirm(`Assign ${subjectCode} to this room?`)) {
        const roomId = document.getElementById('modalRoomId').value;
        const dayOfWeek = document.getElementById('modalDayOfWeek').value;

        const formData = new FormData();
        formData.append('room_id', roomId);
        formData.append('section_id', sectionId);
        formData.append('subject_code', subjectCode);
        formData.append('day_of_week', dayOfWeek);
        formData.append('start_time', startTime);
        formData.append('end_time', endTime);

        fetch('', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(result => {
            alert(result);
            closeAddModal();
            location.reload();
        })
        .catch(error => console.error('Error assigning room:', error));
    }
}
</script>

</body>
</html>