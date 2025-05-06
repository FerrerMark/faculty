<?php

    include_once "../back/class.php";

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class Page</title>
    <link rel="stylesheet" href="../css/class.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="program-title">BSCPE</div>
            <div class="college-info">Bachelor Of Science in Computer Engineering</div>
            <div class="college-info">College: College of Computer Studies</div>
        </div>

        <div id="editClassModal" class="modal" style="display: none;">
            <div class="modal-content">
                <span class="close-btn" onclick="closeEditClassModal()">&times;</span>
                <form id="editClassForm" action="../back/class.php?program_code=<?php echo urlencode($program_code); ?>" method="POST">
                    <input type="hidden" name="action" value="edit_class">
                    <input type="hidden" id="edit_section_id" name="section_id">
                    <label for="edit_year_section">Year/Section:</label>
                    <input type="text" id="edit_year_section" name="year_section" placeholder="e.g., 3-1" required>
                    <button type="submit">Save Changes</button>
                </form>
            </div>
        </div>

        <div class="action-buttons">
            <button id="openModalBtn" onclick="openAddClassModal()">Add New Section</button>
        </div>

        <div id="addClassModal" class="modal" style="
            display: none;
            position: absolute; 
            z-index: 1;
            left: 50%; 
            top: 50%; 
            transform: translate(-50%, -50%); 
            width: 100%;
            max-width: 500px;
            height: auto;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.4);">
            <div class="modal-content" style="background-color: white; padding: 20px; border-radius: 8px;">
                <span class="close-btn" onclick="closeAddClassModal()" style="cursor: pointer; font-size: 24px;">&times;</span>
                <form id="addClassForm" action="../back/class.php?program_code=<?php echo urlencode($program_code); ?>" method="POST">
                    <input type="hidden" name="action" value="add_section">
                    <input type="hidden" name="program_code" value="<?php echo htmlspecialchars($program_code); ?>">
                    <label for="class_year_section">Year/Section:</label>
                    <input type="text" id="class_year_section" name="year_section" placeholder="e.g., 3-1" required>
                    <button type="submit" style="margin-top: 10px; padding: 8px 12px;">Add Section</button>
                </form>
            </div>
        </div>



        <div class="toolbar">
            <button>Copy</button>
            <button>CSV</button>
            <button>Excel</button>
            <button>PDF</button>
            <button>Print</button>
            <button>Column visibility</button>
            <input type="text" placeholder="Search:" class="search-box">
        </div>

        <table>
            <thead>
                <tr>
                    <th>Year/Section</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sections as $section): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($section['year_section']); ?></td>
                        <td>
                            <button class="edit-btn" onclick="openEditClassModal('<?php echo $section['id']; ?>', '<?php echo htmlspecialchars($section['year_section']); ?>')">✎</button>
                            
                            <form action="../back/class.php?program_code=<?php echo urlencode($program_code); ?>" method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="delete_section">
                                <input type="hidden" name="section_id" value="<?php echo htmlspecialchars($section['id']); ?>">
                                <button type="submit" class="delete-btn" style="background-color: red; color: white; border: none; cursor: pointer;">🗑</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

        <div class="pagination">
            <button>Previous</button>
            <button class="active">1</button>
            <button>Next</button>
        </div>
    </div>
    <script src="../scripts.js"></script>
</body>
</html>