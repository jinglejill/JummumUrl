<?php
    include_once("dbConnect.php");
    setConnectionValue("");
    writeToLog("file: " . basename(__FILE__) . ", user: " . $_POST["modifiedUser"]);
    printAllPost();
    ini_set("memory_limit","-1");
    
    
    
    if(isset($_POST["menuID"]) && isset($_POST["branchID"]))
    {
        $menuID = $_POST["menuID"];
        $branchID = $_POST["branchID"];
    }
    
    
    // Check connection
    if (mysqli_connect_errno())
    {
        echo "Failed to connect to MySQL: " . mysqli_connect_error();
    }
    
    
    
    
    
    
    //check if use mainBranch menu or own menu
    $sql = "select * from $jummumOM.branch where branchID = '$branchID'";
    $selectedRow = getSelectedRow($sql);
    $dbName = $selectedRow[0]["DbName"];
    if($selectedRow[0]["BranchID"] != $selectedRow[0]["MainBranchID"])
    {
        $mainBranchID = $selectedRow[0]["MainBranchID"];
        $sql = "select * from $jummumOM.branch where branchID = '$mainBranchID'";
        $selectedRow = getSelectedRow($sql);
        $dbName = $selectedRow[0]["DbName"];
    }


    $sql = "select distinct '$branchID' BranchID, NoteType.`NoteTypeID`, NoteType.`Name`, NoteType.`NameEn`, NoteType.`AllowQuantity`, NoteType.`OrderNo` from $dbName.menuNote left join $dbName.Note on menuNote.noteID = note.noteID left join $dbName.NoteType on Note.NoteTypeID = noteType.NoteTypeID where menuID = '$menuID' and menuNote.status = 1 and note.status = 1 and NoteType.status = 1;";
    $arrResult = executeQueryArray($sql);
    for($i=0; $i<sizeof($arrResult); $i++)
    {
        $noteType = $arrResult[$i];
        $noteTypeID = $noteType->NoteTypeID;
        $allowQuantity = $noteType->AllowQuantity;
        $sql = "select '$branchID' BranchID, note.`NoteID`, note.`Name`, note.`NameEn`, note.`Price`, note.`NoteTypeID`, note.`Type`, note.`OrderNo`,'$allowQuantity' AllowQuantity from $dbName.menuNote left join $dbName.Note on menuNote.noteID = note.noteID left join $dbName.NoteType on Note.NoteTypeID = noteType.NoteTypeID where menuID = '$menuID' and note.noteTypeID = '$noteTypeID' and note.`Type` = 1 and menuNote.status = 1 and note.status = 1 and NoteType.status = 1;";
        $arrResultNoteAdd = executeQueryArray($sql);
        $noteType->NoteAdd = $arrResultNoteAdd;
        
        $sql = "select '$branchID' BranchID, note.`NoteID`, note.`Name`, note.`NameEn`, note.`Price`, note.`NoteTypeID`, note.`Type`, note.`OrderNo`,'$allowQuantity' AllowQuantity from $dbName.menuNote left join $dbName.Note on menuNote.noteID = note.noteID left join $dbName.NoteType on Note.NoteTypeID = noteType.NoteTypeID where menuID = '$menuID' and note.noteTypeID = '$noteTypeID' and note.`Type` = -1 and menuNote.status = 1 and note.status = 1 and NoteType.status = 1;";
        $arrResultNoteRemove = executeQueryArray($sql);
        $noteType->NoteRemove = $arrResultNoteRemove;
    }
    
    
    /* execute multi query */
    $arrMultiResult = executeMultiQueryArray($sql);
    $response = array('success' => true, 'data' => $arrResult, 'error' => null, 'status' => 1);
    echo json_encode($response);


    // Close connections
    mysqli_close($con);
    
?>
