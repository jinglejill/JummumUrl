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
    

    $sql = "select '$branchID' BranchID, $dbName.menuNote.* from $dbName.menuNote where menuID = '$menuID' and status = 1;";
    $sql .= "select '$branchID' BranchID, note.* from $dbName.menuNote left join $dbName.note on menuNote.noteID = note.noteID where menuID = '$menuID' and menuNote.status = 1 and note.status = 1;";
    $sql .= "select distinct '$branchID' BranchID, notetype.*, note.Type from $dbName.menuNote left join $dbName.note on menuNote.noteID = note.noteID left join $dbName.NoteType on Note.NoteTypeID = NoteType.NoteTypeID where menuID = '$menuID' and menuNote.status = 1 and note.status = 1 and noteType.Status = 1;";
    
    
    
    /* execute multi query */
    $jsonEncode = executeMultiQueryArray($sql);
    $response = array('success' => true, 'data' => $jsonEncode, 'error' => null, 'status' => 1);
    echo json_encode($response);


    
    // Close connections
    mysqli_close($con);
    
?>
