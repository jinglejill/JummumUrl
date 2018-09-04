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
    $sql .= "select distinct '$branchID' BranchID, belongToBuffetMenu.* from receipt LEFT JOIN ordertaking ON receipt.ReceiptID = ordertaking.ReceiptID LEFT JOIN $dbName.menu BuffetMenu ON ordertaking.MenuID = BuffetMenu.menuID LEFT JOIN $dbName.menu belongToBuffetMenu on BuffetMenu.menuID = belongToBuffetMenu.belongToMenuID where receipt.receiptID = '$receiptID' and BuffetMenu.buffetMenu = '1' and belongToBuffetMenu.status = '1';";
    $sql .= "select distinct '$branchID' BranchID, $dbName.MenuType.* from receipt LEFT JOIN ordertaking ON receipt.ReceiptID = ordertaking.ReceiptID LEFT JOIN $dbName.menu BuffetMenu ON ordertaking.MenuID = BuffetMenu.menuID LEFT JOIN $dbName.menu belongToBuffetMenu on BuffetMenu.menuID = belongToBuffetMenu.belongToMenuID left join $dbName.menuType on belongToBuffetMenu.menuTypeID = $dbName.menuType.menuTypeID where receipt.receiptID = '$receiptID' and BuffetMenu.buffetMenu = '1' and belongToBuffetMenu.status = '1' and $dbName.menuType.status = '1';";
    $sql .= "select distinct '$branchID' BranchID, Note.* from receipt LEFT JOIN ordertaking ON receipt.ReceiptID = ordertaking.ReceiptID LEFT JOIN $dbName.menu BuffetMenu ON ordertaking.MenuID = BuffetMenu.menuID LEFT JOIN $dbName.menu belongToBuffetMenu on BuffetMenu.menuID = belongToBuffetMenu.belongToMenuID left join $dbName.menuNote on belongToBuffetMenu.menuID = $dbName.menuNote.menuID left join $dbName.Note on $dbName.menuNote.noteID = $dbName.Note.noteID where receipt.receiptID = '$receiptID' and BuffetMenu.buffetMenu = '1' and belongToBuffetMenu.status = '1' and $dbName.Note.status = '1';";
    $sql .= "select distinct '$branchID' BranchID, NoteType.* from receipt LEFT JOIN ordertaking ON receipt.ReceiptID = ordertaking.ReceiptID LEFT JOIN $dbName.menu BuffetMenu ON ordertaking.MenuID = BuffetMenu.menuID LEFT JOIN $dbName.menu belongToBuffetMenu on BuffetMenu.menuID = belongToBuffetMenu.belongToMenuID left join $dbName.menuNote on belongToBuffetMenu.menuID = $dbName.menuNote.menuID left join $dbName.Note on $dbName.menuNote.noteID = $dbName.Note.noteID left join $dbName.NoteType on $dbName.note.noteTypeID = $dbName.NoteType.noteTypeID where receipt.receiptID = '$receiptID' and BuffetMenu.buffetMenu = '1' and belongToBuffetMenu.status = '1' and $dbName.Note.status = '1' and $dbName.NoteType.status = '1';";
    $sql .= "select distinct '$branchID' BranchID, $dbName.specialPriceProgram.* from receipt LEFT JOIN ordertaking ON receipt.ReceiptID = ordertaking.ReceiptID LEFT JOIN $dbName.menu BuffetMenu ON ordertaking.MenuID = BuffetMenu.menuID LEFT JOIN $dbName.menu belongToBuffetMenu on BuffetMenu.menuID = belongToBuffetMenu.belongToMenuID left join $dbName.specialPriceProgram on belongToBuffetMenu.menuID = $dbName.specialPriceProgram.menuID where receipt.receiptID = '$receiptID' and BuffetMenu.buffetMenu = '1' and belongToBuffetMenu.status = '1' and date_format(now(),'%Y-%m-%d') between date_format($dbName.specialPriceProgram.startDate,'%Y-%m-%d') and date_format($dbName.specialPriceProgram.endDate,'%Y-%m-%d');";
    $sql .= "select * from receipt where receiptID = '$receiptID'";

    
    
    
    /* execute multi query */
    $jsonEncode = executeMultiQueryArray($sql);
    $response = array('success' => true, 'data' => $jsonEncode, 'error' => null, 'status' => 1);
    echo json_encode($response);


    
    // Close connections
    mysqli_close($con);
    
?>
