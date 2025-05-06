<?
include_once "../session/session.php";
include_once "../connections/connection.php";
$api_url = "http://localhost/registrar/coursesAPI.php";
$response = file_get_contents($api_url);
$courseData = json_decode($response, true);

if ($courseData['status'] === "success") {
    try {
        $stmt = $pdo->prepare("INSERT INTO courses 
            (course_id, program_code, subject_code, course_title, course_type, year_level, semester, lecture_hours, lab_hours, credit_units, slots) 
            VALUES (:course_id, :program_code, :subject_code, :course_title, :course_type, :year_level, :semester, :lecture_hours, :lab_hours, :credit_units, :slots)
            ON DUPLICATE KEY UPDATE course_title = VALUES(course_title), course_type = VALUES(course_type), year_level = VALUES(year_level), semester = VALUES(semester), lecture_hours = VALUES(lecture_hours), lab_hours = VALUES(lab_hours), credit_units = VALUES(credit_units), slots = VALUES(slots)
        ");

        foreach ($courseData['courses'] as $course) {
            $stmt->execute([
                ':course_id' => $course['course_id'],
                ':program_code' => $course['program_code'],
                ':subject_code' => $course['subject_code'],
                ':course_title' => $course['course_title'],
                ':course_type' => $course['course_type'],
                ':year_level' => $course['year_level'],
                ':semester' => $course['semester'],
                ':lecture_hours' => $course['lecture_hours'],
                ':lab_hours' => $course['lab_hours'],
                ':credit_units' => $course['credit_units'],
                ':slots' => $course['slots']
            ]);
        }
        // echo "✅ Courses synced successfully!";
    } catch (PDOException $e) {
        echo "❌ Sync failed: " . $e->getMessage();
    }
} else {
    echo "❌ Failed to fetch data from API.";
}
?>

