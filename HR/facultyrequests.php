<?php
include_once("../connections/connection.php");

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$sql = "SELECT * FROM faculty_requests 
        WHERE status IS NOT NULL 
        AND status != '' 
        ORDER BY submission_date DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === "add_faculty") {
    try {
        $first_name = isset($_POST['firstname']) ? trim($_POST['firstname']) : '';
        $middle_name = isset($_POST['middlename']) ? trim($_POST['middlename']) : null;
        $last_name = isset($_POST['lastname']) ? trim($_POST['lastname']) : '';
        $college = isset($_POST['college']) ? trim($_POST['college']) : null;
        $employment_status = isset($_POST['employment_status']) ? trim($_POST['employment_status']) : null;
        $address = isset($_POST['address']) ? trim($_POST['address']) : null;
        $phone_no = isset($_POST['phone_no']) ? trim($_POST['phone_no']) : null;
        $departmentID = isset($_POST['departmentID']) ? trim($_POST['departmentID']) : '';
        $role = isset($_POST['role']) ? trim($_POST['role']) : null;
        $master_specialization = isset($_POST['master_specialization']) ? trim($_POST['master_specialization']) : null;
        $max_weekly_hours = isset($_POST['max_weekly_hours']) ? (int)$_POST['max_weekly_hours'] : null;
        $availability = isset($_POST['availability']) ? trim($_POST['availability']) : '';
        $start_time = isset($_POST['start_time']) ? trim($_POST['start_time']) : '';
        $end_time = isset($_POST['end_time']) ? trim($_POST['end_time']) : '';
        $request_id = isset($_POST['request_id']) ? (int)$_POST['request_id'] : 0;

        if (!empty($start_time)) {
            $start_time = $start_time . ":00";
        }
        if (!empty($end_time)) {
            $end_time = $end_time . ":00";
        }

        $missing_fields = [];
        if (empty($first_name)) $missing_fields[] = 'First Name';
        if (empty($last_name)) $missing_fields[] = 'Last Name';
        if (empty($departmentID)) $missing_fields[] = 'Department';
        if (empty($availability)) $missing_fields[] = 'Availability';
        if (empty($start_time)) $missing_fields[] = 'Start Time';
        if (empty($end_time)) $missing_fields[] = 'End Time';
        if ($request_id <= 0) $missing_fields[] = 'Request ID';

        if (!empty($employment_status) && !in_array($employment_status, ['Full-Time', 'Part-Time'])) {
            $missing_fields[] = 'Employment Status (must be Full-Time or Part-Time)';
        }
        if (!empty($role) && !in_array($role, ['Dean', 'Department Head', 'Instructor'])) {
            $missing_fields[] = 'Role (must be Dean, Department Head, or Instructor)';
        }

        if (!empty($missing_fields)) {
            $error_message = "The following required fields are missing or invalid: " . implode(', ', $missing_fields);
            header("Location: facultyrequests.php?success=false&error=" . urlencode($error_message));
            exit();
        }

        $sql = "INSERT INTO faculty (
            firstname, middlename, lastname, college, employment_status, 
            address, phone_no, departmentID, role, master_specialization, 
            max_weekly_hours, availability, start_time, end_time
        ) VALUES (
            :firstname, :middlename, :lastname, :college, :employment_status, 
            :address, :phone_no, :departmentID, :role, :master_specialization, 
            :max_weekly_hours, :availability, :start_time, :end_time
        )";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':firstname', $first_name);
        $stmt->bindParam(':middlename', $middle_name);
        $stmt->bindParam(':lastname', $last_name);
        $stmt->bindParam(':college', $college);
        $stmt->bindParam(':employment_status', $employment_status);
        $stmt->bindParam(':address', $address);
        $stmt->bindParam(':phone_no', $phone_no);
        $stmt->bindParam(':departmentID', $departmentID);
        $stmt->bindParam(':role', $role);
        $stmt->bindParam(':master_specialization', $master_specialization);
        $stmt->bindParam(':max_weekly_hours', $max_weekly_hours, PDO::PARAM_INT);
        $stmt->bindParam(':availability', $availability);
        $stmt->bindParam(':start_time', $start_time);
        $stmt->bindParam(':end_time', $end_time);
        $stmt->execute();

        $faculty_id = $pdo->lastInsertId();

        $sql = "UPDATE faculty_requests SET stat = 'Granted' WHERE request_id = :request_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':request_id', $request_id, PDO::PARAM_INT);
        $stmt->execute();

        $sql = "INSERT INTO users (faculty_id ,username, password, role) VALUES (:faculty_id, :username, :password, :role)";
        $stmt = $pdo->prepare($sql);

        $username = $first_name . '.' . $last_name . '@faculty';
        $password_raw = $last_name . $faculty_id;
        $password_hashed = password_hash($password_raw, PASSWORD_DEFAULT);

        $stmt->bindParam(':faculty_id', $faculty_id, PDO::PARAM_INT);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':password', $password_hashed);
        $stmt->bindParam(':role', $role);
        $stmt->execute();


        header("Location: facultyrequests.php?success=true&message=Faculty added successfully");
    } catch (Exception $e) {
        $error_message = "Error adding faculty: " . $e->getMessage();
        header("Location: facultyrequests.php?success=false&error=" . urlencode($error_message));
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Request List</title>
    <link rel="stylesheet" href="../css/faculty_request.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f4f4f4;
        }
        tr:hover {
            background-color: #f9f9f9;
        }
        .status-pending { color: #FFC107; font-weight: bold; }
        .status-approved { color: #28A745; font-weight: bold; }
        .status-rejected { color: #DC3545; font-weight: bold; }
        .status-cancelled { color: #6c757d; font-weight: bold; }
        .status-granted { color: #17a2b8; font-weight: bold; }
        .status-pending-stat { color: #FFC107; font-weight: bold; }
        .action-btn {
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 5px;
        }
        .approve-btn {
            background-color: #28A745;
            color: white;
        }
        .reject-btn {
            background-color: #DC3545;
            color: white;
        }
        .add-btn {
            background-color: #007bff;
            color: white;
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 5px;
        }
        .no-requests {
            text-align: center;
            color: #555;
            padding: 20px;
        }
        .notification {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
            text-align: center;
        }
        .notification.success {
            background-color: #d4edda;
            color: #155724;
        }
        .notification.error {
            background-color: #f8d7da;
            color: #721c24;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            width: 100%;
            max-width: 500px;
            position: relative;
            max-height: 80vh;
            overflow-y: auto;
        }
        .close-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 24px;
            cursor: pointer;
            color: #333;
        }
        .modal-content h3 {
            margin-top: 0;
            color: #333;
        }
        .modal-content label {
            display: block;
            margin: 10px 0 5px;
            color: #555;
        }
        .modal-content input, .modal-content select, .modal-content textarea {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .modal-content button[type="submit"] {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Faculty Request List (HR Demo)</h2>

        <?php
        if (isset($_GET['success'])) {
            if ($_GET['success'] === "true" && isset($_GET['message'])) {
                echo '<div class="notification success">' . htmlspecialchars(urldecode($_GET['message'])) . '</div>';
            } elseif ($_GET['success'] === "false" && isset($_GET['error'])) {
                echo '<div class="notification error">' . htmlspecialchars(urldecode($_GET['error'])) . '</div>';
            }
        }
        ?>
        
        <?php if (count($requests) > 0): ?>
            <table>
                <tr>
                    <th>Request ID</th>
                    <th>Department</th>
                    <th>Role</th>
                    <th>Specialization</th>
                    <th>Employment Status</th>
                    <th>Status</th>
                    <th>Stat</th>
                    <th>Submitted</th>
                    <th>Actions</th>
                </tr>
                <?php foreach ($requests as $request): ?>
                    <tr>
                        <td>FRQ-<?php echo sprintf("%03d", $request['request_id']); ?></td>
                        <td><?php echo htmlspecialchars($request['department']); ?></td>
                        <td><?php echo htmlspecialchars($request['role']); ?></td>
                        <td><?php echo htmlspecialchars($request['specialization']); ?></td>
                        <td><?php echo htmlspecialchars($request['employment_status']); ?></td>
                        <td class="status-<?php echo strtolower($request['status']); ?>">
                            <?php echo htmlspecialchars($request['status']); ?>
                        </td>
                        <td class="status-<?php echo strtolower($request['stat'] ?? 'pending'); ?>-stat">
                            <?php echo htmlspecialchars($request['stat'] ?? 'Pending'); ?>
                        </td>
                        <td><?php echo date("Y-m-d h:i A", strtotime($request['submission_date'])); ?></td>
                        <td>
                            <?php if ($request['status'] === "Pending"): ?>
                                <form action="faculty_request_action.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="request_id" value="<?php echo $request['request_id']; ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <button type="submit" class="action-btn approve-btn">Approve</button>
                                </form>
                                <form action="faculty_request_action.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="request_id" value="<?php echo $request['request_id']; ?>">
                                    <input type="hidden" name="action" value="reject">
                                    <button type="submit" class="action-btn reject-btn">Reject</button>
                                </form>
                            <?php elseif ($request['status'] === "Approved" && ($request['stat'] ?? 'Pending') !== "Granted"): ?>
                                <button class="action-btn add-btn" onclick="openModal(<?php echo $request['request_id']; ?>, '<?php echo htmlspecialchars($request['department']); ?>', '<?php echo htmlspecialchars($request['role']); ?>')">+</button>
                            <?php else: ?>
                                <span>No actions available</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php else: ?>
            <p class="no-requests">No faculty requests found in the database.</p>
        <?php endif; ?>
    </div>

    <!-- Modal for adding faculty -->
    <div id="addFacultyModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal()">×</span>
            <h3>Add New Faculty</h3>
            <form action="facultyrequests.php" method="POST">
                <input type="hidden" name="action" value="add_faculty">
                <input type="hidden" name="request_id" id="hidden_request_id" value="">
                <input type="hidden" name="departmentID" id="hidden_departmentID" value="">
                <input type="hidden" name="role" id="hidden_role" value="">
                
                <label for="request_id">Request to Fulfill *</label>
                <select name="request_id_display" id="request_id" disabled>
                    <option value="">Select a request</option>
                    <?php foreach ($requests as $request): ?>
                        <?php if ($request['status'] === "Approved" && ($request['stat'] ?? 'Pending') !== "Granted"): ?>
                            <option value="<?php echo $request['request_id']; ?>">
                                FRQ-<?php echo sprintf("%03d", $request['request_id']); ?> (<?php echo htmlspecialchars($request['department']); ?> - <?php echo htmlspecialchars($request['role']); ?>)
                            </option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>

                <label for="firstname">First Name *</label>
                <input type="text" name="firstname" id="firstname" required>

                <label for="middlename">Middle Name</label>
                <input type="text" name="middlename" id="middlename">

                <label for="lastname">Last Name *</label>
                <input type="text" name="lastname" id="lastname" required>

                <label for="college">College</label>
                <input type="text" name="college" id="college" value="College of Computer Science">

                <label for="employment_status">Employment Status</label>
                <select name="employment_status" id="employment_status">
                    <option value="">Select Employment Status</option>
                    <option value="Full-Time">Full-Time</option>
                    <option value="Part-Time">Part-Time</option>
                </select>

                <label for="address">Address</label>
                <input type="text" name="address" id="address">

                <label for="phone_no">Phone Number</label>
                <input type="text" name="phone_no" id="phone_no" placeholder="e.g., 09172394578" pattern="[0-9]{10,11}">

                <label for="departmentID">Department *</label>
                <select name="departmentID_display" id="departmentID" disabled>
                    <option value="">Select Department</option>
                    <option value="BSIT">BSIT</option>
                    <option value="BSBA">BSBA</option>
                </select>

                <label for="role">Role *</label>
                <select name="role_display" id="role" disabled>
                    <option value="">Select Role</option>
                    <option value="Instructor">Instructor</option>
                    <option value="Department Head">Department Head</option>
                    <option value="Dean">Dean</option>
                </select>

                <label for="master_specialization">Specialization</label>
                <input type="text" name="master_specialization" id="master_specialization">

                <label for="max_weekly_hours">Max Weekly Hours</label>
                <input type="number" name="max_weekly_hours" id="max_weekly_hours" min="1" max="40">

                <label for="availability">Availability *</label>
                <textarea name="availability" id="availability" required placeholder="e.g., Monday,Tuesday,Wednesday"></textarea>

                <label for="start_time">Start Time *</label>
                <input type="time" name="start_time" id="start_time" required>

                <label for="end_time">End Time *</label>
                <input type="time" name="end_time" id="end_time" required>

                <button type="submit">Submit</button>
            </form>
        </div>
    </div>

    <script>
        function openModal(requestId, department, role) {
            const modal = document.getElementById('addFacultyModal');
            const requestSelect = document.getElementById('request_id');
            const hiddenRequestId = document.getElementById('hidden_request_id');
            const departmentSelect = document.getElementById('departmentID');
            const hiddenDepartmentId = document.getElementById('hidden_departmentID');
            const roleSelect = document.getElementById('role');
            const hiddenRole = document.getElementById('hidden_role');
            
            requestSelect.value = requestId;
            hiddenRequestId.value = requestId;

            departmentSelect.value = department;
            hiddenDepartmentId.value = department;
            roleSelect.value = role;
            hiddenRole.value = role;
            
            modal.style.display = 'flex';
        }

        function closeModal() {
            const modal = document.getElementById('addFacultyModal');
            const requestSelect = document.getElementById('request_id');
            const hiddenRequestId = document.getElementById('hidden_request_id');
            const departmentSelect = document.getElementById('departmentID');
            const hiddenDepartmentId = document.getElementById('hidden_departmentID');
            const roleSelect = document.getElementById('role');
            const hiddenRole = document.getElementById('hidden_role');
            
            requestSelect.value = '';
            hiddenRequestId.value = '';
            departmentSelect.value = '';
            hiddenDepartmentId.value = '';
            roleSelect.value = '';
            hiddenRole.value = '';
            
            modal.style.display = 'none';
        }

        window.onclick = function(event) {
            const modal = document.getElementById('addFacultyModal');
            if (event.target === modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>