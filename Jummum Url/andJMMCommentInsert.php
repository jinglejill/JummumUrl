<?php
    include_once("dbConnect.php");
    setConnectionValue("");
    writeToLog("file: " . basename(__FILE__) . ", user: " . $_POST["modifiedUser"]);
    printAllPost();
    
    
    
    if(isset($_POST["userAccountID"]) && isset($_POST["text"]) && isset($_POST["modifiedUser"]) && isset($_POST["modifiedDate"]))
    {
//        $commentID = $_POST["commentID"];
        $userAccountID = $_POST["userAccountID"];
        $text = $_POST["text"];
        $modifiedUser = $_POST["modifiedUser"];
        $modifiedDate = $_POST["modifiedDate"];
    }
    
    
    
    // Check connection
    if (mysqli_connect_errno())
    {
        echo "Failed to connect to MySQL: " . mysqli_connect_error();
    }
    
    
    
    // Set autocommit to off
    mysqli_autocommit($con,FALSE);
    writeToLog("set auto commit to off");
    
    
    if(!$userAccountID)
    {
        $error = "กรุณาระบุ userAccountID";
        writeToLog("validate fail: $error, file: " . basename(__FILE__) . ", user: " . $_POST['modifiedUser']);
        $response = array('success' => false, 'data' => null, 'error' => $error);
        echo json_encode($response);
        exit();
    }
    if(!$text)
    {
        $error = "กรุณาระบุ text";
        writeToLog("validate fail: $error, file: " . basename(__FILE__) . ", user: " . $_POST['modifiedUser']);
        $response = array('success' => false, 'data' => null, 'error' => $error);
        echo json_encode($response);
        exit();
    }
    
    
    //query statement
    $sql = "INSERT INTO Comment(UserAccountID, Text, Type, ModifiedUser, ModifiedDate) VALUES ('$userAccountID', '$text', '$type', '$modifiedUser', '$modifiedDate')";
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
    
    
    
    writeToLog("query commit, file: " . basename(__FILE__) . ", user: " . $_POST['modifiedUser']);
    $response = array('success' => true, 'data' => null, 'error' => null);
    echo json_encode($response);
    exit();
?>
