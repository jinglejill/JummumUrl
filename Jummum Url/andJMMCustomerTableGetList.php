<?php
    include_once("dbConnect.php");
    setConnectionValue($jummumOM);
    writeToLog("file: " . basename(__FILE__) . ", user: " . $_POST["modifiedUser"]);
    printAllPost();
    ini_set("memory_limit","-1");
    
    
    if(isset($_POST["branchID"]))
    {
        $branchID = $_POST["branchID"];
    }
    
    
    
    // Check connection
    if (mysqli_connect_errno())
    {
        echo "Failed to connect to MySQL: " . mysqli_connect_error();
    }
    
    
    
    
    //select table -> branch, customerTable
    $sql = "SELECT * FROM $jummumOM.Branch where branchID = '$branchID';";
    $selectedRow = getSelectedRow($sql);
    $dbName = $selectedRow[0]["DbName"];
    
    $sql = "select Name as Zone from $dbName.Zone where status = 1 order by orderNo";
    $arrResult = executeQueryArray($sql);
    for($i=0; $i<sizeof($arrResult); $i++)
    {
        $customerTableZone = $arrResult[$i];
        $zone = $customerTableZone->Zone;
        $sql = "select $branchID as BranchID, `CustomerTableID`, `TableName`, `Zone` from $dbName.CustomerTable where status = 1  and zone = '$zone' order by OrderNo";
        $arrResultCustomerTable = executeQueryArray($sql);
        $customerTableZone->CustomerTable = $arrResultCustomerTable;
    }
//    $sql = "select $branchID as BranchID, `CustomerTableID`, `TableName`, `Zone` from $dbName.CustomerTable where status = 1";

    
    
    /* execute multi query */
    $jsonEncode = executeQueryArray($sql);
    $response = array('success' => true, 'data' => $arrResult, 'error' => null);
    echo json_encode($response);


    
    // Close connections
    mysqli_close($con);
    
?>
