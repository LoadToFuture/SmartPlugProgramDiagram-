<?php
/*************************************************************************************************
 *  Created By: Tauseef Ahmad
 *  Created On: 3 April, 2023
 *  
 *  YouTube Video: https://youtu.be/VEN5kgjEuh8
 *  My Channel: https://www.youtube.com/channel/UCOXYfOHgu-C-UfGyDcu5sYw/
 ***********************************************************************************************/
 include "connect.php";

if(isset($_POST["temperature"]) && isset($_POST["humidity"] ) && isset($_POST["runtime"])) {

	$temp = $_POST["temperature"];
    $humid = $_POST["humidity"];
    $runtime = $_POST["runtime"];

    // Corrected variable names in SQL query
    $sql = "INSERT INTO `log_data`(`temp`, `humid`, `runtime`) VALUES ('$temp','$humid', '$runtime')"; 

    if (mysqli_query($conn, $sql)) { 
        echo "New record created successfully"; 
    } else { 
        echo "Error: " . $sql . "<br>" . mysqli_error($conn);       
    } 
} else {
    echo "Temperature, humidity, or runtime not set.";
}
?>
 
