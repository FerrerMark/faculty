<?php
include_once("../back/faculty.php");

// Extract unique subjects for filter
$subjects = [];
foreach ($facultyList as $faculty) {
    if (!empty($faculty['subjects']) && $faculty['subjects'] !== 'None') {
        $subjectList = array_map('trim', explode(',', $faculty['subjects']));
        $subjects = array_merge($subjects, $subjectList);
    }
}
$subjects = array_unique(array_filter($subjects));
sort($subjects); // Sort for consistent display
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Management</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../css/faculty.css">
</head>
<body class="bg-gray-100 font-sans">
    <!-- Edit Faculty Modal -->
    <div id="editFacultyModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50 <?php echo isset($selectedFaculty) ? '' : 'hidden'; ?>">
        <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold">Edit Faculty Attachment</h2>
                <button onclick="window.location.href='faculty.php?department=<?php echo htmlspecialchars($_GET['department'] ?? ''); ?>'" class="text-gray-500 hover:text-gray-700">×</button>
            </div>
            <div>
                <h4 class="text-lg font-medium"><?php echo isset($selectedFaculty) ? htmlspecialchars($selectedFaculty['firstname'] . ' ' . $selectedFaculty['lastname']) : ''; ?></h4>
                <form id="newFacultyForm" action="../back/faculty.php?action=edit&id=<?php echo isset($selectedFaculty) ? htmlspecialchars($selectedFaculty['faculty_id']) : ''; ?>" method="post" enctype="multipart/form-data" class="space-y-4 mt-4">
                    <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-md hover:bg-blue-700">Submit</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-full mx-auto p-4">
        <div class="mb-4">
            <h1 class="text-2xl font-bold text-gray-800"><?php echo htmlspecialchars($department); ?> Faculty</h1>
        </div>

        <!-- Toolbar -->
        <div class="flex flex-col sm:flex-row justify-between items-start mb-4 gap-3 bg-[#34495e] p-4 rounded-lg">
            <div class="text-white"><?php echo count($facultyList); ?> Faculty</div>
            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4 w-full sm:w-auto">
                <!-- Search Input -->
                <input type="text" id="searchInput" placeholder="Search by Name  or Subject" class="w-full sm:w-48 p-1.5 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                <!-- Subjects Filter -->
                <?php if (!empty($subjects)): ?>
                    <div class="relative">
                        <button id="subjectsFilterBtn" class="bg-blue-600 text-white px-3 py-1.5 rounded-md hover:bg-blue-700"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="currentColor" d="M10 14L4 5V3h16v2l-6 9v6l-4 2z"/></svg></button>
                        <div id="subjectsDropdown" class="hidden absolute z-10 mt-2 w-48 bg-white rounded-md shadow-lg p-4 max-h-60 overflow-y-auto">
                            <?php foreach ($subjects as $subject): ?>
                                <label class="flex items-center gap-2 text-sm">
                                    <input type="checkbox" class="subject-checkbox" value="<?php echo htmlspecialchars($subject); ?>">
                                    <?php echo htmlspecialchars($subject); ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Table Container -->
        <div class="table-container bg-white rounded-lg shadow">
            <table class="faculty-table divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                        <th class="text-left text-xs font-medium text-gray-500 uppercase">College</th>
                        <th class="text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="text-left text-xs font-medium text-gray-500 uppercase hide-on-small">Address</th>
                        <th class="text-left text-xs font-medium text-gray-500 uppercase hide-on-small">Phone</th>
                        <th class="text-left text-xs font-medium text-gray-500 uppercase hide-on-small">Dept ID</th>
                        <th class="text-left text-xs font-medium text-gray-500 uppercase">Subject</th>
                        <th class="text-left text-xs font-medium text-gray-500 uppercase hide-on-small">Role</th>
                        <th class="text-left text-xs font-medium text-gray-500 uppercase hide-on-small">Specialization</th>
                        <th class="text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php foreach ($facultyList as $faculty): ?>
                        <tr>
                            <td class="whitespace-nowrap" title="<?php echo htmlspecialchars($faculty['lastname'] . ', ' . $faculty['firstname'] . ' ' . $faculty['middlename']); ?>">
                                <?php echo htmlspecialchars(substr($faculty['lastname'] . ', ' . $faculty['firstname'], 0, 20)); ?>
                            </td>
                            <td class="whitespace-nowrap"><?php echo htmlspecialchars(substr($faculty['college'], 0, 15)); ?></td>
                            <td class="whitespace-nowrap">
                                <span class="status-badge <?php echo strtolower($faculty['employment_status']); ?>-time"><?php echo htmlspecialchars($faculty['employment_status']); ?></span>
                            </td>
                            <td class="hide-on-small"><?php echo htmlspecialchars(substr($faculty['address'], 0, 20)); ?></td>
                            <td class="whitespace-nowrap hide-on-small"><?php echo htmlspecialchars($faculty['phone_no']); ?></td>
                            <td class="whitespace-nowrap hide-on-small"><?php echo htmlspecialchars($faculty['departmentID']); ?></td>
                            <td class=""><?php echo htmlspecialchars(substr($faculty['subjects'] ?: 'None', 0, 20)); ?></td>
                            <td class="whitespace-nowrap hide-on-small">
                                <span class="position-badge"><?php echo htmlspecialchars($faculty['role']); ?></span>
                            </td>
                            <td class="hide-on-small"><?php echo htmlspecialchars(substr($faculty['master_specialization'], 0, 20)); ?></td>
                            <td class="whitespace-nowrap">
                                <div class="flex gap-1">
                                    <button onclick="window.location.href='faculty.php?department=<?php echo htmlspecialchars($department); ?>&id=<?php echo $faculty['faculty_id']; ?>'" class="bg-blue-600 text-white px-2 py-1 rounded-md hover:bg-blue-700 text-xs">Edit</button>
                                    <button onclick="viewSchedule(<?php echo $faculty['faculty_id']; ?>)" class="bg-green-600 text-white px-2 py-1 rounded-md hover:bg-green-700 text-xs">View</button>
                                    <button onclick="assignSchedule(<?php echo $faculty['faculty_id']; ?>)" class="bg-blue-600 text-white px-2 py-1 rounded-md hover:bg-blue-700 text-xs">Assign</button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($facultyList)): ?>
                        <tr>
                            <td colspan="10" class="px-4 py-3 text-center text-gray-500 text-sm">No faculty found for this department.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Faculty Cards (Mobile, <600px) -->
        <div class="faculty-cards space-y-3">
            <?php foreach ($facultyList as $faculty): ?>
                <div class="bg-white rounded-lg shadow p-3">
                    <h3 class="text-base font-semibold"><?php echo htmlspecialchars($faculty['lastname'] . ', ' . $faculty['firstname'] . ' ' . $faculty['middlename']); ?></h3>
                    <p class="text-sm"><strong>College:</strong> <?php echo htmlspecialchars($faculty['college']); ?></p>
                    <p class="text-sm"><strong>Status:</strong> <span class="status-badge <?php echo strtolower($faculty['employment_status']); ?>-time"><?php echo htmlspecialchars($faculty['employment_status']); ?></span></p>
                    <p class="text-sm"><strong>Address:</strong> <?php echo htmlspecialchars($faculty['address']); ?></p>
                    <p class="text-sm"><strong>Phone:</strong> <?php echo htmlspecialchars($faculty['phone_no']); ?></p>
                    <p class="text-sm"><strong>Department ID:</strong> <?php echo htmlspecialchars($faculty['departmentID']); ?></p>
                    <p class="text-sm"><strong>Subjects:</strong> <?php echo htmlspecialchars($faculty['subjects'] ?: 'None'); ?></p>
                    <p class="text-sm"><strong>Role:</strong> <span class="position-badge"><?php echo htmlspecialchars($faculty['role']); ?></span></p>
                    <p class="text-sm"><strong>Specialization:</strong> <?php echo htmlspecialchars($faculty['master_specialization']); ?></p>
                    <div class="flex gap-1 mt-3">
                        <button onclick="window.location.href='faculty.php?department=<?php echo htmlspecialchars($department); ?>&id=<?php echo $faculty['faculty_id']; ?>'" class="bg-blue-600 text-white px-2 py-1 rounded-md hover:bg-blue-700 text-xs">Edit</button>
                        <button onclick="viewSchedule(<?php echo $faculty['faculty_id']; ?>)" class="bg-green-600 text-white px-2 py-1 rounded-md hover:bg-green-700 text-xs">View</button>
                        <button onclick="assignSchedule(<?php echo $faculty['faculty_id']; ?>)" class="bg-purple-600 text-white px-2 py-1 rounded-md hover:bg-purple-700 text-xs">Assign</button>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (empty($facultyList)): ?>
                <p class="text-center text-gray-500 text-sm">No faculty found for this department.</p>
            <?php endif; ?>
        </div>

        <!-- Pagination Placeholder -->
        <div id="pagination" class="text-center mt-4"></div>
    </div>

    <script src="../scripts.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            const subjectsFilterBtn = document.getElementById('subjectsFilterBtn');
            const subjectsDropdown = document.getElementById('subjectsDropdown');
            const subjectCheckboxes = document.querySelectorAll('.subject-checkbox');

            // Toggle subjects dropdown
            if (subjectsFilterBtn) {
                subjectsFilterBtn.addEventListener('click', function() {
                    subjectsDropdown.classList.toggle('hidden');
                });
            }

            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (!subjectsFilterBtn.contains(e.target) && !subjectsDropdown.contains(e.target)) {
                    subjectsDropdown.classList.add('hidden');
                }
            });

            function searchAndFilterFaculty() {
                const searchValue = searchInput.value.toLowerCase();
                const selectedSubjects = Array.from(subjectCheckboxes)
                    .filter(cb => cb.checked)
                    .map(cb => cb.value.toLowerCase());

                const rows = document.querySelectorAll('tbody tr');
                const cards = document.querySelectorAll('.faculty-cards > div');

                // Filter table rows
                rows.forEach(row => {
                    const lastName = row.cells[0].textContent.toLowerCase();
                    const subjects = row.cells[6].textContent.toLowerCase();

                    const matchesSearch = !searchValue || lastName.includes(searchValue) || subjects.includes(searchValue);
                    const matchesSubjects = selectedSubjects.length === 0 || selectedSubjects.some(subject => subjects.includes(subject));

                    row.style.display = matchesSearch && matchesSubjects ? '' : 'none';
                });

                // Filter mobile cards
                cards.forEach(card => {
                    const lastName = card.querySelector('h3').textContent.toLowerCase();
                    const subjects = card.querySelector('p:nth-child(7)').textContent.toLowerCase().replace('subjects: ', '');

                    const matchesSearch = !searchValue || lastName.includes(searchValue) || subjects.includes(searchValue);
                    const matchesSubjects = selectedSubjects.length === 0 || selectedSubjects.some(subject => subjects.includes(subject));

                    card.style.display = matchesSearch && matchesSubjects ? '' : 'none';
                });

                // Toggle empty state
                const visibleRows = Array.from(rows).filter(row => row.style.display !== 'none');
                const emptyRow = document.querySelector('tbody tr td[colspan="10"]');
                if (emptyRow) {
                    emptyRow.parentElement.style.display = visibleRows.length === 0 && !searchValue && selectedSubjects.length === 0 ? '' : 'none';
                }
            }

            // Real-time search on input
            searchInput.addEventListener('input', searchAndFilterFaculty);

            // Real-time filter on checkbox change
            subjectCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', searchAndFilterFaculty);
            });

            function viewSchedule(facultyId) {
                window.location.href = "../frame/info.php?id=" + facultyId;
            }

            async function assignSchedule(facultyId) {
                try {
                    let response = await fetch("../back/assign_schedule.php", {
                        method: "POST",
                        headers: { "Content-Type": "application/json" },
                        body: JSON.stringify({ faculty_id: facultyId })
                    });
                    if (!response.ok) throw new Error("Network response was not ok");
                    let result = await response.json();
                    if (result.success) {
                        alert("Schedule assigned successfully!");
                        location.reload();
                    } else {
                        alert("Failed to assign schedule: " + (result.message || "Unknown error"));
                    }
                } catch (error) {
                    console.error("Error assigning schedule:", error);
                    alert("An error occurred while assigning the schedule.");
                }
            }
        });
    </script>
</body>
</html>