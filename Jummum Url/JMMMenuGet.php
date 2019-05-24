<?php
    include_once("dbConnect.php");
    setConnectionValue("");
    writeToLog("file: " . basename(__FILE__) . ", user: " . $_POST["modifiedUser"]);
    printAllPost();
    ini_set("memory_limit","-1");
    
    
    
    if(isset($_POST["branchID"]) && isset($_POST["discountGroupMenuID"]))
    {
        $branchID = $_POST["branchID"];
        $discountGroupMenuID = $_POST["discountGroupMenuID"];
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
    
    
    
    
    
    if($inOpeningTime)
    {
        $sql = "select 1 as Text;";
    }
    else
    {
        $sql = "select 0 as Text;";
    }
    $sql .= "select '$branchID' BranchID, $dbName.menu.* from $dbName.DiscountGroupMenuMap left join $dbName.menu on DiscountGroupMenuMap.MenuID = Menu.MenuID where menu.Status = 1 and menu.alacarteMenu = 1 and DiscountGroupMenuID = '$discountGroupMenuID' and DiscountGroupMenuMap.status = 1 and DiscountGroupMenuMap.Quantity > 0;";
    $sql .= "select '$branchID' BranchID, $dbName.specialPriceProgram.* from $dbName.DiscountGroupMenuMap left join $dbName.menu on DiscountGroupMenuMap.MenuID = Menu.MenuID left join $dbName.specialPriceProgram on DiscountGroupMenuMap.MenuID = SpecialPriceProgram.MenuID left join $dbName.specialPriceProgramDay on specialPriceProgram.specialPriceProgramID = specialPriceProgramDay.specialPriceProgramID where menu.Status = 1 and menu.alacarteMenu = 1 and '$currentDateTime' between startDate and endDate and DiscountGroupMenuID = '$discountGroupMenuID' and DiscountGroupMenuMap.status = 1 and DiscountGroupMenuMap.Quantity > 0 and specialPriceProgramDay.Day = weekday('$currentDateTime')+1;";
    $sql .= "select DiscountGroupMenuMap.* from $dbName.DiscountGroupMenuMap left join $dbName.menu on DiscountGroupMenuMap.MenuID = Menu.MenuID where menu.Status = 1 and menu.alacarteMenu = 1 and discountGroupMenuID = '$discountGroupMenuID' and DiscountGroupMenuMap.status = 1 and DiscountGroupMenuMap.Quantity > 0";
    writeToLog("sql = " . $sql);
    
    
    
    /* execute multi query */
    $jsonEncode = executeMultiQueryArray($sql);
    $response = array('success' => true, 'data' => $jsonEncode, 'error' => null, 'status' => 1);
    echo json_encode($response);


    
    // Close connections
    mysqli_close($con);
    
?>
