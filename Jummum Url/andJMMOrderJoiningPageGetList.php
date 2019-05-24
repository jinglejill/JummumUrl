<?php
    include_once("dbConnect.php");
    setConnectionValue($jummum);
    writeToLog("file: " . basename(__FILE__) . ", user: " . $_POST["modifiedUser"]);
    printAllPost();
    ini_set("memory_limit","-1");
    
    
    if(isset($_POST["memberID"]) && isset($_POST["page"]) && isset($_POST["perPage"]))
    {
        $memberID = $_POST["memberID"];
        $page = $_POST["page"];
        $perPage = $_POST["perPage"];
    }
   
   
    // Check connection
    if (mysqli_connect_errno())
    {
        echo "Failed to connect to MySQL: " . mysqli_connect_error();
    }
    
    $sql = "select `ReceiptID`, `BranchID`, `CustomerTableID`, `MemberID`, `TotalAmount`, `CreditCardType`, `CreditCardNo`, `CreditCardAmount`, `Remark`,`SpecialPriceDiscount`,DiscountProgramType,DiscountProgramTitle,DiscountProgramValue, `DiscountType`, `DiscountValue`, `ServiceChargePercent`, `ServiceChargeValue`, `PriceIncludeVat`, `VatPercent`, `VatValue`,NetTotal,LuckyDrawCount,BeforeVat, `Status`, `ReceiptNoID`, `ReceiptDate`, `SendToKitchenDate`, `DeliveredDate`, `BuffetReceiptID`,HasBuffetMenu,TimeToOrder,BuffetEnded,BuffetEndedDate, `VoucherCode`, case `Status` when 1 then 'Waiting payment' when 2 then 'Order sent' when 5 then 'Processing...' when 6 then 'Delivered' when 7 then 'Pending cancel' when 8 then 'Order dispute in process' when 9 then 'Order cancelled' when 10 then 'Order dispute finished' when 11 then 'Negotiate' when 12 then 'Review dispute' when 13 then 'Review dispute in process' when 14 then 'Order dispute finished' end as StatusText from (select (@row_number:=@row_number + 1) AS Num, a.* from (select receipt.* from orderJoining left join receipt on orderJoining.receiptID = receipt.receiptID where orderJoining.memberID = $memberID order by ReceiptDate desc)a, (SELECT @row_number:=0) AS t)b where Num > $perPage*($page-1) limit $perPage;";
    
    $arrReceipt = executeQueryArray($sql);

    
    for($i=0; $i<sizeof($arrReceipt); $i++)
    {
        $customerTableID = $arrReceipt[$i]->CustomerTableID;
        $branchID = $arrReceipt[$i]->BranchID;
        $receiptID = $arrReceipt[$i]->ReceiptID;
        
        
        
        //branch
        $sql2 = "select DbName, `BranchID`, `Name`, `TakeAwayFee`, `ImageUrl` from $jummumOM.branch where branchID = '$branchID'";
        $arrBranch = executeQueryArray($sql2);
        $arrReceipt[$i]->Branch = $arrBranch;
        $eachDbName = $arrBranch[0]->DbName;
        unset($arrBranch[0]->DbName);
        //luckyDraw
        $sql = "select * from $eachDbName.setting where keyName = 'luckyDrawSpend'";
        $selectedRow = getSelectedRow($sql);
        $luckyDrawSpend = $selectedRow[0]["Value"];
        $luckyDrawSpend = $luckyDrawSpend?$luckyDrawSpend:0;
        $arrBranch[0]->LuckyDrawSpend = $luckyDrawSpend;

        //note word เพิ่ม
        $sql = "select * from $eachDbName.setting where keyName = 'wordAdd'";
        $selectedRow = getSelectedRow($sql);
        $wordAdd = $selectedRow[0]["Value"];
        $wordAdd = $wordAdd?$wordAdd:"เพิ่ม";
        $arrBranch[0]->WordAdd = $wordAdd;

        //note word ไม่ใส่
        $sql = "select * from $eachDbName.setting where keyName = 'wordNo'";
        $selectedRow = getSelectedRow($sql);
        $wordNo = $selectedRow[0]["Value"];
        $wordNo = $wordNo?$wordNo:"ไม่ใส่";
        $arrBranch[0]->WordNo = $wordNo;
        
        
        //CustomerTable
        $sql2 = "select $branchID as BranchID, `CustomerTableID`, `TableName`, `Zone` from $eachDbName.CustomerTable where CustomerTableID = '$customerTableID'";
        $arrCustomerTable = executeQueryArray($sql2);
        $arrReceipt[$i]->CustomerTable = $arrCustomerTable;

 
        //OrderTaking
        $sql = "select `BranchID`, `CustomerTableID`, `ReceiptID`, sum(Quantity) Quantity, TakeAway, sum(TakeAwayPrice) TakeAwayPrice, ordertaking.`MenuID`, NoteIDListInText, sum(NotePrice) NotePrice, sum(`SpecialPrice`)SpecialPrice, sum(DiscountValue) DiscountValue from OrderTaking left join $eachDbName.menu on ordertaking.MenuID = menu.menuID LEFT JOIN  $eachDbName.menutype ON menuType.menuTypeID =  menu.menuTypeID where receiptID = '$receiptID' GROUP by `BranchID`, `CustomerTableID`,`ReceiptID`,takeAway, menuType.MenuTypeID,  menu.MenuID, noteIDListInText order by takeAway, menuType.orderNo,  menu.orderNo, noteIDListInText";
        $arrOrderTaking = executeQueryArray($sql);
        $arrReceipt[$i]->OrderTaking = $arrOrderTaking;
        $noOfItem = 0;
        for($j=0; $j<sizeof($arrOrderTaking); $j++)
        {
            $noOfItem += $arrOrderTaking[$j]->Quantity;
        }
        
        
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
            $currentDateTime = date("Y-m-d H:i:s");
            $sql3 = "select '$branchID' BranchID, menu.MenuID, `MenuCode`, `TitleThai`, `Price`, `MenuTypeID`, `BuffetMenu`, `AlacarteMenu`, `TimeToOrder`, `ImageUrl`, `OrderNo` from $eachDbName.Menu where menu.menuID = '$menuID'";
            $arrMenu = executeQueryArray($sql3);
            for($k=0; $k<sizeof($arrMenu); $k++)
            {
                $menuID = $arrMenu[$k]->MenuID;
                $sql = "select * from $dbName.SpecialPriceProgram left join $dbName.SpecialPriceProgramDay on specialPriceProgram.specialPriceProgramID = specialPriceProgramDay.specialPriceProgramID and specialPriceProgramDay.Day = weekday('$currentDateTime')+1 where menuID = '$menuID' AND '$currentDateTime' between startDate and endDate and specialPriceProgramDayID is not null order by StartDate desc, EndDate desc, SpecialPriceProgram.ModifiedDate desc";
                $selectedRow = getSelectedRow($sql);
                if(sizeof($selectedRow)>0)
                {
                    $arrMenu[$k]->SpecialPrice = $selectedRow[0]["SpecialPrice"];
                }
                else
                {
                    $arrMenu[$k]->SpecialPrice = $arrMenu[$k]->Price;
                }
            }
            
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
        
        
        //hasBuffetMenu
        $receiptDate = $arrReceipt[$i]->ReceiptDate;
        $hasBuffetMenu = $arrReceipt[$i]->HasBuffetMenu;
        $timeToOrder = $arrReceipt[$i]->TimeToOrder;
        $status = $arrReceipt[$i]->Status;
        $buffetEnded = $arrReceipt[$i]->BuffetEnded;
        
        if($timeToOrder > 0)
        {
            $seconds = time()-strtotime($receiptDate);
            $timeToCountDown = $timeToOrder - $seconds >= 0?$timeToOrder - $seconds:0;
        }
        else
        {
            $timeToCountDown = 0;
        }
        
        
        $arrReceipt[$i]->HasBuffetMenu = $hasBuffetMenu;
        $arrReceipt[$i]->TimeToOrder = $timeToOrder;
        $arrReceipt[$i]->TimeToCountDown = $timeToCountDown;
        $receiptStatusValid = ($status == 2) || ($status == 5) || ($status == 6);
        $arrReceipt[$i]->ShowOrderBuffetButton = $receiptStatusValid && $hasBuffetMenu && $timeToCountDown && !$buffetEnded;
        
        
        //cancel button
        //dispute button
        $showCancelDisputeButton = 0;
        $receiptStatus = intval($arrReceipt[$i]->Status);
        if($receiptStatus == 2)
        {
            $showCancelDisputeButton = 1;
        }
        else if($receiptStatus == 5)
        {
            $showCancelDisputeButton = 2;
        }
        else if($receiptStatus == 6)
        {
            $showCancelDisputeButton = 2;
        }
        $arrReceipt[$i]->ShowCancelDisputeButton = $showCancelDisputeButton;
        
        
        //order summary
        //calculate value
        $priceIncludeVat = $arrReceipt[$i]->PriceIncludeVat;
        $percentVat = $arrReceipt[$i]->VatPercent;
        $serviceChargePercent = $arrReceipt[$i]->ServiceChargePercent;
        
        //totalAmount
        $totalAmount = $arrReceipt[$i]->TotalAmount;

        //specialPriceDiscount
        $specialPriceDiscount = $arrReceipt[$i]->SpecialPriceDiscount;

        //discountProgram
        $discountProgramValue = $arrReceipt[$i]->DiscountProgramValue;

        //voucherCodeDiscount
        $discountPromoCodeValue = $arrReceipt[$i]->DiscountValue;

        //showVoucherListButton
        $showVoucherListButton = 0;

        //price after discount
        $afterDiscount = ($totalAmount - $specialPriceDiscount - $discountProgramValue - $discountPromoCodeValue);
        $afterDiscount = $afterDiscount < 0?0:$afterDiscount;
        $afterDiscount = round($afterDiscount*100)/100;

//        //price before vat(before service)
//        $priceBeforeVat = $afterDiscount;
//        if($priceIncludeVat)
//        {
//            $priceBeforeVat = $afterDiscount / (($percentVat+100)*0.01);
//            $priceBeforeVat = round($priceBeforeVat*100)/100;
//        }

        //service charge
        $serviceChargeValue = $arrReceipt[$i]->ServiceChargeValue;

        //vat
        $vatValue = $arrReceipt[$i]->VatValue;

        //net total
        $netTotal = $arrReceipt[$i]->NetTotal;

        //luckyDrawSpend
        $luckyDrawCount = $arrReceipt[$i]->LuckyDrawCount;

        //beforeVat after service
        $beforeVat = $arrReceipt[$i]->BeforeVat;
        //---------------------------

        //show item
        $showTotalAmount = 1;
        $showSpecialPriceDiscount = $specialPriceDiscount > 0?1:0;
        $showDiscountProgram = $discountProgramValue > 0?1:0;
        $showAfterDiscount = $afterDiscount > 0?1:0;
        $applyVoucherCode = 1;//$applyVoucherCode;
        $showServiceCharge = $serviceChargePercent > 0?1:0;
        $showVat = $percentVat > 0?1:0;
        $showNetTotal = $serviceChargePercent + $percentVat > 0?1:0;
        $showLuckyDrawCount = 1;//$luckyDrawCount > 0;
        $showBeforeVat = ($showServiceCharge && $showVat) || ($serviceChargePercent == 0 && $percentVat > 0 && $priceIncludeVat)?1:0;
        
        //buffetButton
        $showPayBuffetButton = 2;//0=not show,1=pay,2=order obuffet
        for($j=0; $j<sizeof($arrOrderTaking); $j++)
        {
            $menuID = $arrOrderTaking[$j]->menuID;
            $sql = "select AlacarteMenu from $eachDbName.Menu where menuID = '$menuID'";
            $selectedRow = getSelectedRow($sql);
            $alacarteMenu = $selectedRow[0]["AlacarteMenu"];
            if($alacarteMenu)
            {
                $showPayBuffetButton = 1;
            }
        }
        $showPayBuffetButton = $applyVoucherCode?1:$showPayBuffetButton;
        $showPayBuffetButton = $showPayBuffetButton && ($netTotal > 0)?1:$showPayBuffetButton;
        $showPayBuffetButton = sizeof($arrOrderTaking) > 0?$showPayBuffetButton:0;
        

        //title
        $noOfItem = $noOfItem;
        $discountProgramTitle = $arrReceipt[$i]->DiscountProgramTitle;
        $priceIncludeVat = $priceIncludeVat;
        $serviceChargePercent = $serviceChargePercent;
        $percentVat = $percentVat;
        
        $specialPriceDiscountTitle = "ส่วนลด";
        $afterDiscountTitle = $priceIncludeVat?"ยอดรวม (รวม Vat)":"ยอดรวม";
        $luckyDrawTitle = $luckyDrawCount > 0?"(คุณจะได้สิทธิ์ลุ้นรางวัล $luckyDrawCount ครั้ง)":"(คุณไม่ได้รับสิทธิ์ลุ้นรางวัลในครั้งนี้)";
        $discountPromoCodeTitle = "คูปองส่วนลด $voucherCode";
    //*********
    
    
    
        $sql = "select '$totalAmount' TotalAmount, '$specialPriceDiscount' SpecialPriceDiscount, '$discountProgramValue' DiscountProgramValue, '$discountPromoCodeValue' DiscountPromoCodeValue, '$showVoucherListButton' ShowVoucherListButton, '$afterDiscount' AfterDiscount, '$serviceChargeValue' ServiceChargeValue, '$vatValue' VatValue, '$netTotal' NetTotal, '$luckyDrawCount' LuckyDrawCount, '$beforeVat' BeforeVat, '$showTotalAmount' ShowTotalAmount, '$showSpecialPriceDiscount' ShowSpecialPriceDiscount, '$showDiscountProgram' ShowDiscountProgram, '$applyVoucherCode' ApplyVoucherCode, '$showAfterDiscount' ShowAfterDiscount, '$showServiceCharge' ShowServiceCharge, '$showVat' ShowVat, '$showNetTotal' ShowNetTotal, '$showLuckyDrawCount' ShowLuckyDrawCount, '$showBeforeVat' ShowBeforeVat, '$showPayBuffetButton' ShowPayBuffetButton, '$noOfItem' NoOfItem, '$discountProgramTitle' DiscountProgramTitle, '$priceIncludeVat' PriceIncludeVat, '$serviceChargePercent' ServiceChargePercent, '$percentVat' PercentVat, '$specialPriceDiscountTitle' SpecialPriceDiscountTitle, '$afterDiscountTitle' AfterDiscountTitle, '$luckyDrawTitle' LuckyDrawTitle, '$discountPromoCodeTitle' DiscountPromoCodeTitle;";
        $arrCreditCardAndOrderSummary = executeQueryArray($sql);
        $arrReceipt[$i]->OrderSummary = $arrCreditCardAndOrderSummary;
    }
    
    
    //branch
    $branchList = $jsonEncode[2];
    for($i=0; $i<sizeof($branchList); $i++)
    {
        $branch = $branchList[$i];
        //luckyDraw
        $sql = "select * from $branch->DbName.setting where keyName = 'luckyDrawSpend'";
        $selectedRow = getSelectedRow($sql);
        $luckyDrawSpend = $selectedRow[0]["Value"];
        $luckyDrawSpend = $luckyDrawSpend?$luckyDrawSpend:0;
        $branch->LuckyDrawSpend = $luckyDrawSpend;

        //note word เพิ่ม
        $sql = "select * from $branch->DbName.setting where keyName = 'wordAdd'";
        $selectedRow = getSelectedRow($sql);
        $wordAdd = $selectedRow[0]["Value"];
        $wordAdd = $wordAdd?$wordAdd:"เพิ่ม";
        $branch->WordAdd = $wordAdd;

        //note word ไม่ใส่
        $sql = "select * from $branch->DbName.setting where keyName = 'wordNo'";
        $selectedRow = getSelectedRow($sql);
        $wordNo = $selectedRow[0]["Value"];
        $wordNo = $wordNo?$wordNo:"ไม่ใส่";
        $branch->WordNo = $wordNo;
    }
    
    
    $response = array('success' => true, 'data' => $arrReceipt, 'error' => null);
    echo json_encode($response);

    
    // Close connections
    mysqli_close($con);
    
?>
