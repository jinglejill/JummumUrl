<?php
    include_once("dbConnect.php");
    setConnectionValue($jummumOM);    
    writeToLog("file: " . basename(__FILE__) . ", user: " . $_POST["modifiedUser"]);
    printAllPost();
    ini_set("memory_limit","-1");
    

    
    
    
    if(isset($_GET["ledID"]))
    {
        $ledID = $_GET["ledID"];
    }
    
    
    
    
    // Check connection
    if (mysqli_connect_errno())
    {
        echo "Failed to connect to MySQL: " . mysqli_connect_error();
    }
    
    $sql = "select * from $jummumOM.led where ledID = '$ledID'";
    $selectedRow = getSelectedRow($sql);
    $branchID = $selectedRow[0]["BranchID"];
    
    $sql = "select * from $jummumOM.branch where branchID = '$branchID';";
    $selectedRow = getSelectedRow($sql);
    $dbName = $selectedRow[0]["DbName"];
    
    $sql = "select Value as LedStatus from $dbName.setting where keyName = 'ledStatus'";
    $selectedRow = getSelectedRow($sql);
    echo json_encode($selectedRow[0]);
    

    
    // Close connections
    mysqli_close($con);
    
?>
