<!DOCTYPE html> <html>
<head>
<title>GB Prime Pay</title>
<script type="text/javascript">

function selectChanged(obj)
{
    var arrText = obj.value.split(" ");
    var index = arrText[1];
    
    var colBranchID = "branchID";
    var colReceiptID = "receiptID";
    var colReceiptNoID = "receiptNoID";
    var colNetTotal = "netTotal";
    var colDeviceToken = "deviceToken";
    var colMemberID = "memberID";
    var branchID = document.getElementById(colBranchID.concat(index)).value;
    var receiptID = document.getElementById(colReceiptID.concat(index)).value;
    var receiptNoID = document.getElementById(colReceiptNoID.concat(index)).value;
    var netTotal = document.getElementById(colNetTotal.concat(index)).value;
    var deviceToken = document.getElementById(colDeviceToken.concat(index)).value;
    var memberID = document.getElementById(colMemberID.concat(index)).value;
    
    
    document.getElementById("selectedBranchID").value = branchID;
    document.getElementById("selectedReceiptID").value = receiptID;
    document.getElementById("selectedReceiptNoID").value = receiptNoID;
    document.getElementById("selectedNetTotal").value = netTotal;
    document.getElementById("selectedDeviceToken").value = deviceToken;
    document.getElementById("selectedMemberID").value = memberID;
}
</script>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
</head>
<body>
<div>Merchant Payment</div> <div>
<form action="MobileBankingPayment.php" method="post">
<div>
<label>Order date time: </label>
<select onchange="selectChanged(this)">
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
        writeToGbpLog("file: " . basename(__FILE__) . ", user: " . $_POST["modifiedUser"]);
        
        $receiptID = $_POST["selectedReceiptID"];
        $branchID = $_POST["selectedBranchID"];
        $netTotal = $_POST["selectedNetTotal"];
        $userDeviceToken = $_POST["selectedDeviceToken"];
        $receiptNoID = $_POST["selectedReceiptNoID"];
        $memberID = $_POST["selectedMemberID"];
        $amount = $netTotal;
        
        $sql = "select * from $jummumOM.branch where branchID = '$branchID';";
        $selectedRow = getSelectedRow($sql);
        $dbName = $selectedRow[0]["DbName"];
        {
            $gbpReferenceNo = "gbp" . $receiptNoID;
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
    //        mysqli_commit($con);
            
            
            
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
        ////******
        
            
            //****************send noti to shop (turn on light)
            //alarmShop
            //query statement
    //        if($methodType == 2)
            {
                $ledStatus = 1;
//                $sql = "update $jummumOM.Branch set LedStatus = '$ledStatus', ModifiedUser = '$modifiedUser', ModifiedDate = '$modifiedDate' where branchID = '$branchID';";
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
//        mysqli_close($con);
        writeToGbpLog("end of background");
    }

    $sql = "SELECT ReceiptID, BranchID, ReceiptDate, NetTotal, ReceiptNoID, MemberID from receipt where status = 1 ORDER by receiptdate desc limit 5";
    $selectedRow = getSelectedRow($sql);
    for($i=0; $i<sizeof($selectedRow); $i++)//แสดง option to pay
    {
        $receiptDate = $selectedRow[$i]["ReceiptDate"];
        $netTotal = $selectedRow[$i]["NetTotal"];
        echo "<option value='running " . $i . "'>".($i+1).". $receiptDate" . " / " . "$netTotal</option>";
    }
?>
</select>
<?php
    for($i=0; $i<sizeof($selectedRow); $i++)
    {
        $receiptID = $selectedRow[$i]["ReceiptID"];
        $branchID = $selectedRow[$i]["BranchID"];
        $receiptNoID = $selectedRow[$i]["ReceiptNoID"];
        $netTotal = $selectedRow[$i]["NetTotal"];
        $memberID = $selectedRow[$i]["MemberID"];
        
        $sql = "select login.DeviceToken from receipt left join userAccount on receipt.memberID = userAccount.userAccountID left join login on userAccount.username = login.username where login.status = 1 and receiptID = '$receiptID' order by login.modifiedDate desc limit 1";
        $selectedRow2 = getSelectedRow($sql);
        $deviceToken = $selectedRow2[0]["DeviceToken"];
        if($i == 0)
        {
            echo "<input type='hidden' id='selectedReceiptID' name='selectedReceiptID' value='$receiptID'/>";
            echo "<input type='hidden' id='selectedBranchID' name='selectedBranchID' value='$branchID'/>";
            echo "<input type='hidden' id='selectedReceiptNoID' name='selectedReceiptNoID' value='$receiptNoID'/>";
            echo "<input type='hidden' id='selectedNetTotal' name='selectedNetTotal' value='$netTotal'/>";
            echo "<input type='hidden' id='selectedDeviceToken' name='selectedDeviceToken' value='$deviceToken'/>";
            echo "<input type='hidden' id='selectedMemberID' name='selectedMemberID' value='$memberID'/>";
        }
        echo "<input type='hidden' id='receiptID" . $i . "' value='$receiptID'/>";
        echo "<input type='hidden' id='branchID" . $i . "' value='$branchID'/>";
        echo "<input type='hidden' id='receiptNoID" . $i . "' value='$receiptNoID'/>";
        echo "<input type='hidden' id='netTotal" . $i . "' value='$netTotal'/>";
        echo "<input type='hidden' id='deviceToken" . $i . "' value='$deviceToken'/>";
        echo "<input type='hidden' id='memberID" . $i . "' value='$memberID'/>";
    }
    
?>
</div>
<div>
<button type="submit">Pay</button></div>
</form>
</body> </html>
