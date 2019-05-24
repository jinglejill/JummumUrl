<?php
    include_once("dbConnect.php");
    setConnectionValue("");
    writeToLog("file: " . basename(__FILE__) . ", user: " . $_POST["modifiedUser"]);
    printAllPost();
    ini_set("memory_limit","-1");
    
    
    if(isset($_POST["receiptID"]))
    {
        $receiptID = $_POST["receiptID"];
        $promoCodeID = $_POST["promoCodeID"];
        $modifiedUser = $_POST["modifiedUser"];
        $modifiedDate = $_POST["modifiedDate"];
    }


    // Check connection
    if (mysqli_connect_errno())
    {
        echo "Failed to connect to MySQL: " . mysqli_connect_error();
    }
    
    
    $sql = "select * from receipt where receiptID = '$receiptID';";
    $selectedRow = getSelectedRow($sql);
    $receiptStatus = $selectedRow[0]["Status"];
    if($receiptStatus != 1)
    {
        $warning = "สถานะมีการเปลี่ยนแปลง กรุณาดูสถานะล่าสุดที่หน้าจออีกครั้งหนึ่ง";
        $sql .= "select '$warning' as Text";
        $dataJson = executeMultiQueryArray($sql);
        
        
        mysqli_close($con);
        writeToLog("query commit, file: " . basename(__FILE__) . ", user: " . $_POST['modifiedUser']);
        $response = array('status' => '1', 'sql' => $sql, 'tableName' => 'ReceiptAndPromoCode', dataJson => $dataJson);
        echo json_encode($response);
        exit();
        
    }
    
    
    
    //update receipt
    $sql = "update receipt set status = 3, statusRoute = concat(statusRoute,',','3'),modifiedUser = '$modifiedUser',modifiedDate = '$modifiedDate' where receiptID = '$receiptID'";
    $ret = doQueryTask($sql);
    $ratingID = mysqli_insert_id($con);
    if($ret != "")
    {
        mysqli_rollback($con);
//        putAlertToDevice();
        echo json_encode($ret);
        exit();
    }
    
    
    //update promoCode
    if($promoCodeID != 0)
    {
        $sql = "update promoCode set status = 1,modifiedUser = '$modifiedUser',modifiedDate = '$modifiedDate' where PromoCodeID = '$promoCodeID'";
        $ret = doQueryTask($sql);
        if($ret != "")
        {
            mysqli_rollback($con);
    //        putAlertToDevice();
            echo json_encode($ret);
            exit();
        }
    }
    
    
    //do script successful
    mysqli_commit($con);
    
    
    $sql = "select * from receipt where receiptID = '$receiptID';";
    $sql .= "select '' as Text";
    $dataJson = executeMultiQueryArray($sql);

    mysqli_close($con);
    writeToLog("query commit, file: " . basename(__FILE__) . ", user: " . $_POST['modifiedUser']);
    $response = array('status' => '1', 'sql' => $sql, 'tableName' => 'ReceiptAndPromoCode', dataJson => $dataJson);
    echo json_encode($response);
    exit();
    
?>
