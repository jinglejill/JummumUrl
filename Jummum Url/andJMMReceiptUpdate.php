<?php
    include_once("dbConnect.php");
    setConnectionValue("");
    writeToLog("file: " . basename(__FILE__) . ", user: " . $_POST["modifiedUser"]);
    printAllPost();
    
    

    if(isset($_POST["receiptID"]) && isset($_POST["status"]) && isset($_POST["modifiedUser"]) && isset($_POST["modifiedDate"]))
    {
        $receiptID = $_POST["receiptID"];
        $status = $_POST["status"];
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
    
    
    $sql = "select * from receipt where receiptID = '$receiptID'";
    $selectedRow = getSelectedRow($sql);
    $branchID = $selectedRow[0]["BranchID"];
    
    $sql = "select * from $jummumOM.branch where branchID = '$branchID'";
    $selectedRow = getSelectedRow($sql);
    $dbName = $selectedRow[0]["DbName"];
    
    
    
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
    
    
    
    //alarm admin
    if($status == 11)
    {
        $sql = "select * from setting where keyName = 'AlarmAdmin'";
        $selectedRow = getSelectedRow($sql);
        $alarmAdmin = $selectedRow[0]["Value"];
        if(intval($alarmAdmin) == 1)
        {
            //alarmAdmin
            //query statement
            $sql = "select * from $jummumOM.branch where branchID = '$branchID';";
            $selectedRow = getSelectedRow($sql);
            $dbName = $selectedRow[0]["DbName"];
            
            $ledStatus = 1;
            $sql = "update $dbName.Setting set value = '$ledStatus', ModifiedUser = '$modifiedUser', ModifiedDate = '$modifiedDate' where keyName = 'ledStatus'";
            $ret = doQueryTask($sql);
            if($ret != "")
            {
                mysqli_rollback($con);
                //        putAlertToDevice();
                echo json_encode($ret);
                exit();
            }
        }
    }
    if($status == 13)
    {
        //****************send noti to shop (turn on light)
        //alarmShop
        //query statement
        $sql = "select * from $jummumOM.branch where branchID = '$branchID';";
        $selectedRow = getSelectedRow($sql);
        $dbName = $selectedRow[0]["DbName"];
    
        $ledStatus = 1;
        $sql = "update $dbName.Setting set value = '$ledStatus', ModifiedUser = '$modifiedUser', ModifiedDate = '$modifiedDate' where keyName = 'ledStatus'";
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
    mysqli_commit($con);
    
    
    
    
    
    //get pushSync Device in JUMMUM OM
    $pushSyncDeviceTokenReceiveOrder = array();
    $sql = "select * from $jummumOM.device left join $jummumOM.Branch on $jummumOM.device.DbName = $jummumOM.Branch.DbName where branchID = '$branchID';";
    $selectedRow = getSelectedRow($sql);
    for($i=0; $i<sizeof($selectedRow); $i++)
    {
        $deviceToken = $selectedRow[$i]["DeviceToken"];
        array_push($pushSyncDeviceTokenReceiveOrder,$deviceToken);
    }
    
    
    
    $sql = "select * from receipt where receiptID = '$receiptID'";
    $selectedRow = getSelectedRow($sql);
    $orderNo = $selectedRow[0]["ReceiptNoID"];
    if($status == 11)
    {
        //get pushSync Device in jummum
        $sql = "select * from setting where KeyName = 'DeviceTokenAdmin'";
        $selectedRow = getSelectedRow($sql);
        $arrPushSyncDeviceTokenAdmin = array();
        for($i=0; $i<sizeof($selectedRow); $i++)
        {
            $pushSyncDeviceTokenAdmin = $selectedRow[$i]["Value"];
            array_push($arrPushSyncDeviceTokenAdmin,$pushSyncDeviceTokenAdmin);
        }
        
        $msg = "Order no.$orderNo negotiation arrive!";
        $category = "admin";
        $contentAvailable = 1;
        $data = array("receiptID" => $receiptID);
        sendPushNotificationAdmin($arrPushSyncDeviceTokenAdmin,$title,$msg,$category,$contentAvailable,$data);

        

        
        //send to shop to update status not need any action just inform
        $msg = "";
        $category = "updateStatus";
        $contentAvailable = 1;
        $data = array("receiptID" => $receiptID);
        sendPushNotificationJummumOM($pushSyncDeviceTokenReceiveOrder,$title,$msg,$category,$contentAvailable,$data);

    }
    
    if($status == 13)
    {
        $msg = "Order no.$orderNo Review negotiate";
        $category = "updateStatus";
        $contentAvailable = 1;
        $data = array("receiptID" => $receiptID);
        sendPushNotificationJummumOM($pushSyncDeviceTokenReceiveOrder,$title,$msg,$category,$contentAvailable,$data);
    }
    mysqli_commit($con);
    
    
    
    //json
    $sql = "select * from Receipt where receiptID = '$receiptID';";
    $dataJson = executeMultiQueryArray($sql);
    
    
    
    
    
//    mysqli_commit($con);
    mysqli_close($con);
    writeToLog("query commit, file: " . basename(__FILE__) . ", user: " . $_POST['modifiedUser']);    
    $response = array('success' => true, 'data' => $dataJson, 'error' => null);
    echo json_encode($response);
    exit();
?>
