<?php
include_once("../back/dashboard.php");
include_once "../comps/count.php";

$enrollment_data = [
    "BSIT" => $regconn->query("SELECT COUNT(*) FROM students WHERE department = 'BSIT'")->fetchColumn(),
    "CRIM" => $regconn->query("SELECT COUNT(*) FROM students WHERE department = 'BSCRIM'")->fetchColumn()
];
$total_students = array_sum($enrollment_data);

$percentages = [];
foreach ($enrollment_data as $program => $count) {
    $percentages[$program] = round(($count / $total_students) * 100); 
}

$faculty_data = [
    "BSBA" => $conn->query("SELECT COUNT(*) FROM faculty WHERE departmentID = 'BSBA'")->fetchColumn(),
    "BSIT" => $conn->query("SELECT COUNT(*) FROM faculty WHERE departmentID = 'BSIT'")->fetchColumn(),
    "CRIM" => $conn->query("SELECT COUNT(*) FROM faculty WHERE departmentID = 'BSCRIM'")->fetchColumn(),
    "BSEd" => $conn->query("SELECT COUNT(*) FROM faculty WHERE departmentID = 'BSEd'")->fetchColumn(),
    "BSAIS" => $conn->query("SELECT COUNT(*) FROM faculty WHERE departmentID = 'BSAIS'")->fetchColumn(),
    "BSTM" => $conn->query("SELECT COUNT(*) FROM faculty WHERE departmentID = 'BSTM'")->fetchColumn(),
    "BLIS" => $conn->query("SELECT COUNT(*) FROM faculty WHERE departmentID = 'BLIS'")->fetchColumn(),
    "BSP" => $conn->query("SELECT COUNT(*) FROM faculty WHERE departmentID = 'BSP'")->fetchColumn(),
    "BSENTREP" => $conn->query("SELECT COUNT(*) FROM faculty WHERE departmentID = 'BSENTREP'")->fetchColumn(),
    "BSHM" => $conn->query("SELECT COUNT(*) FROM faculty WHERE departmentID = 'BSHM'")->fetchColumn(),
    "BSLIS" => $conn->query("SELECT COUNT(*) FROM faculty WHERE departmentID = 'BSLIS'")->fetchColumn(),
    "BSOA" => $conn->query("SELECT COUNT(*) FROM faculty WHERE departmentID = 'BSOA'")->fetchColumn(),
    "BSCpE" => $conn->query("SELECT COUNT(*) FROM faculty WHERE departmentID = 'BSCpE'")->fetchColumn(),
    "BPED" => $conn->query("SELECT COUNT(*) FROM faculty WHERE departmentID = 'BPED'")->fetchColumn(),
    "BTLED" => $conn->query("SELECT COUNT(*) FROM faculty WHERE departmentID = 'BTLED'")->fetchColumn(),
    "BEED" => $conn->query("SELECT COUNT(*) FROM faculty WHERE departmentID = 'BEED'")->fetchColumn(),
    "BSAM" => $conn->query("SELECT COUNT(*) FROM faculty WHERE departmentID = 'BSAM'")->fetchColumn(),
    "BSEE" => $conn->query("SELECT COUNT(*) FROM faculty WHERE departmentID = 'BSEE'")->fetchColumn(),
    "BSCIM" => $conn->query("SELECT COUNT(*) FROM faculty WHERE departmentID = 'BSCIM'")->fetchColumn(),
    "BSIS" => $conn->query("SELECT COUNT(*) FROM faculty WHERE departmentID = 'BSIS'")->fetchColumn(),
    "BSTM" => $conn->query("SELECT COUNT(*) FROM faculty WHERE departmentID = 'BSTM'")->fetchColumn()
];
$total_faculty = array_sum($faculty_data);

$faculty_percentages = [];
foreach ($faculty_data as $program => $count) {
    $faculty_percentages[$program] = round(($count / $total_faculty) * 100); 
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../css/dashstyle.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="dashboard">
        <main class="main-content">
            <div class="dashboard-cards">
                <div class="card">
                    <h3>Total Faculty</h3>
                    <p class="number"><?php echo $faculty_count; ?></p>
                    <p class="subtext">+2 from last month</p>
                </div>
                <div class="card">
                    <h3><?php echo $department; ?> Sections</h3>
                    <p class="number"><?php echo $section_count; ?></p>
                    <p class="subtext">Current Sections</p>
                </div>
                <div class="card">
                    <h3>Your Total Teaching Hrs</h3>
                    <p class="number"><?php echo number_format($faculty_teaching_hours, 2); ?> hrs</p>
                    <p class="subtext">Total hours you are scheduled to teach</p>
                </div>
                <div class="card">
                    <h3>Total Rooms</h3>
                    <p class="number">50 Rooms</p>
                    <p class="subtext">Lorem, ipsum.</p>
                </div>
            </div>

            <div class="charts">
                <div class="enrollment-chart">
                    <h3>Enrollment by Program (Total: <?php echo $total_students; ?> Students)</h3>
                    <canvas id="enrollmentAreaChart"></canvas>
                    <div class="chart-key">Key: Area represents student enrollment by program.</div>
                </div>
                <div class="faculty-chart">
                    <h3>Faculty by Program (Total: <?php echo $total_faculty; ?> Faculty)</h3>
                    <canvas id="facultyAreaChart"></canvas>
                    <div class="chart-key">Key: Area represents faculty distribution by program.</div>
                </div>
            </div>
        </main>
    </div>

    <script>
        const enrollmentCtx = document.getElementById('enrollmentAreaChart').getContext('2d');
        new Chart(enrollmentCtx, {
            type: 'line',
            data: {
                labels: [<?php echo "'" . implode("','", array_keys($enrollment_data)) . "'"; ?>],
                datasets: [{
                    label: 'Students',
                    data: [<?php echo implode(',', array_values($enrollment_data)); ?>],
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Number of Students'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Program'
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                label += context.parsed.y + ' students';
                                return label;
                            }
                        }
                    }
                }
            }
        });

        const facultyCtx = document.getElementById('facultyAreaChart').getContext('2d');
        new Chart(facultyCtx, {
            type: 'line',
            data: {
                labels: [<?php echo "'" . implode("','", array_keys($faculty_data)) . "'"; ?>],
                datasets: [{
                    label: 'Faculty',
                    data: [<?php echo implode(',', array_values($faculty_data)); ?>],
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Number of Faculty'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Program'
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                label += context.parsed.y + ' faculty';
                                return label;
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>