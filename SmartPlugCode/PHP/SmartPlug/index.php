<?php
include "connect.php";
// Delete Record
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $delete_sql = "DELETE FROM log_data WHERE id = $delete_id";
    if ($conn->query($delete_sql) === TRUE) {
        echo "<script>alert('Record deleted successfully');</script>";
        // Redirect to refresh the page
        echo "<script>window.location.href='index.php';</script>";
    } else {
        echo "Error deleting record: " . $conn->error;
    }
}
// Pagination variables
$records_per_page = 10;
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$start_from = ($page - 1) * $records_per_page;

// Check if the "Sort" button is pressed
if (isset($_GET['sort']) && $_GET['sort'] == 'runtime') {
    // Add ORDER BY runtime DESC to sort the data by the runtime column from highest to lowest
    $sql = "SELECT id, datetime, temp, humid, runtime FROM log_data ORDER BY runtime DESC LIMIT $start_from, $records_per_page";
} else if (isset($_GET['sort_resert']) && $_GET['sort_resert'] == 'resert') {
    // If the "Sort" button is not pressed or the value sent is not 'runtime', retrieve the data normally
    $sql = "SELECT id, datetime, temp, humid, runtime FROM log_data ORDER BY id ASC LIMIT $start_from, $records_per_page";
} else {
    // Default query if no sorting button is pressed
    $sql = "SELECT id, datetime, temp, humid, runtime FROM log_data ORDER BY id DESC LIMIT $start_from, $records_per_page";
}

$result = $conn->query($sql);


?>
<script>
    // Function to refresh the page content
    function refreshPageContent() {
        // Perform any necessary operations or updates here
        // Reload the page to fetch updated data
        location.reload();
    }
    // Call the refreshPageContent function after a specified interval (in milliseconds)
    setInterval(refreshPageContent, 5000); // 5000 milliseconds = 5 seconds (adjust as needed)
</script>
<!DOCTYPE html>
<html lang="en">
    <!-- เปลี่ยนโลโก้บนแท็บเบราว์เซอร์ -->
<link rel="icon" href="favicon.ico" type="image/x-icon">
<!-- หรือใช้ favicon แบบ PNG -->
<link rel="shortcut icon" type="image/png" href="logo3.png">
<?php
$sql8 = "SELECT  relayState FROM realtimetemp where  real_id = 1";
$result8 = $conn->query($sql8);

if ($result8->num_rows > 0) {
    // วนลูปผลลัพธ์ของคำสั่ง SQL
    while ($row8 = $result8->fetch_assoc()) {
        // เปรียบเทียบค่า relayState และกำหนดสีพื้นหลังตามเงื่อนไข
        $relayState = $row8["relayState"];
        $background_color = ($relayState == 1) ? "salmon" : "skyblue";
    }
} else {
    echo "0 results";
}
?>
<script>
    // อัปเดตสีพื้นหลังทุกๆ 1 วินาที
    setInterval(function() {
        document.body.style.backgroundColor = "<?php echo $background_color; ?>";
    }, 1000); // หน่วงเวลา 1000 มิลลิวินาที (1 วินาที)
</script>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Crazy Plug</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            /*  background-color: #f8f9fa; */
            /* สีพื้นหลังที่ใช้ */
            background-color: <?php echo $background_color; ?>;
        }

        .chart-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-around;
        }

        .chart-card {
            width: 60%;
            /* ปรับขนาดของแต่ละกราฟ */
            margin-bottom: 2px;
            /* ระยะห่างระหว่างแต่ละกราฟ */
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container">
            <a class="navbar-brand" href="#">Crazy Plug</a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="navbar-nav ml-auto">
                    <li class="nav-item active">
                        <a class="nav-link" href="#">Home <span class="sr-only">(current)</span></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Contact</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <h1 class="text-center mb-4">Crazy Plug</h1>
        <div class="container">
            <div class="row" style="padding: 5px;">
                <!-- Temperature Status Card -->
                <div class="col-md-4">
                    <div class="card text-center">
                        <div class="card-header" style="background-color: rgba(255, 99, 132, 0.6);">
                            Temperature Status
                        </div>
                        <div class="card-body">
                            <?php
                            $sql2 = "SELECT real_id, temp FROM realtimetemp LIMIT 1";
                            $result2 = $conn->query($sql2);
                            if ($result2->num_rows > 0) {
                                $row2 = $result2->fetch_assoc();
                            ?>
                                <h5 class="card-title">Temperature: <?php echo $row2['temp']; ?></h5>
                            <?php
                            } else {
                                echo "<p class='card-text'>No data available</p>";
                            }
                            ?>
                            <canvas id="pieChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-center">
                        <div class="card-header" style="background-color: rgba(111, 225, 173);">
                            Runtime
                        </div>
                        <div class="card-body">
                            <?php
                            $sql6 = "SELECT id, runtime FROM log_data ORDER BY id DESC LIMIT 1; ";
                            $result6 = $conn->query($sql6);
                            if ($result6->num_rows > 0) {
                                $row6 = $result6->fetch_assoc();
                                $runtime_minutes = number_format($row6['runtime']); // แปลงเป็นนาทีและจัดรูปแบบเลขทศนิยม
                            ?>
                                <h5 class="card-title">Runtime: <?php echo $runtime_minutes; ?> Secound</h5>
                            <?php
                            } else {
                                echo "<p class='card-text'>No data available</p>";
                            }
                            ?>
                            <canvas id="pieChart3"></canvas>
                        </div>
                    </div>
                </div>
                <!-- Humidity Status Card -->
                <div class="col-md-4">
                    <div class="card text-center">
                        <div class="card-header" style="background-color:rgba(54, 162, 235, 0.6)">
                            Humidity Status
                        </div>
                        <div class="card-body">
                            <?php
                            $sql3 = "SELECT real_id, humid FROM realtimetemp LIMIT 1";
                            $result3 = $conn->query($sql3);
                            if ($result3->num_rows > 0) {
                                $row3 = $result3->fetch_assoc();
                            ?>
                                <h5 class="card-title">Humidity: <?php echo $row3['humid']; ?></h5>
                            <?php
                            } else {
                                echo "<p class='card-text'>No data available</p>";
                            }

                            ?>
                            <canvas id="pieChart2"></canvas>
                        </div>
                    </div>
                </div>
                <!-- Pie Chart for Temperature and Humidity -->
            </div>
        </div>
        <?php
        // Query ข้อมูลจากฐานข้อมูล
        $sql_count = "SELECT COUNT(*) AS total FROM log_data";
        $result_count = $conn->query($sql_count);
        $row_count = $result_count->fetch_assoc();
        $total_records = $row_count['total'];
        ?>
        <form method="get" action="index.php">
            <button type="submit" class="btn btn-primary" name="sort" value="runtime">Sort</button>
            <button type="submit" class="btn btn-warning " style="color:white;" name="sort_resert" value="resert">reset</button>
        </form>
        <div class="card bg-bg-white">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <!-- ส่วนหัวตาราง -->
                        <h2> Total amount of data <a style="color : red;"> <?php echo $total_records; ?> </a> Record </h2>
                        <thead class="thead-dark">
                            <tr>
                                <th style="text-align: center;" scope="col">ROW</th>
                                <th style="text-align: center;" scope="col">KEY</th>
                                <th style="text-align: center;" scope="col">DATETIME</th>
                                <th style="text-align: center;" scope="col">TEMP</th>
                                <th style="text-align: center;" scope="col">HUMID</th>
                                <th style="text-align: center;" scope="col">RUNTIME</th>
                                <th style="text-align: center;" scope="col">ACTION</th><!-- New column for action buttons -->
                            </tr>
                        </thead>
                        <!-- ส่วนเนื้อหาตาราง -->
                        <tbody>
                            <!-- ลูปแสดงผลข้อมูลในตาราง -->
                            <?php
                            if ($result->num_rows > 0) {
                                $i = 0;
                                while ($row = $result->fetch_assoc()) {
                                    $i++;
                                    // Check if runtime is less than 1 minute (60 seconds)
                                    if ($row['runtime'] < 60) {
                                        $runtime_display = $row['runtime'] . " seconds";
                                    } else if ($row['runtime'] >= 60 && $row['runtime'] < 3600) {
                                        $runtime_minutes = floor($row['runtime'] / 60);
                                        $runtime_seconds = $row['runtime'] % 60;
                                        $runtime_display = $runtime_minutes . " minutes " . $runtime_seconds . " seconds";
                                    } else {
                                        $runtime_hours = floor($row['runtime'] / 3600);
                                        $runtime_minutes = floor(($row['runtime'] % 3600) / 60);
                                        $runtime_seconds = $row['runtime'] % 60;
                                        $runtime_display = $runtime_hours . " hours " . $runtime_minutes . " minutes " . $runtime_seconds . " seconds";
                                    }
                            ?>

                                    <tr>
                                        <!-- แสดงข้อมูลในแต่ละคอลัมน์ -->
                                        <td style="text-align: center;"><?php echo $i; ?></td>
                                        <td style="text-align: center;"><?php echo $row['id'] ?></td>
                                        <td style="text-align: center;">
                                            <?php
                                            // แปลงวันที่และเวลาจาก คริสต์ศักราช เป็น พุทธศักราช
                                            $datetime = $row['datetime'];
                                            $timestamp = strtotime($datetime);
                                            $buddhist_year = date('Y', $timestamp) + 543; // เพิ่ม 543 เพื่อแปลงเป็น พุทธศักราช
                                            $buddhist_date = date('d/m/', $timestamp) . $buddhist_year; // รูปแบบวันที่ วัน/เดือน/ปี
                                            $buddhist_time = date('H:i:s', $timestamp); // รูปแบบเวลา ชั่วโมง:นาที:วินาที

                                            // แสดงผลลัพธ์ที่แปลงเป็น พุทธศักราช
                                            echo $buddhist_date . ' ' . $buddhist_time;
                                            ?>
                                        </td>
                                        <td style="text-align: center;"><?php echo $row['temp'] ?><a>%</a></td>
                                        <td style="text-align: center;"><?php echo $row['humid'] ?></td>
                                        <td style="text-align: center;"><?php echo $runtime_display ?></td>
                                        <td style="text-align: center;">
                                            <!-- ปุ่มลบ -->
                                            <a href="?delete_id=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm">Delete</a>
                                        </td>
                                    </tr>
                            <?php
                                }
                            } else {
                                // กรณีไม่มีข้อมูล
                                echo "<tr><td colspan='7'>0 results</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
                <a href="exportExcel.php" class="btn btn-success">Export to Excel</a>
            </div>
        </div>
        <br>
        <!-- Pagination -->
        <div class="text-center">
            <nav aria-label="Page navigation example">
                <ul class="pagination justify-content-center">
                    <?php
                    $sql = "SELECT COUNT(*) AS total FROM log_data";
                    $result = $conn->query($sql);
                    $row = $result->fetch_assoc();
                    $total_pages = ceil($row["total"] / $records_per_page);

                    for ($i = 1; $i <= $total_pages; $i++) {
                        echo "<li class='page-item'><a class='page-link' href='?page=" . $i . "'>" . $i . "</a></li>";
                    }
                    ?>
                </ul>
            </nav>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            // Get data from PHP variables
            var temp = <?php echo json_encode($row2['temp']); ?>;
            // Create a new Chart object for pie chart
            var ctx = document.getElementById('pieChart').getContext('2d');
            var pieChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Temperature'],
                    datasets: [{
                        label: 'Temperature Gage',
                        data: [temp],
                        backgroundColor: [
                            'rgba(255, 99, 132, 0.6)'
                            // 'rgba(54, 162, 235, 0.6)'
                        ],
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
        <script>
            // Get data from PHP variables
            var Humid = <?php echo json_encode($row3['humid']); ?>;
            // Create a new Chart object for pie chart
            var ctx = document.getElementById('pieChart2').getContext('2d');
            var pieChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Humidity'],
                    datasets: [{
                        label: 'Humidity Gage',
                        data: [Humid],
                        backgroundColor: ['rgba(54, 162, 235, 0.6)'],
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
        <script>
            // Get data from PHP variables
            var runtime = <?php echo json_encode($row6['runtime']); ?>;
            // Create a new Chart object for pie chart
            var ctx = document.getElementById('pieChart3').getContext('2d');
            var pieChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Runtime'],
                    datasets: [{
                        label: 'Runtime',
                        data: [runtime],
                        backgroundColor: [
                            'rgba(111, 225, 173)'
                            // 'rgba(54, 162, 235, 0.6)'
                        ],
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
        <!-- Temperature and Humidity Chart -->
        <?php
        $sql4 = "SELECT id,datetime,temp,humid,runtime  FROM log_data";
        $result4 = $conn->query($sql4);
        // Initialize arrays to store data for temperature and humidity
        $dates = [];
        $temperatures = [];
        $humidities = [];
        // Initialize arrays to store data for runtime
        $runtimes = [];

        // Loop through the results and populate the arrays
        if ($result4->num_rows > 0) {
            while ($row4 = $result4->fetch_assoc()) {
                $dates[] = $row4['datetime'];
                $temperatures[] = $row4['temp'];
                $humidities[] = $row4['humid'];
                $runtimes[] = $row4['runtime'] / 60; // Convert runtime to minutes
            }
        }
        ?>

        <div class="chart-container">
            <!-- Temperature Chart -->
            <div class="card bg-white chart-card">
                <div class="card-body">
                    <canvas id="temperatureChart"></canvas>
                </div>
            </div>

            <!-- Humidity Chart -->
            <div class="card bg-white chart-card">
                <div class="card-body">
                    <canvas id="runtimeChart"></canvas>
                </div>
            </div>

            <!-- Runtime Chart -->
            <div class="card bg-white chart-card">
                <div class="card-body">

                    <canvas id="humidityChart"></canvas>
                </div>
            </div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            // Temperature Chart
            var temperatureCtx = document.getElementById('temperatureChart').getContext('2d');
            var temperatureChart = new Chart(temperatureCtx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($dates); ?>,
                    datasets: [{
                        label: 'Temperature (°C)',
                        data: <?php echo json_encode($temperatures); ?>,
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        borderColor: 'rgba(255, 99, 132, 1)',
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
            // Humidity Chart
            var humidityCtx = document.getElementById('humidityChart').getContext('2d');
            var humidityChart = new Chart(humidityCtx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($dates); ?>,
                    datasets: [{
                        label: 'Humidity (%)',
                        data: <?php echo json_encode($humidities); ?>,
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: {
                        yAxes: [{
                            ticks: {
                                beginAtZero: true,

                            }
                        }]
                    }
                }
            });

            // Runtime Chart
            var runtimeCtx = document.getElementById('runtimeChart').getContext('2d');
            var runtimeChart = new Chart(runtimeCtx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($dates); ?>,
                    datasets: [{
                        label: 'Runtime Usage (minutes)', // Update label to reflect the change
                        data: <?php echo json_encode($runtimes); ?>,
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        borderColor: 'rgba(75, 192, 192, 1)',
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
        <br><br>
        <!-- End of Chart.js code -->
    </div>
    <!-- Bootstrap JS and jQuery (optional) -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>

</html>