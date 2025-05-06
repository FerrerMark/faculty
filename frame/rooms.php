<?php
include_once "../back/rooms.php";
include_once "../comps/notif.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Room Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .max-w-full.mx-auto.p-4 {
            margin: unset;
            padding: unset;
        }
        body.bg-gray-100.font-sans {
            padding: 30px;
        }
        .rooms-table th, .rooms-table td {
            font-size: 14px;
            color: #555;
            padding: 0.5rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .flex.flex-col.sm\:flex-row.justify-between.items-center.mb-4.gap-3.bg-\[\#34495e\].p-4.rounded-lg {
            padding: 12px;
            display: flex;
            align-items: center;
            padding: 15px;
            align-content: center;
        }
        form.flex.items-center.gap-2.w-full.sm\:w-auto {
            margin: unset;
        }
        /* Table and cards */
        .table-container {
            max-width: 100%;
            overflow-x: hidden;
        }
        .rooms-table {
            width: 100%;
            table-layout: auto;
        }
        .rooms-table th, .rooms-table td {
            font-size: 0.75rem;
            padding: 0.5rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        @media (max-width: 800px) {
            .rooms-table .hide-on-small {
                display: none;
            }
            .rooms-table th, .rooms-table td {
                font-size: 0.65rem;
                padding: 0.3rem;
            }
        }
        @media (max-width: 600px) {
            .rooms-table {
                display: none;
            }
            .rooms-cards {
                display: block;
            }
        }
        @media (min-width: 601px) {
            .rooms-cards {
                display: none;
            }
        }
        body {
            max-height: 600px;
            overflow-y: auto;
        }
        .notification {
            position: fixed;
            top: 1rem;
            right: 1rem;
            padding: 0.75rem 1.5rem;
            border-radius: 0.375rem;
            color: white;
            z-index: 1000;
        }
        .notification.Green { background-color: #10b981; }
        .notification.Red { background-color: #ef4444; }
        .notification.Yellow { background-color: #f59e0b; }
    </style>
</head>
<body class="bg-gray-100 font-sans">
    <div id="openAddRoomModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold">Add Room</h2>
                <button onclick="closeAddRoomModal()" class="text-gray-500 hover:text-gray-700">×</button>
            </div>
            <form method="POST" action="../back/rooms.php?action=add&department=<?php echo urlencode($_GET['department']); ?>" class="space-y-4">
                <div>
                    <label for="building" class="block text-sm font-medium text-gray-700">Select Campus</label>
                    <select name="building" id="building" class="w-full p-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        <option value="" disabled selected>Select a campus</option>
                        <option value="MV Campus">MV Campus</option>
                        <option value="Bulacan Campus">Bulacan Campus</option>
                        <option value="San Agustin">San Agustin</option>
                        <option value="Main Campus">Main Campus</option>
                    </select>
                </div>
                <div>
                    <label for="room_no" class="block text-sm font-medium text-gray-700">Room No</label>
                    <input type="text" name="room_no" id="room_no" placeholder="Room No" class="w-full p-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                <div>
                    <label for="room_type" class="block text-sm font-medium text-gray-700">Room Type</label>
                    <input type="text" name="room_type" id="room_type" placeholder="Room Type" class="w-full p-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                <div>
                    <label for="capacity" class="block text-sm font-medium text-gray-700">Room Capacity</label>
                    <input type="number" name="capacity" id="capacity" placeholder="Room Capacity" min="1" class="w-full p-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                <button type="submittoxic-green-600 text-white py-2 rounded-md hover:bg-blue-700">Add Room</button>
            </form>
        </div>
    </div>

    <!-- Edit Room Modal -->
    <div id="openEditRoomModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50 <?php echo isset($selectedRoomAndBuilding) ? '' : 'hidden'; ?>">
        <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold">Edit Room</h2>
                <button onclick="closeEditRoomModal()" class="text-gray-500 hover:text-gray-700">×</button>
            </div>
            <form method="POST" action="../back/rooms.php?action=edit&room=<?php echo urlencode($selectedRoomAndBuilding['room_no'] ?? ''); ?>&building=<?php echo urlencode($selectedRoomAndBuilding['building'] ?? ''); ?>&department=<?php echo urlencode($_GET['department'] ?? ''); ?>" class="space-y-4">
                <div>
                    <label for="building" class="block text-sm font-medium text-gray-700">Select Campus</label>
                    <select name="building" id="building" class="w-full p-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        <option value="<?php echo htmlspecialchars($selectedRoomAndBuilding['building'] ?? ''); ?>" selected>
                            <?php echo htmlspecialchars($selectedRoomAndBuilding['building'] ?? ''); ?>
                        </option>
                        <option value="MV Campus">MV Campus</option>
                        <option value="Bulacan Campus">Bulacan Campus</option>
                        <option value="San Agustin">San Agustin</option>
                        <option value="Main Campus">Main Campus</option>
                    </select>
                </div>
                <div>
                    <label for="room_no" class="block text-sm font-medium text-gray-700">Room No</label>
                    <input type="text" id="room_no" name="room_no" placeholder="Room No" value="<?php echo htmlspecialchars($selectedRoomAndBuilding['room_no'] ?? ''); ?>" class="w-full p-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                <div>
                    <label for="room_type" class="block text-sm font-medium text-gray-700">Room Type</label>
                    <input type="text" id="room_type" name="room_type" placeholder="Room Type" value="<?php echo htmlspecialchars($selectedRoomAndBuilding['room_type'] ?? ''); ?>" class="w-full p-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                <div>
                    <label for="capacity" class="block text-sm font-medium text-gray-700">Room Capacity</label>
                    <input type="number" id="capacity" name="capacity" placeholder="Capacity" value="<?php echo htmlspecialchars($selectedRoomAndBuilding['capacity'] ?? ''); ?>" class="w-full p-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                <button type="submit" name="edit_room" class="w-full bg-blue-600 text-white py-2 rounded-md hover:bg-blue-700">Save Changes</button>
            </form>
        </div>
    </div>

    <div class="max-w-full mx-auto p-4">
        <div class="mb-4">
            <h1 class="text-[28px] font-bold text-[#2c3e50]">Rooms</h1>
        </div>

        <div class="flex flex-col sm:flex-row justify-between items-center mb-4 gap-3 bg-[#34495e] p-4 rounded-lg">
            <div class="text-white"><?php echo htmlspecialchars($count ?? 0); ?> Total Rooms</div>
            <form method="POST" action="../back/rooms.php?department=<?php echo urlencode($_GET['department'] ?? ''); ?>&role=<?php echo urlencode($role ?? ''); ?>&action=search" class="flex items-center gap-2 w-full sm:w-auto">
                <input type="text" name="search" placeholder="Search by Room No" class="w-full sm:w-48 p-1.5 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                <button type="submit" class="bg-blue-600 text-white px-3 py-1.5 rounded-md hover:bg-blue-700">Search</button>
            </form>
        </div>

        <div class="mb-4">
            <button onclick="openAddRoomModal()" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">Add New</button>
        </div>

        <div class="table-container bg-white rounded-lg shadow">
            <table class="rooms-table divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="text-left text-xs font-medium text-gray-500 uppercase">Building</th>
                        <th class="text-left text-xs font-medium text-gray-500 uppercase">Room No</th>
                        <th class="text-left text-xs font-medium text-gray-500 uppercase">Room Type</th>
                        <th class="text-left text-xs font-medium text-gray-500 uppercase">Capacity</th>
                        <th class="text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php if (count($rooms ?? []) > 0): ?>
                        <?php foreach ($rooms as $room): ?>
                            <tr>
                                <td class="whitespace-nowrap" title="<?php echo htmlspecialchars($room['building']); ?>">
                                    <?php echo htmlspecialchars(substr($room['building'], 0, 15)); ?>
                                </td>
                                <td class="whitespace-nowrap"><?php echo htmlspecialchars($room['room_no']); ?></td>
                                <td class="whitespace-nowrap"><?php echo htmlspecialchars(substr($room['room_type'], 0, 15)); ?></td>
                                <td class="whitespace-nowrap"><?php echo htmlspecialchars($room['capacity']); ?></td>
                                <td class="whitespace-nowrap">
                                    <div class="flex gap-1">
                                        <a href="rooms.php?building=<?php echo urlencode($room['building']); ?>&room_no=<?php echo urlencode($room['room_no']); ?>&action=select&department=<?php echo urlencode($_GET['department'] ?? ''); ?>">
                                            <button class="bg-blue-500 text-white px-2 py-1 rounded-md hover:bg-blue-700 text-xs">Edit</button>
                                        </a>
                                        <button onclick="deleteRoomConfirm('<?php echo htmlspecialchars($room['building']); ?>', '<?php echo htmlspecialchars($room['room_no']); ?>')" class="bg-red-500 text-white px-2 py-1 rounded-md hover:bg-red-700 text-xs">Delete</button>
                                        <a href="room_sched_view.php?building=<?php echo urlencode($room['building']); ?>&room_id=<?php echo urlencode($room['room_id']); ?>&department=<?php echo urlencode($_GET['department'] ?? ''); ?>">
                                            <button class="bg-blue-500 text-white px-2 py-1 rounded-md hover:bg-purple-700 text-xs">Schedules</button>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="px-4 py-3 text-center text-gray-500 text-sm">No rooms found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="rooms-cards space-y-3">
            <?php if (count($rooms ?? []) > 0): ?>
                <?php foreach ($rooms as $room): ?>
                    <div class="bg-white rounded-lg shadow p-3">
                        <h3 class="text-base font-semibold"><?php echo htmlspecialchars($room['room_no']); ?></h3>
                        <p class="text-sm"><strong>Building:</strong> <?php echo htmlspecialchars($room['building']); ?></p>
                        <p class="text-sm"><strong>Room Type:</strong> <?php echo htmlspecialchars($room['room_type']); ?></p>
                        <p class="text-sm"><strong>Capacity:</strong> <?php echo htmlspecialchars($room['capacity']); ?></p>
                        <div class="flex gap-1 mt-3">
                            <a href="rooms.php?building=<?php echo urlencode($room['building']); ?>&room_no=<?php echo urlencode($room['room_no']); ?>&action=select&department=<?php echo urlencode($_GET['department'] ?? ''); ?>">
                                <button class="bg-blue-600 text-white px-2 py-1 rounded-md hover:bg-blue-700 text-xs">Edit</button>
                            </a>
                            <button onclick="deleteRoomConfirm('<?php echo htmlspecialchars($room['building']); ?>', '<?php echo htmlspecialchars($room['room_no']); ?>')" class="bg-red-100 text-white px-2 py-1 rounded-md hover:bg-red-700 text-xs">Delete</button>
                            <a href="room_sched_view.php?building=<?php echo urlencode($room['building']); ?>&room_id=<?php echo urlencode($room['room_id']); ?>&department=<?php echo urlencode($_GET['department'] ?? ''); ?>">
                                <button class="bg-purple-600 text-white px-2 py-1 rounded-md hover:bg-purple-700 text-xs">Schedules</button>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-center text-gray-500 text-sm">No rooms found</p>
            <?php endif; ?>
        </div>

        <?php
        if (isset($_GET['success'])) {
            showNotification("Added successfully", "Green");
        } elseif (isset($_GET['delete'])) {
            showNotification("Deleted successfully", "Red");
        } elseif (isset($_GET['editted'])) {
            showNotification("Edited successfully", "Yellow");
        }
        ?>
    </div>

    <script>
        function openAddRoomModal() {
            document.getElementById('openAddRoomModal').classList.remove('hidden');
        }

        function closeAddRoomModal() {
            document.getElementById('openAddRoomModal').classList.add('hidden');
        }

        function closeEditRoomModal() {
            window.location.href = 'rooms.php?department=<?php echo urlencode($_GET['department'] ?? ''); ?>';
        }

        function deleteRoomConfirm(building, roomNo) {
            if (confirm(`Are you sure you want to delete room ${roomNo} in ${building}?`)) {
                window.location.href = `../back/rooms.php?action=delete&building=${encodeURIComponent(building)}&room_no=${encodeURIComponent(roomNo)}&department=<?php echo urlencode($_GET['department'] ?? ''); ?>`;
            }
        }

        function autoroom() {
            window.location.href = "../back/auto_room_assigning.php?department=<?php echo urlencode($_GET['department'] ?? ''); ?>&role=<?php echo urlencode($role ?? ''); ?>&action=autoroom";
        }
    </script>
</body>
</html>