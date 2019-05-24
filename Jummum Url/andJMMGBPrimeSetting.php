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
    
    
    //noOfItem
    $sql = "select count(*) NoOfItem from orderTaking where receiptID = '$receiptID'";
    $selectedRow = getSelectedRow($sql);
    $noOfItem = $selectedRow[0]["NoOfItem"];
    
    
    //receipt 3
    $referenceDate = date("ymd");
    $sql = "select '$noOfItem' NoOfItem,`ReceiptID`, `BranchID`, `CustomerTableID`, `MemberID`, `TotalAmount`, `CreditCardType`, `CreditCardNo`, `CreditCardAmount`, `Remark`,`SpecialPriceDiscount`,DiscountProgramType,DiscountProgramTitle,DiscountProgramValue, `DiscountType`, `DiscountValue`, `ServiceChargePercent`, `ServiceChargeValue`, `PriceIncludeVat`, `VatPercent`, `VatValue`,NetTotal,LuckyDrawCount,BeforeVat, `Status`, `ReceiptNoID`, concat($referenceDate,ReceiptNoID) ReferenceNo, `ReceiptDate`, `SendToKitchenDate`, `DeliveredDate`, `BuffetReceiptID`,HasBuffetMenu,TimeToOrder,BuffetEnded,BuffetEndedDate, `VoucherCode`, case `Status` when 2 then 'Order sent' when 5 then 'Processing...' when 6 then 'Delivered' when 7 then 'Pending cancel' when 8 then 'Order dispute in process' when 9 then 'Order cancelled' when 10 then 'Order dispute finished' when 11 then 'Negotiate' when 12 then 'Review dispute' when 13 then 'Review dispute in process' when 14 then 'Order dispute finished' end as StatusText from receipt where receiptID = '$receiptID';";
    $selectedRow = getSelectedRow($sql);
    $branchID = $selectedRow[0]["BranchID"];
    $arrReceipt = executeQueryArray($sql);
    
    
    //branch, customerTable, orderTaking
    for($i=0; $i<sizeof($arrReceipt); $i++)
    {
        $customerTableID = $arrReceipt[$i]->CustomerTableID;
        $branchID = $arrReceipt[$i]->BranchID;
        $receiptID = $arrReceipt[$i]->ReceiptID;
        
        
        //branch
        $sql2 = "select DbName, `BranchID`, `Name`, `TakeAwayFee`, `ServiceChargePercent`, `PercentVat`, `PriceIncludeVat`, `ImageUrl` from $jummumOM.branch where branchID = '$branchID'";
        $arrBranch = executeQueryArray($sql2);
        $arrReceipt[$i]->Branch = $arrBranch;
        $eachDbName = $arrBranch[0]->DbName;
        unset($arrBranch[0]->DbName);
        
        
        //CustomerTable
        $sql2 = "select $branchID as BranchID, `CustomerTableID`, `TableName`, `Zone` from $eachDbName.CustomerTable where CustomerTableID = '$customerTableID'";
        $arrCustomerTable = executeQueryArray($sql2);
        $arrReceipt[$i]->CustomerTable = $arrCustomerTable;
        
        
        //OrderTaking
        $sql = "select `BranchID`, `CustomerTableID`, `ReceiptID`, sum(Quantity) Quantity, TakeAway, TakeAwayPrice, ordertaking.`MenuID`, NoteIDListInText, NotePrice, sum(`SpecialPrice`)SpecialPrice, sum(DiscountValue) DiscountValue from OrderTaking left join $eachDbName.menu on ordertaking.MenuID =  $eachDbName.menu.menuID LEFT JOIN  $eachDbName.menutype ON menuType.menuTypeID =  menu.menuTypeID where receiptID = '$receiptID' GROUP by `BranchID`, `CustomerTableID`,`ReceiptID`,takeAway, menuType.MenuTypeID,  menu.MenuID, ordertaking.`MenuID`, noteIDListInText order by takeAway,  menuType.orderNo,  menu.orderNo, noteIDListInText";
        $arrOrderTaking = executeQueryArray($sql);
        $arrReceipt[$i]->OrderTaking = $arrOrderTaking;
        
        
        //Menu
        for($j=0; $j<sizeof($arrOrderTaking); $j++)
        {
            $menuID = $arrOrderTaking[$j]->MenuID;
            $branchID = $arrOrderTaking[$j]->BranchID;
            $sql3 = "select * from $jummumOM.branch where branchID = '$branchID'";
            $selectedRow3 = getSelectedRow($sql3);
            $eachDbName = $selectedRow3[0]["DbName"];
            $mainBranchID = $selectedRow3[0]["MainBranchID"];
            if($branchID != $mainBranchID)
            {
                $sql3 = "select * from $jummumOM.branch where branchID = '$mainBranchID'";
                $selectedRow3 = getSelectedRow($sql3);
                $eachDbName = $selectedRow3[0]["DbName"];
            }
            
            
            //Menu
            $sql3 = "select '$branchID' BranchID, menu.MenuID, `MenuCode`, `TitleThai`, `Price`, `MenuTypeID`, `BuffetMenu`, `BelongToMenuID`, `TimeToOrder`, `ImageUrl`, `OrderNo`, ifnull(specialPriceProgram.SpecialPrice,menu.price) SpecialPrice from $eachDbName.Menu LEFT JOIN $eachDbName.specialPriceProgram ON menu.menuID = specialPriceProgram.menuID AND date_format(now(),'%Y-%m-%d') between date_format(specialPriceProgram.startDate,'%Y-%m-%d') and date_format(specialPriceProgram.endDate,'%Y-%m-%d') where menu.menuID = '$menuID'";
            $arrMenu = executeQueryArray($sql3);
            $arrOrderTaking[$j]->Menu = $arrMenu;
            
            
            //Note
            if($arrOrderTaking[$j]->NoteIDListInText == "")
            {
                $noteIDListInText = 0;
            }
            else
            {
                $noteIDListInText = $arrOrderTaking[$j]->NoteIDListInText;
            }
            $sql3 = "select `NoteID`, Note.`Name`, Note.`NameEn`, `Price`, Note.`NoteTypeID`, `Type` from $eachDbName.Note left join $eachDbName.NoteType on Note.NoteTypeID = NoteType.NoteTypeID where noteID in ($noteIDListInText) order by NoteType.OrderNo, Note.OrderNo;";
            $arrNote = executeQueryArray($sql3);
            $arrOrderTaking[$j]->Note = $arrNote;
        }
    }
    
    
    
    //creditCardAndOrderSummary 2
    //calculate value
//    $sql = "select * from $jummumOM.branch where branchID = '$branchID'";
//    $selectedRow = getSelectedRow($sql);
    $priceIncludeVat = $selectedRow[0]["PriceIncludeVat"];
    $percentVat = $selectedRow[0]["VatPercent"];
    $serviceChargePercent = $selectedRow[0]["ServiceChargePercent"];
    $totalAmount = $selectedRow[0]["TotalAmount"];
    $specialPriceDiscount = $selectedRow[0]["SpecialPriceDiscount"];
    $discountProgramValue = $selectedRow[0]["DiscountProgramValue"];
    $discountPromoCodeValue = $selectedRow[0]["DiscountValue"];
    $showVoucherListButton = 0;
    
    //price after discount
    $sumSpecialPrice = $totalAmount - $specialPriceDiscount;
    $afterDiscount = ($sumSpecialPrice - $discountProgramValue - $discountPromoCodeValue);
    $afterDiscount = $afterDiscount < 0?0:$afterDiscount;
    $afterDiscount = round($afterDiscount*100)/100;
    
    
    //price before vat(before service)
    $priceBeforeVat = $afterDiscount;
    if($priceIncludeVat)
    {
        $priceBeforeVat = $afterDiscount / (($percentVat+100)*0.01);
        $priceBeforeVat = round($priceBeforeVat*100)/100;
    }
    
    //service charge
    $serviceChargeValue = $selectedRow[0]["ServiceChargeValue"];
    //vat
    $vatValue = $selectedRow[0]["VatValue"];

    //net total
    $netTotal = $selectedRow[0]["NetTotal"];

    //luckyDrawSpend
    $luckyDrawCount = $selectedRow[0]["LuckyDrawCount"];

    //beforeVat after service
    $beforeVat = $selectedRow[0]["BeforeVat"];
    
    $voucherCode = $selectedRow[0]["VoucherCode"];
    $buffetReceiptID = $selectedRow[0]["BuffetReceiptID"];
    $hasBuffetMenu = $selectedRow[0]["HasBuffetMenu"];
    $showQRToPay = 0;
    
    //title
    $noOfItem = $selectedRow[0]["NoOfItem"];
    $discountProgramTitle = $selectedRow[0]["DiscountProgramTitle"];
    $priceIncludeVat = $priceIncludeVat;
    $serviceChargePercent = $serviceChargePercent;
    $percentVat = $percentVat;
    
    
    $specialPriceDiscountTitle = "ส่วนลด";
    $afterDiscountTitle = $priceIncludeVat?"ยอดรวม (รวม Vat)":"ยอดรวม";
    $luckyDrawTitle = $luckyDrawCount > 0?"(คุณได้สิทธิ์ลุ้นรางวัล $luckyDrawCount ครั้ง)":"(คุณไม่ได้รับสิทธิ์ลุ้นรางวัลในครั้งนี้)";
    $discountPromoCodeTitle = "คูปองส่วนลด $voucherCode";
    
    
    //
    //---------------------------

    //show item
    $showTotalAmount = 1;
    $showSpecialPriceDiscount = $specialPriceDiscount > 0?1:0;
    $showDiscountProgram = $discountProgramValue > 0?1:0;
    $showAfterDiscount = $afterDiscount > 0?1:0;
    $applyVoucherCode = $voucherCode != "";
    $showServiceCharge = $serviceChargePercent > 0?1:0;
    $showVat = $percentVat > 0?1:0;
    $showNetTotal = $serviceChargePercent + $percentVat > 0?1:0;
    $showLuckyDrawCount = $luckyDrawCount > 0?1:0;
    $showBeforeVat = ($showServiceCharge && $showVat) || ($serviceChargePercent == 0 && $percentVat > 0 && $priceIncludeVat)?1:0;
    
    
    //buffetButton
    $showPayBuffetButton = 1;
//    $showPayBuffetButton = 2;//0=not show,1=pay,2=order obuffet
//    for($i=0; $i<sizeof($arrOrderTaking); $i++)
//    {
//        $menuID = $arrOrderTaking[$i]["menuID"];
//        $sql = "select AlacarteMenu from $dbName.Menu where menuID = '$menuID'";
//        $selectedRow = getSelectedRow($sql);
//        $alacarteMenu = $selectedRow[0]["AlacarteMenu"];
//        if($alacarteMenu)
//        {
//            $showPayBuffetButton = 1;
//        }
//    }
//    $showPayBuffetButton = $applyVoucherCode?1:$showPayBuffetButton;
//    $showPayBuffetButton = $showPayBuffetButton && ($netTotal > 0)?1:$showPayBuffetButton;
//    $showPayBuffetButton = sizeof($arrOrderTaking) > 0?$showPayBuffetButton:0;
    

    //*********
    
    
    
    $sql = "select '$totalAmount' TotalAmount, '$specialPriceDiscount' SpecialPriceDiscount, '$discountProgramValue' DiscountProgramValue, '$discountPromoCodeValue' DiscountPromoCodeValue, '$showVoucherListButton' ShowVoucherListButton, '$afterDiscount' AfterDiscount, '$serviceChargeValue' ServiceChargeValue, '$vatValue' VatValue, '$netTotal' NetTotal, '$luckyDrawCount' LuckyDrawCount, '$beforeVat' BeforeVat, '$showTotalAmount' ShowTotalAmount, '$showSpecialPriceDiscount' ShowSpecialPriceDiscount, '$showDiscountProgram' ShowDiscountProgram, '$applyVoucherCode' ApplyVoucherCode, '$showAfterDiscount' ShowAfterDiscount, '$showServiceCharge' ShowServiceCharge, '$showVat' ShowVat, '$showNetTotal' ShowNetTotal, '$showLuckyDrawCount' ShowLuckyDrawCount, '$showBeforeVat' ShowBeforeVat, '$showPayBuffetButton' ShowPayBuffetButton, '$noOfItem' NoOfItem, '$discountProgramTitle' DiscountProgramTitle, '$priceIncludeVat' PriceIncludeVat, '$serviceChargePercent' ServiceChargePercent, '$percentVat' PercentVat, '$specialPriceDiscountTitle' SpecialPriceDiscountTitle, '$afterDiscountTitle' AfterDiscountTitle, '$luckyDrawTitle' LuckyDrawTitle, '$discountPromoCodeTitle' DiscountPromoCodeTitle;";
    $arrCreditCardAndOrderSummary = executeQueryArray($sql);
    
    
    
    //luckyDraw 4
    $sql = "select * from setting where keyName = 'LuckyDrawTimeLimit';";
    $selectedRow = getSelectedRow($sql);
    $luckyDrawTimeLimit = $selectedRow[0]["Value"];
    $sql = "select count(*) LuckyDrawCount from luckyDrawTicket left join receipt on luckyDrawTicket.receiptID = receipt.receiptID where luckyDrawTicket.memberID = '$memberID' and receipt.branchID = '$branchID' and rewardRedemptionID = -1 and TIME_TO_SEC(timediff('$currentDateTime', luckyDrawTicket.modifiedDate)) <= '$luckyDrawTimeLimit';";
    $arrLuckyDrawTicket = executeQueryArray($sql);
    
    
    
    
    //buffet 5
    $buffetList = array();
    $thankYouText = $showPayBuffetButton==1?"ชำระเงินสำเร็จ":"สั่งบุฟเฟ่ต์สำเร็จ";//2="สั่งบุฟเฟ่ต์สำเร็จ"
    $showOrderBuffetButton = $hasBuffetMenu || $buffetReceiptID;
    $buffetReceiptID = $hasBuffetMenu?$receiptID:$buffetReceiptID;
    array_push($buffetList,array("ShowQRToPay"=>$showQRToPay, "ThankYouText"=>$thankYouText, "ShowOrderBuffetButton"=>$showOrderBuffetButton, "BuffetReceiptID"=>$buffetReceiptID));
    
    
    //$arrGBPrimeSetting 6
    $sql = "select (select VALUE from setting where keyName = 'GBPrimeQRPostUrl') GBPrimeQRPostUrl,(select VALUE from setting where keyName = 'GBPrimeQRToken') GBPrimeQRToken,(select VALUE from setting where keyName = 'ResponseUrl') ResponseUrl,(select VALUE from setting where keyName = 'BackgroundUrl') BackgroundUrl;";
    $arrGBPrimeSetting = executeQueryArray($sql);
    
    
    
    
    $dataList = array();
    $sql = "select 0 from dual where 0;";
    $arrOrderTaking = executeQueryArray($sql);
    $sql = "select 0 from dual where 0;";
    $arrOrderNote = executeQueryArray($sql);
    
    
    $dataList[] = $arrOrderTaking;
    $dataList[] = $arrOrderNote;
    
    
//    $sql .= "select 0 from dual where 0;";
//    $jsonEncode = executeMultiQueryArray($sql);
    $dataList[] = $arrCreditCardAndOrderSummary;
    $dataList[] = $arrReceipt;
    $dataList[] = $arrLuckyDrawTicket;
    $dataList[] = $buffetList;
    $dataList[] = $arrGBPrimeSetting;
    
    
    
    /* execute multi query */
    $jsonEncode = executeMultiQueryArray($sql);
    $response = array('success' => true, 'data' => $dataList, 'error' => null, 'status' => 1);
    echo json_encode($response);


    
    // Close connections
    mysqli_close($con);
    
?>
