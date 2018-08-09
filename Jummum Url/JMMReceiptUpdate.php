<?php
    include_once("dbConnect.php");
    setConnectionValue("");
    writeToLog("file: " . basename(__FILE__) . ", user: " . $_POST["modifiedUser"]);
    printAllPost();
    
    

    if(isset($_POST["receiptID"]) && isset($_POST["status"]) && isset($_POST["branchID"]) && isset($_POST["modifiedUser"]) && isset($_POST["modifiedDate"]))
    {
        $receiptID = $_POST["receiptID"];
        $status = $_POST["status"];
        $branchID = $_POST["branchID"];
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
    
    
    

    
    
    
    //receipt
    $sql = "update receipt set status = '$status',statusRoute=concat(statusRoute,',','$status'), modifiedUser = '$modifiedUser', modifiedDate = '$modifiedDate' where receiptID = '$receiptID'";
    $ret = doQueryTask($sql);
    if($ret != "")
    {
        mysqli_rollback($con);
        putAlertToDevice();
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
    
    
    if($status == 11)
    {
        
        //get pushSync Device in jummum
        $sql = "select * from setting where KeyName = 'DeviceTokenAdmin'";
        $selectedRow = getSelectedRow($sql);
        $pushSyncDeviceTokenAdmin = $selectedRow[0]["Value"];
        $arrPushSyncDeviceTokenAdmin = array();
        array_push($arrPushSyncDeviceTokenAdmin,$pushSyncDeviceTokenAdmin);
        sendPushNotificationToDeviceWithPath($arrPushSyncDeviceTokenAdmin,'','jill','negotiation arrive!',0,'',1);
        //****************send noti to shop (turn on light)
        //alarmAdmin
        //query statement
        $ledStatus = 1;
        $sql = "update Setting set Value = '$ledStatus', ModifiedUser = '$modifiedUser', ModifiedDate = '$modifiedDate' where KeyName = 'LedStatus';";
        $ret = doQueryTask($sql);
        if($ret != "")
        {
            mysqli_rollback($con);
            //        putAlertToDevice();
            echo json_encode($ret);
            exit();
        }
        //****************
        
        
        $msg = "";
        sendPushNotificationToDeviceWithPath($pushSyncDeviceTokenReceiveOrder,"./../$jummumOM/",'jill',$msg,$receiptID,'cancelOrder',1);
        
    }

    

    
    
    
    
    //do script successful
    
    if($status == 13)
    {
        $msg = "Review negotiate";
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
    
    
    $sql = "select * from Receipt where receiptID = '$receiptID';";
    $dataJson = executeMultiQueryArray($sql);
    
    
    
    
    
    mysqli_commit($con);
    mysqli_close($con);
    writeToLog("query commit, file: " . basename(__FILE__) . ", user: " . $_POST['modifiedUser']);
    $response = array('status' => '1', 'sql' => $sql, 'tableName' => 'Receipt', 'dataJson' => $dataJson);
    echo json_encode($response);
    exit();
?>
