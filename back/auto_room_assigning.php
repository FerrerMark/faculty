<?php
include_once "../session/session.php";
try {
    include_once "../connections/connection.php";
    function assignRooms($pdo) {
        try {
            $result = [
                'assigned' => [],
                'unassigned' => [],
                'message' => ''
            ];

            $sql = "SELECT ss.schedule_id, ss.section_id, ss.subject_code, 
                           ss.day_of_week, ss.start_time, ss.end_time, 
                           ss.semester, ss.program_code, ss.section_name, ss.year_level,
                           c.course_type, c.slots
                    FROM section_schedules ss
                    LEFT JOIN room_assignments ra 
                        ON ra.section_id = ss.section_id 
                        AND ra.subject_code = ss.subject_code 
                        AND ra.day_of_week = ss.day_of_week 
                        AND ra.start_time = ss.start_time 
                        AND ra.end_time = ss.end_time
                    JOIN courses c ON ss.subject_code = c.subject_code
                    WHERE ra.assignment_id IS NULL";
            
            $stmt = $pdo->query($sql);
            $schedules = $stmt->fetchAll();

            $assigned_rooms = [];

            foreach ($schedules as $schedule) {
                $required_room_type = determineRoomType($schedule['subject_code'], $pdo);
                $required_capacity = $schedule['slots'];
                
                $available_rooms = findAvailableRooms(
                    $pdo,
                    $schedule['day_of_week'],
                    $schedule['start_time'],
                    $schedule['end_time'],
                    $required_room_type,
                    $required_capacity,
                    $assigned_rooms
                );
                
                if (!empty($available_rooms)) {
                    $room_id = $available_rooms[0]['room_id'];
                    $room_no = $available_rooms[0]['room_no'];
                    
                    $pdo->beginTransaction();
                    
                    $insert_sql = "INSERT INTO room_assignments 
                                 (section_id, subject_code, day_of_week, start_time, end_time, room_id)
                                 VALUES (:section_id, :subject_code, :day_of_week, :start_time, :end_time, :room_id)";
                    
                    $insert_stmt = $pdo->prepare($insert_sql);
                    $insert_stmt->execute([
                        ':section_id' => $schedule['section_id'],
                        ':subject_code' => $schedule['subject_code'],
                        ':day_of_week' => $schedule['day_of_week'],
                        ':start_time' => $schedule['start_time'],
                        ':end_time' => $schedule['end_time'],
                        ':room_id' => $room_id
                    ]);
                    
                    $pdo->commit();
                    
                    $result['assigned'][] = [
                        'schedule_id' => $schedule['schedule_id'],
                        'section_name' => $schedule['section_name'],
                        'subject_code' => $schedule['subject_code'],
                        'day_of_week' => $schedule['day_of_week'],
                        'start_time' => $schedule['start_time'],
                        'end_time' => $schedule['end_time'],
                        'room_id' => $room_id,
                        'room_no' => $room_no
                    ];
                    
                    $assigned_rooms[] = [
                        'room_id' => $room_id,
                        'day_of_week' => $schedule['day_of_week'],
                        'start_time' => $schedule['start_time'],
                        'end_time' => $schedule['end_time']
                    ];
                } else {
                    $result['unassigned'][] = [
                        'schedule_id' => $schedule['schedule_id'],
                        'section_name' => $schedule['section_name'],
                        'subject_code' => $schedule['subject_code'],
                        'day_of_week' => $schedule['day_of_week'],
                        'start_time' => $schedule['start_time'],
                        'end_time' => $schedule['end_time'],
                        'reason' => 'No available rooms'
                    ];
                }
            }
            
            $result['message'] = "Room assignment process completed. " .
                               count($result['assigned']) . " schedules assigned, " .
                               count($result['unassigned']) . " schedules unassigned.";
            
            return $result;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw new Exception("Error in room assignment: " . $e->getMessage());
        }
    }

    function determineRoomType($subject_code, $pdo) {
        $lab_courses = ['CC101', 'CC102', 'CC103', 'CC104', 'NET101', 'NET102'];
        return in_array($subject_code, $lab_courses) ? 'Computer Lab' : 'Lecture';
    }

    function findAvailableRooms($pdo, $day, $start_time, $end_time, $room_type, $min_capacity, $assigned_rooms) {
        $sql = "SELECT r.room_id, r.room_no, r.building, r.capacity
                FROM rooms r
                WHERE r.room_type = :room_type
                AND r.capacity >= :capacity
                AND r.room_id NOT IN (
                    SELECT ra.room_id 
                    FROM room_assignments ra
                    WHERE ra.day_of_week = :day_of_week
                    AND (
                        (ra.start_time < :end_time1 AND ra.end_time > :start_time1)
                        OR (ra.start_time < :end_time2 AND ra.end_time > :start_time2)
                        OR (ra.start_time >= :start_time3 AND ra.end_time <= :end_time3)
                    )
                )";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':room_type' => $room_type,
            ':capacity' => $min_capacity,
            ':day_of_week' => $day,
            ':start_time1' => $start_time,
            ':end_time1' => $end_time,
            ':start_time2' => $start_time,
            ':end_time2' => $end_time,
            ':start_time3' => $start_time,
            ':end_time3' => $end_time
        ]);
        
        $rooms = $stmt->fetchAll();
        
        $available_rooms = [];
        foreach ($rooms as $room) {
            $is_assigned = false;
            foreach ($assigned_rooms as $assigned) {
                if ($room['room_id'] == $assigned['room_id'] &&
                    $assigned['day_of_week'] == $day &&
                    $assigned['start_time'] == $start_time &&
                    $assigned['end_time'] == $end_time) {
                    $is_assigned = true;
                    break;
                }
            }
            if (!$is_assigned) {
                $available_rooms[] = $room;
            }
        }
        
        return $available_rooms;
    }

    $result = assignRooms($pdo);

    header('Content-Type: application/json');
    echo json_encode($result, JSON_PRETTY_PRINT);

    header("location: ../frame/rooms.php?role=Department%20Head&department=BSIT");


} catch (PDOException $e) {
    $error = [
        'error' => 'Connection failed',
        'message' => $e->getMessage()
    ];
    header('Content-Type: application/json');
    echo json_encode($error, JSON_PRETTY_PRINT);
} catch (Exception $e) {
    $error = [
        'error' => 'Assignment failed',
        'message' => $e->getMessage()
    ];
    header('Content-Type: application/json');
    echo json_encode($error, JSON_PRETTY_PRINT);
}
?>
