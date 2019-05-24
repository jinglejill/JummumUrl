<?php
    include_once("dbConnect.php");
    setConnectionValue("");
    writeToLog("file: " . basename(__FILE__) . ", user: " . $_POST["modifiedUser"]);
    printAllPost();
    
    
    
    if(isset($_POST["receiptID"]) && isset($_POST["bankID"]) && isset($_POST["bankAccountNo"]) && isset($_POST["amount"]) && isset($_POST["phoneNo"]) && isset($_POST["remark"]) && isset($_POST["modifiedUser"]) && isset($_POST["modifiedDate"]))
    {
        $receiptID = $_POST["receiptID"];
        $bankID = $_POST["bankID"];
        $bankAccountNo = $_POST["bankAccountNo"];
        $amount = $_POST["amount"];
        $phoneNo = $_POST["phoneNo"];
        $remark = $_POST["remark"];
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
    $sql = "INSERT INTO `transferform`(`ReceiptID`, `Amount`, `BankID`, `BankAccountNo`, `PhoneNo`, `Remark`, `ModifiedUser`, `ModifiedDate`) VALUES ('$receiptID', '$amount', '$bankID', '$bankAccountNo', '$phoneNo', '$remark', '$modifiedUser', '$modifiedDate')";
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
