<?php
    include_once("dbConnect.php");
    setConnectionValue($jummum);
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
    
    
    
    $message = $receiptID . "," . date("Y-m-d H:i:s");
    $sql = "select hex(aes_encrypt('$message','$encryptKey')) as EncryptedMessage;";
    $selectedRow = getSelectedRow($sql);
    $encryptedMessage = $selectedRow[0]["EncryptedMessage"];
    
    
//    /* execute multi query */
//    $jsonEncode = executeMultiQueryArray($sql);
    
    
    header('Location: ' . 'https://chart.googleapis.com/chart?chs=400x400&cht=qr&chl=' . $encryptedMessage . '&choe=UTF-8');
    
    
//    $response = array('success' => true, 'data' => $jsonEncode, 'error' => null, 'status' => 1);
//    echo json_encode($response);

    
    // Close connections
    mysqli_close($con);
    
?>
