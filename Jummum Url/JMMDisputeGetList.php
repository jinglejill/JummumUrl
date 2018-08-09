<?php
    include_once("dbConnect.php");
    setConnectionValue("");
    writeToLog("file: " . basename(__FILE__) . ", user: " . $_POST["modifiedUser"]);
    printAllPost();
    ini_set("memory_limit","-1");
    
    
    
    
    if(isset($_POST["receiptID"]) && isset($_POST["status"]))
    {
        $receiptID = $_POST["receiptID"];
        $status = $_POST["status"];
    }
    
    
    
    switch($status)
    {
        case 7:
        case 9:
        {
            $type = 1;
        }
            break;
        case 8:
        case 10:
        case 11:
        {
            $type = 2;
        }
            break;
        case 12:
        case 13:
        case 14:
        {
            $type = 5;
        }
            break;

    }
    
    // Check connection
    if (mysqli_connect_errno())
    {
        echo "Failed to connect to MySQL: " . mysqli_connect_error();
    }
    
    
    
    $sql = "Select * from Dispute where receiptID = '$receiptID' and type = '$type';";
    
    
    
    
    /* execute multi query */
    $jsonEncode = executeMultiQuery($sql);
    echo $jsonEncode;


    
    // Close connections
    mysqli_close($con);
    
?>
