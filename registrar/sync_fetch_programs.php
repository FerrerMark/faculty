<?php
include_once "../session/session.php";
include_once "../connections/connection.php";
$api_url = "http://localhost/registrar/programsAPI.php";
$response = file_get_contents($api_url);
$programData = json_decode($response, true);

if ($programData['status'] === "success") {
    try {
        $stmt = $pdo->prepare("INSERT INTO programs 
            (program_code, program_name, college) 
            VALUES (:program_code, :program_name, :college)
            ON DUPLICATE KEY UPDATE program_name = VALUES(program_name), college = VALUES(college)
        ");

        foreach ($programData['programs'] as $program) {
            $stmt->execute([
                ':program_code' => $program['program_code'],
                ':program_name' => $program['program_name'],    
                ':college' => $program['college'],
            ]);
        }
        // echo "✅ Programs synced successfully!";
    } catch (PDOException $e) {
        echo "❌ Sync failed: " . $e->getMessage();
    }
} else {
    echo "❌ Failed to fetch data from API.";
}

