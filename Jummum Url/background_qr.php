<?php
    include_once("dbConnect.php");
    setConnectionValueWithoutCheckUpdate("");
    writeToGbpLog("file: " . basename(__FILE__) . ", user: " . $_POST["modifiedUser"]);
    
        
    $json_str = file_get_contents('php://input');
    //fwrite($respFile, $json_str . "\n\n");
    writeToGbpLog("backgroundQR: " . $json_str);


    $json_obj = json_decode($json_str);
    $resultCode = $json_obj->resultCode;
    $amount = $json_obj->amount;
    $gbpReferenceNo = $json_obj->gbpReferenceNo;
    $receiptID = $json_obj->merchantDefined1;
    $branchID = $json_obj->merchantDefined2;
    $userDeviceToken = $json_obj->merchantDefined3;
    $memberID = $json_obj->merchantDefined4;
    $receiptNoID = $json_obj->merchantDefined5;
    writeToGbpLog("resultCode=" . $json_obj->resultCode);
    writeToGbpLog("amount=" . $json_obj->amount);
    writeToGbpLog("referenceNo=" . $json_obj->referenceNo);
    writeToGbpLog("gbpReferenceNo=" . $json_obj->gbpReferenceNo);
    writeToGbpLog("currencyCode=" . $json_obj->currencyCode);
    writeToGbpLog("totalAmount=" . $json_obj->totalAmount);
    writeToGbpLog("thbAmount=" . $json_obj->thbAmount);
    writeToGbpLog("receiptID=" . $json_obj->merchantDefined1);
    writeToGbpLog("branchID=" . $json_obj->merchantDefined2);
    writeToGbpLog("deviceToken=" . $json_obj->merchantDefined3);
    writeToGbpLog("memberID=" . $json_obj->merchantDefined4);
    writeToGbpLog("receiptNoID=" . $json_obj->merchantDefined5);
    writeToGbpLog("customerTableID=" . $json_obj->detail);


    //update receipt status
    // Check connection
    if (mysqli_connect_errno())
    {
        echo "Failed to connect to MySQL: " . mysqli_connect_error();
    }
    
    
    // Set autocommit to off
    mysqli_autocommit($con,FALSE);
    writeToGbpLog("set auto commit to off");
    
    
    $sql = "select * from $jummumOM.branch where branchID = '$branchID';";
    $selectedRow = getSelectedRow($sql);
    $dbName = $selectedRow[0]["DbName"];
    if($resultCode == "00")
    {
        //update receipt
        $modifiedUser = "GBP";
        $modifiedDate = date("Y-m-d H:i:s");
        $sql = "update receipt set status = '2', GbpReferenceNo = '$gbpReferenceNo', ModifiedUser = '$modifiedUser', ModifiedDate = '$modifiedDate' where receiptID = '$receiptID';";
        $ret = doQueryTask($sql);
        if($ret != "")
        {
            mysqli_rollback($con);
            //        putAlertToDevice();
            echo json_encode($ret);
            exit();
        }
        

        //lucky draw
        {
            $sql = "select * from $dbName.setting where keyName = 'luckyDrawSpend'";
            $selectedRow = getSelectedRow($sql);
            $luckyDrawSpend = $selectedRow[0]["Value"];
            if($luckyDrawSpend)
            {
                $luckyDrawTimes = floor($amount/$luckyDrawSpend);
            }
            else
            {
                $luckyDrawTimes = 0;
            }
            writeToLog("luckyDrawTimes: " . $luckyDrawTimes);
            if($luckyDrawTimes > 0)
            {
                for($i=0; $i<$luckyDrawTimes; $i++)
                {
                    if($i==0)
                    {
                        $sql = "insert into LuckyDrawTicket (ReceiptID,MemberID, RewardRedemptionID,GetTicketDate,ModifiedUser,ModifiedDate) values ('$receiptID','$memberID',-1,'$modifiedDate','$modifiedUser','$modifiedDate')";
                    }
                    else
                    {
                        $sql .= ",('$receiptID','$memberID',-1,'$modifiedDate','$modifiedUser','$modifiedDate')";
                    }
                }
                $ret = doQueryTask($sql);
                if($ret != "")
                {
                    mysqli_rollback($con);
                    //                    putAlertToDevice();
                    echo json_encode($ret);
                    exit();
                }
            }
        }

        
        //****************send noti to shop (turn on light)
        //alarmShop
        //query statement
//        if($methodType == 2)
        {
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
            mysqli_commit($con);
            //****************
            
            
            //get pushSync Device in JUMMUM OM
            $pushSyncDeviceTokenReceiveOrder = array();
            $sql = "select * from $jummumOM.device left join $jummumOM.Branch on $jummumOM.device.DbName = $jummumOM.Branch.DbName where branchID = '$branchID';";
            $selectedRow = getSelectedRow($sql);
            for($i=0; $i<sizeof($selectedRow); $i++)
            {
                $deviceToken = $selectedRow[$i]["DeviceToken"];
                array_push($pushSyncDeviceTokenReceiveOrder,$deviceToken);
            }
            $msg = 'New order coming!! order no:' . $receiptNoID;
            $category = "printKitchenBill";
            $contentAvailable = 1;
            $data = array("receiptID" => $receiptID);
            sendPushNotificationJummumOM($pushSyncDeviceTokenReceiveOrder,$title,$msg,$category,$contentAvailable,$data);
            //-----****************************
        }
        //****************
        
        
        //push to device
        $msg = "";
        $category = "gbpQR";
        $contentAvailable = 1;
        $data = array("receiptID" => $receiptID, "type" => "QRPaymentSuccess");
        $arrDeviceToken = array();
        array_push($arrDeviceToken,$userDeviceToken);
        sendPushNotificationJummum($arrDeviceToken,$title,$msg,$category,$contentAvailable,$data);
    }
    else
    {
        writeToLog("pay by qr failed");
        writeToGbpLog("pay by qr failed");
    }
    
    mysqli_close($con);
    writeToGbpLog("end of background");
?>
