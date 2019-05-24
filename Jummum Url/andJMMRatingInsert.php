<?php
    include_once("dbConnect.php");
    setConnectionValue("");
    writeToLog("file: " . basename(__FILE__) . ", user: " . $_POST["modifiedUser"]);
    printAllPost();
    
    

    if(isset($_POST["receiptID"]) && isset($_POST["score"]) && isset($_POST["modifiedUser"]) && isset($_POST["modifiedDate"]))
    {
        $receiptID = $_POST["receiptID"];
        $score = $_POST["score"];
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
    
    
    if(!$receiptID)
    {
        $error = "กรุณาระบุ ReceiptID";
        writeToLog("validate fail: $error, file: " . basename(__FILE__) . ", user: " . $_POST['modifiedUser']);
        $response = array('success' => false, 'data' => null, 'error' => $error);
        echo json_encode($response);
        exit();
    }
    if(!$score)
    {
        $error = "กรุณาระบุ Score";
        writeToLog("validate fail: $error, file: " . basename(__FILE__) . ", user: " . $_POST['modifiedUser']);
        $response = array('success' => false, 'data' => null, 'error' => $error);
        echo json_encode($response);
        exit();
    }
    
    
    //query statement
    $sql = "INSERT INTO Rating(ReceiptID, Score, Comment, ModifiedUser, ModifiedDate) VALUES ('$receiptID', '$score', '$comment', '$modifiedUser', '$modifiedDate')";
    $ret = doQueryTask($sql);
    $ratingID = mysqli_insert_id($con);
    if($ret != "")
    {
        mysqli_rollback($con);
//        putAlertToDevice();
        echo json_encode($ret);
        exit();
    }
    
    
    
    
    /* execute multi query */
    $sql = "select * from rating where ratingID = '$ratingID';";
    $arrMultiResult = executeMultiQueryArray($sql);
    
    
    
    //do script successful
    mysqli_commit($con);
    
    

    
    
    mysqli_close($con);
    writeToLog("query commit, file: " . basename(__FILE__) . ", user: " . $_POST['modifiedUser']);
    $response = array('success' => true, 'data' => $arrMultiResult, 'error' => null);
    echo json_encode($response);
    exit();
?>
