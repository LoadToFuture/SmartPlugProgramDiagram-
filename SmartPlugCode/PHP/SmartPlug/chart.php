<?php
// Include database connection file
include "connect.php";

// Fetch data from the database
$sql = "SELECT * FROM log_data";
$result = $conn->query($sql);

// Initialize arrays to store data for temperature and humidity
$dates = [];
$temperatures = [];
$humidities = [];

// Loop through the results and populate the arrays
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $dates[] = $row['datetime'];
        $temperatures[] = $row['temp'];
        $humidities[] = $row['humid'];
    }
}

// Close the database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Temperature and Humidity Chart</title>
    <!-- Include Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div style="width: 800px; margin: 0 auto;">
        <canvas id="myChart"></canvas>
    </div>

    <script>
        // Get data from PHP variables
        var dates = <?php echo json_encode($dates); ?>;
        var temperatures = <?php echo json_encode($temperatures); ?>;
        var humidities = <?php echo json_encode($humidities); ?>;

        // Create a new Chart object
        var ctx = document.getElementById('myChart').getContext('2d');
        var myChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: dates,
                datasets: [{
                    label: 'Temperature (°C)',
                    data: temperatures,
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 1
                }, {
                    label: 'Humidity (%)',
                    data: humidities,
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    yAxes: [{
                        ticks: {
                            beginAtZero: true
                        }
                    }]
                }
            }
        });
    </script>
</body>
</html>
