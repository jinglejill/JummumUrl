<?php
    include_once("dbConnect.php");
    setConnectionValue("");
    writeToLog("file: " . basename(__FILE__) . ", user: " . $_POST["modifiedUser"]);
    printAllPost();
    
    
    
    if(isset($_POST["omiseToken"]) && isset($_POST["amount"]))
    {
        $omiseToken = $_POST["omiseToken"];
        $amount = $_POST["amount"];
    }
    
    if(isset($_POST["promoCodeID"]))
    {
        $promoCodeID = $_POST["promoCodeID"];
    }
    
    
    if(isset($_POST["receiptID"]) && isset($_POST["branchID"]) && isset($_POST["customerTableID"]) && isset($_POST["memberID"]) && isset($_POST["servingPerson"]) && isset($_POST["customerType"]) && isset($_POST["openTableDate"]) && isset($_POST["cashAmount"]) && isset($_POST["cashReceive"]) && isset($_POST["creditCardType"]) && isset($_POST["creditCardNo"]) && isset($_POST["creditCardAmount"]) && isset($_POST["transferDate"]) && isset($_POST["transferAmount"]) && isset($_POST["remark"]) && isset($_POST["discountType"]) && isset($_POST["discountAmount"]) && isset($_POST["discountValue"]) && isset($_POST["discountReason"]) && isset($_POST["serviceChargePercent"]) && isset($_POST["serviceChargeValue"]) && isset($_POST["priceIncludeVat"]) && isset($_POST["vatPercent"]) && isset($_POST["vatValue"]) && isset($_POST["status"]) && isset($_POST["statusRoute"]) && isset($_POST["receiptNoID"]) && isset($_POST["receiptNoTaxID"]) && isset($_POST["receiptDate"]) && isset($_POST["mergeReceiptID"]) && isset($_POST["modifiedUser"]) && isset($_POST["modifiedDate"]))
    {
        $receiptID = $_POST["receiptID"];
        $branchID = $_POST["branchID"];
        $customerTableID = $_POST["customerTableID"];
        $memberID = $_POST["memberID"];
        $servingPerson = $_POST["servingPerson"];
        $customerType = $_POST["customerType"];
        $openTableDate = $_POST["openTableDate"];
        $cashAmount = $_POST["cashAmount"];
        $cashReceive = $_POST["cashReceive"];
        $creditCardType = $_POST["creditCardType"];
        $creditCardNo = $_POST["creditCardNo"];
        $creditCardAmount = $_POST["creditCardAmount"];
        $transferDate = $_POST["transferDate"];
        $transferAmount = $_POST["transferAmount"];
        $remark = $_POST["remark"];
        $discountType = $_POST["discountType"];
        $discountAmount = $_POST["discountAmount"];
        $discountValue = $_POST["discountValue"];
        $discountReason = $_POST["discountReason"];
        $serviceChargePercent = $_POST["serviceChargePercent"];
        $serviceChargeValue = $_POST["serviceChargeValue"];
        $priceIncludeVat = $_POST["priceIncludeVat"];
        $vatPercent = $_POST["vatPercent"];
        $vatValue = $_POST["vatValue"];
        $status = $_POST["status"];
        $statusRoute = $_POST["statusRoute"];
        $receiptNoID = $_POST["receiptNoID"];
        $receiptNoTaxID = $_POST["receiptNoTaxID"];
        $receiptDate = $_POST["receiptDate"];
        $mergeReceiptID = $_POST["mergeReceiptID"];
        $modifiedUser = $_POST["modifiedUser"];
        $modifiedDate = $_POST["modifiedDate"];
    }
    
    if (isset($_POST["countOtOrderTaking"]))
    {
        $countOtOrderTaking = $_POST["countOtOrderTaking"];
        for($i=0; $i<$countOtOrderTaking; $i++)
        {
            $otOrderTakingID[$i] = $_POST["otOrderTakingID".sprintf("%02d", $i)];
            $otBranchID[$i] = $_POST["otBranchID".sprintf("%02d", $i)];
            $otCustomerTableID[$i] = $_POST["otCustomerTableID".sprintf("%02d", $i)];
            $otMenuID[$i] = $_POST["otMenuID".sprintf("%02d", $i)];
            $otQuantity[$i] = $_POST["otQuantity".sprintf("%02d", $i)];
            $otSpecialPrice[$i] = $_POST["otSpecialPrice".sprintf("%02d", $i)];
            $otPrice[$i] = $_POST["otPrice".sprintf("%02d", $i)];
            $otTakeAway[$i] = $_POST["otTakeAway".sprintf("%02d", $i)];
            $otNoteIDListInText[$i] = $_POST["otNoteIDListInText".sprintf("%02d", $i)];
            $otOrderNo[$i] = $_POST["otOrderNo".sprintf("%02d", $i)];
            $otStatus[$i] = $_POST["otStatus".sprintf("%02d", $i)];
            $otReceiptID[$i] = $_POST["otReceiptID".sprintf("%02d", $i)];
            $otModifiedUser[$i] = $_POST["otModifiedUser".sprintf("%02d", $i)];
            $otModifiedDate[$i] = $_POST["otModifiedDate".sprintf("%02d", $i)];
        }
    }
    
    if (isset($_POST["countOnOrderNote"]))
    {
        $countOnOrderNote = $_POST["countOnOrderNote"];
        for($i=0; $i<$countOnOrderNote; $i++)
        {
            $onOrderNoteID[$i] = $_POST["onOrderNoteID".sprintf("%02d", $i)];
            $onOrderTakingID[$i] = $_POST["onOrderTakingID".sprintf("%02d", $i)];
            $onNoteID[$i] = $_POST["onNoteID".sprintf("%02d", $i)];
            $onModifiedUser[$i] = $_POST["onModifiedUser".sprintf("%02d", $i)];
            $onModifiedDate[$i] = $_POST["onModifiedDate".sprintf("%02d", $i)];
        }
    }

    $type = $_POST["type"];
    if($type == 1)
    {
        if(isset($_POST["prUserPromotionUsedID"]) && isset($_POST["prUserAccountID"]) && isset($_POST["prPromotionID"]) && isset($_POST["prReceiptID"]) && isset($_POST["prModifiedUser"]) && isset($_POST["prModifiedDate"]))
        {
            $prUserPromotionUsedID = $_POST["prUserPromotionUsedID"];
            $prUserAccountID = $_POST["prUserAccountID"];
            $prPromotionID = $_POST["prPromotionID"];
            $prReceiptID = $_POST["prReceiptID"];
            $prModifiedUser = $_POST["prModifiedUser"];
            $prModifiedDate = $_POST["prModifiedDate"];
        }
    }
    else
    {
        if(isset($_POST["prUserRewardRedemptionUsedID"]) && isset($_POST["prUserAccountID"]) && isset($_POST["prRewardRedemptionID"]) && isset($_POST["prReceiptID"]) && isset($_POST["prModifiedUser"]) && isset($_POST["prModifiedDate"]))
        {
            $prUserRewardRedemptionUsedID = $_POST["prUserRewardRedemptionUsedID"];
            $prUserAccountID = $_POST["prUserAccountID"];
            $prRewardRedemptionID = $_POST["prRewardRedemptionID"];
            $prReceiptID = $_POST["prReceiptID"];
            $prModifiedUser = $_POST["prModifiedUser"];
            $prModifiedDate = $_POST["prModifiedDate"];
        }
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
        
        
        $charge = OmiseCharge::create(array(
                                            'amount'   => $amount,
                                            'currency' => 'THB',
                                            'card'     => "$omiseToken"
                                            ));
        
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
        $sql = "INSERT INTO Receipt(BranchID, CustomerTableID, MemberID, ServingPerson, CustomerType, OpenTableDate, CashAmount, CashReceive, CreditCardType, CreditCardNo, CreditCardAmount, TransferDate, TransferAmount, Remark, DiscountType, DiscountAmount, DiscountValue, DiscountReason, ServiceChargePercent, ServiceChargeValue, PriceIncludeVat, VatPercent, VatValue, Status, StatusRoute, ReceiptNoID, ReceiptNoTaxID, ReceiptDate, MergeReceiptID, ModifiedUser, ModifiedDate) VALUES ('$branchID', '$customerTableID', '$memberID', '$servingPerson', '$customerType', '$openTableDate', '$cashAmount', '$cashReceive', '$creditCardType', '$creditCardNo', '$creditCardAmount', '$transferDate', '$transferAmount', '$remark', '$discountType', '$discountAmount', '$discountValue', '$discountReason', '$serviceChargePercent', '$serviceChargeValue', '$priceIncludeVat', '$vatPercent', '$vatValue', '$status', '$status', '$receiptNoID', '$receiptNoTaxID', '$receiptDate', '$mergeReceiptID', '$modifiedUser', '$modifiedDate')";
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
        if($countOtOrderTaking > 0)
        {
            for($k=0; $k<$countOtOrderTaking; $k++)
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
            for($i=1; $i<$countOtOrderTaking; $i++)
            {
                $sql .= ",'$otOrderTakingID[$i]'";
            }
            $sql .= ");";
            $sqlAll .= $sql;
        }
        //-----
        
        
        
        //orderNoteList
        if($countOnOrderNote > 0)
        {
            for($k=0; $k<$countOnOrderNote; $k++)
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
            for($i=1; $i<$countOnOrderNote; $i++)
            {
                $sql .= ",'$onOrderNoteID[$i]'";
            }
            $sql .= ");";
            $sqlAll .= $sql;
        }
        //------
        /* execute multi query */
        $dataJson = executeMultiQueryArray($sqlAll);
        
        
        
        
        if($type == 1)
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
        
        
        
        
        
        
        //insert into pushsync of db branchID
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
        


        $msg = 'New order coming!! receipt No:' . $receiptNoID;
//        $msg = 'New order coming!! receipt No:' . $receiptNoID . ' ,noti time:' . date("Y/m/d H:i:s");

        
        
        
        
        

        
        
        
        
        
        //do script successful
        
        sendPushNotificationToDeviceWithPath($pushSyncDeviceTokenReceiveOrder,"./../$jummumOM/",'jill',$msg,$receiptID,'printKitchenBill',1);
        //****************send noti to shop (turn on light)
//        if($alarmShop == 1)
        {
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
        }
        //****************
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
