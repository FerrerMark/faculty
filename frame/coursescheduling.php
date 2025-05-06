<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $registrar_db = new PDO("mysql:host=localhost;dbname=registrar", "root", "", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    $facultyloading_db = new PDO("mysql:host=localhost;dbname=facultyloading", "root", "", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

$semester_input = '1';
$semester_map = ['1' => 'First', '2' => 'Second', '3' => 'Summer'];
$semester = $semester_map[$semester_input] ?? 'First';
const SECTION_SIZE = 50;
const START_HOUR = 6;    
const END_HOUR = 21;     
const MAX_TIME_SLOTS = END_HOUR - START_HOUR;
const SUBJECTS_PER_DAY = 3;

$day_groups = [
    'MWF' => ['Monday', 'Wednesday', 'Friday'],
    'TTHS' => ['Tuesday', 'Thursday', 'Saturday']
];

try {
    $stmt = $registrar_db->prepare("SELECT DISTINCT Department, year_level, enrolled_count FROM enrolled_count WHERE semestrial = ? AND Department = 'BSIT'");
    $stmt->execute([$semester_input]);
    $enrollments = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Failed to fetch enrollment data: " . $e->getMessage());
}

try {
    $programs = $facultyloading_db->query("SELECT program_code FROM programs WHERE program_code = 'BSIT'")->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    die("Failed to fetch programs: " . $e->getMessage());
}

$insert_section_stmt = $facultyloading_db->prepare(
    "INSERT INTO sections (program_code, year_level, section_name, semester) VALUES (?, ?, ?, ?)"
);
$schedule_stmt = $facultyloading_db->prepare(
    "INSERT INTO section_schedules (section_id, subject_code, day_of_week, semester, program_code, section_name, year_level, start_time, end_time) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
);
$room_stmt = $facultyloading_db->prepare(
    "INSERT INTO room_assignments (section_id, subject_code, day_of_week, start_time, end_time, room_id) 
    VALUES (?, ?, ?, ?, ?, ?)"
);

$sections = [];
$section_counter = 1;
$created_sections = []; // Track created section names to avoid duplicates
foreach ($enrollments as $enrollment) {
    $program_code = $enrollment['Department'];
    $year_level = $enrollment['year_level'];
    $enrolled_count = $enrollment['enrolled_count'];

    if (!in_array($program_code, $programs)) {
        echo "Skipping {$program_code} - not found in facultyloading.programs\n";
        continue;
    }

    $number_of_sections = ceil($enrolled_count / SECTION_SIZE);
    echo "Processing {$program_code}, Year {$year_level}: {$enrolled_count} students, {$number_of_sections} sections\n";

    for ($i = 1; $i <= $number_of_sections; $i++) {
        $section_number = str_pad($i, 2, '0', STR_PAD_LEFT);
        $section_name = "{$program_code}-{$year_level}{$semester_input}{$section_number}";
        if (in_array($section_name, $created_sections)) {
            echo "Section {$section_name} already created, skipping...\n";
            continue;
        }

        $day_group = ($section_counter % 2 == 0) ? 'TTHS' : 'MWF';
        
        try {
            $insert_section_stmt->execute([$program_code, $year_level, $section_name, $semester]);
            $section_id = $facultyloading_db->lastInsertId();
            $sections[] = [
                'section_id' => $section_id,
                'section_name' => $section_name,
                'program_code' => $program_code,
                'year_level' => $year_level,
                'day_group' => $day_group
            ];
            $created_sections[] = $section_name;
            echo "Created section: {$section_name} (Day Group: {$day_group})\n";
        } catch (PDOException $e) {
            echo "Failed to create section {$section_name}: " . $e->getMessage() . "\n";
        }
        $section_counter++;
    }
}

$all_days = array_merge(...array_values($day_groups));
$rooms = $facultyloading_db->query("SELECT room_id, room_no FROM rooms WHERE room_type = 'Lecture'")->fetchAll();
if (empty($rooms)) {
    die("No lecture rooms available\n");
}

$section_availability = array_fill_keys(array_column($sections, 'section_id'), 
    array_fill_keys($all_days, array_fill(0, MAX_TIME_SLOTS, true))
);
$room_availability = array_fill_keys(array_column($rooms, 'room_id'), 
    array_fill_keys($all_days, array_fill(0, MAX_TIME_SLOTS, true))
);

$global_scheduled_courses = []; // Initialize as an empty array

foreach ($sections as $section) {
    $stmt = $facultyloading_db->prepare(
        "SELECT subject_code, lecture_hours FROM courses WHERE program_code = ? AND year_level = ? AND semester = ?"
    );
    $stmt->execute([$section['program_code'], $section['year_level'], $semester]);
    $courses = $stmt->fetchAll();

    if (empty($courses)) {
        echo "No courses found for {$section['section_name']}\n";
        continue;
    }

    echo "Scheduling for {$section['section_name']} (Day Group: {$section['day_group']}):\n";
    $days = $day_groups[$section['day_group']];
    $local_scheduled_courses = []; // Reset for each section

    $total_courses = count($courses);
    $courses_per_day = min(SUBJECTS_PER_DAY, ceil($total_courses / count($days)));
    $course_chunks = array_chunk($courses, $courses_per_day);

    foreach ($days as $day_index => $day) {
        if (!isset($course_chunks[$day_index])) {
            break;
        }

        $day_courses = $course_chunks[$day_index];
        $start_slot = 0;

        foreach ($day_courses as $course) {
            if (isset($local_scheduled_courses[$course['subject_code']])) {
                echo "Course {$course['subject_code']} already scheduled for this section, skipping...\n";
                continue;
            }

            $hours_needed = max(1, $course['lecture_hours'] - 1);

            if ($start_slot + $hours_needed > MAX_TIME_SLOTS) {
                echo "Not enough time slots remaining on {$day} for {$course['subject_code']}\n";
                break;
            }

            $room = find_available_room($room_availability, [$day], $start_slot, $hours_needed);
            if (!$room || !is_slot_free($section_availability[$section['section_id']][$day], $start_slot, $hours_needed)) {
                $start_slot = find_next_available_slot($section_availability[$section['section_id']][$day], 
                    $room_availability, $day, $hours_needed);
                if ($start_slot === false) {
                    echo "No available slot or room for {$course['subject_code']} on {$day}\n";
                    continue;
                }
                $room = find_available_room($room_availability, [$day], $start_slot, $hours_needed);
                if (!$room) {
                    continue;
                }
            }

            $start_time = sprintf("%02d:00:00", START_HOUR + $start_slot);
            $end_time = sprintf("%02d:00:00", START_HOUR + $start_slot + $hours_needed);

            try {
                $facultyloading_db->beginTransaction();
                
                $schedule_stmt->execute([
                    $section['section_id'], $course['subject_code'], $day, $semester,
                    $section['program_code'], $section['section_name'], $section['year_level'],
                    $start_time, $end_time
                ]);
                
                $room_stmt->execute([
                    $section['section_id'], $course['subject_code'], $day,
                    $start_time, $end_time, $room['room_id']
                ]);

                update_availability(
                    $section_availability[$section['section_id']][$day],
                    $room_availability[$room['room_id']][$day],
                    $start_slot,
                    $hours_needed
                );

                $facultyloading_db->commit();
                $local_scheduled_courses[$course['subject_code']] = true;
                $global_scheduled_courses[$section['section_name']][$course['subject_code']] = true;
                echo "Scheduled {$course['subject_code']} on {$day} {$start_time}-{$end_time} in {$room['room_no']} ({$hours_needed}h)\n";
                $start_slot += $hours_needed;
            } catch (PDOException $e) {
                $facultyloading_db->rollBack();
                echo "Error scheduling {$course['subject_code']}: " . $e->getMessage() . "\n";
            }
        }
    }

    $unscheduled = array_filter($courses, fn($c) => !isset($local_scheduled_courses[$c['subject_code']]));
    if (!empty($unscheduled)) {
        echo "Warning: Could not schedule: " . implode(', ', array_column($unscheduled, 'subject_code')) . "\n";
    }
}

function is_slot_free($availability, $start, $duration) {
    for ($i = $start; $i < $start + $duration; $i++) {
        if (!isset($availability[$i]) || !$availability[$i]) {
            return false;
        }
    }
    return true;
}

function find_next_available_slot($section_day, $room_availability, $day, $duration) {
    global $rooms;
    for ($slot = 0; $slot <= MAX_TIME_SLOTS - $duration; $slot++) {
        if (is_slot_free($section_day, $slot, $duration) && 
            find_available_room($room_availability, [$day], $slot, $duration)) {
            return $slot;
        }
    }
    return false;
}

function find_available_room($room_availability, $days, $start, $duration) {
    global $rooms;
    foreach ($rooms as $room) {
        $available = true;
        foreach ($days as $day) {
            if (!is_slot_free($room_availability[$room['room_id']][$day], $start, $duration)) {
                $available = false;
                break;
            }
        }
        if ($available) {
            return $room;
        }
    }
    return null;
}

function update_availability(&$section_day, &$room_day, $start, $duration) {
    for ($i = $start; $i < $start + $duration; $i++) {
        $section_day[$i] = false;
        $room_day[$i] = false;
    }
}

echo "Scheduling completed.\n";

echo "\nVerifying schedules at 12:00:00:\n";
$result = $facultyloading_db->query("SELECT * FROM section_schedules WHERE start_time = '12:00:00' AND program_code = 'BSIT'");
$schedules = $result->fetchAll();
if (empty($schedules)) {
    echo "No schedules found starting at 12:00:00\n";
} else {
    foreach ($schedules as $schedule) {
        echo "Found: {$schedule['subject_code']} in {$schedule['section_name']} on {$schedule['day_of_week']}\n";
    }
}
?>