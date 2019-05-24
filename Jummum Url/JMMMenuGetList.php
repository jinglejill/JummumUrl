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
    
    
    
    
    
    if($inOpeningTime)
    {
        $sql = "select 1 as Text;";
    }
    else
    {
        $sql = "select 0 as Text;";
    }
    $sql .= "select * from ((select '$branchID' BranchID, 0 MenuTypeOrderNo, `MenuID`, `MenuCode`, `TitleThai`, `Price`,0 `MenuTypeID`, `SubMenuTypeID`, `BuffetMenu`, `AlacarteMenu`, `TimeToOrder`, `Recommended`, `RecommendedOrderNo`, `ImageUrl`, `Color`,recommendedOrderNo `OrderNo`, `Status`, `Remark`, `ModifiedUser`, `ModifiedDate` from $dbName.menu where Status = 1 and alacarteMenu = 1 and recommended = 1 order by recommendedOrderNo) union (select '$branchID' BranchID, MenuType.OrderNo as MenuTypeOrderNo, `MenuID`, `MenuCode`, `TitleThai`, `Price`, menu.`MenuTypeID`, `SubMenuTypeID`, `BuffetMenu`, `AlacarteMenu`, `TimeToOrder`, `Recommended`, `RecommendedOrderNo`, `ImageUrl`, menu.`Color`, menu.`OrderNo`, menu.`Status`, `Remark`, menu.`ModifiedUser`, menu.`ModifiedDate` from $dbName.menu left join $dbName.menuType on menu.MenuTypeID = menuType.MenuTypeID where menu.Status = 1 and alacarteMenu = 1 order by MenuType.OrderNo, Menu.OrderNo) union (select '$branchID' BranchID,MenuType.OrderNo+100 as MenuTypeOrderNo, `MenuID`, `MenuCode`, `TitleThai`, `Price`,100 `MenuTypeID`, `SubMenuTypeID`, `BuffetMenu`, `AlacarteMenu`, `TimeToOrder`, `Recommended`, `RecommendedOrderNo`, `ImageUrl`, menu.`Color`, menu.`OrderNo` MenuOrderNo, menu.`Status`, `Remark`, menu.`ModifiedUser`, menu.`ModifiedDate` from $dbName.menu left join $dbName.menuType on menu.MenuTypeID = menuType.MenuTypeID where menu.Status = 1 and alacarteMenu = 1 order by menuType.OrderNo, MenuOrderNo))a order by MenuTypeOrderNo, OrderNo;";
//    echo "<br>sql:".$sql;
    $sql .= "select '$branchID' BranchID, 0 `MenuTypeID`, 'แนะนำ' `Name`,'Recommended' `NameEn`, 0 `AllowDiscount`, 0 OrderNo union (select distinct '$branchID' BranchID, menuType.`MenuTypeID`, `Name`, `NameEn`, `AllowDiscount`, menuType.`OrderNo` from $dbName.menu left join $dbName.menuType on menu.menuTypeID = menuType.menuTypeID where menu.Status = 1 and menuType.Status = 1 and alacarteMenu = 1 order by menuType.orderNo) union select '$branchID' BranchID, 100 `MenuTypeID`, 'ทั้งหมด' `Name`,'All' `NameEn`, 0 `AllowDiscount`, 100 OrderNo;";
    $sql .= "select '$branchID' BranchID, specialPriceProgram.* from $dbName.specialPriceProgram left join $dbName.specialPriceProgramDay on specialPriceProgram.specialPriceProgramID = specialPriceProgramDay.specialPriceProgramID where '$currentDateTime' between startDate and endDate and specialPriceProgramDay.Day = weekday('$currentDateTime')+1;";
    $sql .= "select * from $dbName.setting where keyName = 'luckyDrawSpend'";
    
    

    /* execute multi query */
    $jsonEncode = executeMultiQueryArray($sql);
    $response = array('success' => true, 'data' => $jsonEncode, 'error' => null, 'status' => 1);
    echo json_encode($response);


    
    // Close connections
    mysqli_close($con);
    
?>
