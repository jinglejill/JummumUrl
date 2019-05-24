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
    
    
    //select table -> branch, customerTable
    $sql = "select * from transferForm where receiptID = '$receiptID' order by transferFormID desc limit 1;";
    $sql .= "Select * from Bank where status = 1 order by orderNo;";
    
    
    /* execute multi query */
    $jsonEncode = executeMultiQueryArray($sql);
    $response = array('success' => true, 'data' => $jsonEncode, 'error' => null);
    echo json_encode($response);


    
    // Close connections
    mysqli_close($con);
    
?>
