<?php
    include_once("dbConnect.php");
    setConnectionValue("");
    writeToLog("file: " . basename(__FILE__) . ", user: " . $_POST["modifiedUser"]);
    printAllPost();



    if(isset($_POST["receiptID"]))
    {
        $receiptID = $_POST["receiptID"];
    }
    
    
    // Check connection
    if (mysqli_connect_errno())
    {
        echo "Failed to connect to MySQL: " . mysqli_connect_error();
    }
    
   

    $sql = "select `ReceiptID`, `BranchID`, `CustomerTableID`, `MemberID`, `TotalAmount`, `CreditCardType`, `CreditCardNo`, `CreditCardAmount`, `Remark`,`SpecialPriceDiscount`,DiscountProgramType,DiscountProgramTitle,DiscountProgramValue, `DiscountType`, `DiscountValue`, `ServiceChargePercent`, `ServiceChargeValue`, `PriceIncludeVat`, `VatPercent`, `VatValue`,NetTotal,LuckyDrawCount,BeforeVat, `Status`, `ReceiptNoID`, `ReceiptDate`, `SendToKitchenDate`, `DeliveredDate`, `BuffetReceiptID`,HasBuffetMenu,TimeToOrder,BuffetEnded,BuffetEndedDate, `VoucherCode`, case `Status` when 1 then 'Waiting payment' when 2 then 'Order sent' when 5 then 'Processing...' when 6 then 'Delivered' when 7 then 'Pending cancel' when 8 then 'Order dispute in process' when 9 then 'Order cancelled' when 10 then 'Order dispute finished' when 11 then 'Negotiate' when 12 then 'Review dispute' when 13 then 'Review dispute in process' when 14 then 'Order dispute finished' end as StatusText, StatusRoute from receipt where receiptID = '$receiptID';";
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
        
        
        //CustomerTable
        $sql2 = "select $branchID as BranchID, `CustomerTableID`, `TableName`, `Zone` from $eachDbName.CustomerTable where CustomerTableID = '$customerTableID'";
        $arrCustomerTable = executeQueryArray($sql2);
        $arrReceipt[$i]->CustomerTable = $arrCustomerTable;

 
        //OrderTaking
        $sql = "select `BranchID`, `CustomerTableID`, `ReceiptID`, sum(Quantity) Quantity, TakeAway, sum(TakeAwayPrice) TakeAwayPrice, ordertaking.`MenuID`, NoteIDListInText, sum(NotePrice) NotePrice, sum(`SpecialPrice`)SpecialPrice, sum(DiscountValue) DiscountValue, OrderTakingID from OrderTaking left join $eachDbName.menu on ordertaking.MenuID =  menu.menuID LEFT JOIN  $eachDbName.menutype ON menuType.menuTypeID =  menu.menuTypeID where receiptID = '$receiptID' GROUP by `BranchID`, `CustomerTableID`,`ReceiptID`,takeAway, menuType.MenuTypeID,  menu.MenuID, noteIDListInText order by takeAway,  menuType.orderNo,  menu.orderNo, noteIDListInText";
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
            $orderTakingID = $arrOrderTaking[$j]->OrderTakingID;
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
            $sql3 = "select '$branchID' BranchID, menu.MenuID, `MenuCode`, `TitleThai`, `Price`, `MenuTypeID`, `BuffetMenu`, `AlacarteMenu`, `TimeToOrder`, `ImageUrl`, `OrderNo`, ifnull(specialPriceProgram.SpecialPrice,menu.price) SpecialPrice from $eachDbName.Menu LEFT JOIN $eachDbName.specialPriceProgram ON menu.menuID = specialPriceProgram.menuID AND now() between specialPriceProgram.startDate and specialPriceProgram.endDate where menu.menuID = '$menuID'";
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
            $sql3 = "select Note.`NoteID`, Note.`Name`, Note.`NameEn`, `Price`, Note.`NoteTypeID`, `Type`, Quantity from ordernote left join $eachDbName.Note on ordernote.noteid = note.noteID left join $eachDbName.NoteType on Note.NoteTypeID = NoteType.NoteTypeID where ordertakingID = '$orderTakingID' order by NoteType.OrderNo, Note.OrderNo";
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
            $sql = "select AlacarteMenu from $dbName.Menu where menuID = '$menuID'";
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
        
        
        
        
        //cancel button
        //dispute button
        $showCancelDisputeButton = 0;
        $receiptDate = $arrReceipt[$i]->ReceiptDate;
        if((strtotime($receiptDate . ' + 7 days') - time()) >= 0)
        {
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
        }
        $arrReceipt[$i]->ShowCancelDisputeButton = $showCancelDisputeButton;
        
        
        
        
        //dispute detail
        $refundAmount = $arrReceipt[$i]->CreditCardAmount;
        $sql = "select `DisputeID`, `ReceiptID`, `DisputeReasonID`, RefundAmount, `Detail`, `PhoneNo`, `Type` from Dispute where receiptID = '$receiptID' order by modifiedDate desc limit 1;";
        $arrDispute = executeQueryArray($sql);
        $arrReceipt[$i]->Dispute = $arrDispute;
        
        if(sizeof($arrDispute) > 0)
        {
            $dispute = $arrDispute[0];
            $receiptStatus = $arrReceipt[$i]->Status;
            $statusRoute = $arrReceipt[$i]->StatusRoute;
            $dispute->StatusDetail = "";
            if($receiptStatus == 7)
            {
                $dispute->StatusDetail = "คุณได้ส่งคำร้องขอยกเลิกบิลนี้  ด้วยเหตุผลด้านล่างนี้ กรุณารอการยืนยันการยกเลิกจากร้านค้า";
            }
            if($receiptStatus == 8)
            {
                $dispute->StatusDetail = "คุณส่ง Open dispute ไปที่ร้านค้าด้วยเหตุผลด้านล่างนี้ กรุณารอการยืนยันจากทางร้านค้าสักครู่";
            }
            if($receiptStatus == 9)
            {
                $arrStatus = explode(",",$statusRoute);
                $priorStatus = $arrStatus[sizeof($arrStatus)-2];
                echo "priorStatus:" . $priorStatus;
                if($priorStatus == 2)
                {
                    $dispute->StatusDetail = "ร้านค้ายกเลิกออเดอร์ให้คุณแล้ว คุณจะได้รับเงินคืนภายใน 48 ชม.";//priorStatusIsTwo
                }
                else
                {
                    $dispute->StatusDetail = "คำร้องขอยกเลิกออเดอร์สำเร็จแล้ว คุณจะได้รับเงินคืนภายใน 48 ชม.";
                }
            }
            if($receiptStatus == 10)
            {
                $arrStatus = explode(",",$statusRoute);
                $priorStatus = $arrStatus[sizeof($arrStatus)-2];
                if($priorStatus == 5 || $priorStatus == 6)
                {
                    $dispute->StatusDetail = "ร้านค้าทำเรื่องคืนเงินให้คุณแล้ว คุณจะได้รับเงินคืนภายใน 48 ชม.";//priorStatusIsFiveOrSix
                }
                else
                {
                    $dispute->StatusDetail = "Open dispute ที่ส่งไป ได้รับการยืนยันแล้ว คุณจะได้รับเงินคืนภายใน 48 ชม.";
                }
            }
            if($receiptStatus == 11)
            {
                $dispute->StatusDetail = "Open dispute ที่ส่งไปกำลังดำเนินการอยู่ จะมีเจ้าหน้าที่ติดต่อกลับไปภายใน 24 ชม.";
            }
            if($receiptStatus == 12)
            {
                $dispute->StatusDetail = "หลังจากคุยกับเจ้าหน้าที่ JUMMUM แล้ว มีการแก้ไขจำนวนเงิน refund ใหม่ ตามด้านล่างนี้ กรุณากด Confirm เพื่อยืนยัน หรือหากต้องการแก้ไขรายการกรุณากด Negotiate";
            }
            if($receiptStatus == 13)
            {
                $dispute->StatusDetail = "Open dispute ที่มีการแก้ไขกำลังดำเนินการอยู่ กรุณารอการยืนยันจากทางร้านค้าสักครู่";
            }
            if($receiptStatus == 14)
            {
                $dispute->StatusDetail = "Open dispute ที่ส่งไป ดำเนินการเสร็จสิ้นแล้ว คุณจะได้รับเงินคืนภายใน 48 ชม.";
            }
            
            
            //transfer button
            $showTransferButton = 0;
            $receiptStatus = intval($arrReceipt[$i]->Status);
            if($receiptStatus == 9 || $receiptStatus == 10 || $receiptStatus == 14)
            {
                $showTransferButton = 1;
            }
            $arrReceipt[$i]->ShowTransferButton = $showTransferButton;
            
            
            
            //status = 12 show negotiate, confirm, cancel button
            //confirm negotiate cancel button
            $showConfirmNegotiateCancelButton = 0;
            $receiptStatus = intval($arrReceipt[$i]->Status);
            if($receiptStatus == 12)
            {
                $showConfirmNegotiateCancelButton = 1;
            }
            $arrReceipt[$i]->ShowConfirmNegotiateCancelButton = $showConfirmNegotiateCancelButton;
            
            
            
            $sql = "select * from DisputeReason where disputeReasonID = '$dispute->DisputeReasonID'";
            $arrDisputeReason = executeQueryArray($sql);
            $disputeReason = $arrDisputeReason[0];
            $dispute->DisputeReason = $disputeReason->Text;
        }

        
        //rating detail
        $sql = "select `RatingID`, `ReceiptID`, `Score`, `Comment` from rating where receiptID = '$receiptID';";
        $arrRating = executeQueryArray($sql);
        $arrReceipt[$i]->Rating = $arrRating;
    }
    
    
    $response = array('success' => true, 'data' => $arrReceipt, 'error' => null);
    echo json_encode($response);
    
    // Close connections
    mysqli_close($con);
?>
