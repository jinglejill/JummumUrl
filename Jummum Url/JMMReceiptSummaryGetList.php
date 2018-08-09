<?php
    include_once("dbConnect.php");
    setConnectionValue("");
    writeToLog("file: " . basename(__FILE__) . ", user: " . $_POST["modifiedUser"]);
    printAllPost();



//    if(isset($_POST["receiptDate"]) && isset($_POST["receiptID"]) && isset($_POST["userAccountID"]))
    if(isset($_POST["receiptDate"]) && isset($_POST["memberID"]))
    {
        $receiptDate = $_POST["receiptDate"];
//        $receiptID = $_POST["receiptID"];
        $memberID = $_POST["memberID"];
    }
    
    
    // Check connection
    if (mysqli_connect_errno())
    {
        echo "Failed to connect to MySQL: " . mysqli_connect_error();
    }
    
    
    
   
    
    
//    $sql = "select * from receipt where memberID = '$userAccountID' and receiptDate <= '$receiptDate' and receiptID < '$receiptID' order by receipt.ReceiptDate DESC, receipt.ReceiptID DESC limit 10;";
    $sql = "select * from receipt where memberID = '$memberID' and receiptDate < '$receiptDate' order by receipt.ReceiptDate DESC limit 10;";
    $selectedRow = getSelectedRow($sql);
    if(sizeof($selectedRow)>0)
    {
        $receiptIDList = array();
        for($i=0; $i<sizeof($selectedRow); $i++)
        {
            array_push($receiptIDList,$selectedRow[$i]["ReceiptID"]);
        }
        if(sizeof($receiptIDList) > 0)
        {
            $receiptIDListInText = $receiptIDList[0];
            for($i=1; $i<sizeof($receiptIDList); $i++)
            {
                $receiptIDListInText .= "," . $receiptIDList[$i];
            }
        }
        $sqlAll = $sql;
        
        
        
        //branch
        $sql = "select distinct BranchID from receipt where memberID = '$memberID' and receiptDate < '$receiptDate' order by receipt.ReceiptDate DESC limit 10;";
        $selectedRow = getSelectedRow($sql);
        
        
        $branchIDList = array();
        for($i=0; $i<sizeof($selectedRow); $i++)
        {
            array_push($branchIDList,$selectedRow[$i]["BranchID"]);
        }
        if(sizeof($branchIDList) > 0)
        {
            $branchIDListInText = $branchIDList[0];
            for($i=1; $i<sizeof($branchIDList); $i++)
            {
                $branchIDListInText .= "," . $branchIDList[$i];
            }
        }
        $sql = "select * from $jummumOM.branch where branchID in ($branchIDListInText);";
        $selectedRow = getSelectedRow($sql);
        $sqlAll .= $sql;
        
        
        
        //orderTaking
        $sql = "select * from $jummum.OrderTaking where receiptID in ($receiptIDListInText);";
        $selectedRow = getSelectedRow($sql);
        $sqlAll .= $sql;
        
        
        //menu
        if(sizeof($selectedRow)>0)
        {
            for($i=0; $i<sizeof($selectedRow); $i++)
            {
                $menuID = $selectedRow[$i]["MenuID"];
                $branchID = $selectedRow[$i]["BranchID"];
                $sql2 = "select * from $jummumOM.branch where branchID = '$branchID'";
                $selectedRow2 = getSelectedRow($sql2);
                $eachDbName = $selectedRow2[0]["DbName"];
                $mainBranchID = $selectedRow2[0]["MainBranchID"];
                if($branchID != $mainBranchID)
                {
                    $sql2 = "select * from $jummumOM.branch where branchID = '$mainBranchID'";
                    $selectedRow2 = getSelectedRow($sql2);
                    $eachDbName = $selectedRow2[0]["DbName"];
                }
                if($i == 0)
                {
                    $sqlMenu = "select '$mainBranchID' BranchID, Menu.* from $eachDbName.Menu left join $eachDbName.menutype ON menu.MenuTypeID = menutype.MenuTypeID where menuID = '$menuID'";
                    $sqlMenuType = "select '$mainBranchID' BranchID, MenuType.* from $eachDbName.Menu left join $eachDbName.menutype ON menu.MenuTypeID = menutype.MenuTypeID where menuID = '$menuID'";
                }
                else
                {
                    $sqlMenu .= " union select '$mainBranchID' BranchID, Menu.* from $eachDbName.Menu left join $eachDbName.menutype ON menu.MenuTypeID = menutype.MenuTypeID where menuID = '$menuID'";
                    $sqlMenuType .= " union select '$mainBranchID' BranchID, MenuType.* from $eachDbName.Menu left join $eachDbName.menutype ON menu.MenuTypeID = menutype.MenuTypeID where menuID = '$menuID'";
                }
            }
            $sqlMenu .= ";";
            $sqlMenuType .= ";";
        }
        $sqlAll .= $sqlMenu;
        $sqlAll .= $sqlMenuType;
        
        
        
        //orderNote
        $sql = "select OrderNote.*,OrderTaking.BranchID from OrderNote left join OrderTaking on OrderNote.orderTakingID = OrderTaking.orderTakingID where OrderNote.orderTakingID in (select orderTakingID from OrderTaking where receiptID in ($receiptIDListInText));";
        $selectedRow = getSelectedRow($sql);
        $sqlAll .= $sql;
        
        
        
        //note
        if(sizeof($selectedRow)>0)
        {
            for($i=0; $i<sizeof($selectedRow); $i++)
            {
                $noteID = $selectedRow[$i]["NoteID"];
                $branchID = $selectedRow[$i]["BranchID"];
                $sql2 = "select * from $jummumOM.branch where branchID = '$branchID'";
                $selectedRow2 = getSelectedRow($sql2);
                $eachDbName = $selectedRow2[0]["DbName"];
                $mainBranchID = $selectedRow2[0]["MainBranchID"];
                if($branchID != $mainBranchID)
                {
                    $sql2 = "select * from $jummumOM.branch where branchID = '$mainBranchID'";
                    $selectedRow2 = getSelectedRow($sql2);
                    $eachDbName = $selectedRow2[0]["DbName"];
                }
                if($i == 0)
                {
                    $sqlNote = "select '$mainBranchID' BranchID, Note.* from $eachDbName.Note left join $eachDbName.NoteType ON Note.NoteTypeID = NoteType.NoteTypeID where noteID = '$noteID'";
                    $sqlNoteType = "select '$mainBranchID' BranchID, NoteType.* from $eachDbName.Note left join $eachDbName.NoteType ON Note.NoteTypeID = NoteType.NoteTypeID where noteID = '$noteID'";
                }
                else
                {
                    $sqlNote .= " union select '$mainBranchID' BranchID, Note.* from $eachDbName.Note left join $eachDbName.NoteType ON Note.NoteTypeID = NoteType.NoteTypeID where noteID = '$noteID'";
                    $sqlNoteType .= " union select '$mainBranchID' BranchID, NoteType.* from $eachDbName.Note left join $eachDbName.NoteType ON Note.NoteTypeID = NoteType.NoteTypeID where noteID = '$noteID'";
                }
            }
            $sqlNote .= ";";
            $sqlNoteType .= ";";
        }
        $sqlAll .= $sqlNote;
        $sqlAll .= $sqlNoteType;
    }
    else
    {
        $sqlAll = "select * from Receipt where 0;";
        $sqlAll .= "select * from Branch where 0;";
        $sqlAll .= "select * from OrderTaking where 0;";
        $sqlAll .= "select * from Menu where 0;";
        $sqlAll .= "select * from MenuType where 0;";
        $sqlAll .= "select * from OrderNote where 0;";
        $sqlAll .= "select * from Note where 0;";
        $sqlAll .= "select * from NoteType where 0;";
    }
    
    
    
    
    
//    {
//        //menu
//        $sql3 = "select * from OrderTaking where receiptID in ($receiptIDListInText);";
//        $selectedRow = getSelectedRow($sql3);        
//        if(sizeof($selectedRow)>0)
//        {
//            
//            $menuID = $selectedRow[0]["MenuID"];
//            $branchID = $selectedRow[0]["BranchID"];
//            $sql2 = "select * from $jummumOM.branch where branchID = '$branchID'";
//            $selectedRow2 = getSelectedRow($sql2);
//            $eachDbName = $selectedRow2[0]["DbName"];
//            $sql4 = "select '$branchID' BranchID, Menu.* from $eachDbName.Menu where menuID = '$menuID'";
//            for($i=1; $i<sizeof($selectedRow); $i++)
//            {
//                $menuID = $selectedRow[$i]["MenuID"];
//                $branchID = $selectedRow[$i]["BranchID"];
//                $sql2 = "select * from $jummumOM.branch where branchID = '$branchID'";
//                $selectedRow2 = getSelectedRow($sql2);
//                $eachDbName = $selectedRow2[0]["DbName"];
//                $sql4 .= " union select '$branchID' BranchID, Menu.* from $eachDbName.Menu where menuID = '$menuID'";
//            }
//        }
//        
//        
//        $sql .= "select * from OrderTaking where receiptID in ($receiptIDListInText);";
//        $sql .= "select * from OrderNote where orderTakingID in (select orderTakingID from OrderTaking where receiptID in ($receiptIDListInText));";
//        $sql .= $sql4;
//    }
//    else
//    {
//        $sql .= "select * from OrderTaking where 0;";
//        $sql .= "select * from OrderNote where 0;";
//        $sql .= "select 0 as BranchID, Menu.* from Menu where 0;";
//    }
    
    
    
    
    
    
    
    
    /* execute multi query */
    $jsonEncode = executeMultiQuery($sqlAll);
    echo $jsonEncode;
    
    
    
    // Close connections
    mysqli_close($con);
?>
