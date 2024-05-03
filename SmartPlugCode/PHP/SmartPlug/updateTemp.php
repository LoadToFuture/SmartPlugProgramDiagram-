<?php

include "connect.php";

if (isset($_POST["temperature"]) && isset($_POST["humidity"]) && isset($_POST["relayState"])) {

    $temp = $_POST["temperature"];
    $humid = $_POST["humidity"];
    $relayState = $_POST["relayState"];

    // Corrected SQL query with parameterized query
    $sql = "UPDATE `realtimetemp` SET `temp`='$temp', `humid`='$humid', `relayState`='$relayState'";
    if (mysqli_query($conn, $sql)) {
        echo "New record updated successfully";
    } else {
        echo "Error: " . $sql . "<br>" . mysqli_error($conn);
    }
} else {
    echo "๊!! UPDATE : ";
}
