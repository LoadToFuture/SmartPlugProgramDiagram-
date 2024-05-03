<?php
// Include database connection file
include_once 'connect.php';

// Filter the excel data
function filterData(&$str){ 
    $str = preg_replace("/\t/", "\\t", $str); 
    $str = preg_replace("/\r?\n/", "\\n", $str); 
    if(strstr($str, '"')) $str = '"' . str_replace('"', '""', $str) . '"'; 
} 

// Excel file name for download
$fileName = "Crazyplug_" . date('Y-m-d') . ".xls";

// Column names
$fields = array('ID', 'DATETIME', 'TEMP', 'HUMID', 'RUNTIME');

// Display column names as first row
$excelData = implode("\t", array_values($fields)) . "\n";

// Fetch records from database
$query = $conn->query("SELECT * FROM log_data ORDER BY id ASC");
if($query->num_rows > 0){
    // Output each row of the data
    while($row = $query->fetch_assoc()){
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

        $lineData = array($row['id'], $row['datetime'], $row['temp'], $row['humid'], $runtime_display);
        array_walk($lineData, 'filterData');
        $excelData .= implode("\t", array_values($lineData)) . "\n";
    }
}else{
    $excelData .= 'No records found...' . "\n";
}

// Headers for download
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=\"$fileName\"");

// Render excel data
echo $excelData;

exit;
?>
