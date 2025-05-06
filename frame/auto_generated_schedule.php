<?php

include_once("./session/session.php");
include_once("..\connections\connection.php");

function isTimeSlotAvailable($faculty, $day, $start_time, $end_time) {
    $days = explode(',', $faculty['availability']);
    if (!in_array($day, $days)) return false;
    return ($start_time >= $faculty['start_time'] && $end_time <= $faculty['end_time']);
}

function hasConflict($conn, $faculty_id, $day, $start_time, $end_time) {
    $query = "SELECT * FROM schedules 
              WHERE faculty_id = :faculty_id AND day_of_week = :day 
              AND ((start_time <= :end_time) AND (end_time >= :start_time))";
    $stmt = $conn->prepare($query);
    $stmt->execute([
        ':faculty_id' => $faculty_id,
        ':day' => $day,
        ':start_time' => $start_time,
        ':end_time' => $end_time
    ]);
    $count = $stmt->rowCount();
    echo "Checking conflict for faculty {$faculty_id} on {$day} from {$start_time} to {$end_time}: " . ($count > 0 ? "Conflict" : "No conflict") . "<br>";
    return $count > 0;
}

function scheduleCourse($conn, $course, $section, $faculty_list, $rooms) {
    $lecture_hours = $course['lecture_hours'];
    $subject_code = $course['subject_code'];
    $course_id = $course['course_id'];
    $slots = $course['slots'];
    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

    echo "Scheduling course: {$subject_code} (ID: {$course_id}) for section {$section['section_name']}<br>";

    $faculty_query = "SELECT f.* FROM faculty f
                      JOIN faculty_courses fc ON f.faculty_id = fc.faculty_id
                      WHERE fc.subject_code = :subject_code";
    $stmt = $conn->prepare($faculty_query);
    $stmt->execute([':subject_code' => $subject_code]);
    $eligible_faculty = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($eligible_faculty)) {
        echo "No eligible faculty found for {$subject_code}<br>";
        return;
    }

    foreach ($eligible_faculty as $faculty) {
        echo "Trying faculty: {$faculty['firstname']} {$faculty['lastname']} (ID: {$faculty['faculty_id']})<br>";
        $hours_assigned = 0;
        shuffle($days);

        foreach ($days as $day) {
            if (!isTimeSlotAvailable($faculty, $day, "06:00:00", "17:00:00")) {
                echo "{$day} not available for faculty {$faculty['faculty_id']}<br>";
                continue;
            }

            $start_time = "06:00:00";
            while ($hours_assigned < $lecture_hours) {
                $end_time = date("H:i:s", strtotime($start_time) + 3600);
                echo "Attempting {$start_time} to {$end_time} on {$day}<br>";

                if (!hasConflict($conn, $faculty['faculty_id'], $day, $start_time, $end_time)) {
                    foreach ($rooms as $room) {
                        if ($room['room_type'] == 'Lecture' && $room['capacity'] >= $slots) {
                            echo "Found room: {$room['room_no']} (ID: {$room['room_id']})<br>";
                            $insert = "INSERT INTO schedules (faculty_id, subject_code, section_id, day_of_week, start_time, end_time, course_id, room_id, semester)
                                       VALUES (:faculty_id, :subject_code, :section_id, :day_of_week, :start_time, :end_time, :course_id, :room_id, :semester)";
                            try {
                                $stmt = $conn->prepare($insert);
                                $stmt->execute([
                                    ':faculty_id' => $faculty['faculty_id'],
                                    ':subject_code' => $subject_code,
                                    ':section_id' => $section['section_id'],
                                    ':day_of_week' => $day,
                                    ':start_time' => $start_time,
                                    ':end_time' => $end_time,
                                    ':course_id' => $course_id,
                                    ':room_id' => $room['room_id'],
                                    ':semester' => $course['semester']
                                ]);
                                echo "Scheduled {$subject_code} for faculty {$faculty['faculty_id']} on {$day} from {$start_time} to {$end_time}<br>";
                                $hours_assigned++;
                            } catch (PDOException $e) {
                                echo "Insert failed: " . $e->getMessage() . "<br>";
                            }
                            break;
                        }
                    }
                } else {
                    echo "Conflict detected, skipping slot<br>";
                }
                $start_time = $end_time;
                if (strtotime($start_time) >= strtotime($faculty['end_time'])) {
                    echo "Reached end of faculty availability ({$faculty['end_time']})<br>";
                    break;
                }
            }
            if ($hours_assigned >= $lecture_hours) break 2;
        }
    }
}

$semester = "First";

$courses_query = "SELECT * FROM courses WHERE semester = :semester AND program_code = 'BSIT'";
$stmt = $conn->prepare($courses_query);
$stmt->execute([':semester' => $semester]);
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

$sections_query = "SELECT * FROM sections WHERE program_code = 'BSIT' AND semester = :semester";
$stmt = $conn->prepare($sections_query);
$stmt->execute([':semester' => $semester]);
$sections = $stmt->fetchAll(PDO::FETCH_ASSOC);

$rooms_query = "SELECT * FROM rooms";
$stmt = $conn->query($rooms_query);
$rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

$faculty_query = "SELECT * FROM faculty";
$stmt = $conn->query($faculty_query);
$faculty_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($courses as $course) {
    foreach ($sections as $section) {
        scheduleCourse($conn, $course, $section, $faculty_list, $rooms);
    }
}

echo "Scheduling completed!<br>";
$conn = null;
?>
