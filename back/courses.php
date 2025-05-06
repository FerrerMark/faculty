<?php
include_once("../session/session.php");
include_once "../connections/connection.php";

// include_once "../registrar/sync_fetch_courses.php";

$programCode = $_GET['program_code'] ?? $_POST['program_code'] ?? null;

if (!$programCode) {
    header("Location: programs.php?error=No program code provided");
    exit;
}

try {
    $stmt = $conn->prepare("SELECT program_name, college FROM programs WHERE program_code = :program_code");
    $stmt->bindParam(':program_code', $programCode, PDO::PARAM_STR);
    $stmt->execute();
    $program = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$program) {
        die("Program not found.");
    }

    $stmt = $conn->prepare("SELECT * FROM courses WHERE program_code = :program_code");
    $stmt->bindParam(':program_code', $programCode, PDO::PARAM_STR);
    $stmt->execute();
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_GET['action'] === 'add' && $_GET['role'] === 'Dean') {
        $subjectCode = $_POST['subject_code'];
        $courseTitle = $_POST['course_title'];
        $yearLevel = $_POST['year_level'];
        $semester = $_POST['semester'];
        $lectureHours = $_POST['lecture_hours'];
        $labHours = $_POST['lab_hours'];
        $creditUnits = $_POST['credit_units'];
        $slots = $_POST['slots'];

        $stmt = $conn->prepare("
            INSERT INTO courses (program_code, subject_code, course_title, year_level, semester, lecture_hours, lab_hours, credit_units, slots)
            VALUES (:program_code, :subject_code, :course_title, :year_level, :semester, :lecture_hours, :lab_hours, :credit_units, :slots)
        ");
        $stmt->bindParam(':program_code', $programCode);
        $stmt->bindParam(':subject_code', $subjectCode);
        $stmt->bindParam(':course_title', $courseTitle);
        $stmt->bindParam(':year_level', $yearLevel, PDO::PARAM_INT);
        $stmt->bindParam(':semester', $semester);
        $stmt->bindParam(':lecture_hours', $lectureHours, PDO::PARAM_INT);
        $stmt->bindParam(':lab_hours', $labHours, PDO::PARAM_INT);
        $stmt->bindParam(':credit_units', $creditUnits, PDO::PARAM_INT);
        $stmt->bindParam(':slots', $slots, PDO::PARAM_INT);
        $stmt->execute();

        header("Location: courses.php?program_code=" . urlencode($programCode) . "&role=" . urlencode($_GET['role']) . "&success=Course added successfully&department=" . urlencode($programCode));
        exit;
    }

    if (isset($_GET['delete_course_code'])) {
        $programCode = $_GET['program_code'];
        $role = $_GET['role'];

        $courseCodeToDelete = $_GET['delete_course_code'];

        $stmt = $conn->prepare("DELETE FROM courses WHERE subject_code = :subject_code AND program_code = :program_code");
        $stmt->bindParam(':subject_code', $courseCodeToDelete, PDO::PARAM_STR);
        $stmt->bindParam(':program_code', $programCode, PDO::PARAM_STR);
        $stmt->execute();

        header("Location: courses.php?program_code=" . urlencode($programCode) . "&role=" . urlencode($role) . "&success=Course deleted successfully&department=" . urlencode($programCode));
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_GET['action'] === 'edit' && $_GET['role'] === 'Dean') {
        $role = $_GET['role'];

        $subject_code = $_POST['subject_code'];
        $course_title = $_POST['course_title'];
        $year_level = $_POST['year_level'];
        $semester = $_POST['semester'];
        $lecture_hours = $_POST['lecture_hours'];
        $lab_hours = $_POST['lab_hours'];
        $credit_units = $_POST['credit_units'];
        $slots = $_POST['slots'];
        $program_code = $_POST['program_code'];

        $query = "SELECT * FROM courses WHERE subject_code = :subject_code";
        $stmt = $pdo->prepare($query);
        $stmt->execute(['subject_code' => $subject_code]);
        $existingCourse = $stmt->fetch();

        if ($existingCourse && !isset($_POST['edit_course'])) {
            echo "Course code already exists!";
        } else {
            if (isset($_POST['edit_course'])) {
                $course_id = $_POST['course_id'];

                $query = "UPDATE courses SET subject_code = :subject_code, 
                        course_title = :course_title, 
                        year_level = :year_level, semester = :semester, lecture_hours = :lecture_hours, 
                        lab_hours = :lab_hours, credit_units = :credit_units, slots = :slots
                        WHERE course_id = :course_id";
                $stmt = $pdo->prepare($query);
                $stmt->execute([
                    'subject_code' => $subject_code,
                    'course_title' => $course_title,
                    'year_level' => $year_level,
                    'semester' => $semester,
                    'lecture_hours' => $lecture_hours,
                    'lab_hours' => $lab_hours,
                    'credit_units' => $credit_units,
                    'slots' => $slots,
                    'course_id' => $course_id
                ]);

                header("Location: courses.php?program_code=" . urlencode($programCode) . "&role=" . urlencode($role) . "&success=Course updated successfully&department=" . urlencode($programCode));
            }
        }
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

$sql = "SELECT * from courses WHERE program_code = :program_code";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':program_code', $programCode);
$stmt->execute();
$coursecount = $stmt->rowCount();


?>