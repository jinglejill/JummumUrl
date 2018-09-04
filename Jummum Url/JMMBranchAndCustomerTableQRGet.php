<?php
    include_once("dbConnect.php");
    setConnectionValue($jummumOM);
    writeToLog("file: " . basename(__FILE__) . ", user: " . $_POST["modifiedUser"]);
    printAllPost();
    ini_set("memory_limit","-1");
    
    
    if(isset($_POST["decryptedMessage"]))
    {
        $decryptedMessage = $_POST["decryptedMessage"];
    }
    
    
    
    // Check connection
    if (mysqli_connect_errno())
    {
        echo "Failed to connect to MySQL: " . mysqli_connect_error();
    }
    
    
    $sql = "select aes_decrypt(unhex('$decryptedMessage'),'$encryptKey') as message;";
    $selectedRow = getSelectedRow($sql);
    $message = $selectedRow[0]["message"];
    $arrMessage = explode(",", $message);
    if(sizeof($arrMessage) == 2)
    {
        $branchPart = $arrMessage[0];
        $customerTablePart = $arrMessage[1];
        
        //branch
        $arrBranchPart = explode(":", $branchPart);
        if(sizeof($arrBranchPart) == 2)
        {
            $branchID = $arrBranchPart[1];
        }
        
        //customerTable
        $arrCustomerTablePart = explode(":", $customerTablePart);
        if(sizeof($arrCustomerTablePart) == 2)
        {
            $customerTableID = $arrCustomerTablePart[1];
        }
    }
    
    
    
    
    //select table -> branch, customerTable
    $sql = "SELECT * FROM Branch where status = 1 and customerApp = 1 and branchID = '$branchID';";

    

    //build sql statement for table
    $selectedRow = getSelectedRow($sql);
    if(sizeof($selectedRow)>0)
    {
        $eachDbName = $selectedRow[0]["DbName"];
        $sqlCustomerTable = "select $branchID as BranchID, $eachDbName.CustomerTable.* from $eachDbName.CustomerTable where customerTableID = '$customerTableID'";
    }
    else
    {
        $sqlCustomerTable = "select * from branch where 0";
    }
    $sql .= $sqlCustomerTable . ";";
    
    
    
    /* execute multi query */
    $jsonEncode = executeMultiQueryArray($sql);
    $response = array('success' => true, 'data' => $jsonEncode, 'error' => null, 'status' => 1);
    echo json_encode($response);

    
    // Close connections
    mysqli_close($con);
    
?>
