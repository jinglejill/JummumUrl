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
    
    
    $sql = "select * from receipt where receiptID = '$receiptID';";
    $sql .= "select * from Dispute where receiptID = '$receiptID';";
    $sql .= "select * from rating where receiptID = '$receiptID';";
    
    
    
    //ordertaking, orderNote
    $sql .= "select * from OrderTaking where receiptID = '$receiptID';";
    $sql .= "select * from OrderNote where orderTakingID in (select orderTakingID from OrderTaking where receiptID in (select receiptID from receipt where receiptID = '$receiptID'));";
    
    
    
    //menu
    $sql3 = "select * from OrderTaking where receiptID in (select receiptID from receipt where receiptID = '$receiptID');";
    $selectedRow = getSelectedRow($sql3);
    if(sizeof($selectedRow)>0)
    {
        $menuID = $selectedRow[0]["MenuID"];
        $branchID = $selectedRow[0]["BranchID"];
        $sql2 = "select * from $jummumOM.branch where branchID = '$branchID'";
        $selectedRow2 = getSelectedRow($sql2);
        $eachDbName = $selectedRow2[0]["DbName"];
        if($selectedRow2[0]["BranchID"] != $selectedRow2[0]["MainBranchID"])
        {
            $mainBranchID = $selectedRow2[0]["MainBranchID"];
            $sql2 = "select * from $jummumOM.branch where branchID = '$mainBranchID'";
            $selectedRow2 = getSelectedRow($sql2);
            $eachDbName = $selectedRow2[0]["DbName"];
        }
        
        
        $sql4 = "select '$branchID' BranchID, Menu.* from $eachDbName.Menu where menuID = '$menuID'";
        for($i=1; $i<sizeof($selectedRow); $i++)
        {
            $menuID = $selectedRow[$i]["MenuID"];
            $branchID = $selectedRow[$i]["BranchID"];
            $sql2 = "select * from $jummumOM.branch where branchID = '$branchID'";
            $selectedRow2 = getSelectedRow($sql2);
            $eachDbName = $selectedRow2[0]["DbName"];
            $sql4 .= " union select '$branchID' BranchID, Menu.* from $eachDbName.Menu where menuID = '$menuID'";
        }
        $sql4 .= ";";
    }
    
    $sql .= $sql4;
    $sql .= "select * from DisputeReason where status = 1;";
    
    
    /* execute multi query */
    $jsonEncode = executeMultiQueryArray($sql);
    $response = array('success' => true, 'data' => $jsonEncode, 'error' => null, 'status' => 1);
    echo json_encode($response);


    
    // Close connections
    mysqli_close($con);
    
?>
