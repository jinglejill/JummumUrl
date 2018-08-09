<?php
    include_once("dbConnect.php");
    setConnectionValue("");
    writeToLog("file: " . basename(__FILE__) . ", user: " . $_POST["modifiedUser"]);
    printAllPost();
    
    
    
    if(isset($_POST["username"]) && isset($_POST["password"]))
    {
        $username = $_POST["username"];
        $password = $_POST["password"];
    }
//    if(isset($_POST["logInID"]) && isset($_POST["username"]) && isset($_POST["status"]) && isset($_POST["deviceToken"]) && isset($_POST["modifiedUser"]) && isset($_POST["modifiedDate"]))
    if(isset($_POST["deviceToken"]) && isset($_POST["modifiedUser"]) && isset($_POST["modifiedDate"]))
    {
        $logInID = $_POST["logInID"];
        $username = $_POST["username"];
        $status = $_POST["status"];
        $deviceToken = $_POST["deviceToken"];
        $modifiedUser = $_POST["modifiedUser"];
        $modifiedDate = $_POST["modifiedDate"];
    }
    $status = 1;
    $deviceToken = $_POST["modifiedDeviceToken"];
    
    
    // Check connection
    if (mysqli_connect_errno())
    {
        echo "Failed to connect to MySQL: " . mysqli_connect_error();
    }
    
    
    
    // Set autocommit to off
    mysqli_autocommit($con,FALSE);
    writeToLog("set auto commit to off");
    
    
    
    
    $sql = "select * from UserAccount where username = '$username' and password = '$password'";
    $selectedRow = getSelectedRow($sql);
    if(sizeof($selectedRow)==0)
    {
        //download UserAccount
        $sql = "select * from UserAccount where 0";
        writeToLog("sql = " . $sql);
        
        
        /* execute multi query */
        $dataJson = executeMultiQueryArray($sql);
        
        
        
        writeToLog("query commit, file: " . basename(__FILE__) . ", user: " . $_POST['modifiedUser']);
        $response = array('status' => '1', 'sql' => $sql , 'tableName' => 'UserAccountValidate', 'dataJson' => $dataJson);
        echo json_encode($response);
        exit();
    }
    
    
    
    
    
    //login--------------------
    //query statement
//    $sql = "INSERT INTO LogIn(Username, Status, DeviceToken, ModifiedUser, ModifiedDate) VALUES ('$username', '$status', '$deviceToken', '$modifiedUser', '$modifiedDate')";
    $sql = "INSERT INTO LogIn(Username, Status, DeviceToken, ModifiedUser, ModifiedDate) VALUES ('$username', '$status', '$deviceToken', '$modifiedUser', '$modifiedDate')";
    $ret = doQueryTask($sql);
    if($ret != "")
    {
        mysqli_rollback($con);
//        putAlertToDevice();
        echo json_encode($ret);
        exit();
    }
    //-----
    
    
    
    

    //userAccount
    $sql = "select * from UserAccount where username = '$username' and password = '$password';";
    $selectedRow = getSelectedRow($sql);
    $userAccountID = $selectedRow[0]["UserAccountID"];
    $sqlAll = $sql;
    
    
    
    //receipt
    $sql = "select * from receipt where memberID = '$userAccountID' order by receipt.ReceiptDate DESC limit 10;";
    $selectedRow = getSelectedRow($sql);
    
    
    
    if(sizeof($selectedRow) > 0)
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
        $sqlAll .= $sql;
        
        
        
        //branch
        $sql = "select distinct BranchID from receipt where memberID = '$userAccountID' order by receipt.ReceiptDate DESC limit 10;";
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
        $sql = "select * from OrderTaking where receiptID in ($receiptIDListInText);";
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
        $sqlAll .= "select * from Receipt where 0;";
        $sqlAll .= "select * from Branch where 0;";
        $sqlAll .= "select * from OrderTaking where 0;";
        $sqlAll .= "select * from Menu where 0;";
        $sqlAll .= "select * from MenuType where 0;";
        $sqlAll .= "select * from OrderNote where 0;";
        $sqlAll .= "select * from Note where 0;";
        $sqlAll .= "select * from NoteType where 0;";
    }
    
    
    
    
    
    
    /* execute multi query */
    $dataJson = executeMultiQueryArray($sqlAll);
    
    
    
    //do script successful
    mysqli_commit($con);
    mysqli_close($con);
    
    
    
    writeToLog("query commit, file: " . basename(__FILE__) . ", user: " . $_POST['modifiedUser']);
    $response = array('status' => '1', 'sql' => $sql, 'tableName' => 'UserAccountValidate', dataJson => $dataJson);
    echo json_encode($response);
    exit();
?>
