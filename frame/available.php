<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once "../connections/connection.php";
include_once "../comps/notif.php";
include_once "../session/session.php";

$faculty_id = $_SESSION['id'];
$query = "SELECT employment_status, availability, start_time, end_time FROM faculty WHERE faculty_id = :faculty_id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':faculty_id', $faculty_id, PDO::PARAM_INT);
$stmt->execute();
$faculty = $stmt->fetch(PDO::FETCH_ASSOC);
$employment_status = $faculty['employment_status'];
$current_availability = explode(',', $faculty['availability']);
$current_start_time = $faculty['start_time'];
$current_end_time = $faculty['end_time'];

$assigned_courses_query = "SELECT fc.subject_code, c.course_title, c.year_level, c.semester 
                          FROM faculty_courses fc 
                          JOIN courses c ON fc.subject_code = c.subject_code 
                          WHERE fc.faculty_id = :faculty_id";
$stmt = $conn->prepare($assigned_courses_query);
$stmt->bindParam(':faculty_id', $faculty_id, PDO::PARAM_INT);
$stmt->execute();
$assigned_courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

$request_history_query = "SELECT ppc.pending_id, ppc.subject_code, c.course_title, c.year_level, c.semester, ppc.status, ppc.submission_date 
                          FROM pending_preferred_courses ppc 
                          JOIN courses c ON ppc.subject_code = c.subject_code 
                          WHERE ppc.faculty_id = :faculty_id 
                          ORDER BY ppc.submission_date DESC 
                          LIMIT 10";
$stmt = $conn->prepare($request_history_query);
$stmt->bindParam(':faculty_id', $faculty_id, PDO::PARAM_INT);
$stmt->execute();
$request_history = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch distinct year levels for courses
$year_level_query = "SELECT DISTINCT year_level FROM courses WHERE program_code = 'BSIT' AND year_level IS NOT NULL ORDER BY year_level";
$stmt = $conn->prepare($year_level_query);
$stmt->execute();
$year_levels = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Fetch distinct semesters for courses
$semester_query = "SELECT DISTINCT semester FROM courses WHERE program_code = 'BSIT' AND semester IS NOT NULL ORDER BY semester";
$stmt = $conn->prepare($semester_query);
$stmt->execute();
$semesters = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Fetch courses for the toolbar and form
$course_query = "SELECT subject_code, course_title, year_level, semester FROM courses WHERE program_code = 'BSIT'";
$stmt = $conn->prepare($course_query);
$stmt->execute();
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: []; // Initialize as empty array if null

$message = isset($_SESSION['message']) ? $_SESSION['message'] : null;
unset($_SESSION['message']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instructor Availability</title>
    <link rel="stylesheet" href="../css/available.css">
</head>
<body>
    <h1>Set Your Availability</h1>
    <?php if ($message): ?>
        <div class="notification">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <!-- Toolbar -->
    <div class="toolbar">
        <h3><?php echo count($courses); ?> Courses</h3>
        <div class="toolbar-controls">
            <div class="filter-section">
                <span class="filter-title">Year Level:</span>
                <div class="filter-items" id="yearFilter">
                    <?php foreach ($year_levels as $year): ?>
                        <?php
                        $count_query = "SELECT COUNT(*) FROM courses WHERE program_code = 'BSIT' AND year_level = :year";
                        $stmt = $conn->prepare($count_query);
                        $stmt->bindParam(':year', $year, PDO::PARAM_INT);
                        $stmt->execute();
                        $count = $stmt->fetchColumn();
                        ?>
                        <span class="filter-item" data-filter="year" data-value="<?php echo $year; ?>"><?php echo $year; ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="filter-section">
                <span class="filter-title">Semester:</span>
                <div class="filter-items" id="semesterFilter">
                    <?php foreach ($semesters as $semester): ?>
                        <?php
                        $count_query = "SELECT COUNT(*) FROM courses WHERE program_code = 'BSIT' AND semester = :semester";
                        $stmt = $conn->prepare($count_query);
                        $stmt->bindParam(':semester', $semester, PDO::PARAM_STR);
                        $stmt->execute();
                        $count = $stmt->fetchColumn();
                        
                        // Convert semester display (1st -> 1, 2nd -> 2)
                        $display_semester = ($semester === '1st') ? '1' : (($semester === '2nd') ? '2' : $semester);
                        ?>
                        <span class="filter-item sem" data-filter="semester" data-value="<?php echo $display_semester; ?>"><?php echo $display_semester; ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="search-container">
                <input type="text" placeholder="Search courses..." class="search-box">
            </div>
        </div>
    </div>

    <form action="../back/availability.php" method="POST">
        <fieldset>
            <legend>Select Courses:</legend>
            <div class="courses-list">
                <?php
                $assigned_subject_codes = array_column($assigned_courses, 'subject_code');
                foreach ($courses as $course) {
                    $checked = in_array($course['subject_code'], $assigned_subject_codes) ? 'checked' : '';
                    echo "<div class='course-item' data-year='{$course['year_level']}' data-semester='{$course['semester']}'>";
                    echo "<input type='checkbox' name='courses[]' value='{$course['subject_code']}' id='course_{$course['subject_code']}' $checked>";
                    echo "<label for='course_{$course['subject_code']}'>{$course['course_title']} (Year {$course['year_level']}, {$course['semester']} Semester)</label>";
                    echo "</div>";
                }
                ?>
            </div>
        </fieldset>
        <button type="submit">Save Availability</button>
    </form>

    <h4>My Current Course/s</h4>
    <?php if (!empty($assigned_courses)): ?>
        <table id="current-courses">
            <thead>
                <tr>
                    <th>Course Title</th>
                    <th>Year Level</th>
                    <th>Semester</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($assigned_courses as $course): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($course['course_title']); ?></td>
                        <td><?php echo htmlspecialchars($course['year_level']); ?></td>
                        <td><?php echo htmlspecialchars($course['semester']); ?></td>
                        <td>
                            <form action="../back/availability.php" method="POST" style="display:inline;">
                                <input type="hidden" name="subject_code" value="<?php echo htmlspecialchars($course['subject_code']); ?>">
                                <input type="hidden" name="delete_course" value="1">
                                <button type="submit" class="delete-btn" onclick="return confirm('Are you sure you want to delete <?php echo htmlspecialchars($course['course_title']); ?>?');">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="no-data">No current courses assigned.</p>
    <?php endif; ?>

    <h4>My Request History</h4>
    <?php if (!empty($request_history)): ?>
        <table id="request-history">
            <thead>
                <tr>
                    <th>Course Title</th>
                    <th>Year Level</th>
                    <th>Semester</th>
                    <th>Status</th>
                    <th>Submission Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($request_history as $request): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($request['course_title']); ?></td>
                        <td><?php echo htmlspecialchars($request['year_level']); ?></td>
                        <td><?php echo htmlspecialchars($request['semester']); ?></td>
                        <td><?php echo htmlspecialchars($request['status']); ?></td>
                        <td><?php echo htmlspecialchars($request['submission_date']); ?></td>
                        <td>
                            <?php if ($request['status'] === 'Pending'): ?>
                                <form action="../back/availability.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="pending_id" value="<?php echo htmlspecialchars($request['pending_id']); ?>">
                                    <input type="hidden" name="cancel_request" value="1">
                                    <button type="submit" class="cancel-btn" onclick="return confirm('Are you sure you want to cancel the request for <?php echo htmlspecialchars($request['course_title']); ?>?');">Cancel</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="no-data">No request history available.</p>
    <?php endif; ?>

    <?php
    if (isset($_GET['cancel_success'])) {
        showNotification('Request cancelled successfully.', 'green');
    }

    if (isset($_GET['delete_success'])) {
        showNotification('Delete course successfully.', 'red');
    }

    if (isset($_GET['request_success'])) {
        showNotification('Request successfully.', 'green');
    }
    ?>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.querySelector('.search-box');
        const filterItems = document.querySelectorAll('.filter-item');
        const courseItems = document.querySelectorAll('.course-item');
        let activeFilters = { year: null, semester: null };

        // Search input event
        searchInput.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            filterContent(searchTerm, activeFilters);
        });

        // Filter event for year and semester
        filterItems.forEach(item => {
            item.addEventListener('click', function() {
                const filterType = this.getAttribute('data-filter');
                const filterValue = this.getAttribute('data-value');

                // Toggle active filter
                if (activeFilters[filterType] === filterValue) {
                    activeFilters[filterType] = null;
                    this.classList.remove('active');
                } else {
                    document.querySelectorAll(`[data-filter="${filterType}"]`).forEach(el => {
                        el.classList.remove('active');
                    });
                    activeFilters[filterType] = filterValue;
                    this.classList.add('active');
                }

                filterContent(searchInput.value.toLowerCase(), activeFilters);
            });
        });

        function filterContent(searchTerm, filters) {
            // Filter course checkboxes only
            courseItems.forEach(item => {
                const courseTitle = item.querySelector('label').textContent.toLowerCase();
                const yearLevel = item.getAttribute('data-year');
                const semester = item.getAttribute('data-semester');
                
                let matchesYear = true;
                if (filters.year) {
                    matchesYear = yearLevel === filters.year;
                }

                let matchesSemester = true;
                if (filters.semester) {
                    // Map filter values (1, 2) to database values (1st, 2nd)
                    const mappedSemester = filters.semester === '1' ? '1st' : (filters.semester === '2' ? '2nd' : filters.semester);
                    matchesSemester = semester === mappedSemester;
                }

                let matchesSearch = true;
                if (searchTerm) {
                    matchesSearch = courseTitle.includes(searchTerm);
                }

                item.style.display = matchesYear && matchesSemester && matchesSearch ? '' : 'none';
            });

            // Update empty state for courses list
            const visibleCourses = Array.from(courseItems).filter(item => item.style.display !== 'none');
            const coursesList = document.querySelector('.courses-list');
            coursesList.dataset.empty = visibleCourses.length === 0 ? 'true' : 'false';
        }
    });
    </script>
</body>
</html>