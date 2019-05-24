<?php
    include_once("dbConnect.php");
    setConnectionValue("");
    writeToLog("file: " . basename(__FILE__) . ", user: " . $_POST["modifiedUser"]);
    printAllPost();
    ini_set("memory_limit","-1");
    
    
    
    if(isset($_POST["branchID"]))
    {
        $branchID = $_POST["branchID"];
    }
    
    
    // Check connection
    if (mysqli_connect_errno())
    {
        echo "Failed to connect to MySQL: " . mysqli_connect_error();
    }
    
    
    $sql = "select * from $jummumOM.branch where BranchID = '$branchID'";
    $selectedRow = getSelectedRow($sql);
    $dbName = $selectedRow[0]["DbName"];
    
    
    //get customer order status from branch
    $sql = "select * from $dbName.Setting where keyName = 'customerOrderStatus'";
    $selectedRow = getSelectedRow($sql);
    $customerOrderStatus = $selectedRow[0]["Value"];
    
    
    $currentDateTime = date('Y-m-d H:i:s');
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
    //*********
    
    
    
    
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
    
    
    

    $sql = "(select '$branchID' BranchID, menu.MenuID, `MenuCode`, `TitleThai`, `Price`, 0 as `MenuTypeID`, `BuffetMenu`, `AlacarteMenu`, `TimeToOrder`, `ImageUrl`,RecommendedOrderNo, `OrderNo` from $dbName.menu where Status = 1 and alacarteMenu = 1 and Recommended = 1 order by RecommendedOrderNo) union (select '$branchID' BranchID, menu.MenuID, `MenuCode`, `TitleThai`, `Price`, `MenuTypeID`, `BuffetMenu`, `AlacarteMenu`, `TimeToOrder`, `ImageUrl`, RecommendedOrderNo, `OrderNo` from $dbName.menu where Status = 1 and alacarteMenu = 1 order by MenuTypeID, OrderNo);";
    $sql .= "select distinct '$branchID' BranchID, 0 MenuTypeID, 'แนะนำ' Name, 'Recommended' NameEn, 0 OrderNo union (select distinct '$branchID' BranchID, menuType.`MenuTypeID`, `Name`, `NameEn`, MenuType.`OrderNo` from $dbName.menu left join $dbName.menuType on menu.menuTypeID = menuType.menuTypeID where menu.Status = 1 and menuType.Status = 1 and alacarteMenu = 1 ORDER BY OrderNo);";
    $sql .= "select Value as LuckyDrawSpend from $dbName.setting where keyName = 'luckyDrawSpend'";

    
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
