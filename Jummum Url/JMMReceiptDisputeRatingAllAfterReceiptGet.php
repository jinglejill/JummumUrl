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
    $selectedRow = getSelectedRow($sql);
    $receiptDate = $selectedRow[0]["ReceiptDate"];
    
    
    
    $sql = "select * from receipt where receiptDate >= '$receiptDate';";
    $sql .= "select * from dispute where receiptID in (select receiptID from receipt where receiptDate >= '$receiptDate');";
    $sql .= "select * from rating where receiptID in (select receiptID from receipt where receiptDate >= '$receiptDate');";
    
    
    
    //ordertaking, orderNote
    $sql .= "select * from OrderTaking where receiptID in (select receiptID from receipt where receiptDate >= '$receiptDate');";
    $sql .= "select * from OrderNote where orderTakingID in (select orderTakingID from OrderTaking where receiptID in (select receiptID from receipt where receiptDate >= '$receiptDate'));";
    
    
    
    //menu
    $sql3 = "select * from OrderTaking where receiptID in (select receiptID from receipt where receiptDate >= '$receiptDate');";
    $selectedRow = getSelectedRow($sql3);
    if(sizeof($selectedRow)>0)
    {
        $menuID = $selectedRow[0]["MenuID"];
        $branchID = $selectedRow[0]["BranchID"];
        $sql2 = "select * from $jummumOM.branch where branchID = '$branchID'";
        $selectedRow2 = getSelectedRow($sql2);
        $eachDbName = $selectedRow2[0]["DbName"];
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
    }
    
    $sql .= $sql4;
    
    
    
    
    /* execute multi query */
    $jsonEncode = executeMultiQuery($sql);
    echo $jsonEncode;


    
    // Close connections
    mysqli_close($con);
    
?>
