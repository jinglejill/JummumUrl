<?php
    include_once("dbConnect.php");
    setConnectionValue("");
    writeToLog("file: " . basename(__FILE__) . ", user: " . $_POST["modifiedUser"]);
    printAllPost();
    
    
    
    if(isset($_POST["ratingID"]) && isset($_POST["receiptID"]) && isset($_POST["score"]) && isset($_POST["comment"]) && isset($_POST["modifiedUser"]) && isset($_POST["modifiedDate"]))
    {
        $ratingID = $_POST["ratingID"];
        $receiptID = $_POST["receiptID"];
        $score = $_POST["score"];
        $comment = $_POST["comment"];
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
    
    
    if(!$ratingID)
    {
        $error = "กรุณาระบุ ratingID";
        writeToLog("validate fail: $error, file: " . basename(__FILE__) . ", user: " . $_POST['modifiedUser']);
        $response = array('success' => false, 'data' => null, 'error' => $error);
        echo json_encode($response);
        exit();
    }
    if(!$receiptID)
    {
        $error = "กรุณาระบุ receiptID";
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
    if(!$comment)
    {
        $error = "กรุณาระบุ comment";
        writeToLog("validate fail: $error, file: " . basename(__FILE__) . ", user: " . $_POST['modifiedUser']);
        $response = array('success' => false, 'data' => null, 'error' => $error);
        echo json_encode($response);
        exit();
    }
    
    
    //query statement
    $sql = "update Rating set ReceiptID = '$receiptID', Score = '$score', Comment = '$comment', ModifiedUser = '$modifiedUser', ModifiedDate = '$modifiedDate' where RatingID = '$ratingID'";
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
