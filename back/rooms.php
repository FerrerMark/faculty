<?php
include_once("../session/session.php");
include_once "../connections/connection.php";

$role = "Department Head";
$dep = $_GET['department'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' || $role === 'Department Head') {
    if (isset($_GET['action'])) {
        $action = $_GET['action'];

        if ($action === "add") {
            $building = $_POST['building'];
            $room_no = $_POST['room_no'];
            $room_type = $_POST['room_type'];
            $capacity = $_POST['capacity'];
        
            try {
                $stmt = $conn->prepare("
                    INSERT INTO rooms (building, room_no, room_type, capacity) 
                    VALUES (:building, :room_no, :room_type, :capacity)
                ");
                $stmt->bindParam(':building', $building, PDO::PARAM_STR);
                $stmt->bindParam(':room_no', $room_no, PDO::PARAM_STR);
                $stmt->bindParam(':room_type', $room_type, PDO::PARAM_STR);
                $stmt->bindParam(':capacity', $capacity, PDO::PARAM_INT);
                $stmt->execute();
        
                header("Location: ../frame/rooms.php?department=$dep&success");
                exit;
            } catch (PDOException $e) {
                echo "Error: " . $e->getMessage();
            }

        } else if ($action === "delete") {
            $building = $_GET['building'];
            $room_no = $_GET['room'];

            $sql = "DELETE FROM rooms WHERE building = :building AND room_no = :room_no";
            $stmt = $conn->prepare($sql); // Changed $pdo to $conn (assuming $conn is PDO)
            $stmt->bindParam(':building', $building, PDO::PARAM_STR);
            $stmt->bindParam(':room_no', $room_no, PDO::PARAM_STR);
            $stmt->execute();

            header("location: ../frame/rooms.php?role=Department Head&department=$dep&delete");    
            exit;
        } else if ($action === "edit") {
            if (isset($_GET['building'], $_GET['room']) && isset($_POST['building'], $_POST['room_no'], $_POST['room_type'], $_POST['capacity'])) {
                $original_building = $_GET['building'];
                $original_room = $_GET['room'];
        
                $new_building = $_POST['building'];
                $new_room = $_POST['room_no'];
                $room_type = $_POST['room_type'];
                $capacity = $_POST['capacity'];
        
                try {
                    $sql = "UPDATE rooms 
                            SET building = :new_building, room_no = :new_room, room_type = :room_type, capacity = :capacity
                            WHERE building = :original_building AND room_no = :original_room";
        
                    $stmt = $conn->prepare($sql); // Changed $pdo to $conn
                    $stmt->bindParam(':new_building', $new_building, PDO::PARAM_STR);
                    $stmt->bindParam(':new_room', $new_room, PDO::PARAM_STR);
                    $stmt->bindParam(':room_type', $room_type, PDO::PARAM_STR);
                    $stmt->bindParam(':capacity', $capacity, PDO::PARAM_INT);
                    $stmt->bindParam(':original_building', $original_building, PDO::PARAM_STR);
                    $stmt->bindParam(':original_room', $original_room, PDO::PARAM_STR);
        
                    $stmt->execute();
        
                    if ($stmt->rowCount() > 0) {
                        header("location: ../frame/rooms.php?department=$dep&editted");
                    } else {
                        echo "No changes made or record not found.";
                    }
                } catch (PDOException $e) {
                    echo "Error updating room: " . $e->getMessage();
                }
            } else {
                echo "Missing required parameters.";
            }
            exit;
        } else if ($action === "search") {
            // Handle search action
            $search = isset($_POST['search']) ? trim($_POST['search']) : '';
            try {
                $sql = "SELECT * FROM rooms WHERE room_no LIKE :search";
                $stmt = $conn->prepare($sql);
                $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
                $stmt->execute();
                $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Redirect back to rooms.php with search term in URL
                header("Location: ../frame/rooms.php?department=$dep&search=" . urlencode($search));
                exit;
            } catch (PDOException $e) {
                echo "Error searching rooms: " . $e->getMessage();
            }
        }
    }
}

try {
    $sql = "SELECT COUNT(*) FROM rooms;";
    $stmt = $conn->prepare($sql);
    $stmt->execute();

    $count = $stmt->fetchColumn();
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

// Default fetch of all rooms (for GET requests or when no action is specified)
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
if ($search) {
    $sql = "SELECT * FROM rooms WHERE room_no LIKE :search";
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
} else {
    $sql = "SELECT * FROM rooms";
    $stmt = $conn->prepare($sql);
}
$stmt->execute();
$rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle room selection for edit modal
$selectedRoomAndBuilding = null;
if (isset($_GET['action']) && $_GET['action'] === "select") {
    $room = $_GET['room_no'];
    $building = $_GET['building'];

    try {
        $sql = "SELECT * FROM rooms WHERE building = :building AND room_no = :room_no"; 
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':building', $building, PDO::PARAM_STR); // Fixed PARAM_INT to PARAM_STR
        $stmt->bindParam(':room_no', $room, PDO::PARAM_STR); // Fixed PARAM_INT to PARAM_STR
        $stmt->execute();
        $selectedRoomAndBuilding = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "Error fetching room: " . $e->getMessage();
    }
}
?>