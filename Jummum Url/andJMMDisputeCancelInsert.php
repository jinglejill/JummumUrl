<?php
    include_once("dbConnect.php");
    setConnectionValue("");
    writeToLog("file: " . basename(__FILE__) . ", user: " . $_POST["modifiedUser"]);
    printAllPost();
    
    

    if(isset($_POST["branchID"]))
    {
        $branchID = $_POST["branchID"];
    }
    if(isset($_POST["receiptID"]) && isset($_POST["disputeReasonID"]) && isset($_POST["refundAmount"]) && isset($_POST["detail"]) && isset($_POST["phoneNo"]) && isset($_POST["modifiedUser"]) && isset($_POST["modifiedDate"]))
    {
        $receiptID = $_POST["receiptID"];
        $disputeReasonID = $_POST["disputeReasonID"];
        $refundAmount = $_POST["refundAmount"];
        $detail = $_POST["detail"];
        $phoneNo = $_POST["phoneNo"];
        $modifiedUser = $_POST["modifiedUser"];
        $modifiedDate = $_POST["modifiedDate"];
    }
    $type = 1;
    
    
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
        $error = "กรุณาระบุ receiptID";
        writeToLog("validate fail: $error, file: " . basename(__FILE__) . ", user: " . $_POST['modifiedUser']);
        $response = array('success' => false, 'data' => null, 'error' => $error);
        echo json_encode($response);
        exit();
    }
    if(!$disputeReasonID)
    {
        $error = "กรุณาระบุ disputeReasonID";
        writeToLog("validate fail: $error, file: " . basename(__FILE__) . ", user: " . $_POST['modifiedUser']);
        $response = array('success' => false, 'data' => null, 'error' => $error);
        echo json_encode($response);
        exit();
    }
    if(!$refundAmount)
    {
        $error = "กรุณาระบุ refundAmount";
        writeToLog("validate fail: $error, file: " . basename(__FILE__) . ", user: " . $_POST['modifiedUser']);
        $response = array('success' => false, 'data' => null, 'error' => $error);
        echo json_encode($response);
        exit();
    }
    if(!$detail)
    {
        $error = "กรุณาระบุ detail";
        writeToLog("validate fail: $error, file: " . basename(__FILE__) . ", user: " . $_POST['modifiedUser']);
        $response = array('success' => false, 'data' => null, 'error' => $error);
        echo json_encode($response);
        exit();
    }
    if(!$phoneNo)
    {
        $error = "กรุณาระบุ phoneNo";
        writeToLog("validate fail: $error, file: " . basename(__FILE__) . ", user: " . $_POST['modifiedUser']);
        $response = array('success' => false, 'data' => null, 'error' => $error);
        echo json_encode($response);
        exit();
    }
    
    
    
    $sql = "select * from receipt where receiptID = '$receiptID';";
    $selectedRow = getSelectedRow($sql);
    $receiptStatus = $selectedRow[0]["Status"];
    if($receiptStatus == 2)
    {
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
        $status = 7;
        
        
        
        
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
        
        

        
        
        
        
        if($type == "1")
        {
            $msg = "Order cancel request";
        }
        else if($type == "2")
        {
            $msg = "Open dispute request";
        }
        else
        {
            $msg = "Review negotiation";
        }
        
        $category = "updateStatus";
        $contentAvailable = 1;
        $data = array("receiptID" => $receiptID);
        sendPushNotificationJummumOM($pushSyncDeviceTokenReceiveOrder,$title,$msg,$category,$contentAvailable,$data);
        
        
        
        
        
        /* execute multi query */
        $sql = "select `ReceiptID`, `BranchID`, `CustomerTableID`, `MemberID`, `TotalAmount`, `CreditCardType`, `CreditCardNo`, `CreditCardAmount`, `Remark`,`SpecialPriceDiscount`,DiscountProgramType,DiscountProgramTitle,DiscountProgramValue, `DiscountType`, `DiscountValue`, `ServiceChargePercent`, `ServiceChargeValue`, `PriceIncludeVat`, `VatPercent`, `VatValue`,NetTotal,LuckyDrawCount,BeforeVat, `Status`, `ReceiptNoID`, `ReceiptDate`, `SendToKitchenDate`, `DeliveredDate`, `BuffetReceiptID`,HasBuffetMenu,TimeToOrder,BuffetEnded,BuffetEndedDate, `VoucherCode` from receipt where receiptID = '$receiptID';";
        $sql .= "Select * from Dispute where receiptID = '$receiptID' and disputeID = '$disputeID';";
        $dataJson = executeMultiQueryArray($sql);
        
    }
    else
    {        
        /* execute multi query */
        $sql = "select `ReceiptID`, `BranchID`, `CustomerTableID`, `MemberID`, `TotalAmount`, `CreditCardType`, `CreditCardNo`, `CreditCardAmount`, `Remark`,`SpecialPriceDiscount`,DiscountProgramType,DiscountProgramTitle,DiscountProgramValue, `DiscountType`, `DiscountValue`, `ServiceChargePercent`, `ServiceChargeValue`, `PriceIncludeVat`, `VatPercent`, `VatValue`,NetTotal,LuckyDrawCount,BeforeVat, `Status`, `ReceiptNoID`, `ReceiptDate`, `SendToKitchenDate`, `DeliveredDate`, `BuffetReceiptID`,HasBuffetMenu,TimeToOrder,BuffetEnded,BuffetEndedDate, `VoucherCode` from receipt where receiptID = '$receiptID';";
        $sql .= "select 0 from dual where 0";
        $dataJson = executeMultiQueryArray($sql);
    }
    
    
    
    
    
    //do script successful
    mysqli_commit($con);
    mysqli_close($con);
    writeToLog("query commit, file: " . basename(__FILE__) . ", user: " . $_POST['modifiedUser']);
    $response = array('success' => true, 'data' => $dataJson, 'error' => null);
    echo json_encode($response);
    exit();
?>
