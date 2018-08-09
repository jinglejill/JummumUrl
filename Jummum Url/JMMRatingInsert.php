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
    $dataJson = executeMultiQueryArray($sql);
    
    
    
    //do script successful
    mysqli_commit($con);
    
    

    
    
    mysqli_close($con);
    writeToLog("query commit, file: " . basename(__FILE__) . ", user: " . $_POST['modifiedUser']);
    $response = array('status' => '1', 'sql' => $sql, 'tableName' => 'Rating', dataJson => $dataJson);
    echo json_encode($response);
    exit();
?>
