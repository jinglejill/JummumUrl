<?php
    include_once("dbConnect.php");
    setConnectionValue("");
    writeToLog("file: " . basename(__FILE__) . ", user: " . $_POST["modifiedUser"]);
    printAllPost();
    ini_set("memory_limit","-1");
    
    
    if(isset($_POST["receiptID"]))
    {
        $receiptID = $_POST["receiptID"];
    }
    
    
    
    // Check connection
    if (mysqli_connect_errno())
    {
        echo "Failed to connect to MySQL: " . mysqli_connect_error();
    }
    $sql = "select 0 from dual where 0;";
    $sql .= "select 0 from dual where 0;";
    $sql .= "select 0 from dual where 0;";
    $sql .= "select `ReceiptID`, `BranchID`, `CustomerTableID`, `MemberID`, `TotalAmount`, `CreditCardType`, `CreditCardNo`, `CreditCardAmount`, `Remark`,`SpecialPriceDiscount`,DiscountProgramType,DiscountProgramTitle,DiscountProgramValue, `DiscountType`, `DiscountValue`, `ServiceChargePercent`, `ServiceChargeValue`, `PriceIncludeVat`, `VatPercent`, `VatValue`,NetTotal,LuckyDrawCount,BeforeVat, `Status`, `ReceiptNoID`, `ReceiptDate`, `SendToKitchenDate`, `DeliveredDate`, `BuffetReceiptID`,HasBuffetMenu,TimeToOrder,BuffetEnded,BuffetEndedDate, `VoucherCode`, case `Status` when 2 then 'Order sent' when 5 then 'Processing...' when 6 then 'Delivered' when 7 then 'Pending cancel' when 8 then 'Order dispute in process' when 9 then 'Order cancelled' when 10 then 'Order dispute finished' when 11 then 'Negotiate' when 12 then 'Review dispute' when 13 then 'Review dispute in process' when 14 then 'Order dispute finished' end as StatusText from receipt where receiptID = '$receiptID';";
    $selectedRow = getSelectedRow($sql);
    $branchID = $selectedRow[0]["BranchID"];
    $memberID = $selectedRow[0]["MemberID"];
    $buffetReceiptID = $selectedRow[0]["BuffetReceiptID"];
    

    $currentDateTime = date('Y-m-d H:i:s');
    $sql2 = "select * from setting where keyName = 'LuckyDrawTimeLimit';";
    $selectedRow = getSelectedRow($sql2);
    $luckyDrawTimeLimit = $selectedRow[0]["Value"];
    
    
    $sql .= "select count(*) LuckyDrawCount from luckyDrawTicket left join receipt on luckyDrawTicket.receiptID = receipt.receiptID where luckyDrawTicket.memberID = '$memberID' and receipt.branchID = '$branchID' and rewardRedemptionID = -1 and TIME_TO_SEC(timediff('$currentDateTime', luckyDrawTicket.modifiedDate)) <= '$luckyDrawTimeLimit';";


    /* execute multi query */
    $jsonEncode = executeMultiQueryArray($sql);


    //get dbName
    $sql = "select * from $jummumOM.branch where branchID = '$branchID'";
    $selectedRow = getSelectedRow($sql);
    $dbName = $selectedRow[0]["DbName"];


    //has buffet menu
    $hasBuffetMenu = 0;
    $sql = "select * from orderTaking where receiptID = '$receiptID'";
    $selectedRow = getSelectedRow($sql);
    $arrOrderTaking = $selectedRow;
    for($i=0; $i<sizeof($arrOrderTaking); $i++)
    {
        $menuID = $arrOrderTaking[$i]["MenuID"];
        $sql = "select * from $dbName.Menu where menuID = '$menuID'";
        $selectedRow = getSelectedRow($sql);
        $buffetMenu = $selectedRow[0]["BuffetMenu"];
        if($buffetMenu)
        {
            $hasBuffetMenu = 1;
            break;
        }
    }



    $showQRToPay = 0;
    $buffetList = array();
    $thankYouText = "ชำระเงินสำเร็จ";
    $showOrderBuffetButton = $hasBuffetMenu || $buffetReceiptID;
    array_push($buffetList,array("ShowQRToPay"=>$showQRToPay, "ThankYouText"=>$thankYouText, "ShowOrderBuffetButton"=>$showOrderBuffetButton, "BuffetReceiptID"=>$buffetReceiptID));
    $jsonEncode[] = $buffetList;
    $jsonEncode[] = array();
    $response = array('success' => true, 'data' => $jsonEncode, 'error' => null, 'status' => 1);
    echo json_encode($response);



    // Close connections
    mysqli_close($con);
    
    
?>
