<?php
    include_once("dbConnect.php");
    setConnectionValue("");
    writeToLog("file: " . basename(__FILE__) . ", user: " . $_POST["modifiedUser"]);
    printAllPost();
    ini_set("memory_limit","-1");
    
    
    
    if(isset($_POST["receiptID"]) && isset($_POST["branchID"]))
    {
        $receiptID = $_POST["receiptID"];
        $branchID = $_POST["branchID"];
    }
    
    
    
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
    
    
    
    if($inOpeningTime)
    {
        $sql = "select 1 as Text;";
    }
    else
    {
        $sql = "select 0 as Text;";
    }
    $sql .= "(select distinct '$branchID' BranchID, 0 MenuTypeOrderNo, menu.`MenuID`, `MenuCode`, `TitleThai`, menu.`Price`,0 `MenuTypeID`, `SubMenuTypeID`, `BuffetMenu`, `AlacarteMenu`, menu.`TimeToOrder`, `Recommended`, `RecommendedOrderNo`, `ImageUrl`,recommendedOrderNo `OrderNo`, menu.`Status`, menu.`Remark`, menu.`ModifiedUser`, menu.`ModifiedDate` from receipt LEFT JOIN ordertaking ON receipt.ReceiptID = ordertaking.ReceiptID LEFT JOIN $dbName.BuffetMenuMap on orderTaking.MenuID = BuffetMenuMap.BuffetMenuID LEFT JOIN $dbName.Menu on BuffetMenuMap.MenuID = Menu.MenuID where receipt.receiptID = '$receiptID' and BuffetMenuMap.menuID is not null and BuffetMenuMap.Status = 1 and Menu.status = 1 and recommended = 1) union (select distinct '$branchID' BranchID , menuType.orderNo MenuTypeOrderNo, menu.`MenuID`, `MenuCode`, `TitleThai`, menu.`Price`,menu.menuTypeID `MenuTypeID`, `SubMenuTypeID`, `BuffetMenu`, `AlacarteMenu`, menu.`TimeToOrder`, `Recommended`, `RecommendedOrderNo`, `ImageUrl`,recommendedOrderNo `OrderNo`, menu.`Status`, menu.`Remark`, menu.`ModifiedUser`, menu.`ModifiedDate`  from receipt LEFT JOIN ordertaking ON receipt.ReceiptID = ordertaking.ReceiptID LEFT JOIN $dbName.BuffetMenuMap on orderTaking.MenuID = BuffetMenuMap.BuffetMenuID LEFT JOIN $dbName.Menu on BuffetMenuMap.MenuID = Menu.MenuID left join $dbName.menuType on menu.menuTypeID = menutype.menutypeID where receipt.receiptID = '$receiptID' and BuffetMenuMap.menuID is not null and BuffetMenuMap.Status = 1 and Menu.status = 1);";
//    echo "<br>$sql";
    $sql .= "select distinct '1' BranchID, 0 `MenuTypeID`, 'แนะนำ' `Name`,'Recommended' `NameEn`, 0 `AllowDiscount`, 0 OrderNo union(select distinct '$branchID' BranchID, MenuType.MenuTypeID, MenuType.Name, MenuType.NameEn, MenuType.AllowDiscount, MenuType.OrderNo from receipt LEFT JOIN ordertaking ON receipt.ReceiptID = ordertaking.ReceiptID LEFT JOIN $dbName.BuffetMenuMap on orderTaking.MenuID = BuffetMenuMap.BuffetMenuID LEFT JOIN $dbName.Menu on BuffetMenuMap.MenuID = Menu.MenuID left join $dbName.menuType on Menu.menuTypeID = menuType.menuTypeID where receipt.receiptID = '$receiptID' and BuffetMenuMap.menuID is not null and BuffetMenuMap.Status = 1 and Menu.status = 1 and menuType.status = '1' order by MenuType.OrderNo);";
    $sql .= "select distinct '$branchID' BranchID, specialPriceProgram.* from receipt LEFT JOIN ordertaking ON receipt.ReceiptID = ordertaking.ReceiptID LEFT JOIN $dbName.BuffetMenuMap on orderTaking.MenuID = BuffetMenuMap.BuffetMenuID LEFT JOIN $dbName.Menu on BuffetMenuMap.MenuID = Menu.MenuID left join $dbName.specialPriceProgram on Menu.menuID = specialPriceProgram.menuID left join $dbName.specialPriceProgramDay on specialPriceProgram.specialPriceProgramID = specialPriceProgramDay.specialPriceProgramID where receipt.receiptID = '$receiptID' and BuffetMenuMap.menuID is not null and BuffetMenuMap.Status = 1 and Menu.status = 1 and '$currentDateTime' between specialPriceProgram.startDate and specialPriceProgram.endDate and specialPriceProgramDay.Day = weekday('$currentDateTime')+1;";
    $sql .= "select * from receipt where receiptID = '$receiptID';";

    
    
    
    /* execute multi query */
    $jsonEncode = executeMultiQueryArray($sql);
    $response = array('success' => true, 'data' => $jsonEncode, 'error' => null, 'status' => 1);
    echo json_encode($response);


    
    // Close connections
    mysqli_close($con);
    
?>
