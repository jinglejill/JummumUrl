<?php
    include_once("dbConnect.php");
    setConnectionValue("");
    writeToLog("file: " . basename(__FILE__) . ", user: " . $_POST["modifiedUser"]);
    printAllPost();
    
    
    
    // Check connection
    if (mysqli_connect_errno())
    {
        echo "Failed to connect to MySQL: " . mysqli_connect_error();
    }
    
    
    
    $sql = "SELECT substring(KeyName,14,length(KeyName)-13) LatestVersion FROM `setting` WHERE KeyName like 'updateversion%' order by substring(KeyName,14,length(KeyName)-13) DESC limit 1";
    $selectedRow = getSelectedRow($sql);
    $latestVersion = $selectedRow[0]["LatestVersion"];
    
    
    $success = $latestVersion != null;
    $error = $success?"":"No info";
    /* execute multi query */
    $jsonEncode = executeMultiQueryArray($sql);
    $response = array('success' => $success, 'latestVersion' => "$latestVersion", 'error' => "$error", 'status' => 1);
    echo json_encode($response);
    
    
    
    // Close connections
    mysqli_close($con);
?>
