<?php
    include_once("dbConnect.php");
    setConnectionValue($jummumOM);
    writeToLog("file: " . basename(__FILE__) . ", user: " . $_GET["modifiedUser"]);
    printAllPost();
    
    
    
    if(isset($_GET["branchID"]) && isset($_GET["ledStatus"]) && isset($_GET["modifiedUser"]) && isset($_GET["modifiedDate"]))
    {
        $branchID = $_GET["branchID"];
        $ledStatus = $_GET["ledStatus"];
        $modifiedUser = $_GET["modifiedUser"];
        $modifiedDate = $_GET["modifiedDate"];
    }
    
    
    
    // Check connection
    if (mysqli_connect_errno())
    {
        echo "Failed to connect to MySQL: " . mysqli_connect_error();
    }
    
    
    
    // Set autocommit to off
    mysqli_autocommit($con,FALSE);
    writeToLog("set auto commit to off");
    
    
    
    //query statement
    $sql = "update Branch set LedStatus = '$ledStatus', ModifiedUser = '$modifiedUser', ModifiedDate = '$modifiedDate' where branchID = '$branchID';";
    $ret = doQueryTask($sql);
    if($ret != "")
    {
        mysqli_rollback($con);
//        putAlertToDevice();
        echo json_encode($ret);
        exit();
    }
    //-----
    
    
    
    //do script successful
    mysqli_commit($con);
    mysqli_close($con);
    
    
    
    writeToLog("query commit, file: " . basename(__FILE__) . ", user: " . $_GET['modifiedUser']);
    $response = array('status' => '1', 'sql' => $sql);
    echo json_encode($response);
    exit();
?>
