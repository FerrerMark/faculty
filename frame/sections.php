<?php
include_once "../back/sections.php";

error_log(print_r($sections, true));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Section Management</title>
    <link rel="stylesheet" href="../css/sections.css">
</head>
<body>
        <h2><?php echo htmlspecialchars($dep); ?> Sections List</h2>

        <div class="toolbar">
            <h3><?php echo count($sections)?>&nbsp;Sections</h3>
            <div class="toolbar-controls">
                <div class="filter-section">
                    <span class="filter-title">Year Level:</span>
                    <div class="filter-items" id="yearFilter">
                        <?php
                        $years = [1, 2, 3, 4];
                        foreach ($years as $year) {
                            $count = array_reduce($sections, function($carry, $section) use ($year) {
                                return $carry + ($section['year_level'] == $year ? 1 : 0);
                            }, 0);
                            echo "<span class='filter-item' data-filter='year' data-value='$year'>$year</span>";
                        }
                        ?>
                    </div>
                </div>
                <div class="search-container">
                    <input type="text" placeholder="Search sections..." class="search-box">
                </div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Department</th>
                    <th>Year/Section</th>
                    <th>Year Level</th>
                    <th>Semestrial</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($sections)) : ?>
                    <?php foreach ($sections as $section) : ?>
                        <tr>
                            <td><?php echo htmlspecialchars($section['program_code']); ?></td>
                            <td><?php echo htmlspecialchars($section['section_name']); ?></td>
                            <td><?php echo htmlspecialchars($section['year_level']); ?></td>
                            <td><?php echo htmlspecialchars($section['semester']); ?></td>
                            <td>
                                <button onclick="window.location.href='manual_scheduling.php?section_id=<?php echo urlencode($section['section_id']); ?>&department=<?php echo urlencode($section['program_code']); ?>'">View Schedule</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="5" style="text-align:center;">No sections added yet.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.querySelector('.search-box');
        const tableRows = document.querySelectorAll('tbody tr');
        const filterItems = document.querySelectorAll('.filter-item');
        let activeFilters = { year: null };

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
                const programCode = row.cells[0].textContent.toLowerCase();
                const sectionName = row.cells[1].textContent.toLowerCase();
                const yearLevel = row.cells[2].textContent;
                const semester = row.cells[3].textContent.toLowerCase();

                let matchesYear = true;
                if (filters.year) {
                    matchesYear = yearLevel === filters.year;
                }

                let matchesSearch = true;
                if (searchTerm) {
                    matchesSearch = (
                        programCode.includes(searchTerm) ||
                        sectionName.includes(searchTerm) ||
                        semester.includes(searchTerm)
                    );
                }

                row.style.display = (matchesYear && matchesSearch) ? '' : 'none';
            });

            const visibleRows = Array.from(tableRows).filter(row => row.style.display !== 'none');
            const noResultsRow = document.querySelector('tbody tr td[colspan="5"]');
            if (noResultsRow) {
                noResultsRow.parentElement.style.display = visibleRows.length === 0 ? '' : 'none';
            }
        }
    });
    </script>
</body>
</html>