<?php
    include_once("dbConnect.php");
    setConnectionValue("");
    writeToLog("file: " . basename(__FILE__) . ", user: " . $_POST["modifiedUser"]);
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
    
    
    writeToLog("json data: " . json_encode($data));
    {
        $receiptID = $data["receiptID"];
        $branchID = $data["branchID"];
        $customerTableID = $data["customerTableID"];
        $memberID = $data["memberID"];
        $servingPerson = $data["servingPerson"];
        $customerType = $data["customerType"];
        $openTableDate = $data["openTableDate"];
        $cashAmount = $data["cashAmount"];
        $cashReceive = $data["cashReceive"];
        $creditCardType = $data["creditCardType"];
        $creditCardNo = $data["creditCardNo"];
        $creditCardAmount = $data["creditCardAmount"];
        $transferDate = $data["transferDate"];
        $transferAmount = $data["transferAmount"];
        $remark = $data["remark"];
        $discountType = $data["discountType"];
        $discountAmount = $data["discountAmount"];
        $discountValue = $data["discountValue"];
        $discountReason = $data["discountReason"];
        $serviceChargePercent = $data["serviceChargePercent"];
        $serviceChargeValue = $data["serviceChargeValue"];
        $priceIncludeVat = $data["priceIncludeVat"];
        $vatPercent = $data["vatPercent"];
        $vatValue = $data["vatValue"];
        $status = $data["status"];
        $statusRoute = $data["statusRoute"];
        $receiptNoID = $data["receiptNoID"];
        $receiptNoTaxID = $data["receiptNoTaxID"];
        $receiptDate = $data["receiptDate"];
        $sendToKitchenDate = $data["sendToKitchenDate"];
        $deliveredDate = $data["deliveredDate"];
        $mergeReceiptID = $data["mergeReceiptID"];
        $buffetReceiptID = $data["buffetReceiptID"];
        $voucherCode = $data["voucherCode"];
        $modifiedUser = $data["modifiedUser"];
        $modifiedDate = $data["modifiedDate"];
    }
    
    {
        $arrOrderTaking = $data["orderTaking"];
        for($i=0; $i<sizeof($arrOrderTaking); $i++)
        {
            $orderTaking = $arrOrderTaking[$i];
            
            
            $otOrderTakingID[$i] = $orderTaking["orderTakingID"];
            $otBranchID[$i] = $orderTaking["branchID"];
            $otCustomerTableID[$i] = $orderTaking["customerTableID"];
            $otMenuID[$i] = $orderTaking["menuID"];
            $otQuantity[$i] = $orderTaking["quantity"];
            $otSpecialPrice[$i] = $orderTaking["specialPrice"];
            $otPrice[$i] = $orderTaking["price"];
            $otTakeAway[$i] = $orderTaking["takeAway"];
            $otNoteIDListInText[$i] = $orderTaking["noteIDListInText"];
            $otOrderNo[$i] = $orderTaking["orderNo"];
            $otStatus[$i] = $orderTaking["status"];
            $otReceiptID[$i] = $orderTaking["receiptID"];
            $otModifiedUser[$i] = $orderTaking["modifiedUser"];
            $otModifiedDate[$i] = $orderTaking["modifiedDate"];
        }
    }
    
    
    $arrOrderNote = $data["orderNote"];
    for($i=0; $i<sizeof($arrOrderNote); $i++)
    {
        $orderNote = $arrOrderNote[$i];
        
        
        $onOrderNoteID[$i] = $orderNote["orderNoteID"];
        $onOrderTakingID[$i] = $orderNote["orderTakingID"];
        $onNoteID[$i] = $orderNote["noteID"];
        $onModifiedUser[$i] = $orderNote["modifiedUser"];
        $onModifiedDate[$i] = $orderNote["modifiedDate"];
    }
    
    
    
    //validate shop opening time*******************
    $sql = "select * from $jummumOM.branch where branchID = '$branchID'";
    $selectedRow = getSelectedRow($sql);
    $selectedDbName = $selectedRow[0]["DbName"];
    
    
    
    $inOpeningTime = 0;
    $sql = "select * from $selectedDbName.Setting where keyName = 'customerOrderStatus'";
    $selectedRow = getSelectedRow($sql);
    $customerOrderStatus = $selectedRow[0]["Value"];
    if($customerOrderStatus == 1)
    {
        $inOpeningTime = 1;
    }
    else if($customerOrderStatus == 2)
    {
        $inOpeningTime = 0;
    }
    else
    {
        //get today's opening time
        $strDate = date("Y-m-d");
        $currentDate = date("Y-m-d H:i:s");
        $dayOfWeek = date('w', strtotime($strDate));
        $sql = "select * from $selectedDbName.OpeningTime where day = '$dayOfWeek' order by day,shiftNo";
        $selectedRow = getSelectedRow($sql);
        
        for($i=0; $i<sizeof($selectedRow); $i++)
        {
            $day = $selectedRow[$i]["Day"];
            $startTime = $selectedRow[$i]["StartTime"];
            $endTime = $selectedRow[$i]["EndTime"];
            
            
            
            $intStartTime = intVal(str_replace(":","",$startTime));
            $intEndTime = intVal(str_replace(":","",$endTime));
            if($intStartTime < $intEndTime)
            {
                $startDate = date($strDate . " " . $startTime . ":00");
                $endDate = date($strDate . " " . $endTime . ":00");
                if($startDate<=$currentDate && $currentDate<=$endDate)
                {
                    $inOpeningTime = 1;
                }
            }
            else
            {
                $nextDate = date("Y-m-d", strtotime($strDate. ' + 1 days'));
                $startDate = date($strDate . " " . $startTime . ":00");
                $endDate = date($nextDate . " " . $endTime . ":00");                
                if($startDate<=$currentDate && $currentDate<=$endDate)
                {
                    $inOpeningTime = 1;
                }
            }
        }
    }
    
    

    
    if(!$inOpeningTime)
    {
        writeToLog("omise charge fail, file: " . basename(__FILE__) . ", user: " . $_POST['modifiedUser']);
        $response = array('status' => '2', 'msg' => 'ทางร้านไม่ได้เปิดระบบการสั่งอาหารด้วยตนเองตอนนี้ ขออภัยในความไม่สะดวกค่ะ');
        echo json_encode($response);
        exit();
    }
    /////////******************
    
    
    
    //validate menu
    //validate menuNote
    $arrOrderTakingNew = array();
    $arrOrderNoteNew = array();
    $orderChanged = 0;
    $sql = "select * from $jummumOM.branch where branchID = '$branchID';";
    $selectedRow = getSelectedRow($sql);
    $dbName = $selectedRow[0]["DbName"];
//    $takeAwayFee = $selectedRow[0]["TakeAwayFee"];
    for($i=0; $i<sizeof($arrOrderTaking); $i++)
    {
        $menuID = $arrOrderTaking[$i]["menuID"];
        $sql = "select menu.*, case when specialPriceProgramDay.specialPriceProgramDayID is null then menu.price else ifnull(specialPriceProgram.SpecialPrice,menu.price) end AS SpecialPrice from $dbName.menu LEFT JOIN $dbName.specialPriceProgram ON menu.menuID = specialPriceProgram.menuID AND date_format(now(),'%Y-%m-%d') between date_format(specialPriceProgram.startDate,'%Y-%m-%d') and date_format(specialPriceProgram.endDate,'%Y-%m-%d') left join $dbName.specialPriceProgramDay on specialPriceProgram.specialPriceProgramID = specialPriceProgramDay.specialPriceProgramID and specialPriceProgramDay.Day = weekday(now())+1 where status = 1 and menu.menuID = '$menuID'";
        $selectedRow = getSelectedRow($sql);
        if(sizeof($selectedRow) == 0)
        {
            $orderChanged = 1;
            writeToLog("menu status not active, file: " . basename(__FILE__) . ", user: " . $data['modifiedUser']);
        }
        else
        {
            //buffetMap
//            if($buffetReceiptID)
            {
                $sql = "select Menu.* from receipt LEFT JOIN ordertaking ON receipt.ReceiptID = ordertaking.ReceiptID LEFT JOIN $dbName.BuffetMenuMap on orderTaking.MenuID = BuffetMenuMap.BuffetMenuID LEFT JOIN $dbName.Menu on BuffetMenuMap.MenuID = Menu.MenuID where receipt.receiptID = '$buffetReceiptID' and BuffetMenuMap.menuID is not null and BuffetMenuMap.Status = 1 and Menu.status = 1 and menu.menuID = '$menuID'";
                $selectedRow = getSelectedRow($sql);
                if(sizeof($selectedRow) == 0)
                {
                    $orderChanged = 1;
                    writeToLog("buffet menu status not active, file: " . basename(__FILE__) . ", user: " . $data['modifiedUser']);
                    continue;
                }
            }
            
            
            
            $arrOrderTakingNew[] = $arrOrderTaking[$i];
            
        }
    }
    
    
    
    //make key capital letter for OrderTaking
    $arrOrderTakingNewCapitalKey = array();
    for($i=0; $i<sizeof($arrOrderTakingNew); $i++)
    {
        $orderTakingNewCapitalKey = array();
        $orderTakingNew = $arrOrderTakingNew[$i];
        foreach ($orderTakingNew as $key => $value)
        {
            $orderTakingNewCapitalKey[makeFirstLetterUpperCase($key)] = $value;
        }
        array_push($arrOrderTakingNewCapitalKey,$orderTakingNewCapitalKey);
    }
    
    
    //make key capital letter for orderNote
    $arrOrderNoteNewCapitalKey = array();
    for($i=0; $i<sizeof($arrOrderNoteNew); $i++)
    {
        $orderNoteNewCapitalKey = array();
        $orderNoteNew = $arrOrderNoteNew[$i];
        foreach ($orderNoteNew as $key => $value)
        {
            $orderNoteNewCapitalKey[makeFirstLetterUpperCase($key)] = $value;
        }
        array_push($arrOrderNoteNewCapitalKey,$orderNoteNewCapitalKey);
    }
    
    
    $dataList = array();
    $dataList[] = $arrOrderTakingNewCapitalKey;
    $dataList[] = $arrOrderNoteNewCapitalKey;
    
    
    
    if($orderChanged)
    {
        $warningMsg = "รายการอาหารที่คุณสั่งมีการเปลี่ยนแปลงบางส่วน กรุณาตรวจทานรายการที่คุณสั่งอีกครั้งค่ะ";
        writeToLog("รายการอาหารที่สั่งมีการอัพเดต: $warningMsg, file: " . basename(__FILE__) . ", user: " . $data['modifiedUser']);
        
        
        /* execute multi query */
        $response = array('status' => '2', 'msg' => $warningMsg, 'tableName' => 'OrderBelongToBuffet', dataJson => $dataList);
        echo json_encode($response);
        exit();
    }
    //------------
    
    

    
    
//    if($doReceiptProcess || $charge["status"] == "successful")//omise status
    {
        // Check connection
        if (mysqli_connect_errno())
        {
            echo "Failed to connect to MySQL: " . mysqli_connect_error();
        }
        
        
        
        // Set autocommit to off
        mysqli_autocommit($con,FALSE);
        writeToLog("set auto commit to off");
        
        
        
        //query statement
        $sql = "INSERT INTO Receipt(BranchID, CustomerTableID, MemberID, ServingPerson, CustomerType, OpenTableDate, CashAmount, CashReceive, CreditCardType, CreditCardNo, CreditCardAmount, TransferDate, TransferAmount, Remark, DiscountType, DiscountAmount, DiscountValue, DiscountReason, ServiceChargePercent, ServiceChargeValue, PriceIncludeVat, VatPercent, VatValue, Status, StatusRoute, ReceiptNoID, ReceiptNoTaxID, ReceiptDate, MergeReceiptID, BuffetReceiptID, VoucherCode, ModifiedUser, ModifiedDate) VALUES ('$branchID', '$customerTableID', '$memberID', '$servingPerson', '$customerType', '$openTableDate', '$cashAmount', '$cashReceive', '$creditCardType', '$creditCardNo', '$creditCardAmount', '$transferDate', '$transferAmount', '$remark', '$discountType', '$discountAmount', '$discountValue', '$discountReason', '$serviceChargePercent', '$serviceChargeValue', '$priceIncludeVat', '$vatPercent', '$vatValue', '$status', '$status', '$receiptNoID', '$receiptNoTaxID', '$receiptDate', '$mergeReceiptID', '$buffetReceiptID', '$voucherCode', '$modifiedUser', '$modifiedDate')";
        $ret = doQueryTask($sql);
        if($ret != "")
        {
            mysqli_rollback($con);
//            putAlertToDevice();
            echo json_encode($ret);
            exit();
        }
        
        
        
        //insert ผ่าน
        $newID = mysqli_insert_id($con);
        
        
        
        
        //update receiptNoID and
        //select row ที่แก้ไข ขึ้นมาเก็บไว้
        $receiptID = $newID;
        $receiptNoID = luhnAlgorithm(sprintf("%06d", $receiptID));
        $sql = "update Receipt set ReceiptNoID = '$receiptNoID' where ReceiptID = '$receiptID'";
        $ret = doQueryTask($sql);
        if($ret != "")
        {
            mysqli_rollback($con);
//            putAlertToDevice();
            echo json_encode($ret);
            exit();
        }

        
        $sql = "select * from Receipt where ReceiptID = '$receiptID';";
        $sqlAll = $sql;
        //-----
        
        
        
        
        //orderTakingList
        $orderTakingOldNew = array();
        if(sizeof($arrOrderTaking) > 0)
        {
            for($k=0; $k<sizeof($arrOrderTaking); $k++)
            {
                //query statement
                $sql = "INSERT INTO OrderTaking(BranchID, CustomerTableID, MenuID, Quantity, SpecialPrice, Price, TakeAway, NoteIDListInText, OrderNo, Status, ReceiptID, ModifiedUser, ModifiedDate) VALUES ('$otBranchID[$k]', '$otCustomerTableID[$k]', '$otMenuID[$k]', '$otQuantity[$k]', '$otSpecialPrice[$k]', '$otPrice[$k]', '$otTakeAway[$k]', '$otNoteIDListInText[$k]', '$otOrderNo[$k]', '$otStatus[$k]', '$receiptID', '$otModifiedUser[$k]', '$otModifiedDate[$k]')";
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
                $orderTakingOldNew[$otOrderTakingID[$k]] = $newID;
                $otOrderTakingID[$k] = $newID;
            }
            
            
            
            //**********sync device token อื่น
            //select row ที่แก้ไข ขึ้นมาเก็บไว้
            $sql = "select * from OrderTaking where OrderTakingID in ('$otOrderTakingID[0]'";
            for($i=1; $i<sizeof($arrOrderTaking); $i++)
            {
                $sql .= ",'$otOrderTakingID[$i]'";
            }
            $sql .= ");";
            $sqlAll .= $sql;
        }
        //-----
        
        
        
        //orderNoteList
        if(sizeof($arrOrderNote) > 0)
        {
            for($k=0; $k<sizeof($arrOrderNote); $k++)
            {
                //query statement
                $onOrderTakingID[$k] = $orderTakingOldNew[$onOrderTakingID[$k]];
                $sql = "INSERT INTO OrderNote(OrderTakingID, NoteID, ModifiedUser, ModifiedDate) VALUES ('$onOrderTakingID[$k]', '$onNoteID[$k]', '$onModifiedUser[$k]', '$onModifiedDate[$k]')";
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
                $onOrderNoteID[$k] = $newID;
            }
            
            
            
            //**********sync device token อื่น
            //select row ที่แก้ไข ขึ้นมาเก็บไว้
            $sql = "select * from OrderNote where OrderNoteID in ('$onOrderNoteID[0]'";
            for($i=1; $i<sizeof($arrOrderNote); $i++)
            {
                $sql .= ",'$onOrderNoteID[$i]'";
            }
            $sql .= ");";
            $sqlAll .= $sql;
        }
        //------
        /* execute multi query */
        $dataJson = executeMultiQueryArray($sqlAll);
        
        
        
        
        
        

        //-----****************************
        //get pushSync Device in JUMMUM OM
        $pushSyncDeviceTokenReceiveOrder = array();
        $sql = "select * from $jummumOM.device left join $jummumOM.Branch on $jummumOM.device.DbName = $jummumOM.Branch.DbName where branchID = '$branchID';";
        $selectedRow = getSelectedRow($sql);
        for($i=0; $i<sizeof($selectedRow); $i++)
        {
            $deviceToken = $selectedRow[$i]["DeviceToken"];
            array_push($pushSyncDeviceTokenReceiveOrder,$deviceToken);
        }
        //-----****************************
        

        $category = "printKitchenBill";
        $contentAvailable = 1;
        $data = array("receiptID" => $receiptID);
        $msg = 'New order coming!! order no:' . $receiptNoID;
        sendPushNotificationJummumOM($pushSyncDeviceTokenReceiveOrder,$title,$msg,$category,$contentAvailable,$data);
        //****************send noti to shop (turn on light)
        $ledStatus = 1;
        $sql = "update $jummumOM.Branch set LedStatus = '$ledStatus', ModifiedUser = '$modifiedUser', ModifiedDate = '$modifiedDate' where branchID = '$branchID';";
        $ret = doQueryTask($sql);
        if($ret != "")
        {
            mysqli_rollback($con);
            //        putAlertToDevice();
            echo json_encode($ret);
            exit();
        }
        //****************
        
        
        
        
        
        
        //do script successful
        mysqli_commit($con);
        mysqli_close($con);
        writeToLog("query commit, file: " . basename(__FILE__) . ", user: " . $_POST['modifiedUser']);
        $response = array('status' => '1', 'sql' => $sql, 'tableName' => 'BuffetOrder', dataJson => $dataJson);
        
        echo json_encode($response);
        
        exit();
    }

?>
