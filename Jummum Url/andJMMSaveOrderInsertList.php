<?php
    include_once("dbConnect.php");
    setConnectionValue($jummum);
//    writeToLog("file: " . basename(__FILE__) . ", user: " . $_POST["modifiedUser"]);
    printAllPost();
    
    
    header("Content-Type: application/json");
    
    // get the lower case rendition of the headers of the request
    
    $headers = array_change_key_case(getallheaders());
    
    // extract the content-type
    
    if (isset($headers["content-type"]))
    {
        $content_type = $headers["content-type"];
    }
    else
    {
        $content_type = "";
    }
    
    // if JSON, read and parse it
    if ($content_type == "application/json" || strpos($content_type,"application/json")!== false)
    {
        // read it
        $handle = fopen("php://input", "rb");
        $raw_post_data = '';
        while (!feof($handle)) {
            $raw_post_data .= fread($handle, 8192);
        }
        fclose($handle);
        
        // parse it
        
        $data = json_decode($raw_post_data, true);
    }
    else
    {
        // report non-JSON request and exit
    }
    
    
    writeToLog("file: " . basename(__FILE__) . ", user: " . $data["modifiedUser"]);
    writeToLog("json data: " . json_encode($data));
    //saveReceipt
    {
        $saveReceiptID = $data["saveReceiptID"];
        $branchID = $data["branchID"];
        $customerTableID = $data["customerTableID"];
        $memberID = $data["memberID"];
        $remark = $data["remark"];
        $status = $data["status"];
        $buffetReceiptID = $data["buffetReceiptID"];
        $voucherCode = $data["voucherCode"];
        $modifiedUser = $data["modifiedUser"];
        $modifiedDate = $data["modifiedDate"];
    }
    
    //saveOrderTaking
    {
        $arrSaveOrderTaking = $data["saveOrderTaking"];
        for($i=0; $i<sizeof($arrSaveOrderTaking); $i++)
        {
            $saveOrderTaking = $arrSaveOrderTaking[$i];
            
            $otSaveOrderTakingID[$i] = $saveOrderTaking["saveOrderTakingID"];
            $otBranchID[$i] = $saveOrderTaking["branchID"];
            $otCustomerTableID[$i] = $saveOrderTaking["customerTableID"];
            $otMenuID[$i] = $saveOrderTaking["menuID"];
            $otQuantity[$i] = $saveOrderTaking["quantity"];
            $otSpecialPrice[$i] = $saveOrderTaking["specialPrice"];
            $otPrice[$i] = $saveOrderTaking["price"];
            $otTakeAway[$i] = $saveOrderTaking["takeAway"];
            $otTakeAwayPrice[$i] = $saveOrderTaking["takeAwayPrice"];
//            $otNoteIDListInText[$i] = $saveOrderTaking["noteIDListInText"];
            $otNotePrice[$i] = $saveOrderTaking["notePrice"];
//            $otDiscountValue[$i] = $saveOrderTaking["discountValue"];
            $otOrderNo[$i] = $saveOrderTaking["orderNo"];
            $otStatus[$i] = 1;
            $otSaveReceiptID[$i] = $saveOrderTaking["saveReceiptID"];
            $otModifiedUser[$i] = $saveOrderTaking["modifiedUser"];
            $otModifiedDate[$i] = $saveOrderTaking["modifiedDate"];
        }
    }

    //saveOrderNote
    {
        $arrSaveOrderNote = $data["saveOrderNote"];
        for($i=0; $i<sizeof($arrSaveOrderNote); $i++)
        {
            $saveOrderNote = $arrSaveOrderNote[$i];
            
            $onSaveOrderNoteID[$i] = $saveOrderNote["saveOrderNoteID"];
            $onSaveOrderTakingID[$i] = $saveOrderNote["saveOrderTakingID"];
            $onNoteID[$i] = $saveOrderNote["noteID"];
            $onQuantity[$i] = $saveOrderNote["quantity"];
            $onModifiedUser[$i] = $saveOrderNote["modifiedUser"];
            $onModifiedDate[$i] = $saveOrderNote["modifiedDate"];
        }
    }
    
    
    for($i=0; $i<sizeof($arrOrderTaking); $i++)
    {
        for($j=0; $j<sizeof($arrOrderNote); $j++)
        {
            if($arrOrderTaking[$i]["orderTakingID"] == $arrOrderNote[$j]["orderTakingID"])
            {
                if($arrOrderTaking[$i]["noteIDListInText"] == "")
                {
                    $arrOrderTaking[$i]["noteIDListInText"] = $arrOrderNote[$j]["noteID"];
                }
                else
                {
                    $arrOrderTaking[$i]["noteIDListInText"] .= "," . $arrOrderNote[$j]["noteID"];
                }
            }
        }
    }
    
    
    // Check connection
    if (mysqli_connect_errno())
    {
        echo "Failed to connect to MySQL: " . mysqli_connect_error();
    }



    // Set autocommit to off
    mysqli_autocommit($con,FALSE);
    writeToLog("set auto commit to off");
    
    
    //SaveReceipt
    $sql = "INSERT INTO SaveReceipt(BranchID, CustomerTableID, MemberID, Remark, Status, BuffetReceiptID, VoucherCode, ModifiedUser, ModifiedDate) VALUES ('$branchID', '$customerTableID', '$memberID', '$remark', '$status', '$buffetReceiptID', '$voucherCode', '$modifiedUser', '$modifiedDate')";
    $ret = doQueryTask($sql);
    if($ret != "")
    {
        mysqli_rollback($con);
//            putAlertToDevice();
        echo json_encode($ret);
        exit();
    }
    
    
    //insert ผ่าน
    $saveReceiptID = mysqli_insert_id($con);
    
    
    
    //saveOrderTakingList
    $saveOrderTakingOldNew = array();
    if(sizeof($arrSaveOrderTaking) > 0)
    {
        for($k=0; $k<sizeof($arrSaveOrderTaking); $k++)
        {
            //query statement
             $sql = "INSERT INTO SaveOrderTaking(BranchID, CustomerTableID, MenuID, Quantity, SpecialPrice, Price, TakeAway, TakeAwayPrice, NoteIDListInText, NotePrice, DiscountValue, OrderNo, Status, SaveReceiptID, ModifiedUser, ModifiedDate) VALUES ('$otBranchID[$k]', '$otCustomerTableID[$k]', '$otMenuID[$k]', '$otQuantity[$k]', '$otSpecialPrice[$k]', '$otPrice[$k]', '$otTakeAway[$k]', '$otTakeAwayPrice[$k]', '$otNoteIDListInText[$k]', '$otNotePrice[$k]', '$otDiscountValue[$k]', '$otOrderNo[$k]', '$otStatus[$k]', '$saveReceiptID', '$otModifiedUser[$k]', '$otModifiedDate[$k]')";
            $ret = doQueryTask($sql);
            if($ret != "")
            {
                mysqli_rollback($con);
                //                    putAlertToDevice();
                echo json_encode($ret);
                exit();
            }
            
            
            
            //insert ผ่าน
            $newID = mysqli_insert_id($con);
            
            
            
            
            //select row ที่แก้ไข ขึ้นมาเก็บไว้
            $saveOrderTakingOldNew[$otSaveOrderTakingID[$k]] = $newID;
            $otSaveOrderTakingID[$k] = $newID;
        }
    }
    //-----



    //saveOrderNoteList
    if(sizeof($arrSaveOrderNote) > 0)
    {
        for($k=0; $k<sizeof($arrSaveOrderNote); $k++)
        {
            //query statement
            $onSaveOrderTakingID[$k] = $saveOrderTakingOldNew[$onSaveOrderTakingID[$k]];
            $sql = "INSERT INTO SaveOrderNote(SaveOrderTakingID, NoteID, Quantity, ModifiedUser, ModifiedDate) VALUES ('$onSaveOrderTakingID[$k]', '$onNoteID[$k]', '$onQuantity[$k]', '$onModifiedUser[$k]', '$onModifiedDate[$k]')";
            $ret = doQueryTask($sql);
            if($ret != "")
            {
                mysqli_rollback($con);
                //                    putAlertToDevice();
                echo json_encode($ret);
                exit();
            }
            
            
            
            //insert ผ่าน
            $newID = mysqli_insert_id($con);
            
            
            
            //select row ที่แก้ไข ขึ้นมาเก็บไว้
            $onSaveOrderNoteID[$k] = $newID;
        }
    }
    //------
    
    $messageToEncrypt = "http://www.jummum.co/app/appStorePlayStore.php?shareMenuToOrder=";
    $sql = "select hex(aes_encrypt('$saveReceiptID','$encryptKey')) as EncryptedMessage;";
    $selectedRow = getSelectedRow($sql);
    $encryptShareMenuToOrder = $selectedRow[0]["EncryptedMessage"];


    $messageToEncrypt .= $encryptShareMenuToOrder;
    $sql = "select '$messageToEncrypt' as EncryptedMessage;";
    $dataJson = executeMultiQueryArray($sql);



    //do script successful
    mysqli_commit($con);
    mysqli_close($con);
    writeToLog("query commit, file: " . basename(__FILE__) . ", user: " . $data['modifiedUser']);

    header('Location: ' . 'https://chart.googleapis.com/chart?chs=400x400&cht=qr&chl=' . $messageToEncrypt . '&choe=UTF-8');
    
//    $response = array('success' => true, 'data' => $dataJson, 'error' => null);
//    echo json_encode($response);

    exit();
    
?>
