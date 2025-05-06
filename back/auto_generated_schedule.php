<?php 
include_once "../session/session.php";
header("Content-Type: application/json");
include "../connections/connection.php"; 

error_log("Starting schedule assignment for all faculty");

$facultyQuery = "SELECT faculty_id, max_weekly_hours, start_time, end_time, availability FROM faculty";
$stmt = $conn->prepare($facultyQuery);
$stmt->execute();
$allFaculty = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($allFaculty)) {
    error_log("No faculty found in the database.");
    echo json_encode(["success" => false, "message" => "No faculty found"]);
    exit();
}

$results = [];

foreach ($allFaculty as $faculty) {
    $faculty_id = $faculty["faculty_id"];
    error_log("Assigning schedule for Faculty ID: $faculty_id");

    $availableDays = array_unique(array_map('trim', explode(',', $faculty['availability'])));
    error_log("Faculty ID $faculty_id is available on: " . implode(", ", $availableDays));

    $currentHoursQuery = "
        SELECT SUM(TIMESTAMPDIFF(HOUR, start_time, end_time)) 
        FROM schedules WHERE faculty_id = :faculty_id";
    $stmt = $conn->prepare($currentHoursQuery);
    $stmt->execute(["faculty_id" => $faculty_id]);
    $currentHours = (int) $stmt->fetchColumn();

    $coursesQuery = "SELECT subject_code FROM faculty_courses WHERE faculty_id = :faculty_id";
    $stmt = $conn->prepare($coursesQuery);
    $stmt->execute(["faculty_id" => $faculty_id]);
    $facultyCourses = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($facultyCourses)) {
        error_log("Faculty ID $faculty_id has no assigned courses.");
        $results[$faculty_id] = ["success" => false, "message" => "No assigned courses"];
        continue;
    }

    $sectionsQuery = "
        SELECT s.section_id, s.year_level, s.semester, sc.subject_code, sc.start_time, sc.end_time, sc.day_of_week 
        FROM section_schedules sc
        JOIN sections s ON sc.section_id = s.section_id
        WHERE sc.subject_code IN (" . implode(",", array_fill(0, count($facultyCourses), "?")) . ")
    ";
    $stmt = $conn->prepare($sectionsQuery);
    $stmt->execute($facultyCourses);
    $sections = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $assignedSchedules = [];
    foreach ($sections as $row) {
        if (!in_array($row["day_of_week"], $availableDays)) {
            error_log("Skipping: Faculty ID $faculty_id is NOT available on {$row["day_of_week"]}.");
            continue;
        }

        if (strtotime($row["start_time"]) < strtotime($faculty["start_time"]) || 
            strtotime($row["end_time"]) > strtotime($faculty["end_time"])) {
            error_log("Faculty ID $faculty_id: Conflict with availability for subject {$row["subject_code"]} at {$row["start_time"]} - {$row["end_time"]}");
            continue;
        }

        $facultyConflictQuery = "
            SELECT COUNT(*) FROM schedules 
            WHERE faculty_id = :faculty_id 
            AND day_of_week = :day_of_week 
            AND (
                (start_time < :end_time AND end_time > :start_time)
            )";
        $stmt = $conn->prepare($facultyConflictQuery);
        $stmt->execute([
            "faculty_id"  => $faculty_id,
            "day_of_week" => $row["day_of_week"],
            "start_time"  => $row["start_time"],
            "end_time"    => $row["end_time"]
        ]);

        if ($stmt->fetchColumn() > 0) {
            error_log("Skipping: Faculty ID $faculty_id already has a schedule conflict on {$row["day_of_week"]}.");
            continue;
        }

        $facultySectionConflictQuery = "
            SELECT COUNT(*) FROM schedules 
            WHERE faculty_id = :faculty_id 
            AND section_id = :section_id 
            AND day_of_week = :day_of_week";
        $stmt = $conn->prepare($facultySectionConflictQuery);
        $stmt->execute([
            "faculty_id"  => $faculty_id,
            "section_id"  => $row["section_id"],
            "day_of_week" => $row["day_of_week"]
        ]);

        if ($stmt->fetchColumn() > 0) {
            error_log("Skipping: Faculty ID $faculty_id is already assigned to Section ID {$row["section_id"]} on {$row["day_of_week"]}.");
            continue;
        }

        $sectionConflictQuery = "
            SELECT COUNT(*) FROM schedules 
            WHERE section_id = :section_id 
            AND day_of_week = :day_of_week 
            AND (
                (start_time < :end_time AND end_time > :start_time)
            )";
        $stmt = $conn->prepare($sectionConflictQuery);
        $stmt->execute([
            "section_id"  => $row["section_id"],
            "day_of_week" => $row["day_of_week"],
            "start_time"  => $row["start_time"],
            "end_time"    => $row["end_time"]
        ]);

        if ($stmt->fetchColumn() > 0) {
            error_log("Skipping: Section ID {$row["section_id"]} already has a faculty assigned on {$row["day_of_week"]} at {$row["start_time"]}.");
            continue;
        }

        $newHours = (int) $currentHours + (strtotime($row["end_time"]) - strtotime($row["start_time"])) / 3600;
        if ($newHours > $faculty["max_weekly_hours"]) {
            error_log("Faculty ID $faculty_id has exceeded max weekly hours ({$faculty["max_weekly_hours"]}). Skipping...");
            continue;
        }

        $insertQuery = "INSERT INTO schedules (faculty_id, subject_code, section_id, day_of_week, start_time, end_time) 
                        VALUES (:faculty_id, :subject_code, :section_id, :day_of_week, :start_time, :end_time)";
        $stmt = $conn->prepare($insertQuery);

        if ($stmt->execute([
            "faculty_id"   => $faculty_id,
            "subject_code" => $row["subject_code"],
            "section_id"   => $row["section_id"],
            "day_of_week"  => $row["day_of_week"],
            "start_time"   => $row["start_time"],
            "end_time"     => $row["end_time"]
        ])) {
            error_log("Successfully assigned Faculty ID: $faculty_id to Section ID: {$row["section_id"]} for Subject: {$row["subject_code"]}");
            $assignedSchedules[] = $row;
            $currentHours = $newHours;
        } else {
            error_log("Failed to insert schedule for Faculty ID $faculty_id, Course: {$row["subject_code"]}");
        }
    }

    if (empty($assignedSchedules)) {
        error_log("No valid schedules assigned for Faculty ID: $faculty_id.");
        $results[$faculty_id] = ["success" => false, "message" => "No valid schedules assigned"];
    } else {
        $results[$faculty_id] = ["success" => true, "assignedSchedules" => $assignedSchedules];
    }
}

echo json_encode(["success" => true, "facultyAssignments" => $results]);
