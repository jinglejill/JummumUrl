<?php
    include_once("dbConnect.php");
    setConnectionValue($jummumOM);
    writeToLog("file: " . basename(__FILE__) . ", user: " . $_POST["modifiedUser"]);
    printAllPost();
    ini_set("memory_limit","-1");
    
    
    if(isset($_POST["branchID"]) && isset($_POST["customerTableID"]))
    {
        $branchID = $_POST["branchID"];
        $customerTableID = $_POST["customerTableID"];
    }
    
    
    
    
    
    // Check connection
    if (mysqli_connect_errno())
    {
        echo "Failed to connect to MySQL: " . mysqli_connect_error();
    }
    
    
    //select table -> branch, customerTable
    $sql = "SELECT * FROM Branch where status = 1 and customerApp = 1 and branchID = '$branchID';";

    

    //build sql statement for table
    $selectedRow = getSelectedRow($sql);
    if(sizeof($selectedRow)>0)
    {
        $eachDbName = $selectedRow[0]["DbName"];
        $sqlCustomerTable = "select $branchID as BranchID, $eachDbName.CustomerTable.* from $eachDbName.CustomerTable";
    }
    else
    {
        $sqlCustomerTable = "select * from Branch where 0";
    }
    $sql .= $sqlCustomerTable . ";";
    
    
    
    /* execute multi query */
    $jsonEncode = executeMultiQuery($sql);
    echo $jsonEncode;


    
    // Close connections
    mysqli_close($con);
    
?>
