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
        
        
        $status = 2;
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

    for($i=0; $i<sizeof($arrOrderTaking); $i++)
    {
        for($j=0; $j<sizeof($arrOrderNote); $j++)
        {
            if($otOrderTakingID[$i] == $onOrderTakingID[$j])
            {
                if($otNoteIDListInText[$i] == "")
                {
                    $otNoteIDListInText[$i] = $onNoteID[$j];
                }
                else
                {
                    $otNoteIDListInText[$i] .= "," . $onNoteID[$j];
                }
            }
        }
    }
    
    //pay or order buffet button
    $comeFromBuffetButton = 1;
    
    
    
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
//
//
//        $sql = "select * from Receipt where ReceiptID = '$receiptID';";
//        $sqlAll = $sql;
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
            
//
//
//            //**********sync device token อื่น
//            //select row ที่แก้ไข ขึ้นมาเก็บไว้
//            $sql = "select * from OrderTaking where OrderTakingID in ('$otOrderTakingID[0]'";
//            for($i=1; $i<sizeof($arrOrderTaking); $i++)
//            {
//                $sql .= ",'$otOrderTakingID[$i]'";
//            }
//            $sql .= ");";
//            $sqlAll .= $sql;
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
            
            
            
//            //**********sync device token อื่น
//            //select row ที่แก้ไข ขึ้นมาเก็บไว้
//            $sql = "select * from OrderNote where OrderNoteID in ('$onOrderNoteID[0]'";
//            for($i=1; $i<sizeof($arrOrderNote); $i++)
//            {
//                $sql .= ",'$onOrderNoteID[$i]'";
//            }
//            $sql .= ");";
//            $sqlAll .= $sql;
        }
        //------
//        /* execute multi query */
//        $dataJson = executeMultiQueryArray($sqlAll);
        
        
        
        
        //return receipt detail
        $sql = "select `ReceiptID`, `BranchID`, `CustomerTableID`, `MemberID`, `TotalAmount`, `CreditCardType`, `CreditCardNo`, `CreditCardAmount`, `Remark`,`SpecialPriceDiscount`,DiscountProgramType,DiscountProgramTitle,DiscountProgramValue, `DiscountType`, `DiscountValue`, `ServiceChargePercent`, `ServiceChargeValue`, `PriceIncludeVat`, `VatPercent`, `VatValue`,NetTotal,LuckyDrawCount,BeforeVat, `Status`, `ReceiptNoID`, `ReceiptDate`, `SendToKitchenDate`, `DeliveredDate`, `BuffetReceiptID`,HasBuffetMenu,TimeToOrder,BuffetEnded,BuffetEndedDate, `VoucherCode`, case `Status` when 2 then 'Order sent' when 5 then 'Processing...' when 6 then 'Delivered' when 7 then 'Pending cancel' when 8 then 'Order dispute in process' when 9 then 'Order cancelled' when 10 then 'Order dispute finished' when 11 then 'Negotiate' when 12 then 'Review dispute' when 13 then 'Review dispute in process' when 14 then 'Order dispute finished' end as StatusText from receipt where receiptID = '$receiptID';";
        $arrReceipt = executeQueryArray($sql);
        
        
        for($i=0; $i<sizeof($arrReceipt); $i++)
        {
            $customerTableID = $arrReceipt[$i]->CustomerTableID;
            $branchID = $arrReceipt[$i]->BranchID;
            $receiptID = $arrReceipt[$i]->ReceiptID;
            
            
            //branch
            $sql2 = "select DbName, `BranchID`, `Name`, `TakeAwayFee`, `ServiceChargePercent`, `PercentVat`, `PriceIncludeVat`, `ImageUrl` from $jummumOM.branch where branchID = '$branchID'";
            $arrBranch = executeQueryArray($sql2);
            $arrReceipt[$i]->Branch = $arrBranch;
            $eachDbName = $arrBranch[0]->DbName;
            unset($arrBranch[0]->DbName);
            
            
            //CustomerTable
            $sql2 = "select $branchID as BranchID, `CustomerTableID`, `TableName`, `Zone` from $eachDbName.CustomerTable where CustomerTableID = '$customerTableID'";
            $arrCustomerTable = executeQueryArray($sql2);
            $arrReceipt[$i]->CustomerTable = $arrCustomerTable;
            
            
            //OrderTaking
            $sql = "select `BranchID`, `CustomerTableID`, `ReceiptID`, sum(Quantity) Quantity, TakeAway, TakeAwayPrice, ordertaking.`MenuID`, NoteIDListInText, NotePrice, sum(`SpecialPrice`)SpecialPrice, sum(DiscountValue) DiscountValue from OrderTaking left join $eachDbName.menu on ordertaking.MenuID =  $eachDbName.menu.menuID LEFT JOIN  $eachDbName.menutype ON menuType.menuTypeID =  menu.menuTypeID where receiptID = '$receiptID' GROUP by `BranchID`, `CustomerTableID`,`ReceiptID`,takeAway, menuType.MenuTypeID,  menu.MenuID, ordertaking.`MenuID`, noteIDListInText order by takeAway,  menuType.orderNo,  menu.orderNo, noteIDListInText";
            $arrOrderTaking = executeQueryArray($sql);
            $arrReceipt[$i]->OrderTaking = $arrOrderTaking;
            
            
            //Menu
            for($j=0; $j<sizeof($arrOrderTaking); $j++)
            {
                $menuID = $arrOrderTaking[$j]->MenuID;
                $branchID = $arrOrderTaking[$j]->BranchID;
                $sql3 = "select * from $jummumOM.branch where branchID = '$branchID'";
                $selectedRow3 = getSelectedRow($sql3);
                $eachDbName = $selectedRow3[0]["DbName"];
                $mainBranchID = $selectedRow3[0]["MainBranchID"];
                if($branchID != $mainBranchID)
                {
                    $sql3 = "select * from $jummumOM.branch where branchID = '$mainBranchID'";
                    $selectedRow3 = getSelectedRow($sql3);
                    $eachDbName = $selectedRow3[0]["DbName"];
                }
                
                
                //Menu
                $sql3 = "select '$branchID' BranchID, menu.MenuID, `MenuCode`, `TitleThai`, `Price`, `MenuTypeID`, `BuffetMenu`, `BelongToMenuID`, `TimeToOrder`, `ImageUrl`, `OrderNo`, ifnull(specialPriceProgram.SpecialPrice,menu.price) SpecialPrice from $eachDbName.Menu LEFT JOIN $eachDbName.specialPriceProgram ON menu.menuID = specialPriceProgram.menuID AND date_format(now(),'%Y-%m-%d') between date_format(specialPriceProgram.startDate,'%Y-%m-%d') and date_format(specialPriceProgram.endDate,'%Y-%m-%d') where menu.menuID = '$menuID'";
                $arrMenu = executeQueryArray($sql3);
                $arrOrderTaking[$j]->Menu = $arrMenu;
                
                
                //Note
                if($arrOrderTaking[$j]->NoteIDListInText == "")
                {
                    $noteIDListInText = 0;
                }
                else
                {
                    $noteIDListInText = $arrOrderTaking[$j]->NoteIDListInText;
                }
                $sql3 = "select `NoteID`, Note.`Name`, Note.`NameEn`, `Price`, Note.`NoteTypeID`, `Type` from $eachDbName.Note left join $eachDbName.NoteType on Note.NoteTypeID = NoteType.NoteTypeID where noteID in ($noteIDListInText) order by NoteType.OrderNo, Note.OrderNo;";
                $arrNote = executeQueryArray($sql3);
                $arrOrderTaking[$j]->Note = $arrNote;
            }
        }
        
        //BuffetReceiptID
        if($hasBuffetMenu)
        {
            $buffetReceiptID = $receiptID;
        }
        $showOrderBuffetButton = $hasBuffetMenu || $buffetReceiptID;
        
        
        //JummumLogo
        $sql = "select * from setting where KeyName = 'JummumLogo'";
        $selectedRow = getSelectedRow($sql);
        $jummumLogo = $selectedRow[0]["Value"];
        $arrReceipt[0]->JummumLogo = $jummumLogo;
        
        
        $dataList = array();
        array_push($dataList,$arrReceipt);
//        array_push($dataList,$arrLuckyDrawTicket);
        $buffetList = array();
        $thankYouText = "สั่งบุฟเฟ่ต์สำเร็จ";
        array_push($buffetList,array("ThankYouText"=>$thankYouText, "ShowOrderBuffetButton"=>$showOrderBuffetButton, "BuffetReceiptID"=>$buffetReceiptID));
        array_push($dataList,$buffetList);
        //-------------------------
        
        
        
        

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
        $msg = 'New order coming!! receipt No:' . $receiptNoID;
        sendPushNotificationJummumOM($pushSyncDeviceTokenReceiveOrder,$title,$msg,$category,$contentAvailable,$data);
//        sendPushNotificationToDeviceWithPath($pushSyncDeviceTokenReceiveOrder,"./../$jummumOM/",'jill',$msg,$receiptID,'printKitchenBill',1);
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
        $response = array('success' => true, 'data' => $dataList, 'error' => null);
        
        echo json_encode($response);
        
        exit();
    }

?>
