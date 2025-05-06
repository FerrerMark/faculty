<?php
session_start();

if (!isset($_SESSION['id'])) {
    header("Location: /login.php");
    exit();
}

include_once("../connections/connection.php");

$faculty_id = $_SESSION['id'];
$sql = "SELECT f.role, f.departmentID, p.program_code, p.program_name 
        FROM faculty f 
        JOIN programs p ON f.departmentID = p.program_code 
        WHERE f.faculty_id = :faculty_id";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':faculty_id', $faculty_id, PDO::PARAM_INT);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user['role'] !== "Department Head") {
    die("Access denied: Only Department Heads can request faculty.");
}

$user_department_code = $user['program_code'];
$user_department_name = $user['program_name'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Request Management</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../css/faculty_request_form.css">
</head>
<body class="bg-gray-100 font-sans">
    <div class="container max-w-7xl mx-auto p-6">
        <!-- Form Section -->
        <div class="form-container bg-white rounded-lg shadow-lg p-6 mb-6">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">New Faculty Request (Due to Shortage)</h2>
            <form action="../back/faculty_request_form.php" method="POST" class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Requesting Department *</label>
                    <div class="mt-1 p-2 bg-gray-100 border border-gray-300 rounded-md text-gray-800"><?php echo htmlspecialchars($user_department_name); ?></div>
                    <input type="hidden" name="department" value="<?php echo htmlspecialchars($user_department_code); ?>">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Role Needed *</label>
                    <select name="role" required class="mt-1 block w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Select Role</option>
                        <option value="Dean">Dean</option>
                        <option value="Department Head">Department Head</option>
                        <option value="Instructor">Instructor</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Employment Status *</label>
                    <div class="mt-2 flex gap-4">
                        <label class="flex items-center">
                            <input type="radio" name="employment_status" value="Full-Time" required class="h-4 w-4 text-blue-600 focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-700">Full-Time</span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="employment_status" value="Part-Time" class="h-4 w-4 text-blue-600 focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-700">Part-Time</span>
                        </label>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Specialization Needed</label>
                    <input type="text" name="specialization" placeholder="e.g., General Education" class="mt-1 block w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Reason for Shortage *</label>
                    <textarea name="reason" required class="mt-1 block w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" rows="4">Due to lack of faculty</textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Number of Faculty Needed *</label>
                    <input type="number" name="quantity" min="1" value="1" required class="mt-1 block w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Urgent *</label>
                    <div class="mt-2 flex gap-4">
                        <label class="flex items-center">
                            <input type="radio" name="urgency" value="Low" required class="h-4 w-4 text-blue-600 focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-700">Low</span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="urgency" value="Medium" class="h-4 w-4 text-blue-600 focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-700">Medium</span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="urgency" value="High" class="h-4 w-4 text-blue-600 focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-700">High</span>
                        </label>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Requested Start Date *</label>
                    <input type="date" name="start_date" required class="mt-1 block w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="flex justify-end gap-4">
                    <button type="button" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400" onclick="window.location.href='dashboard.php'">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Submit Request</button>
                </div>
            </form>
        </div>

        <!-- Request List Section -->
        <div class="list-container bg-white rounded-lg shadow-lg p-6">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">My Submitted Requests</h2>
            <?php
            $sql = "SELECT request_id, department, role, status, stat, employment_status, submission_date, specialization
                    FROM faculty_requests
                    WHERE submitted_by = :faculty_id
                    AND department = :department
                    AND status IS NOT NULL
                    AND status != ''
                    ORDER BY submission_date DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':faculty_id', $faculty_id, PDO::PARAM_INT);
            $stmt->bindParam(':department', $user_department_code, PDO::PARAM_STR);
            $stmt->execute();
            $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (count($requests) > 0): ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-500">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 font-medium text-gray-700">Request ID</th>
                                <th class="px-4 py-3 font-medium text-gray-700">Department</th>
                                <th class="px-4 py-3 font-medium text-gray-700">Role</th>
                                <th class="px-4 py-3 font-medium text-gray-700">Employment Status</th>
                                <th class="px-4 py-3 font-medium text-gray-700">Status</th>
                                <th class="px-4 py-3 font-medium text-gray-700">Stat</th>
                                <th class="px-4 py-3 font-medium text-gray-700">Submitted</th>
                                <th class="px-4 py-3 font-medium text-gray-700">Specialization</th>
                                <th class="px-4 py-3 font-medium text-gray-700">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($requests as $row): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3">FRQ-<?php echo htmlspecialchars($row['request_id']); ?></td>
                                    <td class="px-4 py-3"><?php echo htmlspecialchars($row['department']); ?></td>
                                    <td class="px-4 py-3"><?php echo htmlspecialchars($row['role']); ?></td>
                                    <td class="px-4 py-3"><?php echo htmlspecialchars($row['employment_status']); ?></td>
                                    <td class="px-4 py-3">
                                        <span class="inline-block px-2 py-1 text-xs font-semibold rounded-full 
                                            <?php echo $row['status'] === 'Approved' ? 'bg-green-100 text-green-800' : 
                                                  ($row['status'] === 'Pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'); ?>">
                                            <?php echo htmlspecialchars($row['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-block px-2 py-1 text-xs font-semibold rounded-full 
                                            <?php echo $row['stat'] === 'Approved' ? 'bg-green-100 text-green-800' : 
                                                  ($row['stat'] === 'Pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'); ?>">
                                            <?php echo htmlspecialchars($row['stat']); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3"><?php echo date("Y-m-d H:i", strtotime($row['submission_date'])); ?></td>
                                    <td class="px-4 py-3"><?php echo htmlspecialchars($row['specialization'] ?: 'N/A'); ?></td>
                                    <td class="px-4 py-3">
                                        <?php if ($row['status'] === 'Pending'): ?>
                                            <form action="../back/faculty_request_form.php" method="POST" class="inline">
                                                <input type="hidden" name="request_id" value="<?php echo htmlspecialchars($row['request_id']); ?>">
                                                <button type="submit" class="px-3 py-1 bg-red-600 text-white text-xs font-semibold rounded-md hover:bg-red-700">Cancel</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-gray-400 text-xs">No action</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-gray-500 text-center">No requests submitted yet for your department.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Notifications -->
    <?php
    if (isset($_GET['success']) && $_GET['success'] === "true"): ?>
        <div id="notification" class="fixed top-4 right-4 bg-green-500 text-white px-4 py-2 rounded-md shadow-lg flex items-center gap-2">
            <span>Request submitted successfully</span>
            <button onclick="this.parentElement.remove()" class="text-white hover:text-gray-200">×</button>
        </div>
    <?php elseif (isset($_GET['cancelled']) && $_GET['cancelled'] === "true"): ?>
        <div id="notification" class="fixed top-4 right-4 bg-green-500 text-white px-4 py-2 rounded-md shadow-lg flex items-center gap-2">
            <span>Request cancelled successfully</span>
            <button onclick="this.parentElement.remove()" class="text-white hover:text-gray-200">×</button>
        </div>
    <?php endif; ?>
    <script>
        // Auto-dismiss notifications after 5 seconds
        setTimeout(() => {
            const notification = document.getElementById('notification');
            if (notification) notification.remove();
        }, 5000);
    </script>
</body>
</html>