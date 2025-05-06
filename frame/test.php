<?php
// Database connections
try {
    $registrar_db = new PDO("mysql:host=localhost;dbname=registrar", "username", "password");
    $facultyloading_db = new PDO("mysql:host=localhost;dbname=facultyloading", "username", "password");
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Configuration
$semester = '1';         // Semester from enrolled_count (single digit)
$section_size = 50;      // Max students per section

// Step 1: Get enrollment data from registrar.enrolled_count
$stmt = $registrar_db->prepare("SELECT Department, year_level, enrolled_count FROM enrolled_count WHERE semestrial = ?");
$stmt->execute([$semester]);
$enrollments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Step 2: Get valid programs from facultyloading.programs
$programs = $facultyloading_db->query("SELECT program_code FROM programs")->fetchAll(PDO::FETCH_COLUMN);

// Step 3: Create sections with BSIT-1101 naming
$insert_section_stmt = $facultyloading_db->prepare("INSERT INTO sections (program_code, year_level, section_name, semester) VALUES (?, ?, ?, ?)");
$sections = [];
foreach ($enrollments as $enrollment) {
    $program_code = $enrollment['Department'];
    $year_level = $enrollment['year_level'];
    $enrolled_count = $enrollment['enrolled_count'];

    // Validate program_code against facultyloading.programs
    if (!in_array($program_code, $programs)) {
        echo "Skipping {$program_code} - not found in facultyloading.programs\n";
        continue;
    }

    $number_of_sections = ceil($enrolled_count / $section_size);
    echo "Processing {$program_code}, Year {$year_level}: {$enrolled_count} students, {$number_of_sections} sections needed\n";

    for ($i = 1; $i <= $number_of_sections; $i++) {
        $section_number = str_pad($i, 2, '0', STR_PAD_LEFT); // e.g., 01
        $section_name = "{$program_code}-{$year_level}{$semester}{$section_number}"; // e.g., BSIT-1101
        $insert_section_stmt->execute([$program_code, $year_level, $section_name, $semester]);
        $section_id = $facultyloading_db->lastInsertId();
        $sections[] = [
            'section_id' => $section_id,
            'section_name' => $section_name,
            'program_code' => $program_code,
            'year_level' => $year_level
        ];
        echo "Created section: {$section_name}\n";
    }
}

// Step 4: Scheduling setup
$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
$time_slots = range(0, 14); // 6:00 AM to 9:00 PM (0 = 6:00-7:00, 14 = 20:00-21:00)

// Fetch lecture rooms from facultyloading.rooms
$rooms = $facultyloading_db->query("SELECT room_id, room_no FROM rooms WHERE room_type = 'Lecture'")->fetchAll(PDO::FETCH_ASSOC);

// Initialize availability trackers
$section_availability = array_fill_keys(array_column($sections, 'section_id'), array_fill_keys($days, array_fill(0, 15, true)));
$room_availability = array_fill_keys(array_column($rooms, 'room_id'), array_fill_keys($days, array_fill(0, 15, true)));

// Step 5: Schedule sections with courses from facultyloading.courses
$schedule_stmt = $facultyloading_db->prepare("
    INSERT INTO section_schedules (section_id, subject_code, day_of_week, semester, program_code, section_name, year_level, start_time, end_time) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
");
$room_stmt = $facultyloading_db->prepare("INSERT INTO room_assignments (section_id, subject_code, day_of_week, start_time, end_time, room_id) VALUES (?, ?, ?, ?, ?, ?)");

foreach ($sections as $section) {
    // Fetch courses for this section's program and year level
    $stmt = $facultyloading_db->prepare("SELECT subject_code, lecture_hours FROM courses WHERE program_code = ? AND year_level = ? AND semester = ?");
    $stmt->execute([$section['program_code'], $section['year_level'], $semester]);
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Scheduling courses for {$section['section_name']}:\n";
    foreach ($courses as $course) {
        $duration = $course['lecture_hours'];
        $scheduled = false;

        foreach ($days as $day) {
            for ($slot = 0; $slot <= 15 - $duration; $slot++) {
                if (is_slot_free($section_availability[$section['section_id']][$day], $slot, $duration)) {
                    foreach ($rooms as $room) {
                        if (is_slot_free($room_availability[$room['room_id']][$day], $slot, $duration)) {
                            $start_time = sprintf("%02d:00:00", 6 + $slot);
                            $end_time = sprintf("%02d:00:00", 6 + $slot + $duration);

                            // Store in section_schedules
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

                            // Store in room_assignments
                            $room_stmt->execute([
                                $section['section_id'],
                                $course['subject_code'],
                                $day,
                                $start_time,
                                $end_time,
                                $room['room_id']
                            ]);

                            // Update availability
                            for ($i = $slot; $i < $slot + $duration; $i++) {
                                $section_availability[$section['section_id']][$day][$i] = false;
                                $room_availability[$room['room_id']][$day][$i] = false;
                            }

                            echo "Scheduled {$course['subject_code']} for {$section['section_name']} on {$day} from {$start_time} to {$end_time} in room {$room['room_no']}\n";
                            $scheduled = true;
                            break 2; // Exit room and day loops
                        }
                    }
                }
            }
            if ($scheduled) break;
        }
        if (!$scheduled) {
            echo "Could not schedule {$course['subject_code']} for {$section['section_name']} - no available slots or rooms.\n";
        }
    }
}

// Helper function to check slot availability
function is_slot_free($availability, $start, $duration) {
    for ($i = $start; $i < $start + $duration; $i++) {
        if (!$availability[$i]) return false;
    }
    return true;
}

echo "Scheduling completed.\n";
?>