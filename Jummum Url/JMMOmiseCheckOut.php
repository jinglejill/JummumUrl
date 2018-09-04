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
        writeToLog("set contentType: " . $content_type);
    }
    else
    {
        $content_type = "";
        writeToLog("not set contentType: " . $content_type);
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
    
    
    writeToLog("data from omise pay: " . json_encode($data));
    
    
    {
        $omiseToken = $data["omiseToken"];
        $amount = $data["amount"];
    }
    
    {
        $promoCodeID = $data["promoCodeID"];
    }
    
    
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
    
    
    $userPromotionUsed = $data["userPromotionUsed"];
    if($userPromotionUsed)
    {
        $prUserPromotionUsedID = $userPromotionUsed["userPromotionUsedID"];
        $prUserAccountID = $userPromotionUsed["userAccountID"];
        $prPromotionID = $userPromotionUsed["promotionID"];
        $prReceiptID = $userPromotionUsed["receiptID"];
        $prModifiedUser = $userPromotionUsed["modifiedUser"];
        $prModifiedDate = $userPromotionUsed["modifiedDate"];
    }
    
    
    $userRewardRedemptionUsed = $data["userRewardRedemptionUsed"];
    if($userRewardRedemptionUsed)
    {
        $prUserRewardRedemptionUsedID = $userRewardRedemptionUsed["userRewardRedemptionUsedID"];
        $prUserAccountID = $userRewardRedemptionUsed["userAccountID"];
        $prRewardRedemptionID = $userRewardRedemptionUsed["rewardRedemptionID"];
        $prReceiptID = $userRewardRedemptionUsed["receiptID"];
        $prModifiedUser = $userRewardRedemptionUsed["modifiedUser"];
        $prModifiedDate = $userRewardRedemptionUsed["modifiedDate"];
    }

    
    
    
    
    writeToLog('token : ' . $omiseToken);
    writeToLog('amount : ' . $amount);
    
    
    
    
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
    
    
    
    
    
    
    //omise part
    if($amount != 0)
    {
        require_once  dirname(__FILE__) . '/omise-php/lib/Omise.php';
        
        
        $sql = "select * from Setting where keyName = 'PublicKey'";
        $selectedRow = getSelectedRow($sql);
        $publicKey = $selectedRow[0]["Value"];
        $sql = "select * from Setting where keyName = 'SecretKey'";
        $selectedRow = getSelectedRow($sql);
        $secretKey = $selectedRow[0]["Value"];
        define('OMISE_PUBLIC_KEY', "$publicKey");
        define('OMISE_SECRET_KEY', "$secretKey");
        
        
        try
        {
            $charge = OmiseCharge::create(array(
                                                'amount'   => $amount,
                                                'currency' => 'THB',
                                                'card'     => "$omiseToken"
                                                ));
            
        }
        catch (Exception $e)
        {
            writeToLog("omise charge fail, file: " . basename(__FILE__) . ", user: " . $_POST['modifiedUser']);
            $response = array('status' => '2', 'msg' => $e->getMessage());
            echo json_encode($response);
            exit();
        }
    }
    else
    {
        $doReceiptProcess = 1;
    }
    
    
    if($doReceiptProcess || $charge["status"] == "successful")//omise status
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
        for ($i = 0; $i<2; $i++)
        {
            $a .= mt_rand(0,9);
        }
        $receiptNoID = sprintf("%06d", $receiptID) . $a;
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
        
        
        
        
        if($userPromotionUsed)
        {
            //user promotion used - voucher code
            if($prPromotionID != 0)
            {
                //query statement
                $sql = "INSERT INTO UserPromotionUsed(UserAccountID, PromotionID, ReceiptID, ModifiedUser, ModifiedDate) VALUES ('$prUserAccountID', '$prPromotionID', '$receiptID', '$prModifiedUser', '$prModifiedDate')";
                $ret = doQueryTask($sql);
                if($ret != "")
                {
                    mysqli_rollback($con);
                    //                    putAlertToDevice();
                    echo json_encode($ret);
                    exit();
                }
            }
        }
        else
        {
            //user rewardRedemption used - voucher code
            if($prRewardRedemptionID != 0)
            {
                //query statement
                $sql = "INSERT INTO UserRewardRedemptionUsed(UserAccountID, RewardRedemptionID, ReceiptID, ModifiedUser, ModifiedDate) VALUES ('$prUserAccountID', '$prRewardRedemptionID', '$receiptID', '$prModifiedUser', '$prModifiedDate')";
                $ret = doQueryTask($sql);
                if($ret != "")
                {
                    mysqli_rollback($con);
                    //                    putAlertToDevice();
                    echo json_encode($ret);
                    exit();
                }
                
                
                
                //query statement
                $sql = "update promoCode set status = 2, modifiedUser = '$modifiedUser', modifiedDate = '$modifiedDate' where promoCodeID = '$promoCodeID'";
                $ret = doQueryTask($sql);
                if($ret != "")
                {
                    mysqli_rollback($con);
                    //                    putAlertToDevice();
                    echo json_encode($ret);
                    exit();
                }
            }
        }
        
        
        
        
        
        
        //reward
        $sql = "SELECT * FROM `rewardprogram` WHERE StartDate <= now() and EndDate >= now() and type = 1 order by modifiedDate desc";
        $selectedRow = getSelectedRow($sql);
        if(sizeof($selectedRow)>0)
        {
            $salesSpent = $selectedRow[0]["SalesSpent"];
            $receivePoint = $selectedRow[0]["ReceivePoint"];
            $rewardPoint = $amount/100.0/$salesSpent*$receivePoint;
            
            
            $sql = "INSERT INTO `rewardpoint`(`MemberID`, `ReceiptID`, `Point`, `Status`, `ModifiedUser`, `ModifiedDate`) VALUES ('$memberID','$receiptID','$rewardPoint',1,'$modifiedUser','$modifiedDate')";
            $sql = "INSERT INTO RewardPoint(MemberID, ReceiptID, Point, Status, PromoCodeID, ModifiedUser, ModifiedDate) VALUES ('$memberID', '$receiptID', '$rewardPoint', '1', '0', '$modifiedUser', '$modifiedDate')";
            $ret = doQueryTask($sql);
            if($ret != "")
            {
                mysqli_rollback($con);
//                putAlertToDevice();
                echo json_encode($ret);
                exit();
            }
        }
        //-----********
        
        
        
        
        
        //-----****************************
        //****************send noti to shop (turn on light)
        //alarmShop
        //query statement
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
        mysqli_commit($con);
        //****************
        
        
        
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
        


        $msg = 'New order coming!! receipt No:' . $receiptNoID;
        $category = "printKitchenBill";
        $contentAvailable = 1;
        $data = array("receiptID" => $receiptID);
        sendPushNotificationJummumOM($pushSyncDeviceTokenReceiveOrder,$title,$msg,$category,$contentAvailable,$data);

        
        
        
        
        
        
        //do script successful
        mysqli_commit($con);
        mysqli_close($con);
        
        
        
        
        writeToLog("query commit, file: " . basename(__FILE__) . ", user: " . $_POST['modifiedUser']);
        $response = array('status' => '1', 'sql' => $sql, 'tableName' => 'OmiseCheckOut', dataJson => $dataJson);
        
        echo json_encode($response);
        
        exit();
    }
    else
    {
        writeToLog("omise charge fail, file: " . basename(__FILE__) . ", user: " . $_POST['modifiedUser']);
        $response = array('status' => '2', 'msg' => 'ตัดบัตรเครดิตไม่สำเร็จ กรุณาตรวจสอบข้อมูลบัตรเครดิตใหม่อีกครั้ง');
        echo json_encode($response);
        exit();
    }


?>
