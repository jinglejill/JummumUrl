<?php
    include_once("dbConnect.php");
    setConnectionValue("");
    writeToLog("file: " . basename(__FILE__) . ", user: " . $_POST["modifiedUser"]);
    printAllPost();
    
    

    if(isset($_POST["branchID"]))
    {
        $branchID = $_POST["branchID"];
    }
    if(isset($_POST["disputeID"]) && isset($_POST["receiptID"]) && isset($_POST["disputeReasonID"]) && isset($_POST["refundAmount"]) && isset($_POST["detail"]) && isset($_POST["phoneNo"]) && isset($_POST["type"]) && isset($_POST["modifiedUser"]) && isset($_POST["modifiedDate"]))
    {
        $disputeID = $_POST["disputeID"];
        $receiptID = $_POST["receiptID"];
        $disputeReasonID = $_POST["disputeReasonID"];
        $refundAmount = $_POST["refundAmount"];
        $detail = $_POST["detail"];
        $phoneNo = $_POST["phoneNo"];
        $type = $_POST["type"];
        $modifiedUser = $_POST["modifiedUser"];
        $modifiedDate = $_POST["modifiedDate"];
        
        
        $status = 8;
    }
    else if(isset($_GET["admin"]))//admin
    {
        $branchID = $_GET["branchID"];
        
        
        $disputeID = 0;
        $receiptID = $_GET["receiptID"];
        $disputeReasonID = '';
        $refundAmount = $_GET["refundAmount"];
        $detail = $_GET["detail"];
        $phoneNo = '';
        $type = '5';
        $modifiedUser = 'admin';
        $modifiedDate = date('Y-m-d H:i:s');
        
        
        $status = 12;
    }
    
    
    
    // Check connection
    if (mysqli_connect_errno())
    {
        echo "Failed to connect to MySQL: " . mysqli_connect_error();
    }
    
    
    
    // Set autocommit to off
    mysqli_autocommit($con,FALSE);
    writeToLog("set auto commit to off");
    
    
    
    
    //dispute
    //query statement
    $sql = "INSERT INTO Dispute(ReceiptID, DisputeReasonID, RefundAmount, Detail, PhoneNo, Type, ModifiedUser, ModifiedDate) VALUES ('$receiptID', '$disputeReasonID', '$refundAmount', '$detail', '$phoneNo', '$type', '$modifiedUser', '$modifiedDate')";
    $ret = doQueryTask($sql);
    $disputeID = mysqli_insert_id($con);
    if($ret != "")
    {
        mysqli_rollback($con);
//        putAlertToDevice();
        echo json_encode($ret);
        exit();
    }
    
    
    
    
    //receipt
    $sql = "update receipt set status = '$status',statusRoute=concat(statusRoute,',','$status'), modifiedUser = '$modifiedUser', modifiedDate = '$modifiedDate' where receiptID = '$receiptID'";
    $ret = doQueryTask($sql);
    if($ret != "")
    {
        mysqli_rollback($con);
//        putAlertToDevice();
        echo json_encode($ret);
        exit();
    }
    
    
    
    //get pushSync Device in JUMMUM OM
    $pushSyncDeviceTokenReceiveOrder = array();
    $sql = "select * from $jummumOM.device left join $jummumOM.Branch on $jummumOM.device.DbName = $jummumOM.Branch.DbName where branchID = '$branchID';";
    $selectedRow = getSelectedRow($sql);
    for($i=0; $i<sizeof($selectedRow); $i++)
    {
        $deviceToken = $selectedRow[$i]["DeviceToken"];
        array_push($pushSyncDeviceTokenReceiveOrder,$deviceToken);
    }
    
    

    
    
    
    
    
    /* execute multi query */
    $sql = "select * from receipt where receiptID = '$receiptID';";
    $sql .= "Select * from Dispute where receiptID = '$receiptID' and disputeID = '$disputeID';";
    $dataJson = executeMultiQueryArray($sql);
    
    
    
    //send noti to om
    if($type == 2)
    {
        $msg = "Open dispute request";
        sendPushNotificationToDeviceWithPath($pushSyncDeviceTokenReceiveOrder,"./../$jummumOM/",'jill',$msg,$receiptID,'cancelOrder',1);
        //****************send noti to shop (turn on light)
        //alarmShop
        //query statement
        $ledStatus = 1;
        $sql = "update $jummumOM.Branch set LedStatus = '$ledStatus', ModifiedUser = '$modifiedUser', ModifiedDate = '$modifiedDate' where branchID = '$branchID';";
        $ret = doQueryTask($sql);
        if($ret != "")
        {
            mysqli_rollback($con);
            //        putAlertToDevice();
            echo json_encode($ret);
            exit();
        }
        //****************
    }
    
    
    //send noti to customer
    if($type == 5)
    {
        $sql = "select login.DeviceToken from login left join useraccount on login.username = useraccount.username where useraccount.MemberID = '$memberID' order by login.modifiedDate desc limit 1;";
        $selectedRow = getSelectedRow($sql);
        $customerDeviceToken = $selectedRow[0]["DeviceToken"];
        
        $msg = "Review dispute";
        $category = "updateStatus";
        sendPushNotificationToDeviceWithPath($customerDeviceToken,'./','jill',$msg,$receiptID,'cancelOrder',1);
    }
    
    
    //do script successful
    mysqli_commit($con);
    mysqli_close($con);
    writeToLog("query commit, file: " . basename(__FILE__) . ", user: " . $_POST['modifiedUser']);
    $response = array('status' => '1', 'sql' => $sql, 'tableName' => 'Receipt', 'dataJson' => $dataJson);
    echo json_encode($response);
    exit();
?>
