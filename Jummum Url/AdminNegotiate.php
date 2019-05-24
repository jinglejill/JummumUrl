<!DOCTYPE html> <html>
<head>
<title>Admin negotiate</title>
<script type="text/javascript">

function selectChanged(obj)
{
    var arrText = obj.value.split(" ");
    var index = arrText[1];
    
    var colBranchID = "branchID";
    var colReceiptID = "receiptID";
    var colReceiptNoID = "receiptNoID";
    var colNetTotal = "netTotal";
    var colRefundAmount = "refundAmount";
    var colDetail = "detail";
    var colPhoneNo = "phoneNo";
    var colDisputeReasonID = "disputeReasonID";
    var colDisputeReason = "disputeReason";
    var colMemberID = "memberID";
    var branchID = document.getElementById(colBranchID.concat(index)).value;
    var receiptID = document.getElementById(colReceiptID.concat(index)).value;
    var receiptNoID = document.getElementById(colReceiptNoID.concat(index)).value;
    var netTotal = document.getElementById(colNetTotal.concat(index)).value;
    var refundAmount = document.getElementById(colRefundAmount.concat(index)).value;
    var detail = document.getElementById(colDetail.concat(index)).value;
    var phoneNo = document.getElementById(colPhoneNo.concat(index)).value;
    var disputeReasonID = document.getElementById(colDisputeReasonID.concat(index)).value;
    var disputeReason = document.getElementById(colDisputeReason.concat(index)).value;
    var memberID = document.getElementById(colMemberID.concat(index)).value;
    
    
    document.getElementById("selectedBranchID").value = branchID;
    document.getElementById("selectedReceiptID").value = receiptID;
    document.getElementById("selectedReceiptNoID").value = receiptNoID;
    document.getElementById("selectedNetTotal").value = netTotal;
    document.getElementById("selectedRefundAmount").value = refundAmount;
    document.getElementById("selectedDetail").value = detail;
    document.getElementById("selectedPhoneNo").value = phoneNo;
    document.getElementById("selectedDisputeReasonID").value = disputeReasonID;
    document.getElementById("selectedMemberID").value = memberID;
    
    
    document.getElementById("refundAmount").value = refundAmount;
    document.getElementById("detail").value = detail;
    
    
    document.getElementById("phoneNo").value = phoneNo;
    document.getElementById("reason").value = disputeReason;
}
</script>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
</head>
<body>
<table><tr><th>Admin negotiate</th></tr></table><div>
<form action="AdminNegotiate.php" method="post">
<div>
<Table>
<tr><td>
เลือกบิล: </td>
<td><select onchange="selectChanged(this)">
<?php
    include_once("dbConnect.php");
    setConnectionValueWithoutCheckUpdate("");
    writeToLog("file: " . basename(__FILE__) . ", user: " . $_POST["modifiedUser"]);
    
    // Check connection
    if (mysqli_connect_errno())
    {
        echo "Failed to connect to MySQL: " . mysqli_connect_error();
    }


    if($_POST["selectedReceiptID"])
    {
        $branchID = $_POST["selectedBranchID"];
        $receiptID = $_POST["selectedReceiptID"];
        $phoneNo = $_POST["phoneNo"];
        $disputeReasonID = $_POST["selectedDisputeReasonID"];
        $refundAmount = $_POST["refundAmount"];
        $detail = $_POST["detail"];
        $type = 5;
        $modifiedUser = 'admin';
        $modifiedDate = date("Y-m-d H:i:s");
        $status = 12;
        
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

        if($type == 2)
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




        //send noti to om
        $sql = "select * from receipt where receiptID = '$receiptID'";
        $selectedRow = getSelectedRow($sql);
        $memberID = $selectedRow[0]["MemberID"];
        $orderNo = $selectedRow[0]["ReceiptNoID"];


        if($type == 2)
        {
            $msg = "Order no.$orderNo Open dispute request";
            $category = "updateStatus";
            $contentAvailable = 1;
            $data = array("receiptID" => $receiptID);
            sendPushNotificationJummumOM($pushSyncDeviceTokenReceiveOrder,$title,$msg,$category,$contentAvailable,$data);
        }



        if($type == 5)
        {
            //send noti to customer from admin
            $sql = "select login.DeviceToken from login left join useraccount on login.username = useraccount.username where useraccount.userAccountID = '$memberID' order by login.modifiedDate desc limit 1;";
            $selectedRow = getSelectedRow($sql);
            $customerDeviceToken = $selectedRow[0]["DeviceToken"];
            $arrCustomerDeviceToken = array();
            array_push($arrCustomerDeviceToken,$customerDeviceToken);
            
            $msg = "Order no.$orderNo Review dispute";
            $category = "updateStatus";
            $contentAvailable = 1;
            $data = array("receiptID" => $receiptID);
            sendPushNotificationJummum($arrCustomerDeviceToken,$title,$msg,$category,$contentAvailable,$data);

            
            
            
            
            //send to shop to update status not need any action just inform
            $msg = "";
            $category = "updateStatus";
            $contentAvailable = 1;
            $data = array("receiptID" => $receiptID);
            sendPushNotificationJummumOM($pushSyncDeviceTokenReceiveOrder,$title,$msg,$category,$contentAvailable,$data);
            
        }



        /* execute multi query */
        $sql = "select * from receipt where receiptID = '$receiptID';";
        $sql .= "Select * from Dispute where receiptID = '$receiptID' and disputeID = '$disputeID';";
        $dataJson = executeMultiQueryArray($sql);




        //do script successful
        mysqli_commit($con);
//        mysqli_close($con);
        writeToLog("query commit, file: " . basename(__FILE__) . ", user: " . $_POST['modifiedUser']);
    }


    $sql = "select * from receipt where status = 11 order by receiptDate desc limit 10";
    $selectedRow = getSelectedRow($sql);
    for($i=0; $i<sizeof($selectedRow); $i++)//แสดง option to pay
    {
        $receiptDate = $selectedRow[$i]["ReceiptDate"];
        $receiptNoID = $selectedRow[$i]["ReceiptNoID"];
        $netTotal = $selectedRow[$i]["NetTotal"];
        echo "<option value='running " . $i . "'>".($i+1).". $receiptDate" . " / " . $receiptNoID . " / " . "$netTotal</option>";
    }
?>
</select></td></tr>
<?php
    for($i=0; $i<sizeof($selectedRow); $i++)
    {
        $receiptID = $selectedRow[$i]["ReceiptID"];
        $branchID = $selectedRow[$i]["BranchID"];
        $receiptNoID = $selectedRow[$i]["ReceiptNoID"];
        $netTotal = $selectedRow[$i]["NetTotal"];
        $memberID = $selectedRow[$i]["MemberID"];
        

        $sql = "select * from dispute left join disputereason on dispute.disputereasonid = disputereason.disputereasonID where receiptID = '$receiptID' order by dispute.modifiedDate desc limit 1";
        $selectedRow2 = getSelectedRow($sql);
        $refundAmount = $selectedRow2[0]["RefundAmount"];
        $detail = $selectedRow2[0]["Detail"];
        $phoneNo = $selectedRow2[0]["PhoneNo"];
        $disputeReasonID = $selectedRow2[0]["DisputeReasonID"];
        $disputeReason = $selectedRow2[0]["Text"];
        if($i == 0)
        {
            echo "<input type='hidden' id='selectedReceiptID' name='selectedReceiptID' value='$receiptID'/>";
            echo "<input type='hidden' id='selectedBranchID' name='selectedBranchID' value='$branchID'/>";
            echo "<input type='hidden' id='selectedReceiptNoID' name='selectedReceiptNoID' value='$receiptNoID'/>";
            echo "<input type='hidden' id='selectedNetTotal' name='selectedNetTotal' value='$netTotal'/>";
            echo "<input type='hidden' id='selectedRefundAmount' name='selectedRefundAmount' value='$refundAmount'/>";
            echo "<input type='hidden' id='selectedDetail' name='selectedDetail' value='$detail'/>";
            echo "<input type='hidden' id='selectedPhoneNo' name='selectedPhoneNo' value='$phoneNo'/>";
            echo "<input type='hidden' id='selectedDisputeReasonID' name='selectedDisputeReasonID' value='$disputeReasonID'/>";
            echo "<input type='hidden' id='selectedDisputeReason' name='selectedDisputeReason' value='$disputeReason'/>";
            echo "<input type='hidden' id='selectedMemberID' name='selectedMemberID' value='$memberID'/>";
            
            
            $refundAmountFirstRow = $refundAmount;
            $detailFirstRow = $detail;
            
            
            $phoneNoFirstRow = $phoneNo;
            $disputeReasonFirstRow = $disputeReason;
        }
        echo "<input type='hidden' id='receiptID" . $i . "' value='$receiptID'/>";
        echo "<input type='hidden' id='branchID" . $i . "' value='$branchID'/>";
        echo "<input type='hidden' id='receiptNoID" . $i . "' value='$receiptNoID'/>";
        echo "<input type='hidden' id='netTotal" . $i . "' value='$netTotal'/>";
        echo "<input type='hidden' id='refundAmount" . $i . "' value='$refundAmount'/>";
        echo "<input type='hidden' id='detail" . $i . "' value='$detail'/>";
        echo "<input type='hidden' id='phoneNo" . $i . "' value='$phoneNo'/>";
        echo "<input type='hidden' id='disputeReasonID" . $i . "' value='$disputeReasonID'/>";
        echo "<input type='hidden' id='disputeReason" . $i . "' value='$disputeReason'/>";
        echo "<input type='hidden' id='memberID" . $i . "' value='$memberID'/>";
    }
    mysqli_close($con);
    
?>
</div>
<?php
    if(sizeof($selectedRow) > 0)
    {
        $refundAmount = $refundAmountFirstRow;
        $detail = $detailFirstRow;
        
    
        $phoneNo = $phoneNoFirstRow;
        $disputeReason = $disputeReasonFirstRow;
    }
    else
    {
        $refundAmount = "";
        $detail = "";
        
        
        $phoneNo = "";
        $disputeReason = "";
    }

    echo "<tr><td>เหตุผล: </td><td><input type='text' id='reason' name='reason' value='$disputeReason' disabled/></td></tr>";
    echo "<tr><td>จำนวนเงินที่ขอคืน: </td><td><input type='text' id='refundAmount' name='refundAmount' value='$refundAmount'/></td></tr>";
    echo "<tr><td>รายละเอียดเหตุผล: </td><td><textarea id='detail' name='detail' cols='20' rows='5'>$detail</textarea></td></tr>";
    echo "<tr><td>เบอร์โทร: </td><td><input type='text' id='phoneNo' name='phoneNo' value='$phoneNo'/></td></tr></table>";
?>
<div>
<button type="submit">Submit</button></div>
</form>
</body> </html>
