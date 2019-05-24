<?php
    include_once("dbConnect.php");
    setConnectionValue("");
//    writeToLog("file: " . basename(__FILE__) . ", user: " . $_POST["modifiedUser"]);
//    printAllPost();
    ini_set("memory_limit","-1");
    
    
    
    header("Content-Type: application/json");
    
    // get the lower case rendition of the headers of the request
    
    $headers = array_change_key_case(getallheaders());
    
    // extract the content-type
    
    if (isset($headers["content-type"]))
    {
        $content_type = $headers["content-type"];
        writeToLog("set contentType: " . $content_type);
    }
    else
    {
        $content_type = "";
        writeToLog("not set contentType: " . $content_type);
    }
    
    // if JSON, read and parse it
    if ($content_type == "application/json" || strpos($content_type,"application/json")!== false)
    {
        // read it
        
        $handle = fopen("php://input", "rb");
        $raw_post_data = '';
        while (!feof($handle)) {
            $raw_post_data .= fread($handle, 8192);
        }
        fclose($handle);
        
        // parse it
        
        $data = json_decode($raw_post_data, true);
    }
    else
    {
        // report non-JSON request and exit
    }
    
    writeToLog("file: " . basename(__FILE__) . ", user: " . $data["modifiedUser"]);
    writeToLog("json data: " . json_encode($data));
    
    
    
    
    
//    if(isset($_POST["branchID"]) && isset($_POST["menuID"]))
    {
        $branchID = $data["branchID"];
//        $menuID = $_POST["menuID"];
    }
    
    {
        $discountGroupMenuMapID = $data["discountGroupMenuMapID"];
        $discountGroupMenuID = $data["discountGroupMenuID"];
        $menuID = $data["menuID"];
        $modifiedUser = $data["modifiedUser"];
        $modifiedDate = $data["modifiedDate"];
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
        $currentDate = date("Y-m-d H:i:s");
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
                if($startDate<=$currentDate && $currentDate<=$endDate)
                {
                    $inOpeningTime = 1;
                }
            }
            else
            {
                $nextDate = date("Y-m-d", strtotime($strDate. ' + 1 days'));
                $startDate = date($strDate . " " . $startTime . ":00");
                $endDate = date($nextDate . " " . $endTime . ":00");
                if($startDate<=$currentDate && $currentDate<=$endDate)
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
    $sql .= "select '$branchID' BranchID, $dbName.menu.* from $dbName.DiscountGroupMenuMap left join $dbName.menu on DiscountGroupMenuMap.MenuID = Menu.MenuID where menu.Status = 1 and menu.belongToMenuID = 0 and DiscountGroupMenuID = '$discountGroupMenuID';";
    $sql .= "select '$branchID' BranchID, $dbName.menuType.* from $dbName.DiscountGroupMenuMap left join $dbName.menu on DiscountGroupMenuMap.MenuID = Menu.MenuID left join $dbName.menuType on $dbName.menu.menuTypeID = $dbName.menuType.menuTypeID where $dbName.menu.Status = 1 and $dbName.menuType.Status = 1 and menu.belongToMenuID = 0 and DiscountGroupMenuID = '$discountGroupMenuID';";
    $sql .= "select '$branchID' BranchID, $dbName.note.* from $dbName.note where Status = 1;";
    $sql .= "select '$branchID' BranchID, '$branchID' BranchID, $dbName.notetype.* from $dbName.notetype where Status = 1;";
    $sql .= "select '$branchID' BranchID, $dbName.specialPriceProgram.* from $dbName.DiscountGroupMenuMap left join $dbName.specialPriceProgram on DiscountGroupMenuMap.MenuID = SpecialPriceProgram.MenuID where date_format(now(),'%Y-%m-%d') between date_format(startDate,'%Y-%m-%d') and date_format(endDate,'%Y-%m-%d') and DiscountGroupMenuID = '$discountGroupMenuID';";
    writeToLog("sql = " . $sql);
    
    
    
    /* execute multi query */
    $jsonEncode = executeMultiQueryArray($sql);
    $response = array('success' => true, 'data' => $jsonEncode, 'error' => null, 'status' => 1);
    echo json_encode($response);


    
    // Close connections
    mysqli_close($con);
    
?>
