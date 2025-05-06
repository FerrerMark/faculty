<?php
include_once("../session/session.php");
include_once "../back/add_programs.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Programs</title>
    <link rel="stylesheet" href="../css/program.css">
</head>
<body>
    <div class="header">
        <h1>Programs</h1>
    </div>
    <div class="actions-bar ">
        <div>
            <h6>Lists of Programs</h6>
        </div>
        <div class="search-container">
            <input type="text" placeholder="Search programs..." class="search-box" id="searchBox" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Program Code</th>
                <th>Program</th>
                <th>College</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($programs)): ?>
                <?php foreach ($programs as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['program_code']); ?></td>
                        <td><?php echo htmlspecialchars($row['program_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['college']); ?></td>
                        <td>
                            <div class="action-buttons">
                                <a href="sections.php?department=<?php echo urlencode($row['program_code']); ?>&role=<?php echo urlencode($_GET['role']); ?>">
                                    <button class="programs-btn">Class</button>
                                </a>
                                <a href="courses.php?program_code=<?php echo urlencode($row['program_code']); ?>&role=<?php echo urlencode($_GET['role']); ?>&department=<?php echo urlencode($_GET['department']); ?>">
                                    <button class="programs-btn">Courses</button>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4" class="no-data">No programs found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchBox');
        const tableRows = document.querySelectorAll('tbody tr:not(.no-data)');

        searchInput.addEventListener('input', function() {
            const searchTerm = searchInput.value.toLowerCase();
            let hasVisibleRows = false;

            tableRows.forEach(row => {
                const programCode = row.cells[0].textContent.toLowerCase();
                const programName = row.cells[1].textContent.toLowerCase();
                const college = row.cells[2].textContent.toLowerCase();

                const matchesSearch = (
                    programCode.includes(searchTerm) ||
                    programName.includes(searchTerm) ||
                    college.includes(searchTerm)
                );

                row.style.display = matchesSearch ? '' : 'none';
                if (matchesSearch) hasVisibleRows = true;
            });

            // Toggle empty state
            const noDataRow = document.querySelector('tbody tr.no-data');
            if (noDataRow) {
                noDataRow.style.display = hasVisibleRows ? 'none' : '';
            }
        });
    });
    </script>
</body>
</html>