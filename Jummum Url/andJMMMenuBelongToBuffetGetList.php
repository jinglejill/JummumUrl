<?php
    include_once("dbConnect.php");
    setConnectionValue("");
    writeToLog("file: " . basename(__FILE__) . ", user: " . $_POST["modifiedUser"]);
    printAllPost();
    ini_set("memory_limit","-1");
    
    
    
    if(isset($_POST["buffetReceiptID"]) && isset($_POST["branchID"]))
    {
        $buffetReceiptID = $_POST["buffetReceiptID"];
        $branchID = $_POST["branchID"];
    }
    $receiptID = $buffetReceiptID;
    
    
    
    // Check connection
    if (mysqli_connect_errno())
    {
        echo "Failed to connect to MySQL: " . mysqli_connect_error();
    }
    
    
    
    //*** get dbName
    $sql = "select * from $jummumOM.branch where BranchID = '$branchID'";
    $selectedRow = getSelectedRow($sql);
    $dbName = $selectedRow[0]["DbName"];
    //***------
    
    
    
    
    //*** get OpeningTime
    //get customer order status from branch
    $sql = "select * from $dbName.Setting where keyName = 'customerOrderStatus'";
    $selectedRow = getSelectedRow($sql);
    $customerOrderStatus = $selectedRow[0]["Value"];
    
    
    $currentDateTime = date("Y-m-d H:i:s");
    $inOpeningTime = 0;
    if($customerOrderStatus == 1)
    {
        $inOpeningTime = 1;
    }
    else if($customerOrderStatus == 2)
    {
        $inOpeningTime = 0;
    }
    else
    {
        //get today's opening time**********
        $strDate = date("Y-m-d");
        $dayOfWeek = date('w', strtotime($strDate));
        $sql = "select * from $dbName.OpeningTime where day = '$dayOfWeek' order by day,shiftNo";
        $selectedRow = getSelectedRow($sql);
        
        for($i=0; $i<sizeof($selectedRow); $i++)
        {
            $day = $selectedRow[$i]["Day"];
            $startTime = $selectedRow[$i]["StartTime"];
            $endTime = $selectedRow[$i]["EndTime"];
            
            
            
            $intStartTime = intVal(str_replace(":","",$startTime));
            $intEndTime = intVal(str_replace(":","",$endTime));
            if($intStartTime < $intEndTime)
            {
                $startDate = date($strDate . " " . $startTime . ":00");
                $endDate = date($strDate . " " . $endTime . ":00");
                if($startDate<=$currentDateTime && $currentDateTime<=$endDate)
                {
                    $inOpeningTime = 1;
                }
            }
            else
            {
                $nextDate = date("Y-m-d", strtotime($strDate. ' + 1 days'));
                $startDate = date($strDate . " " . $startTime . ":00");
                $endDate = date($nextDate . " " . $endTime . ":00");
                if($startDate<=$currentDateTime && $currentDateTime<=$endDate)
                {
                    $inOpeningTime = 1;
                }
            }
        }
    }
    //***------
    
    
    
    
    //check if use mainBranch menu or own menu
    $sql = "select * from $jummumOM.branch where branchID = '$branchID'";
    $selectedRow = getSelectedRow($sql);
    if($selectedRow[0]["BranchID"] != $selectedRow[0]["MainBranchID"])
    {
        $mainBranchID = $selectedRow[0]["MainBranchID"];
        $sql = "select * from $jummumOM.branch where branchID = '$mainBranchID'";
        $selectedRow = getSelectedRow($sql);
        $dbName = $selectedRow[0]["DbName"];
    }
    
    
    
    $sql = "(select distinct '$branchID' BranchID, Menu.MenuID, Menu.`MenuCode`, Menu.`TitleThai`, Menu.`Price`, 0 `MenuTypeID`, Menu.`BuffetMenu`, Menu.`AlacarteMenu`, Menu.`TimeToOrder`, Menu.`ImageUrl`, Menu.`OrderNo` from receipt LEFT JOIN ordertaking ON receipt.ReceiptID = ordertaking.ReceiptID LEFT JOIN $dbName.BuffetMenuMap on orderTaking.MenuID = BuffetMenuMap.BuffetMenuID LEFT JOIN $dbName.Menu on BuffetMenuMap.MenuID = Menu.MenuID where receipt.receiptID = '$receiptID' and BuffetMenuMap.menuID is not null and BuffetMenuMap.Status = 1 and Menu.status = 1 and Recommended = 1 order by recommendedOrderNo) union (select distinct '$branchID' BranchID, Menu.MenuID, Menu.`MenuCode`, Menu.`TitleThai`, Menu.`Price`, Menu.`MenuTypeID`, Menu.`BuffetMenu`, Menu.`AlacarteMenu`, Menu.`TimeToOrder`, Menu.`ImageUrl`, Menu.`OrderNo` from receipt LEFT JOIN ordertaking ON receipt.ReceiptID = ordertaking.ReceiptID LEFT JOIN $dbName.BuffetMenuMap on orderTaking.MenuID = BuffetMenuMap.BuffetMenuID LEFT JOIN $dbName.Menu on BuffetMenuMap.MenuID = Menu.MenuID where receipt.receiptID = '$receiptID' and BuffetMenuMap.menuID is not null  and BuffetMenuMap.Status = 1 and Menu.status = 1);";
    $sql .= "select distinct '$branchID' BranchID, 0 MenuTypeID, 'แนะนำ' Name, 'Recommended' NameEn, 0 OrderNo union (select distinct '$branchID' BranchID, menuType.`MenuTypeID`, `Name`, `NameEn`, MenuType.`OrderNo` from receipt LEFT JOIN ordertaking ON receipt.ReceiptID = ordertaking.ReceiptID LEFT JOIN $dbName.BuffetMenuMap on orderTaking.MenuID = BuffetMenuMap.BuffetMenuID LEFT JOIN $dbName.Menu on BuffetMenuMap.MenuID = Menu.MenuID left join $dbName.menuType on Menu.menuTypeID = menuType.menuTypeID where receipt.receiptID = '$receiptID' and menuType.status = '1' and BuffetMenuMap.menuID is not null and BuffetMenuMap.Status = 1 and Menu.status = 1 and menuType.status = '1');";
    $sql .= "select `ReceiptID`, `BranchID`, `CustomerTableID`, `MemberID`, `TotalAmount`, `CreditCardType`, `CreditCardNo`, `CreditCardAmount`, `Remark`, `DiscountType`, `DiscountValue`, `ServiceChargePercent`, `ServiceChargeValue`, `PriceIncludeVat`, `VatPercent`, `VatValue`, `Status`, `ReceiptNoID`, `ReceiptDate`, `SendToKitchenDate`, `DeliveredDate`, `BuffetReceiptID`, `VoucherCode`, case `Status` when 2 then 'Order sent' when 5 then 'Processing...' when 6 then 'Delivered' when 7 then 'Pending cancel' when 8 then 'Order dispute in process' when 9 then 'Order cancelled' when 10 then 'Order dispute finished' when 11 then 'Negotiate' when 12 then 'Review dispute' when 13 then 'Review dispute in process' when 14 then 'Order dispute finished' end as StatusText from receipt where receiptID = '$receiptID';";
    $sql .= "SELECT `BranchID`, `Name`, `TakeAwayFee`, `ImageUrl` FROM $jummumOM.Branch where status = 1 and customerApp = 1 and branchID = '$branchID';";
    /* execute multi query */
    $arrMultiResult = executeMultiQueryArray($sql);
    
    
    $menuList = $arrMultiResult[0];
    for($i=0; $i<sizeof($menuList); $i++)
    {
        $menuID = $menuList[$i]->MenuID;
        $sql = "select * from $dbName.SpecialPriceProgram left join $dbName.SpecialPriceProgramDay on specialPriceProgram.specialPriceProgramID = specialPriceProgramDay.specialPriceProgramID and specialPriceProgramDay.Day = weekday('$currentDateTime')+1 where menuID = '$menuID' AND '$currentDateTime' between startDate and endDate and specialPriceProgramDayID is not null order by StartDate desc, EndDate desc, SpecialPriceProgram.ModifiedDate desc";
        $selectedRow = getSelectedRow($sql);
        if(sizeof($selectedRow)>0)
        {
            $menuList[$i]->SpecialPrice = $selectedRow[0]["SpecialPrice"];
        }
        else
        {
            $menuList[$i]->SpecialPrice = $menuList[$i]->Price;
        }
    }
    
    
    if($inOpeningTime)
    {
        $response = array('success' => true, 'data' => $arrMultiResult, 'error' => null);
        echo json_encode($response);
        
        
        // Close connections
        mysqli_close($con);
    }
    else
    {
        $error = "ทางร้านไม่ได้เปิดระบบการสั่งอาหารด้วยตนเองตอนนี้ ขออภัยในความไม่สะดวกค่ะ";
        writeToLog("order fail: $error, file: " . basename(__FILE__) . ", user: " . $_POST["modifiedUser"]);
        $response = array('success' => false, 'data' => $arrMultiResult, 'error' => $error);
        echo json_encode($response);
        
        
        
        // Close connections
        mysqli_close($con);
    }
    
?>
