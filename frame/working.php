<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $registrar_db = new PDO("mysql:host=localhost;dbname=registrar", "root", "");
    $registrar_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $facultyloading_db = new PDO("mysql:host=localhost;dbname=facultyloading", "root", "");
    $facultyloading_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

$semester_input = '1'; 
$semester_map = [
    '1' => 'First',
    '2' => 'Second',
    '3' => 'Summer'
];
$semester = $semester_map[$semester_input] ?? 'First';
$section_size = 50;      
$min_courses_per_day = 3; // Minimum courses per day per section

// Step 1: Get enrollment data from registrar.enrolled_count
$stmt = $registrar_db->prepare("SELECT Department, year_level, enrolled_count FROM enrolled_count WHERE semestrial = ?");
$stmt->execute([$semester_input]);
$enrollments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Step 2: Get valid programs from facultyloading.programs
$programs = $facultyloading_db->query("SELECT program_code FROM programs")->fetchAll(PDO::FETCH_COLUMN);

// Step 3: Create sections
$insert_section_stmt = $facultyloading_db->prepare("INSERT INTO sections (program_code, year_level, section_name, semester) VALUES (?, ?, ?, ?)");
$sections = [];
$section_counter = 1; // To alternate between MWF and TTHS
foreach ($enrollments as $enrollment) {
    $program_code = $enrollment['Department'];
    $year_level = $enrollment['year_level'];
    $enrolled_count = $enrollment['enrolled_count'];

    if (!in_array($program_code, $programs)) {
        echo "Skipping {$program_code} - not found in facultyloading.programs\n";
        continue;
    }

    $number_of_sections = ceil($enrolled_count / $section_size);
    echo "Processing {$program_code}, Year {$year_level}: {$enrolled_count} students, {$number_of_sections} sections needed\n";

    for ($i = 1; $i <= $number_of_sections; $i++) {
        $section_number = str_pad($i, 2, '0', STR_PAD_LEFT);
        $section_name = "{$program_code}-{$year_level}{$semester_input}{$section_number}"; // e.g., BSIT-1101
        $day_group = ($section_counter % 2 == 0) ? 'TTHS' : 'MWF'; // Alternate between MWF and TTHS
        $insert_section_stmt->execute([$program_code, $year_level, $section_name, $semester]);
        $section_id = $facultyloading_db->lastInsertId();
        $sections[] = [
            'section_id' => $section_id,
            'section_name' => $section_name,
            'program_code' => $program_code,
            'year_level' => $year_level,
            'day_group' => $day_group 
        ];
        $section_counter++;
        echo "Created section: {$section_name} (Day Group: {$day_group})\n";
    }
}

// Step 4: Scheduling setup
$day_groups = [
    'MWF' => ['Monday', 'Wednesday', 'Friday'],
    'TTHS' => ['Tuesday', 'Thursday', 'Saturday']
];
$time_slots = range(0, 14);

// Fetch lecture rooms
$rooms = $facultyloading_db->query("SELECT room_id, room_no FROM rooms WHERE room_type = 'Lecture'")->fetchAll(PDO::FETCH_ASSOC);
if (empty($rooms)) {
    die("No lecture rooms found in facultyloading.rooms\n");
}

// Initialize availability trackers
$all_days = array_merge(...array_values($day_groups));
$section_availability = [];
$room_availability = [];
foreach ($sections as $section) {
    $section_availability[$section['section_id']] = array_fill_keys($all_days, array_fill(0, 15, true));
}
foreach ($rooms as $room) {
    $room_availability[$room['room_id']] = array_fill_keys($all_days, array_fill(0, 15, true));
}

// Step 5: Schedule sections with courses (once per week)
$schedule_stmt = $facultyloading_db->prepare("
    INSERT INTO section_schedules (section_id, subject_code, day_of_week, semester, program_code, section_name, year_level, start_time, end_time) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
");
$room_stmt = $facultyloading_db->prepare("INSERT INTO room_assignments (section_id, subject_code, day_of_week, start_time, end_time, room_id) VALUES (?, ?, ?, ?, ?, ?)");

foreach ($sections as $section) {
    // Fetch courses
    $stmt = $facultyloading_db->prepare("SELECT subject_code, lecture_hours FROM courses WHERE program_code = ? AND year_level = ? AND semester = ?");
    $stmt->execute([$section['program_code'], $section['year_level'], $semester]);
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Scheduling courses for {$section['section_name']} (Day Group: {$section['day_group']}):\n";
    if (count($courses) < $min_courses_per_day) { // Need at least 3 courses
        echo "Not enough courses for {$section['section_name']} to schedule at least {$min_courses_per_day} per day.\n";
        continue;
    }

    $assigned_courses = [];
    $start_slot = 0; // Start at 6:00 AM
    $days = $day_groups[$section['day_group']];

    // Schedule 3 courses per day across the assigned day group
    $courses_per_day = array_splice($courses, 0, $min_courses_per_day * count($days)); // Enough for all days
    $course_index = 0;

    foreach ($days as $day) {
        for ($i = 0; $i < $min_courses_per_day && $course_index < count($courses_per_day); $i++) {
            $course = $courses_per_day[$course_index];
            if (isset($assigned_courses[$course['subject_code']])) {
                $i--; // Retry with next course if already assigned
                $course_index++;
                continue;
            }
            $total_hours = $course['lecture_hours'] -1;
            if ($start_slot + $total_hours > 15) {
                echo "Not enough time slots on {$day} for {$course['subject_code']} in {$section['section_name']}.\n";
                break;
            }

            $room = find_available_room($room_availability, [$day], $start_slot, $total_hours);
            if (!$room || !is_slot_free($section_availability[$section['section_id']][$day], $start_slot, $total_hours)) {
                echo "No available room or slot for {$course['subject_code']} on {$day} for {$section['section_name']}.\n";
                continue;
            }

            $start_time = sprintf("%02d:00:00", 6 + $start_slot);
            $end_time = sprintf("%02d:00:00", 6 + $start_slot + $total_hours);

            try {
                $schedule_stmt->execute([
                    $section['section_id'],
                    $course['subject_code'],
                    $day,
                    $semester,
                    $section['program_code'],
                    $section['section_name'],
                    $section['year_level'],
                    $start_time,
                    $end_time
                ]);
                $room_stmt->execute([
                    $section['section_id'],
                    $course['subject_code'],
                    $day,
                    $start_time,
                    $end_time,
                    $room['room_id']
                ]);

                // Update availability
                for ($j = $start_slot; $j < $start_slot + $total_hours; $j++) {
                    $section_availability[$section['section_id']][$day][$j] = false;
                    $room_availability[$room['room_id']][$day][$j] = false;
                }

                $assigned_courses[$course['subject_code']] = true;
                echo "Scheduled {$course['subject_code']} for {$section['section_name']} on {$day} from {$start_time} to {$end_time} in room {$room['room_no']}\n";
                $start_slot += $total_hours; // Move to next slot
                $course_index++;
            } catch (PDOException $e) {
                echo "Error inserting {$course['subject_code']} for {$section['section_name']} on {$day}: " . $e->getMessage() . "\n";
            }
        }
        $start_slot = 0; // Reset slot for next day
    }

    // Handle remaining courses (if any) on remaining days within the same day group
    foreach ($courses as $course) {
        if (isset($assigned_courses[$course['subject_code']])) continue; // Skip if already scheduled
        $scheduled = false;
        foreach ($days as $day) {
            for ($slot = 0; $slot <= 15 - $course['lecture_hours']; $slot++) {
                if (is_slot_free($section_availability[$section['section_id']][$day], $slot, $course['lecture_hours']) &&
                    ($room = find_available_room($room_availability, [$day], $slot, $course['lecture_hours']))) {
                    $start_time = sprintf("%02d:00:00", 6 + $slot);
                    $end_time = sprintf("%02d:00:00", 6 + $slot + $course['lecture_hours']);

                    try {
                        $schedule_stmt->execute([
                            $section['section_id'],
                            $course['subject_code'],
                            $day,
                            $semester,
                            $section['program_code'],
                            $section['section_name'],
                            $section['year_level'],
                            $start_time,
                            $end_time
                        ]);
                        $room_stmt->execute([
                            $section['section_id'],
                            $course['subject_code'],
                            $day,
                            $start_time,
                            $end_time,
                            $room['room_id']
                        ]);

                        for ($i = $slot; $i < $slot + $course['lecture_hours']; $i++) {
                            $section_availability[$section['section_id']][$day][$i] = false;
                            $room_availability[$room['room_id']][$day][$i] = false;
                        }

                        $assigned_courses[$course['subject_code']] = true;
                        echo "Scheduled remaining {$course['subject_code']} for {$section['section_name']} on {$day} from {$start_time} to {$end_time} in room {$room['room_no']}\n";
                        $scheduled = true;
                        break 2;
                    } catch (PDOException $e) {
                        echo "Error inserting {$course['subject_code']} for {$section['section_name']} on {$day}: " . $e->getMessage() . "\n";
                    }
                }
            }
        }
        if (!$scheduled) {
            echo "Could not schedule remaining {$course['subject_code']} for {$section['section_name']} - no available slots or rooms.\n";
        }
    }
}

// Helper functions
function is_slot_free($availability, $start, $duration) {
    for ($i = $start; $i < $start + $duration; $i++) {
        if (!$availability[$i]) return false;
    }
    return true;
}

function find_available_room($room_availability, $days, $start, $duration) {
    global $rooms;
    foreach ($rooms as $room) {
        $all_free = true;
        foreach ($days as $day) {
            for ($i = $start; $i < $start + $duration; $i++) {
                if (!$room_availability[$room['room_id']][$day][$i]) {
                    $all_free = false;
                    break;
                }
            }
            if (!$all_free) break;
        }
        if ($all_free) return $room;
    }
    return null;
}

echo "Scheduling completed.\n";
?>