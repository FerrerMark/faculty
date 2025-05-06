<?php
include_once "../back/courses.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1. 0">
    <title>Courses - <?php echo htmlspecialchars($programCode); ?></title>
    <link rel="stylesheet" href="../css/courses.css">
</head>
<body>
    
    <div class="header">
        <div class="program-title"><?php echo htmlspecialchars($programCode); ?></div>
        <div class="program-subtitle"><?php echo htmlspecialchars($program['program_name']); ?></div>
        <div class="program-subtitle">College: <?php echo htmlspecialchars($program['college']); ?></div>
        
    </div>

    <div class="toolbar">
        <h3><?php echo $coursecount ?>&nbsp;Courses</h3>
        <div class="toolbar-controls">
            <div class="filter-section">
                <span class="filter-title">Semester:</span>
                <div class="filter-items" id="semesterFilter">
                    <?php
                    $semesters = ['First', 'Second'];
                    foreach ($semesters as $sem) {
                        $count = array_reduce($courses, function($carry, $course) use ($sem) {
                            return $carry + ($course['semester'] === $sem ? 1 : 0);
                        }, 0);
                        echo "<span class='filter-item' data-filter='semester' data-value='$sem'>$sem ($count)</span>";
                    }
                    ?>
                </div>
            </div>
            <div class="filter-section">
                <span class="filter-title">Year Level:</span>
                <div class="filter-items" id="yearFilter">
                    <?php
                    $years = [1, 2, 3, 4];
                    foreach ($years as $year) {
                        $count = array_reduce($courses, function($carry, $course) use ($year) {
                            return $carry + ($course['year_level'] === $year ? 1 : 0);
                        }, 0);
                        echo "<span class='filter-item' data-filter='year' data-value='$year'>$year ($count)</span>";
                    }
                    ?>
                </div>
            </div>
            <div class="search-container">
                <input type="text" placeholder="Search..." class="search-box">
            </div>
        </div>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Course Code</th>
                    <th>Course Title</th>
                    <th>Program</th>
                    <th>Year Level</th>
                    <th>Semester</th>
                    <th>Lecture/Lab Hrs</th>
                    <th>Credit Units</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($courses as $course): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($course['subject_code']); ?></td>
                        <td><?php echo htmlspecialchars($course['course_title']); ?></td>
                        <td><?php echo htmlspecialchars($course['program_code']); ?></td>
                        <td><?php echo htmlspecialchars($course['year_level']); ?></td>
                        <td><?php echo htmlspecialchars($course['semester']); ?></td>
                        <td><?php echo htmlspecialchars($course['lecture_hours']) . '/' . htmlspecialchars($course['lab_hours']); ?></td>
                        <td><?php echo htmlspecialchars($course['credit_units']); ?></td>
                        
                        
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script src="../scripts.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.querySelector('.search-box');
        const tableRows = document.querySelectorAll('tbody tr');
        const filterItems = document.querySelectorAll('.filter-item');
        let activeFilters = {
            semester: null,
            year: null
        };

        searchInput.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            filterTable(searchTerm, activeFilters);
        });

        filterItems.forEach(item => {
            item.addEventListener('click', function() {
                const filterType = this.getAttribute('data-filter');
                const filterValue = this.getAttribute('data-value');

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

                filterTable(searchInput.value.toLowerCase(), activeFilters);
            });
        });

        function filterTable(searchTerm, filters) {
            tableRows.forEach(row => {
                const courseCode = row.cells[0].textContent.toLowerCase();
                const courseTitle = row.cells[1].textContent.toLowerCase();
                const semester = row.cells[4].textContent;
                const yearLevel = row.cells[3].textContent;

                const matchesSearch = courseCode.includes(searchTerm) || courseTitle.includes(searchTerm);
                const matchesSemester = !filters.semester || semester === filters.semester;
                const matchesYear = !filters.year || yearLevel === filters.year.toString();

                if (matchesSearch && matchesSemester && matchesYear) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        window.openAddCourseModal = function() {
            document.getElementById('addCourseModal').style.display = 'block';
        }

        window.closeAddCourseModal = function() {
            document.getElementById('addCourseModal').style.display = 'none';
        }

        window.openEditCourseModal = function(courseJson) {
            const course = JSON.parse(courseJson);
            document.getElementById('edit_course_id').value = course.course_id || '';
            document.getElementById('edit_subject_code').value = course.subject_code;
            document.getElementById('edit_course_title').value = course.course_title;
            document.getElementById('edit_year_level').value = course.year_level;
            document.getElementById('edit_semester').value = course.semester;
            document.getElementById('edit_lecture_hours').value = course.lecture_hours;
            document.getElementById('edit_lab_hours').value = course.lab_hours;
            document.getElementById('edit_credit_units').value = course.credit_units;
            document.getElementById('edit_slots').value = course.slots;
            document.getElementById('editCourseModal').style.display = 'block';
        }

        window.closeEditCourseModal = function() {
            document.getElementById('editCourseModal').style.display = 'none';
        }
    });
    </script>
</body>
</html>