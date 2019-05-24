<?php

    include_once("dbConnect.php");
    setConnectionValue("");
    
    
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
    
    writeToLog("file: " . basename(__FILE__) . ", user: " . $data["modifiedUser"]);
    writeToLog("json data: " . json_encode($data));
    
    
    {
        $omiseToken = $data["omiseToken"];
        $voucherCode = $data["voucherCode"];
        
        $userAccountID = $data["memberID"];
        $currentDateTime = date("Y-m-d H:i:s");
    }
    
    {
        $branchID = $data["branchID"];
        $customerTableID = $data["customerTableID"];
        $memberID = $data["memberID"];
        $paymentMethod = $data["paymentMethod"];
        $creditCardType = $data["creditCardType"];
        $creditCardNo = $data["creditCardNo"];
        $remark = $data["remark"];
        $status = ($omiseToken == "") && ($paymentMethod == 1)?1:2;
        $receiptDate = $data["receiptDate"];
        $buffetReceiptID = $data["buffetReceiptID"];
        $modifiedUser = $data["modifiedUser"];
        $modifiedDate = $data["modifiedDate"];
    }
    
    $arrOrderTaking = $data["orderTaking"];
    $arrOrderNote = $data["orderNote"];

    
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
    
    
    //get dbName
    $sql = "select * from $jummumOM.branch where branchID = '$branchID';";
    $selectedRow = getSelectedRow($sql);
    $dbName = $selectedRow[0]["DbName"];
    $takeAwayFee = $selectedRow[0]["TakeAwayFee"];
    
    
    //validate shop opening time*******************
    {
        $inOpeningTime = 0;
        $sql = "select * from $dbName.Setting where keyName = 'customerOrderStatus'";
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
            $sql = "select * from $dbName.OpeningTime where day = '$dayOfWeek' order by day,shiftNo";
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
    }
    /////////******************
    
    
    //validate changed
    //menu,buffetMenu,price,menuNote
    if($inOpeningTime)
    {
        $arrOrderTakingNew = array();
        $arrOrderNoteNew = array();
        $orderChanged = 0;
        for($i=0; $i<sizeof($arrOrderTaking); $i++)
        {
            $menuID = $arrOrderTaking[$i]["menuID"];
            $sql = "select menu.* from $dbName.menu where status = 1 and menu.menuID = '$menuID'";
            $selectedRow = getSelectedRow($sql);
            if(sizeof($selectedRow) == 0)
            {
                $orderChanged = 1;
                writeToLog("menu status not active, file: " . basename(__FILE__) . ", user: " . $data['modifiedUser']);
            }
            else
            {
                //**get SpecialPrice
                {
                    $sql = "select * from $dbName.SpecialPriceProgram left join $dbName.SpecialPriceProgramDay on specialPriceProgram.specialPriceProgramID = specialPriceProgramDay.specialPriceProgramID and specialPriceProgramDay.Day = weekday('$currentDateTime')+1 where menuID = '$menuID' AND '$currentDateTime' between startDate and endDate and specialPriceProgramDayID is not null order by SpecialPriceProgram.ModifiedDate desc";
                    $selectedRowSpecialPrice = getSelectedRow($sql);
                    if(sizeof($selectedRowSpecialPrice)>0)
                    {
                        $selectedRow[0]["SpecialPrice"] = $selectedRowSpecialPrice[0]["SpecialPrice"];
                    }
                    else
                    {
                        $selectedRow[0]["SpecialPrice"] = $selectedRow[0]["Price"];
                    }
                }
                //**
                
                
                //buffetMap
                if($buffetReceiptID)
                {
                    $sql = "select Menu.* from receipt LEFT JOIN ordertaking ON receipt.ReceiptID = ordertaking.ReceiptID LEFT JOIN $dbName.BuffetMenuMap on orderTaking.MenuID = BuffetMenuMap.BuffetMenuID LEFT JOIN $dbName.Menu on BuffetMenuMap.MenuID = Menu.MenuID where receipt.receiptID = '$buffetReceiptID' and BuffetMenuMap.menuID is not null and BuffetMenuMap.Status = 1 and Menu.status = 1 and menu.menuID = '$menuID'";
                    $selectedRow2 = getSelectedRow($sql);
                    if(sizeof($selectedRow2) == 0)
                    {
                        $orderChanged = 1;
                        writeToLog("buffet menu status not active, file: " . basename(__FILE__) . ", user: " . $data['modifiedUser']);
                        continue;
                    }
                }



                $arrOrderTakingNew[] = $arrOrderTaking[$i];


                //specialPrice
                if($selectedRow[0]["SpecialPrice"] != $arrOrderTaking[$i]["specialPrice"])
                {
                    $arrOrderTakingNew[sizeof($arrOrderTakingNew)-1]["specialPrice"] = $selectedRow[0]["SpecialPrice"];
                    $arrOrderTakingNew[sizeof($arrOrderTakingNew)-1]["price"] = $selectedRow[0]["Price"];
                    $orderChanged = 1;
                    writeToLog("menu special price changed, file: " . basename(__FILE__) . ", user: " . $data['modifiedUser']);
                }


                //takeawayPrice
                if($arrOrderTaking[$i]["takeAway"] && $takeAwayFee != $arrOrderTaking[$i]["takeAwayPrice"])
                {
                    $arrOrderTakingNew[sizeof($arrOrderTakingNew)-1]["takeAwayPrice"] = $takeAwayFee;
                    $orderChanged = 1;
                    writeToLog("take away price changed, file: " . basename(__FILE__) . ", user: " . $data['modifiedUser']);
                }


                //notePrice
                if($arrOrderTaking[$i]["noteIDListInText"] != "")
                {
                    $noteIDListInTextNew = "";
                    $notePriceNew = 0;
                    $arrNoteID = explode(",",$arrOrderTaking[$i]["noteIDListInText"]);
                    for($j=0; $j<sizeof($arrNoteID); $j++)
                    {
                        $noteID = $arrNoteID[$j];
                        $sql = "select * from $dbName.menuNote left join $dbName.Note on menuNote.NoteID = Note.NoteID where menuNote.status = 1 and Note.status = 1 and menuID = '$menuID' and menuNote.noteID = '$noteID';";
                        $selectedRow = getSelectedRow($sql);
                        if(sizeof($selectedRow) == 0)
                        {
                            $orderChanged = 1;
                            writeToLog("menuNote or note status not active, file: " . basename(__FILE__) . ", user: " . $data['modifiedUser']);
                        }
                        else
                        {
                            $noteIDListInTextNew .= $noteIDListInTextNew == ""?$noteID:",".$noteID;



                            //orderNote
                            for($k=0; $k<sizeof($arrOrderNote); $k++)
                            {
                                if($arrOrderNote[$k]["orderTakingID"] == $arrOrderTaking[$i]["orderTakingID"] && $arrOrderNote[$k]["noteID"] == $noteID)
                                {
                                    $arrOrderNoteNew[] = $arrOrderNote[$k];
                                    break;
                                }
                            }
                            $notePriceNew += $selectedRow[0]["Price"] * $arrOrderNoteNew[sizeof($arrOrderNoteNew)-1]["quantity"];
                        }
                    }
                    if($arrOrderTaking[$i]["notePrice"] != $notePriceNew)
                    {
                        $orderChanged = 1;
                        writeToLog("note price changed, file: " . basename(__FILE__) . ", user: " . $data['modifiedUser']);
                    }



                    $arrOrderTakingNew[sizeof($arrOrderTakingNew)-1]["noteIDListInText"] = $noteIDListInTextNew;
                    $arrOrderTakingNew[sizeof($arrOrderTakingNew)-1]["notePrice"] = $notePriceNew;
                }
            }
        }
    }
    //******
    
    
    
    //get discount from discountProgram
    if($inOpeningTime)
    {
        $warningMsgOrderChanged = "";
        if($orderChanged)
        {
            $warningMsgOrderChanged = "รายการอาหารที่คุณสั่งมีการเปลี่ยนแปลงบางส่วน กรุณาตรวจทานรายการที่คุณสั่งอีกครั้งค่ะ";
            $arrOrderTaking = $arrOrderTakingNew;
            $arrOrderNote = $arrOrderNoteNew;
        }
        
        
        //set value after orderChange*****
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
            $otTakeAwayPrice[$i] = $orderTaking["takeAwayPrice"];
            $otNoteIDListInText[$i] = $orderTaking["noteIDListInText"];
            $otNotePrice[$i] = $orderTaking["notePrice"];
            $otDiscountValue[$i] = $orderTaking["discountValue"];
            $otOrderNo[$i] = $orderTaking["orderNo"];
            $otStatus[$i] = $orderTaking["status"];
            $otReceiptID[$i] = $orderTaking["receiptID"];
            $otModifiedUser[$i] = $orderTaking["modifiedUser"];
            $otModifiedDate[$i] = $orderTaking["modifiedDate"];
        }

        for($i=0; $i<sizeof($arrOrderNote); $i++)
        {
            $orderNote = $arrOrderNote[$i];

            $onOrderNoteID[$i] = $orderNote["orderNoteID"];
            $onOrderTakingID[$i] = $orderNote["orderTakingID"];
            $onNoteID[$i] = $orderNote["noteID"];
            $onQuantity[$i] = $orderNote["quantity"];
            $onModifiedUser[$i] = $orderNote["modifiedUser"];
            $onModifiedDate[$i] = $orderNote["modifiedDate"];
        }
        //*****


        //get totalPrice
        $totalAmount = 0;
        for($i=0; $i<sizeof($arrOrderTaking); $i++)
        {
            $totalAmount += $otPrice[$i] + $otTakeAwayPrice[$i] + $otNotePrice[$i];
        }

        //get sumSpecialPrice
        $sumSpecialPrice = 0;
        for($i=0; $i<sizeof($arrOrderTaking); $i++)
        {
            $sumSpecialPrice += $otSpecialPrice[$i] + $otTakeAwayPrice[$i] + $otNotePrice[$i];
        }

        //has buffet menu
        $hasBuffetMenu = 0;
        $timeToOrder = 0;
        for($i=0; $i<sizeof($arrOrderTaking); $i++)
        {
            $sql = "select * from $dbName.Menu where menuID = '$otMenuID[$i]'";
            $selectedRow = getSelectedRow($sql);
            $buffetMenu = $selectedRow[0]["BuffetMenu"];
            if($buffetMenu)
            {
                $hasBuffetMenu = 1;
                $timeToOrder = $selectedRow[0]["TimeToOrder"];
                break;
            }
        }
        

        // Set autocommit to off
        mysqli_autocommit($con,FALSE);
        writeToLog("set auto commit to off");


        
        //เช็คส่วนลดแล้ว return ไปทีเดียว
        //discountProgram
        //discountProgramDay
        //discountProgramUser
        if($sumSpecialPrice > 0)
        {
            $returnDiscount = 0;
            $arrDiscount = array();
            $sql = "select a.*, (select count(*) CurrentNoOfUse from $dbName.discountProgramUser where discountProgramUser.discountProgramID = a.discountProgramID) CurrentNoOfUse, (select count(*) CurrentNoOfUsePerUser from $dbName.discountProgramUser where discountProgramUser.UserAccountID = '$memberID' and discountProgramUser.discountProgramID = a.discountProgramID) CurrentNoOfUsePerUser, (select count(*) CurrentNoOfUsePerUserPerDay from $dbName.discountProgramUser where discountProgramUser.UserAccountID = '$memberID' and discountProgramUser.discountProgramID = a.discountProgramID and date_format(discountProgramUser.modifiedDate,'%Y-%m-%d') = date_format('$currentDateTime','%Y-%m-%d')) CurrentNoOfUsePerUserPerDay from $dbName.DiscountProgram a left join $dbName.DiscountProgramDay on a.DiscountProgramID = DiscountProgramDay.DiscountProgramID where ('$currentDateTime' between startDate and endDate) and DiscountProgramDay.Day = weekday('$currentDateTime')+1 order by DiscountType";
            $discountProgram = getSelectedRow($sql);
            for($k=0; $k<sizeof($discountProgram); $k++)
            {
                $arrOrderTakingParticipate = array();
                $menuParticipateValue = 0;
                $discountProgramID = $discountProgram[$k]["DiscountProgramID"];
                $discountType = $discountProgram[$k]["DiscountType"];
                $discountTitle = $discountProgram[$k]["Title"];
                $amount = $discountProgram[$k]["Amount"];
                $minimumSpend = $discountProgram[$k]["MinimumSpend"];
                $noOfLimitUse = $discountProgram[$k]["NoOfLimitUse"];
                $noOfLimitUsePerUser = $discountProgram[$k]["NoOfLimitUsePerUser"];
                $noOfLimitUsePerUserPerDay = $discountProgram[$k]["NoOfLimitUsePerUserPerDay"];
                $currentNoOfUse = $discountProgram[$k]["CurrentNoOfUse"];
                $currentNoOfUsePerUser = $discountProgram[$k]["CurrentNoOfUsePerUser"];
                $currentNoOfUsePerUserPerDay = $discountProgram[$k]["CurrentNoOfUsePerUserPerDay"];
                $discountGroupMenuID = $discountProgram[$k]["DiscountGroupMenuID"];
                $discountStepID = $discountProgram[$k]["DiscountStepID"];
                $discountOnTop = $discountProgram[$k]["DiscountOnTop"];


                if($discountType == 1 || $discountType == 2)//baht, percent
                {
                    if(($noOfLimitUsePerUserPerDay == 0 || $currentNoOfUsePerUserPerDay < $noOfLimitUsePerUserPerDay) && ($noOfLimitUsePerUser == 0 || $currentNoOfUsePerUser < $noOfLimitUsePerUser) && ($noOfLimitUse == 0 || $currentNoOfUse < $noOfLimitUse))
                    {
                        if($discountGroupMenuID == 0)
                        {
                            for($i=0; $i<sizeof($arrOrderTaking); $i++)
                            {
                                if($discountOnTop || ($arrOrderTaking[$i]["price"] == $arrOrderTaking[$i]["specialPrice"]))
                                {
                                    $menuParticipateValue += $arrOrderTaking[$i]["specialPrice"];
                                    $arrOrderTakingParticipate[] = &$arrOrderTaking[$i];
                                }
                            }
                        }
                        else
                        {
                            $sql = "select * from $dbName.discountGroupMenuMap where discountGroupMenuID = '$discountGroupMenuID' and status = 1";
                            $discountGroupMenuMap = getSelectedRow($sql);
                            for($j=0; $j<sizeof($discountGroupMenuMap); $j++)
                            {
                                $menuID = $discountGroupMenuMap[$j]["MenuID"];
                                for($i=0; $i<sizeof($arrOrderTaking); $i++)
                                {
                                    if(($menuID == $arrOrderTaking[$i]["menuID"]) && ($discountOnTop || ($arrOrderTaking[$i]["price"] == $arrOrderTaking[$i]["specialPrice"])))
                                    {
                                        $menuParticipateValue += $arrOrderTaking[$i]["specialPrice"];
                                        $arrOrderTakingParticipate[] = &$arrOrderTaking[$i];
                                    }
                                }
                            }
                        }
                    }

                    if($menuParticipateValue >= $minimumSpend)
                    {
                        if($discountType == 1)
                        {
    //                        $discountValueType1 = $menuParticipateValue<$amount?$menuParticipateValue:$amount;
                            $discountValueType1 = $amount;
                            writeToLog("discountProgramType:$discountType,discountProgramTitle:$discountTitle, discountProgramValue:$discountValueType1");
                            if($discountValueType1 > $returnDiscount)
                            {
                                $returnDiscount = $discountValueType1;
                                $discountFromDiscountProgram = array("DiscountProgramID" => $discountProgramID, "Title" => $discountTitle, "DiscountValue" => $returnDiscount, "OrderTaking" => $arrOrderTakingParticipate);
                            }
                        }
                        else if($discountType == 2)
                        {
                            $discountValueType2 = round($menuParticipateValue*$amount*0.01 * 10000)/10000;
                            writeToLog("discountProgramType:$discountType,discountProgramTitle:$discountTitle, discountProgramValue:$discountValueType2");
                            if($discountValueType2 > $returnDiscount)
                            {
                                $returnDiscount = $discountValueType2;
                                $discountFromDiscountProgram = array("DiscountProgramID" => $discountProgramID, "Title" => $discountTitle, "DiscountValue" => $returnDiscount, "OrderTaking" => $arrOrderTakingParticipate);
                            }
                        }
                    }
                }
                else if($discountType == 3)//buy 1 get 1, buy 2 get 1, buy 3 get 1
                {
                    $noOfUseLeftPerUserPerDay  = $noOfLimitUsePerUserPerDay-$currentNoOfUsePerUserPerDay;
                    $noOfUseLeftPerUser = $noOfLimitUsePerUser-$currentNoOfUsePerUser;
                    $noOfUseLeft = $noOfLimitUse-$currentNoOfUse;
                    
                    $unlimitUse = 0;
                    if(($noOfLimitUse == 0) && ($noOfLimitUsePerUser == 0) && ($noOfLimitUsePerUserPerDay == 0))
                    {
                        $unlimitUse = 1;
                    }
                    else if(($noOfLimitUse == 0) && ($noOfLimitUsePerUser == 0) && !($noOfLimitUsePerUserPerDay == 0))
                    {
                        $noOfUseLeft = $noOfUseLeftPerUserPerDay;
                    }
                    else if(($noOfLimitUse == 0) && !($noOfLimitUsePerUser == 0) && ($noOfLimitUsePerUserPerDay == 0))
                    {
                        $noOfUseLeft = $noOfUseLeftPerUser;
                    }
                    else if(!($noOfLimitUse == 0) && ($noOfLimitUsePerUser == 0) && ($noOfLimitUsePerUserPerDay == 0))
                    {
                        $noOfUseLeft = $noOfUseLeft;
                    }
                    else if(($noOfLimitUse == 0) && !($noOfLimitUsePerUser == 0) && !($noOfLimitUsePerUserPerDay == 0))
                    {
                        $noOfUseLeft = $noOfUseLeftPerUser < $noOfUseLeftPerUserPerDay?$noOfUseLeftPerUser:$noOfUseLeftPerUserPerDay;
                    }
                    else if(!($noOfLimitUse == 0) && ($noOfLimitUsePerUser == 0) && !($noOfLimitUsePerUserPerDay == 0))
                    {
                        $noOfUseLeft = $noOfUseLeft < $noOfUseLeftPerUserPerDay?$noOfUseLeft:$noOfUseLeftPerUserPerDay;
                    }
                    else if(!($noOfLimitUse == 0) && !($noOfLimitUsePerUser == 0) && ($noOfLimitUsePerUserPerDay == 0))
                    {
                        $noOfUseLeft = $noOfUseLeft < $noOfUseLeftPerUser?$noOfUseLeft:$noOfUseLeftPerUser;
                    }
                    else
                    {
                        $noOfUseLeft = $noOfUseLeft < $noOfUseLeftPerUser?$noOfUseLeft:$noOfUseLeftPerUser;
                        $noOfUseLeft = $noOfUseLeft < $noOfUseLeftPerUserPerDay?$noOfUseLeft:$noOfUseLeftPerUserPerDay;
                    }
                    
                    
                    if($unlimitUse || $noOfUseLeft > 0)
                    {
                        $arrMenuParticipateValue = array();
                        if($discountGroupMenuID == 0)
                        {
                            for($i=0; $i<sizeof($arrOrderTaking); $i++)
                            {
                                if($discountOnTop || ($arrOrderTaking[$i]["price"] == $arrOrderTaking[$i]["specialPrice"]))
                                {
                                    $arrMenuParticipateValue[] = $arrOrderTaking[$i]["specialPrice"];
                                    $arrOrderTakingParticipate[] = &$arrOrderTaking[$i];
                                }
                            }
                        }
                        else
                        {
                            $sql = "select * from $dbName.discountGroupMenuMap where discountGroupMenuID = '$discountGroupMenuID' and status = 1";
                            $discountGroupMenuMap = getSelectedRow($sql);
                            for($j=0; $j<sizeof($discountGroupMenuMap); $j++)
                            {
                                $menuID = $discountGroupMenuMap[$j]["MenuID"];
                                for($i=0; $i<sizeof($arrOrderTaking); $i++)
                                {
                                    if(($menuID == $arrOrderTaking[$i]["menuID"]) && ($discountOnTop || ($arrOrderTaking[$i]["price"] == $arrOrderTaking[$i]["specialPrice"])))
                                    {
                                        $arrMenuParticipateValue[] = $arrOrderTaking[$i]["specialPrice"];
                                        $arrOrderTakingParticipate[] = &$arrOrderTaking[$i];
                                    }
                                }
                            }
                        }



                        if(sizeof($arrMenuParticipateValue) >= $amount+1)
                        {
                            rsort($arrMenuParticipateValue);
                            $noOfUseThisTime = floor(sizeof($arrMenuParticipateValue)/($amount+1)) > $noOfUseLeft?$noOfUseLeft:floor(sizeof($arrMenuParticipateValue)/($amount+1));
                            $noOfUseThisTime = $unlimitUse?floor(sizeof($arrMenuParticipateValue)/($amount+1)):$noOfUseThisTime;
                            for($i=1; $i<=$noOfUseThisTime; $i++)
                            {
                                $discountValueType3 += $arrMenuParticipateValue[$i*($amount+1)-1];
                            }
                            writeToLog("discountProgramType:$discountType,discountProgramTitle:$discountTitle, discountProgramValue:$discountValueType3");
                            if($discountValueType3 > $returnDiscount)
                            {
                                $returnDiscount = $discountValueType3;
                                $discountFromDiscountProgram = array("DiscountProgramID" => $discountProgramID, "Title" => $discountTitle, "DiscountValue" => $returnDiscount, "OrderTaking" => $arrOrderTakingParticipate);
                            }
                        }
                    }
                }
                else if($discountType == 4)//buy at least 2 get 20%
                {
                    $noOfItem = 0;
                    if(($noOfLimitUsePerUserPerDay == 0 || $currentNoOfUsePerUserPerDay < $noOfLimitUsePerUserPerDay) && ($noOfLimitUsePerUser == 0 || $currentNoOfUsePerUser < $noOfLimitUsePerUser) && ($noOfLimitUse == 0 || $currentNoOfUse < $noOfLimitUse))
                    {
                        if($discountGroupMenuID == 0)
                        {
                            for($i=0; $i<sizeof($arrOrderTaking); $i++)
                            {
                                if($discountOnTop || ($arrOrderTaking[$i]["price"] == $arrOrderTaking[$i]["specialPrice"]))
                                {
                                    $menuParticipateValue += $arrOrderTaking[$i]["specialPrice"];
                                    $noOfItem++;
                                    $arrOrderTakingParticipate[] = &$arrOrderTaking[$i];
                                }
                            }
                        }
                        else
                        {
                            $sql = "select * from $dbName.discountGroupMenuMap where discountGroupMenuID = '$discountGroupMenuID' and status = 1";
                            $discountGroupMenuMap = getSelectedRow($sql);
                            for($j=0; $j<sizeof($discountGroupMenuMap); $j++)
                            {
                                $menuID = $discountGroupMenuMap[$j]["MenuID"];
                                for($i=0; $i<sizeof($arrOrderTaking); $i++)
                                {
                                    if(($menuID == $arrOrderTaking[$i]["menuID"]) && ($discountOnTop || ($arrOrderTaking[$i]["price"] == $arrOrderTaking[$i]["specialPrice"])))
                                    {
                                        $menuParticipateValue += $arrOrderTaking[$i]["specialPrice"];
                                        $noOfItem++;
                                        $arrOrderTakingParticipate[] = &$arrOrderTaking[$i];
                                    }
                                }
                            }
                        }
                        

                        if($noOfItem >= $minimumSpend)
                        {
                            $discountValueType4 = round($menuParticipateValue*$amount*0.01 * 10000)/10000;
                            writeToLog("discountProgramType:$discountType,discountProgramTitle:$discountTitle, discountProgramValue:$discountValueType4");
                            if($discountValueType4 > $returnDiscount)
                            {
                                $returnDiscount = $discountValueType4;
                                $discountFromDiscountProgram = array("DiscountProgramID" => $discountProgramID, "Title" => $discountTitle, "DiscountValue" => $returnDiscount, "OrderTaking" => $arrOrderTakingParticipate);
                            }
                        }
                    }
                }
                else if($discountType == 5)//buy 1 get 10%, buy 2 get 20%, buy 3 or more get 30%
                {
                    $noOfItem = 0;
                    if(($noOfLimitUsePerUserPerDay == 0 || $currentNoOfUsePerUserPerDay < $noOfLimitUsePerUserPerDay) && ($noOfLimitUsePerUser == 0 || $currentNoOfUsePerUser < $noOfLimitUsePerUser) && ($noOfLimitUse == 0 || $currentNoOfUse < $noOfLimitUse))
                    {
                        if($discountGroupMenuID == 0)
                        {
                            for($i=0; $i<sizeof($arrOrderTaking); $i++)
                            {
                                if($discountOnTop || ($arrOrderTaking[$i]["price"] == $arrOrderTaking[$i]["specialPrice"]))
                                {
                                    $menuParticipateValue += $arrOrderTaking[$i]["specialPrice"];
                                    $noOfItem++;
                                    $arrOrderTakingParticipate[] = &$arrOrderTaking[$i];
                                }
                            }
                        }
                        else
                        {
                            $sql = "select * from $dbName.discountGroupMenuMap where discountGroupMenuID = '$discountGroupMenuID' and status = 1";
                            $discountGroupMenuMap = getSelectedRow($sql);
                            for($j=0; $j<sizeof($discountGroupMenuMap); $j++)
                            {
                                $menuID = $discountGroupMenuMap[$j]["MenuID"];
                                for($i=0; $i<sizeof($arrOrderTaking); $i++)
                                {
                                    if(($menuID == $arrOrderTaking[$i]["menuID"]) && ($discountOnTop || ($arrOrderTaking[$i]["price"] == $arrOrderTaking[$i]["specialPrice"])))
                                    {
                                        $menuParticipateValue += $arrOrderTaking[$i]["specialPrice"];
                                        $noOfItem++;
                                        $arrOrderTakingParticipate[] = &$arrOrderTaking[$i];
                                    }
                                }
                            }
                        }

                        $amount = 0;
                        $sql = "select * from $dbName.discountStepMap where discountStepID = '$discountStepID' and status = 1 order by StepSpend";
                        $discountStepMap = getSelectedRow($sql);
                        if(sizeof($discountStepMap))
                        {
                            for($i=0; $i<sizeof($discountStepMap); $i++)
                            {
                                $stepSpend = $discountStepMap[$i]["StepSpend"];
                                $amountDiscount = $discountStepMap[$i]["Amount"];
                                if($noOfItem >= $stepSpend)
                                {
                                    $amount = $amountDiscount;
                                }
                            }
                            $discountValueType5 = round($menuParticipateValue*$amount*0.01 * 10000)/10000;
                            writeToLog("discountProgramType:$discountType,discountProgramTitle:$discountTitle, discountProgramValue:$discountValueType5");
                            if($discountValueType5 > $returnDiscount)
                            {
                                $returnDiscount = $discountValueType5;
                                $discountFromDiscountProgram = array("DiscountProgramID" => $discountProgramID, "Title" => $discountTitle, "DiscountValue" => $returnDiscount, "OrderTaking" => $arrOrderTakingParticipate);
                            }
                        }
                    }
                }
                else if($discountType == 6)//buy 1000 get 100, buy 2000 get 250, buy 3000 or more get 400
                {
                    if(($noOfLimitUsePerUserPerDay == 0 || $currentNoOfUsePerUserPerDay < $noOfLimitUsePerUserPerDay) && ($noOfLimitUsePerUser == 0 || $currentNoOfUsePerUser < $noOfLimitUsePerUser) && ($noOfLimitUse == 0 || $currentNoOfUse < $noOfLimitUse))
                    {
                        if($discountGroupMenuID == 0)
                        {
                            for($i=0; $i<sizeof($arrOrderTaking); $i++)
                            {
                                if($discountOnTop || ($arrOrderTaking[$i]["price"] == $arrOrderTaking[$i]["specialPrice"]))
                                {
                                    $menuParticipateValue += $arrOrderTaking[$i]["specialPrice"];
                                    $arrOrderTakingParticipate[] = &$arrOrderTaking[$i];
                                }
                            }
                        }
                        else
                        {
                            $sql = "select * from $dbName.discountGroupMenuMap where discountGroupMenuID = '$discountGroupMenuID' and status = 1";
                            $discountGroupMenuMap = getSelectedRow($sql);
                            for($j=0; $j<sizeof($discountGroupMenuMap); $j++)
                            {
                                $menuID = $discountGroupMenuMap[$j]["MenuID"];
                                for($i=0; $i<sizeof($arrOrderTaking); $i++)
                                {
                                    if(($menuID == $arrOrderTaking[$i]["menuID"]) && ($discountOnTop || ($arrOrderTaking[$i]["price"] == $arrOrderTaking[$i]["specialPrice"])))
                                    {
                                        $menuParticipateValue += $arrOrderTaking[$i]["specialPrice"];
                                        $arrOrderTakingParticipate[] = &$arrOrderTaking[$i];
                                    }
                                }
                            }
                        }
                        

                        $amount = 0;
                        $sql = "select * from $dbName.discountStepMap where discountStepID = '$discountStepID' and status = 1 order by StepSpend";
                        $discountStepMap = getSelectedRow($sql);
                        if(sizeof($discountStepMap))
                        {
                            for($i=0; $i<sizeof($discountStepMap); $i++)
                            {
                                $stepSpend = $discountStepMap[$i]["StepSpend"];
                                $amountDiscount = $discountStepMap[$i]["Amount"];
                                if($menuParticipateValue >= $stepSpend)
                                {
                                    $amount = $amountDiscount;
                                }
                            }
    //                        $discountValueType6 = $menuParticipateValue<$amount?$menuParticipateValue:$amount;
                            if($amount > 0)
                            {
                                $discountValueType6 = $amount;
                                writeToLog("discountProgramType:$discountType,discountProgramTitle:$discountTitle, discountProgramValue:$discountValueType6");
                                if($discountValueType6 > $returnDiscount)
                                {
                                    $returnDiscount = $discountValueType6;
                                    $discountFromDiscountProgram = array("DiscountProgramID" => $discountProgramID, "Title" => $discountTitle, "DiscountValue" => $returnDiscount, "OrderTaking" => $arrOrderTakingParticipate);
                                }
                            }
                        }
                    }
                }
                else if($discountType == 7)//get 10 baht per item (set minimumSpend)
                {
                    $discountValue = 0;
                    $noOfUse = 0;
                    
                    if(($noOfLimitUsePerUserPerDay == 0 || $currentNoOfUsePerUserPerDay < $noOfLimitUsePerUserPerDay) && ($noOfLimitUsePerUser == 0 || $currentNoOfUsePerUser < $noOfLimitUsePerUser) && ($noOfLimitUse == 0 || $currentNoOfUse < $noOfLimitUse))
                    {
                        if($discountGroupMenuID == 0)
                        {
                            for($i=0; $i<sizeof($arrOrderTaking); $i++)
                            {
                                if($discountOnTop || ($arrOrderTaking[$i]["price"] == $arrOrderTaking[$i]["specialPrice"]))
                                {
                                    if(unlimitUse)
                                    {
                                        $discountValue += $amount;
                                        $arrOrderTakingParticipate[] = &$arrOrderTaking[$i];
                                    }
                                    else
                                    {
                                        if($noOfUse < $minimumSpend)//actually is maximumSpend
                                        {
                                            $discountValue += $amount;
                                            $arrOrderTakingParticipate[] = &$arrOrderTaking[$i];
                                        }
                                        $noOfUse++;
                                    }
                                }
                            }
                        }
                        else
                        {
                            $sql = "select * from $dbName.discountGroupMenuMap where discountGroupMenuID = '$discountGroupMenuID' and status = 1";
                            $discountGroupMenuMap = getSelectedRow($sql);
                            for($j=0; $j<sizeof($discountGroupMenuMap); $j++)
                            {
                                $menuID = $discountGroupMenuMap[$j]["MenuID"];
                                for($i=0; $i<sizeof($arrOrderTaking); $i++)
                                {
                                    if(($menuID == $arrOrderTaking[$i]["menuID"]) && ($discountOnTop || ($arrOrderTaking[$i]["price"] == $arrOrderTaking[$i]["specialPrice"])))
                                    {
                                        if(unlimitUse)
                                        {
                                            $discountValue += $amount;
                                            $arrOrderTakingParticipate[] = &$arrOrderTaking[$i];
                                        }
                                        else
                                        {
                                            if($noOfUse < $minimumSpend)//actually is maximumSpend
                                            {
                                                $discountValue += $amount;
                                                $arrOrderTakingParticipate[] = &$arrOrderTaking[$i];
                                            }
                                            $noOfUse++;
                                        }
                                    }
                                }
                            }
                        }
                        
                        
                        
                        $discountValueType7 = $discountValue;
                        writeToLog("discountProgramType:$discountType,discountProgramTitle:$discountTitle, discountProgramValue:$discountValueType7");
                        if($discountValueType7 > $returnDiscount)
                        {
                            $returnDiscount = $discountValueType7;
                            $discountFromDiscountProgram = array("DiscountProgramID" => $discountProgramID, "Title" => $discountTitle, "DiscountValue" => $returnDiscount, "OrderTaking" => $arrOrderTakingParticipate);
                        }
                    }
                }
                else if($discountType == 8)//get discount % step by day (limit max)
                {
                    $noOfUseLeftPerUserPerDay  = $noOfLimitUsePerUserPerDay-$currentNoOfUsePerUserPerDay;
                    $noOfUseLeftPerUser = $noOfLimitUsePerUser-$currentNoOfUsePerUser;
                    $noOfUseLeft = $noOfLimitUse-$currentNoOfUse;
                    
                    $unlimitUse = 0;
                    if(($noOfLimitUse == 0) && ($noOfLimitUsePerUser == 0) && ($noOfLimitUsePerUserPerDay == 0))
                    {
                        $unlimitUse = 1;
                    }
                    else if(($noOfLimitUse == 0) && ($noOfLimitUsePerUser == 0) && !($noOfLimitUsePerUserPerDay == 0))
                    {
                        $noOfUseLeft = $noOfUseLeftPerUserPerDay;
                    }
                    else if(($noOfLimitUse == 0) && !($noOfLimitUsePerUser == 0) && ($noOfLimitUsePerUserPerDay == 0))
                    {
                        $noOfUseLeft = $noOfUseLeftPerUser;
                    }
                    else if(!($noOfLimitUse == 0) && ($noOfLimitUsePerUser == 0) && ($noOfLimitUsePerUserPerDay == 0))
                    {
                        $noOfUseLeft = $noOfUseLeft;
                    }
                    else if(($noOfLimitUse == 0) && !($noOfLimitUsePerUser == 0) && !($noOfLimitUsePerUserPerDay == 0))
                    {
                        $noOfUseLeft = $noOfUseLeftPerUser < $noOfUseLeftPerUserPerDay?$noOfUseLeftPerUser:$noOfUseLeftPerUserPerDay;
                    }
                    else if(!($noOfLimitUse == 0) && ($noOfLimitUsePerUser == 0) && !($noOfLimitUsePerUserPerDay == 0))
                    {
                        $noOfUseLeft = $noOfUseLeft < $noOfUseLeftPerUserPerDay?$noOfUseLeft:$noOfUseLeftPerUserPerDay;
                    }
                    else if(!($noOfLimitUse == 0) && !($noOfLimitUsePerUser == 0) && ($noOfLimitUsePerUserPerDay == 0))
                    {
                        $noOfUseLeft = $noOfUseLeft < $noOfUseLeftPerUser?$noOfUseLeft:$noOfUseLeftPerUser;
                    }
                    else
                    {
                        $noOfUseLeft = $noOfUseLeft < $noOfUseLeftPerUser?$noOfUseLeft:$noOfUseLeftPerUser;
                        $noOfUseLeft = $noOfUseLeft < $noOfUseLeftPerUserPerDay?$noOfUseLeft:$noOfUseLeftPerUserPerDay;
                    }
                    
                    if($unlimitUse || $noOfUseLeft > 0)
                    {
                        if($discountGroupMenuID == 0)
                        {
                            for($i=0; $i<sizeof($arrOrderTaking); $i++)
                            {
                                if($discountOnTop || ($arrOrderTaking[$i]["price"] == $arrOrderTaking[$i]["specialPrice"]))
                                {
                                    $menuParticipateValue += $arrOrderTaking[$i]["specialPrice"];
                                    $arrOrderTakingParticipate[] = &$arrOrderTaking[$i];
                                }
                            }
                        }
                        else
                        {
                            $sql = "select * from $dbName.discountGroupMenuMap where discountGroupMenuID = '$discountGroupMenuID' and status = 1";
                            $discountGroupMenuMap = getSelectedRow($sql);
                            for($j=0; $j<sizeof($discountGroupMenuMap); $j++)
                            {
                                $menuID = $discountGroupMenuMap[$j]["MenuID"];
                                for($i=0; $i<sizeof($arrOrderTaking); $i++)
                                {
                                    if(($menuID == $arrOrderTaking[$i]["menuID"]) && ($discountOnTop || ($arrOrderTaking[$i]["price"] == $arrOrderTaking[$i]["specialPrice"])))
                                    {
                                        $menuParticipateValue += $arrOrderTaking[$i]["specialPrice"];
                                        $arrOrderTakingParticipate[] = &$arrOrderTaking[$i];
                                    }
                                }
                            }
                        }
                        
                        
                        $sql = "select * from $dbName.discountStepMap where discountStepID = '$discountStepID' and status = 1 order by StepSpend";
                        $discountStepMap = getSelectedRow($sql);
                        if(sizeof($discountStepMap))
                        {
                            for($i=0; $i<sizeof($discountStepMap); $i++)
                            {
                                $stepSpend = $discountStepMap[$i]["StepSpend"];
                                $amountDiscount = $discountStepMap[$i]["Amount"];
                                $maxDiscount = $discountStepMap[$i]["MaxDiscount"];
                                if($currentNoOfUsePerUser+1 == $stepSpend)
                                {
                                    $discountValue = $menuParticipateValue * $amountDiscount * 0.01;
                                    $discountValue = round($discountValue * 10000)/10000;
                                    $discountValue = $discountValue > $maxDiscount?$maxDiscount:$discountValue;
                                    break;
                                }
                            }
                            
                            $discountValueType8 = $discountValue;
                            writeToLog("discountProgramType:$discountType,discountProgramTitle:$discountTitle, discountProgramValue:$discountValueType8");
                            if($discountValueType8 > $returnDiscount)
                            {
                                $returnDiscount = $discountValueType8;
                                $discountFromDiscountProgram = array("DiscountProgramID" => $discountProgramID, "Title" => $discountTitle, "DiscountValue" => $returnDiscount, "OrderTaking" => $arrOrderTakingParticipate);
                            }
                        }
                    }
                }
            }
        }
        
        
        //*****
        if(!$discountFromDiscountProgram)
        {
            $discountFromDiscountProgram = array("DiscountProgramID" => 0, "Title" => "", "DiscountValue" => 0 , "OrderTaking" => null);
        }
        
        $totalAfterDiscountProgram = $sumSpecialPrice - $discountFromDiscountProgram["DiscountValue"];
        $discountProgramValue = $discountFromDiscountProgram["DiscountValue"];
        $discountProgramTitle = $discountFromDiscountProgram["Title"];
//        $arrOrderTakingParticipate = $discountFromDiscountProgram["OrderTaking"];
        
        
        //หาสัดส่วน ส่วนลดของแต่ละ item
        $actualDiscount = $discountProgramValue > $sumSpecialPrice?$sumSpecialPrice:$discountProgramValue;
        $sumBeforeDiscount = 0;
        for($i=0; $i<sizeof($arrOrderTakingParticipate); $i++)
        {
            $sumBeforeDiscount += $arrOrderTakingParticipate[$i]["specialPrice"];
        }
        
        for($i=0; $i<sizeof($arrOrderTakingParticipate); $i++)
        {
            $arrOrderTakingParticipate[$i]["discountProgramValue"] = $arrOrderTakingParticipate[$i]["specialPrice"]/$sumBeforeDiscount*$actualDiscount;
            $arrOrderTakingParticipate[$i]["discountProgramValue"] = round($arrOrderTakingParticipate[$i]["discountProgramValue"]*10000)/10000;
        }
    }
    //--------------------------
    
    
    
    
    //get discount from promoCode
    $applyVoucherCode = 0;
    $discountValue = 0;
    $discountPromoCodeValue = 0;
    $warningMsgVoucher = "";
    if($inOpeningTime && ($totalAfterDiscountProgram > 0) && ($voucherCode != ""))
    {
        $warningMsg;
        $voucherValid = 1;
        $sql = "select * from promotion where voucherCode = '$voucherCode';";
        $selectedRow = getSelectedRow($sql);
        if(sizeof($selectedRow) == 0)
        {
            //คูปองส่วนลดไม่ถูกต้อง -> ไม่มี voucher code นี้
            $voucherValid = 0;
            $warningMsg = "ไม่มี Voucher Code นี้";
        }

        if($voucherValid)
        {
            $hasVoucherInPromotionTable = 1;
            $dayOfWeekText = "";
            $sql = "select promotionDay.* from promotion left join promotionDay on promotion.promotionID = promotionDay.promotionID where voucherCode = '$voucherCode' and ('$currentDateTime' between usingStartDate and usingEndDate);";
            $selectedRow = getSelectedRow($sql);
            for($i=0; $i<sizeof($selectedRow); $i++)
            {
                $dayOfWeekText = $dayOfWeekText != ""?$dayOfWeekText . ",":$dayOfWeekText;
                $dayOfWeekText .= getWeekDayText($selectedRow[$i]["Day"]);

            }

            $sql2 = "select promotion.* from promotion left join promotionDay on promotion.promotionID = promotionDay.promotionID where voucherCode = '$voucherCode' and ('$currentDateTime' between usingStartDate and usingEndDate) and weekday('$currentDateTime')+1 = promotionDay.day;";
            $selectedRow2 = getSelectedRow($sql2);
            if(sizeof($selectedRow2) == 0)
            {
                //คูปองส่วนลดไม่ถูกต้อง -> voucher code นี้ใช้ได้เฉพาะวันจันทร์ เป็นต้น
                $voucherValid = 0;
                $warningMsg = "Voucher Code นี้ใช้ได้เฉพาะวัน" . $dayOfWeekText;
            }
            else
            {
                $promotion = $selectedRow2;
                $promotionID = $promotion[0]["PromotionID"];
                $noOfLimitUse = $promotion[0]["NoOfLimitUse"];
                $noOfLimitUsePerUser = $promotion[0]["NoOfLimitUsePerUser"];
                $noOfLimitUsePerUserPerDay = $promotion[0]["NoOfLimitUsePerUserPerDay"];
                $minimumSpending = $promotion[0]["MinimumSpending"];
                $allowEveryone = $promotion[0]["AllowEveryone"];
            }
        }

        if($voucherValid)
        {
            if(!$allowEveryone)
            {
                //checkUser allow มัั๊ย
                $sql = "select * from promotionUser where useraccountID = '$userAccountID'";
                $selectedRow = getSelectedRow($sql);
                if(sizeof($selectedRow) == 0)
                {
                    //คูปองส่วนลดไม่ถูกต้อง -> คุณไม่สามารถใช้คูปองนี้ได้
                    $voucherValid = 0;
                    $warningMsg = "คุณไม่สามารถใช้คูปองนี้ได้";
                }
            }
        }


        if($voucherValid)
        {
            $sql = "select * from promotionBranch where promotionID = '$promotionID' and branchID = '$branchID'";
            $selectedRow = getSelectedRow($sql);
            if(sizeof($selectedRow) == 0)
            {
                //คูปองส่วนลดไม่ถูกต้อง -> คูปองไม่สามารถใช้ได้กับร้านนี้
                $voucherValid = 0;
                $warningMsg = "คูปองไม่สามารถใช้ได้กับร้านนี้";
            }
        }


        if($voucherValid)
        {
            //NoOfLimitUse
            $sql = "select count(*) UsedCount from userPromotionUsed where promotionID = '$promotionID'";
            $selectedRow = getSelectedRow($sql);
            $usedCount = $selectedRow[0]["UsedCount"];
            if($noOfLimitUse > 0 && $usedCount >= $noOfLimitUse)
            {
                //คูปองส่วนลดไม่ถูกต้อง -> จำนวนสิทธิ์ครบแล้ว
                $voucherValid = 0;
                $warningMsg = "จำนวนสิทธิ์ครบแล้ว";
            }
        }


        if($voucherValid)
        {
            $sql = "select count(*) UsedCount from userPromotionUsed where promotionID = '$promotionID' and userAccountID = '$userAccountID'";
            $selectedRow = getSelectedRow($sql);
            $usedCountPerUser = $selectedRow[0]["UsedCount"];
            if($noOfLimitUsePerUser > 0 && $usedCountPerUser >= $noOfLimitUsePerUser)
            {
                //คูปองส่วนลดไม่ถูกต้อง -> คุณใช้สิทธิ์ครบแล้ว
                $voucherValid = 0;
                $warningMsg = "คุณใช้สิทธิ์ครบแล้ว";
            }
        }


        if($voucherValid)
        {
            $sql = "select count(*) UsedCount from userPromotionUsed where promotionID = '$promotionID' and userAccountID = '$userAccountID' and date_format(modifiedDate,'%Y-%m-%d') = date_format('$currentDateTime','%Y-%m-%d')";
            $selectedRow = getSelectedRow($sql);
            $usedCountPerUserPerDay = $selectedRow[0]["UsedCount"];
            if($noOfLimitUsePerUserPerDay > 0 && $usedCountPerUserPerDay >= $noOfLimitUsePerUserPerDay)
            {
                //คูปองส่วนลดไม่ถูกต้อง -> วันนี้คุณใช้สิทธิ์ครบแล้ว
                $voucherValid = 0;
                $warningMsg = "วันนี้คุณใช้สิทธิ์ครบแล้ว";
            }
        }


        if($voucherValid)
        {
            //minimumSpending
            if($sumSpecialPrice < $minimumSpending)
            {
                //คูปองส่วนลดไม่ถูกต้อง -> ยอดสั่งซื้อขั้นต่ำไม่ถึง
                $voucherValid = 0;
                $warningMsg = "ยอดสั่งซื้อขั้นต่ำไม่ถึง";
            }
        }

        if(!$hasVoucherInPromotionTable)
        {
            //search at table rewardRedemption,rewardPoint, promoCode
            $warningMsg2 = "";
            $voucherValid2 = 1;
            $sql = "SELECT rewardRedemption.*,promoCode.PromoCodeID FROM `rewardpoint` left join promoCode on rewardPoint.promoCodeID = promoCode.promoCodeID left join RewardRedemption on promocode.rewardRedemptionID = RewardRedemption.rewardRedemptionID WHERE MemberID = '$userAccountID' and rewardpoint.status = -1 and ((TIME_TO_SEC(timediff('$currentDateTime', rewardpoint.ModifiedDate)) < rewardredemption.WithInPeriod) or (rewardRedemption.WithInPeriod = 0 and '$currentDateTime'<rewardRedemption.usingEndDate)) and promoCode.Code = '$voucherCode' and promoCode.status = 1";
            $selectedRow = getSelectedRow($sql);
            $minimumSpending = $selectedRow[0]["MinimumSpending"];
            $maxDiscountAmountPerDay = $selectedRow[0]["MaxDiscountAmountPerDay"];
            $rewardRedemptionID = $selectedRow[0]["RewardRedemptionID"];
            $promoCodeID = $selectedRow[0]["PromoCodeID"];
            if($voucherValid2)
            {
                if(sizeof($selectedRow)==0)
                {
                    $sql = "SELECT rewardRedemption.* FROM `rewardpoint` left join promoCode on rewardPoint.promoCodeID = promoCode.promoCodeID left join RewardRedemption on promocode.rewardRedemptionID = RewardRedemption.rewardRedemptionID WHERE MemberID = '$userAccountID' and rewardpoint.status = -1 and ((TIME_TO_SEC(timediff('$currentDateTime', rewardpoint.ModifiedDate)) < rewardredemption.WithInPeriod) or (rewardRedemption.WithInPeriod = 0 and '$currentDateTime'<rewardRedemption.usingEndDate)) and promoCode.Code = '$voucherCode' and promoCode.status = 2";
                    $selectedRow = getSelectedRow($sql);
                    if(sizeof($selectedRow)>0)
                    {
                        $voucherValid2 = 0;
                        $warningMsg2 = "Voucher Code นี้ใช้ไปแล้ว";
                    }
                    else
                    {
                        $sql = "SELECT rewardRedemption.*,promoCode.PromoCodeID FROM `rewardpoint` left join promoCode on rewardPoint.promoCodeID = promoCode.promoCodeID left join RewardRedemption on promocode.rewardRedemptionID = RewardRedemption.rewardRedemptionID WHERE MemberID = '$userAccountID' and rewardpoint.status = -1 and promoCode.Code = '$voucherCode' and promoCode.status = 1";
                        $selectedRow = getSelectedRow($sql);
                        if(sizeof($selectedRow)>0)
                        {
                            $voucherValid2 = 0;
                            $warningMsg2 = "Voucher Code นี้หมดอายุแล้ว";
                        }
                        else
                        {
                            $voucherValid2 = 0;
                            $warningMsg2 = "ไม่มี Voucher Code นี้";
                        }
                    }
                }
            }



            if($voucherValid2)
            {
                $sql = "select * from rewardRedemptionBranch where rewardRedemptionID = '$rewardRedemptionID' and branchID = '$branchID'";
                $selectedRow = getSelectedRow($sql);
                if(sizeof($selectedRow) == 0)
                {
                    //คูปองส่วนลดไม่ถูกต้อง -> คูปองไม่สามารถใช้ได้กับร้านนี้
                    $voucherValid2 = 0;
                    $warningMsg2 = "คูปองไม่สามารถใช้ได้กับร้านนี้";
                }
            }



//            if($voucherValid2)
//            {
//                //minimumSpending
//                if($sumSpecialPrice < $minimumSpending)
//                {
//                    //คูปองส่วนลดไม่ถูกต้อง -> ยอดสั่งซื้อขั้นต่ำไม่ถึง
//                    $voucherValid2 = 0;
//                    $warningMsg2 = "ยอดสั่งซื้อขั้นต่ำไม่ถึง";
//                }
//            }
        }



        if($voucherValid)
        {
            $sql = "select promotion.*,0 PromoCodeID, (select count(*) CurrentNoOfUse from UserPromotionUsed where UserPromotionUsed.PromotionID = promotion.PromotionID) CurrentNoOfUse, (select count(*) CurrentNoOfUsePerUser from UserPromotionUsed where UserPromotionUsed.PromotionID = promotion.PromotionID and UserPromotionUsed.UserAccountID = '$userAccountID') CurrentNoOfUsePerUser, (select count(*) CurrentNoOfUsePerUser from UserPromotionUsed where UserPromotionUsed.PromotionID = promotion.PromotionID and UserPromotionUsed.UserAccountID = '$userAccountID' and date_format(UserPromotionUsed.ModifiedDate,'%Y-%m-%d') = date_format('$currentDateTime','%Y-%m-%d')) CurrentNoOfUsePerUserPerDay from promotion where voucherCode = '$voucherCode' and '$currentDateTime' between usingStartDate and usingEndDate;";
            $sql .= "select '' as Text;";
            $sql .= "select 1 as Text";
        }
        else if(!$voucherValid && $hasVoucherInPromotionTable)
        {
            $sql = "select 0 from dual where 0;";
            $sql .= "select '$warningMsg' as Text;";
            $sql .= "select 1 as Text";
        }
        else if(!$voucherValid && !$hasVoucherInPromotionTable)
        {
            if($voucherValid2)
            {
                $sql = "select RewardRedemptionID,Header ,DiscountType,DiscountAmount, `ShopDiscount`, `JummumDiscount`, `SharedDiscountType`, `SharedDiscountAmountMax`,MainBranchID,DiscountGroupMenuID,DiscountStepID,DiscountOnTop,NoOfLimitUse,NoOfLimitUsePerUser,NoOfLimitUsePerUserPerDay,$promoCodeID PromoCodeID, (select count(*) CurrentNoOfUse from UserRewardRedemptionUsed where UserRewardRedemptionUsed.RewardRedemptionID = RewardRedemption.RewardRedemptionID) CurrentNoOfUse, (select count(*) CurrentNoOfUsePerUser from UserRewardRedemptionUsed where UserRewardRedemptionUsed.RewardRedemptionID = RewardRedemption.RewardRedemptionID and UserRewardRedemptionUsed.UserAccountID = '$userAccountID') CurrentNoOfUsePerUser, (select count(*) CurrentNoOfUsePerUser from UserRewardRedemptionUsed where UserRewardRedemptionUsed.RewardRedemptionID = RewardRedemption.RewardRedemptionID and UserRewardRedemptionUsed.UserAccountID = '$userAccountID' and date_format(UserRewardRedemptionUsed.ModifiedDate,'%Y-%m-%d') = date_format('$currentDateTime','%Y-%m-%d')) CurrentNoOfUsePerUserPerDay from rewardRedemption where rewardRedemptionID = '$rewardRedemptionID';";
                $sql .= "select '' as Text;";
                $sql .= "select 2 as Text";
            }
            else
            {
                $sql = "select 0 from dual where 0;";
                $sql .= "select '$warningMsg2' as Text;";
                $sql .= "select 2 as Text";
            }
        }



        //*********** get error message,discount type,discount Amount, discount value, promotionID, rewardRedemptionID, promoCodeID
        $sqlList = explode(";",$sql);
        $promotionList = getSelectedRow($sqlList[0]);
        $warningMsgList = getSelectedRow($sqlList[1]);
        $typeList = getSelectedRow($sqlList[2]);


        
        $warningMsgVoucher = $warningMsgList[0]["Text"];
        if(!$warningMsgVoucher)
        {
            //validate เมนูที่เลือก ร่วมโปรมั๊ย
            $hasDiscountForMenu = 0;
            $menuParticipateValue = 0;
            $arrOrderTakingParticipate = array();
            $promotion = $promotionList[0];
            $discountType = $promotion["DiscountType"];
            $promotionHeader = $promotion["Header"];
            $amount = $promotion["DiscountAmount"];
            $minimumSpend = $promotion["MinimumSpending"];
            $noOfLimitUse = $promotion["NoOfLimitUse"];
            $noOfLimitUsePerUser = $promotion["NoOfLimitUsePerUser"];
            $noOfLimitUsePerUserPerDay = $promotion["NoOfLimitUsePerUserPerDay"];
            $currentNoOfUse = $promotion["CurrentNoOfUse"];
            $currentNoOfUsePerUser = $promotion["CurrentNoOfUsePerUser"];
            $currentNoOfUsePerUserPerDay = $promotion["CurrentNoOfUsePerUserPerDay"];
            $discountGroupMenuID = $promotion["DiscountGroupMenuID"];
            $discountStepID = $promotion["DiscountStepID"];
            $discountOnTop = $promotion["DiscountOnTop"];
            
            $hasDiscountForMenu = $discountGroupMenuID == 0?1:0;
            if($discountGroupMenuID != 0)
            {
                $sql = "select * from $dbName.discountGroupMenuMap where discountGroupMenuID = '$discountGroupMenuID' and status = 1";
                $discountGroupMenuMap = getSelectedRow($sql);
                for($j=0; $j<sizeof($discountGroupMenuMap); $j++)
                {
                    $menuID = $discountGroupMenuMap[$j]["MenuID"];
                    for($i=0; $i<sizeof($arrOrderTaking); $i++)
                    {
                        if($menuID == $arrOrderTaking[$i]["menuID"])
                        {
                            if($promotion["DiscountOnTop"] || ($otSpecialPrice[$i] == $otPrice[$i]))
                            {
                                $hasDiscountForMenu = 1;
                                break;
                            }
                        }
                    }
                }
            }
            

            if(!$hasDiscountForMenu)
            {
                $warningMsgVoucher = "โค้ดที่ใส่ไม่สามารถใช้กับเมนูที่คุณเลือก";
                $discountPromoCodeValue = 0;
                $applyVoucherCode = 0;
            }
            else
            {
                if($discountType == 1 || $discountType == 2)//baht, percent
                {
                    if(($noOfLimitUsePerUserPerDay == 0 || $currentNoOfUsePerUserPerDay < $noOfLimitUsePerUserPerDay) && ($noOfLimitUsePerUser == 0 || $currentNoOfUsePerUser < $noOfLimitUsePerUser) && ($noOfLimitUse == 0 || $currentNoOfUse < $noOfLimitUse))
                    {
                        if($discountGroupMenuID == 0)
                        {
                            for($i=0; $i<sizeof($arrOrderTaking); $i++)
                            {
                                if($discountOnTop || (($arrOrderTaking[$i]["price"] == $arrOrderTaking[$i]["specialPrice"]) && ($arrOrderTaking[$i]["discountProgramValue"] == 0)))
                                {
                                    $menuParticipateValue += $arrOrderTaking[$i]["specialPrice"] - $arrOrderTaking[$i]["discountProgramValue"];
                                    $arrOrderTakingParticipate[] = &$arrOrderTaking[$i];
                                }
                            }
                        }
                        else
                        {
                            $sql = "select * from $dbName.discountGroupMenuMap where discountGroupMenuID = '$discountGroupMenuID' and status = 1";
                            $discountGroupMenuMap = getSelectedRow($sql);
                            for($j=0; $j<sizeof($discountGroupMenuMap); $j++)
                            {
                                $menuID = $discountGroupMenuMap[$j]["MenuID"];
                                for($i=0; $i<sizeof($arrOrderTaking); $i++)
                                {
                                    if(($menuID == $arrOrderTaking[$i]["menuID"]) && ($discountOnTop || (($arrOrderTaking[$i]["price"] == $arrOrderTaking[$i]["specialPrice"]) && ($arrOrderTaking[$i]["discountProgramValue"] == 0))))
                                    {
                                        $menuParticipateValue += $arrOrderTaking[$i]["specialPrice"] - $arrOrderTaking[$i]["discountProgramValue"];
                                        $arrOrderTakingParticipate[] = &$arrOrderTaking[$i];
                                    }
                                }
                            }
                        }
                    }

                    if($menuParticipateValue >= $minimumSpend)
                    {
                        if($discountType == 1)
                        {
                            $discountPromoCodeValue = $amount;
                        }
                        else if($discountType == 2)
                        {
                            $discountPromoCodeValue = round($menuParticipateValue*$amount*0.01 * 10000)/10000;
                        }
                    }
                }
                else if($discountType == 5)//buy 1 get 10%, buy 2 get 20%, buy 3 or more get 30%
                {
                    $discountValue = 0;
                    $noOfItem = 0;
                    if(($noOfLimitUsePerUserPerDay == 0 || $currentNoOfUsePerUserPerDay < $noOfLimitUsePerUserPerDay) && ($noOfLimitUsePerUser == 0 || $currentNoOfUsePerUser < $noOfLimitUsePerUser) && ($noOfLimitUse == 0 || $currentNoOfUse < $noOfLimitUse))
                    {
                        if($discountGroupMenuID == 0)
                        {
                            for($i=0; $i<sizeof($arrOrderTaking); $i++)
                            {
                                if($discountOnTop || (($arrOrderTaking[$i]["price"] == $arrOrderTaking[$i]["specialPrice"]) && ($arrOrderTaking[$i]["discountProgramValue"] == 0)))
                                {
                                    $menuParticipateValue += $arrOrderTaking[$i]["specialPrice"] - $arrOrderTaking[$i]["discountProgramValue"];
                                    $noOfItem++;
                                    $arrOrderTakingParticipate[] = &$arrOrderTaking[$i];
                                }
                            }
                        }
                        else
                        {
                            $sql = "select * from $dbName.discountGroupMenuMap where discountGroupMenuID = '$discountGroupMenuID' and status = 1";
                            $discountGroupMenuMap = getSelectedRow($sql);
                            for($j=0; $j<sizeof($discountGroupMenuMap); $j++)
                            {
                                $menuID = $discountGroupMenuMap[$j]["MenuID"];
                                for($i=0; $i<sizeof($arrOrderTaking); $i++)
                                {
                                    if(($menuID == $arrOrderTaking[$i]["menuID"]) && ($discountOnTop || (($arrOrderTaking[$i]["price"] == $arrOrderTaking[$i]["specialPrice"]) && ($arrOrderTaking[$i]["discountProgramValue"] == 0))))
                                    {
                                        $menuParticipateValue += $arrOrderTaking[$i]["specialPrice"] - $arrOrderTaking[$i]["discountProgramValue"];
                                        $noOfItem++;
                                        $arrOrderTakingParticipate[] = &$arrOrderTaking[$i];
                                    }
                                }
                            }
                        }

                        $amount = 0;
                        $sql = "select * from $dbName.discountStepMap where discountStepID = '$discountStepID' and status = 1 order by StepSpend";
                        $discountStepMap = getSelectedRow($sql);
                        if(sizeof($discountStepMap))
                        {
                            for($i=0; $i<sizeof($discountStepMap); $i++)
                            {
                                $stepSpend = $discountStepMap[$i]["StepSpend"];
                                $amountDiscount = $discountStepMap[$i]["Amount"];
                                $maxDiscount = $discountStepMap[$i]["MaxDiscount"];
                                if($noOfItem >= $stepSpend)
                                {
                                    $amount = $amountDiscount;
                                }
                            }
                            $discountValue = round($menuParticipateValue*$amount*0.01 * 10000)/10000;
                            $discountValue = $discountValue > $maxDiscount?$maxDiscount:$discountValue;
                            $discountPromoCodeValue = $discountValue;
                        }
                    }
                }
                else if($discountType == 7)//get 10 baht per item (set $minimumSpend)
                {
                    $discountValue = 0;
                    $noOfUse = 0;
                
                    if(($noOfLimitUsePerUserPerDay == 0 || $currentNoOfUsePerUserPerDay < $noOfLimitUsePerUserPerDay) && ($noOfLimitUsePerUser == 0 || $currentNoOfUsePerUser < $noOfLimitUsePerUser) && ($noOfLimitUse == 0 || $currentNoOfUse < $noOfLimitUse))
                    {
                        if($discountGroupMenuID == 0)
                        {
                            for($i=0; $i<sizeof($arrOrderTaking); $i++)
                            {
                                if($discountOnTop || (($arrOrderTaking[$i]["price"] == $arrOrderTaking[$i]["specialPrice"]) && ($arrOrderTaking[$i]["discountProgramValue"] == 0)))
                                {
                                    if(unlimitUse)
                                    {
                                        $discountValue += $amount;
                                        $arrOrderTakingParticipate[] = &$arrOrderTaking[$i];
                                    }
                                    else
                                    {
                                        if($noOfUse < $minimumSpend)//actually is maximumSpend
                                        {
                                            $discountValue += $amount;
                                            $arrOrderTakingParticipate[] = &$arrOrderTaking[$i];
                                        }
                                        $noOfUse++;
                                    }
                                }
                            }
                        }
                        else
                        {
                            $sql = "select * from $dbName.discountGroupMenuMap where discountGroupMenuID = '$discountGroupMenuID' and status = 1";
                            $discountGroupMenuMap = getSelectedRow($sql);
                            for($j=0; $j<sizeof($discountGroupMenuMap); $j++)
                            {
                                $menuID = $discountGroupMenuMap[$j]["MenuID"];
                                for($i=0; $i<sizeof($arrOrderTaking); $i++)
                                {
                                    if(($menuID == $arrOrderTaking[$i]["menuID"]) && ($discountOnTop || (($arrOrderTaking[$i]["price"] == $arrOrderTaking[$i]["specialPrice"]) && ($arrOrderTaking[$i]["discountProgramValue"] == 0))))
                                    {
                                        if(unlimitUse)
                                        {
                                            $discountValue += $amount;
                                            $arrOrderTakingParticipate[] = &$arrOrderTaking[$i];
                                        }
                                        else
                                        {
                                            if($noOfUse < $minimumSpend)//actually is maximumSpend
                                            {
                                                $discountValue += $amount;
                                                $arrOrderTakingParticipate[] = &$arrOrderTaking[$i];
                                            }
                                            $noOfUse++;
                                        }
                                    }
                                }
                            }
                        }
                        $discountPromoCodeValue = $discountValue;
                    }
                }
                else if($discountType == 8)//get discount % step by day (limt max) 10 baht per item (limit noOfUse)
                {
                    $discountValue = 0;
                    $noOfUseLeftPerUserPerDay  = $noOfLimitUsePerUserPerDay-$currentNoOfUsePerUserPerDay;
                    $noOfUseLeftPerUser = $noOfLimitUsePerUser-$currentNoOfUsePerUser;
                    $noOfUseLeft = $noOfLimitUse-$currentNoOfUse;
                    
                    $unlimitUse = 0;
                    if(($noOfLimitUse == 0) && ($noOfLimitUsePerUser == 0) && ($noOfLimitUsePerUserPerDay == 0))
                    {
                        $unlimitUse = 1;
                    }
                    else if(($noOfLimitUse == 0) && ($noOfLimitUsePerUser == 0) && !($noOfLimitUsePerUserPerDay == 0))
                    {
                        $noOfUseLeft = $noOfUseLeftPerUserPerDay;
                    }
                    else if(($noOfLimitUse == 0) && !($noOfLimitUsePerUser == 0) && ($noOfLimitUsePerUserPerDay == 0))
                    {
                        $noOfUseLeft = $noOfUseLeftPerUser;
                    }
                    else if(!($noOfLimitUse == 0) && ($noOfLimitUsePerUser == 0) && ($noOfLimitUsePerUserPerDay == 0))
                    {
                        $noOfUseLeft = $noOfUseLeft;
                    }
                    else if(($noOfLimitUse == 0) && !($noOfLimitUsePerUser == 0) && !($noOfLimitUsePerUserPerDay == 0))
                    {
                        $noOfUseLeft = $noOfUseLeftPerUser < $noOfUseLeftPerUserPerDay?$noOfUseLeftPerUser:$noOfUseLeftPerUserPerDay;
                    }
                    else if(!($noOfLimitUse == 0) && ($noOfLimitUsePerUser == 0) && !($noOfLimitUsePerUserPerDay == 0))
                    {
                        $noOfUseLeft = $noOfUseLeft < $noOfUseLeftPerUserPerDay?$noOfUseLeft:$noOfUseLeftPerUserPerDay;
                    }
                    else if(!($noOfLimitUse == 0) && !($noOfLimitUsePerUser == 0) && ($noOfLimitUsePerUserPerDay == 0))
                    {
                        $noOfUseLeft = $noOfUseLeft < $noOfUseLeftPerUser?$noOfUseLeft:$noOfUseLeftPerUser;
                    }
                    else
                    {
                        $noOfUseLeft = $noOfUseLeft < $noOfUseLeftPerUser?$noOfUseLeft:$noOfUseLeftPerUser;
                        $noOfUseLeft = $noOfUseLeft < $noOfUseLeftPerUserPerDay?$noOfUseLeft:$noOfUseLeftPerUserPerDay;
                    }
                    
                    if($unlimitUse || $noOfUseLeft > 0)
                    {
                        if($discountGroupMenuID == 0)
                        {
                            for($i=0; $i<sizeof($arrOrderTaking); $i++)
                            {
                                if($discountOnTop || (($arrOrderTaking[$i]["price"] == $arrOrderTaking[$i]["specialPrice"]) && ($arrOrderTaking[$i]["discountProgramValue"] == 0)))
                                {
                                    $menuParticipateValue += $arrOrderTaking[$i]["specialPrice"] - $arrOrderTaking[$i]["discountProgramValue"];
                                    $arrOrderTakingParticipate[] = &$arrOrderTaking[$i];
                                }
                            }
                        }
                        else
                        {
                            $sql = "select * from $dbName.discountGroupMenuMap where discountGroupMenuID = '$discountGroupMenuID' and status = 1";
                            $discountGroupMenuMap = getSelectedRow($sql);
                            for($j=0; $j<sizeof($discountGroupMenuMap); $j++)
                            {
                                $menuID = $discountGroupMenuMap[$j]["MenuID"];
                                for($i=0; $i<sizeof($arrOrderTaking); $i++)
                                {
                                    if(($menuID == $arrOrderTaking[$i]["menuID"]) && ($discountOnTop || (($arrOrderTaking[$i]["price"] == $arrOrderTaking[$i]["specialPrice"]) && ($arrOrderTaking[$i]["discountProgramValue"] == 0))))
                                    {
                                        $menuParticipateValue += $arrOrderTaking[$i]["specialPrice"] - $arrOrderTaking[$i]["discountProgramValue"];
                                        $arrOrderTakingParticipate[] = &$arrOrderTaking[$i];
                                    }
                                }
                            }
                        }
                        
                        $sql = "select * from $dbName.discountStepMap where discountStepID = '$discountStepID' and status = 1 order by StepSpend";
                        $discountStepMap = getSelectedRow($sql);
                        if(sizeof($discountStepMap))
                        {
                            for($i=0; $i<sizeof($discountStepMap); $i++)
                            {
                                $stepSpend = $discountStepMap[$i]["StepSpend"];
                                $amountDiscount = $discountStepMap[$i]["Amount"];
                                $maxDiscount = $discountStepMap[$i]["MaxDiscount"];
                                if($currentNoOfUsePerUser+1 == $stepSpend)
                                {
                                    $discountValue = $menuParticipateValue * $amountDiscount * 0.01;
                                    $discountValue = round($discountValue * 10000)/10000;
                                    $discountValue = $discountValue > $maxDiscount?$maxDiscount:$discountValue;
                                    break;
                                }
                            }
                            
                            $discountPromoCodeValue = $discountValue;
                        }
                    }
                }
                else if($discountType == 9)//get discount % step by day (limt max) **use DiscountStep in center(not specific branch)
                {
                    $discountValue = 0;
                    $noOfUseLeftPerUserPerDay  = $noOfLimitUsePerUserPerDay-$currentNoOfUsePerUserPerDay;
                    $noOfUseLeftPerUser = $noOfLimitUsePerUser-$currentNoOfUsePerUser;
                    $noOfUseLeft = $noOfLimitUse-$currentNoOfUse;
                    
                    $unlimitUse = 0;
                    if(($noOfLimitUse == 0) && ($noOfLimitUsePerUser == 0) && ($noOfLimitUsePerUserPerDay == 0))
                    {
                        $unlimitUse = 1;
                    }
                    else if(($noOfLimitUse == 0) && ($noOfLimitUsePerUser == 0) && !($noOfLimitUsePerUserPerDay == 0))
                    {
                        $noOfUseLeft = $noOfUseLeftPerUserPerDay;
                    }
                    else if(($noOfLimitUse == 0) && !($noOfLimitUsePerUser == 0) && ($noOfLimitUsePerUserPerDay == 0))
                    {
                        $noOfUseLeft = $noOfUseLeftPerUser;
                    }
                    else if(!($noOfLimitUse == 0) && ($noOfLimitUsePerUser == 0) && ($noOfLimitUsePerUserPerDay == 0))
                    {
                        $noOfUseLeft = $noOfUseLeft;
                    }
                    else if(($noOfLimitUse == 0) && !($noOfLimitUsePerUser == 0) && !($noOfLimitUsePerUserPerDay == 0))
                    {
                        $noOfUseLeft = $noOfUseLeftPerUser < $noOfUseLeftPerUserPerDay?$noOfUseLeftPerUser:$noOfUseLeftPerUserPerDay;
                    }
                    else if(!($noOfLimitUse == 0) && ($noOfLimitUsePerUser == 0) && !($noOfLimitUsePerUserPerDay == 0))
                    {
                        $noOfUseLeft = $noOfUseLeft < $noOfUseLeftPerUserPerDay?$noOfUseLeft:$noOfUseLeftPerUserPerDay;
                    }
                    else if(!($noOfLimitUse == 0) && !($noOfLimitUsePerUser == 0) && ($noOfLimitUsePerUserPerDay == 0))
                    {
                        $noOfUseLeft = $noOfUseLeft < $noOfUseLeftPerUser?$noOfUseLeft:$noOfUseLeftPerUser;
                    }
                    else
                    {
                        $noOfUseLeft = $noOfUseLeft < $noOfUseLeftPerUser?$noOfUseLeft:$noOfUseLeftPerUser;
                        $noOfUseLeft = $noOfUseLeft < $noOfUseLeftPerUserPerDay?$noOfUseLeft:$noOfUseLeftPerUserPerDay;
                    }
                    
                    if($unlimitUse || $noOfUseLeft > 0)
                    {
                        if($discountGroupMenuID == 0)
                        {
                            for($i=0; $i<sizeof($arrOrderTaking); $i++)
                            {
                                if($discountOnTop || (($arrOrderTaking[$i]["price"] == $arrOrderTaking[$i]["specialPrice"]) && ($arrOrderTaking[$i]["discountProgramValue"] == 0)))
                                {
                                    $menuParticipateValue += $arrOrderTaking[$i]["specialPrice"] - $arrOrderTaking[$i]["discountProgramValue"];
                                    $arrOrderTakingParticipate[] = &$arrOrderTaking[$i];
                                }
                            }
                        }
                        else
                        {
                            $sql = "select * from $dbName.discountGroupMenuMap where discountGroupMenuID = '$discountGroupMenuID' and status = 1";
                            $discountGroupMenuMap = getSelectedRow($sql);
                            for($j=0; $j<sizeof($discountGroupMenuMap); $j++)
                            {
                                $menuID = $discountGroupMenuMap[$j]["MenuID"];
                                for($i=0; $i<sizeof($arrOrderTaking); $i++)
                                {
                                    if(($menuID == $arrOrderTaking[$i]["menuID"]) && ($discountOnTop || (($arrOrderTaking[$i]["price"] == $arrOrderTaking[$i]["specialPrice"]) && ($arrOrderTaking[$i]["discountProgramValue"] == 0))))
                                    {
                                        $menuParticipateValue += $arrOrderTaking[$i]["specialPrice"] - $arrOrderTaking[$i]["discountProgramValue"];
                                        $arrOrderTakingParticipate[] = &$arrOrderTaking[$i];
                                    }
                                }
                            }
                        }
                        
                        
                        $sql = "select * from discountStepMap where discountStepID = '$discountStepID' and status = 1 order by StepSpend";
                        $discountStepMap = getSelectedRow($sql);
                        if(sizeof($discountStepMap))
                        {
                            for($i=0; $i<sizeof($discountStepMap); $i++)
                            {
                                $stepSpend = $discountStepMap[$i]["StepSpend"];
                                $amountDiscount = $discountStepMap[$i]["Amount"];
                                $maxDiscount = $discountStepMap[$i]["MaxDiscount"];
                                if($currentNoOfUsePerUser+1 == $stepSpend)
                                {
                                    $discountValue = $menuParticipateValue * $amountDiscount * 0.01;
                                    $discountValue = round($discountValue * 10000)/10000;
                                    $discountValue = $discountValue > $maxDiscount?$maxDiscount:$discountValue;
                                    break;
                                }
                            }
                            
                            $discountPromoCodeValue = $discountValue;
                        }
                    }
                }
                $applyVoucherCode = 1;
                writeToLog("discountType:$discountType,header:$promotionHeader, discountValue:$discountPromoCodeValue");
                
                //หาสัดส่วน ส่วนลดของแต่ละ item ***** สำหรับ insert ตอนจ่ายตัง
                $actualDiscount = $discountPromoCodeValue > $totalAfterDiscountProgram?$totalAfterDiscountProgram:$discountPromoCodeValue;
                $sumBeforeDiscount = 0;
                for($i=0; $i<sizeof($arrOrderTakingParticipate); $i++)
                {
                    $sumBeforeDiscount += $arrOrderTakingParticipate[$i]["specialPrice"]-$arrOrderTakingParticipate[$i]["discountProgramValue"];
                }
                
                for($i=0; $i<sizeof($arrOrderTakingParticipate); $i++)
                {
                    $beforeDiscount = $arrOrderTakingParticipate[$i]["specialPrice"]-$arrOrderTakingParticipate[$i]["discountProgramValue"];
                    $arrOrderTakingParticipate[$i]["discountValue"] = $beforeDiscount/$sumBeforeDiscount*$actualDiscount;
                    $arrOrderTakingParticipate[$i]["discountValue"] = round($arrOrderTakingParticipate[$i]["discountValue"]*10000)/10000;
                }
                
                
                //for insert into receipt*****
                $discountValue = $actualDiscount;

                
                //check sharedDiscountAmount
                {
                    $shopDiscount = $discountValue * $promotion["ShopDiscount"] * 0.01;
                    $shopDiscount = round($shopDiscount * 10000)/10000;
                    $jummumDiscount = $discountValue - $shopDiscount;
                    
                    if($promotion["SharedDiscountType"] == 1)//shop set maxDiscount
                    {
                        if($shopDiscount > $promotion["SharedDiscountAmountMax"])
                        {
                            $shopDiscount = $promotion["SharedDiscountAmountMax"];
                            $shopDiscount = round($shopDiscount * 10000)/10000;
                            $jummumDiscount = $discountValue - $shopDiscount;
                        }
                    }
                    else if($promotion["SharedDiscountType"] == 2)//jummum set maxDiscount
                    {
                        if($jummumDiscount > $promotion["SharedDiscountAmountMax"])
                        {
                            $jummumDiscount = $promotion["SharedDiscountAmountMax"];
                            $jummumDiscount = round($jummumDiscount * 10000)/10000;
                            $shopDiscount = $discountValue - $jummumDiscount;
                        }
                    }
                }
                //******
                
                
                //promo or reward insert into userPromotionUsed,userRewardRedemptionUsed,promoCodeStatus
                if($typeList[0]["Text"] == 1)
                {
                    $promotionID = $promotion["PromotionID"];
                }
                else if($typeList[0]["Text"] == 2)
                {
                    $rewardRedemptionID = $promotion["RewardRedemptionID"];
                    $promoCodeID = $promotion["PromoCodeID"];
                }
                ////*****
            }
        }
    }
    //***********
    //end voucher code validate/////////////
    
    
    //voucherList
    {
        $sql = "select count(*) PromotionCount from promotion left join promotionBranch on promotion.promotionID = promotionBranch.promotionID where promotionBranch.branchID = '$branchID' and '$currentDateTime' between usingStartDate and usingEndDate and type in (0,1) and (promotion.NoOfLimitUsePerUser = 0 or promotion.NoOfLimitUsePerUser > (select count(*) from userPromotionUsed where promotionID = promotion.promotionID and userAccountID = '$memberID')) order by promotion.type, promotion.orderNo;";
        $sql .= "SELECT count(*) RewardRedemptionCount FROM `rewardpoint` left join promoCode on rewardPoint.promoCodeID = promoCode.promoCodeID left join RewardRedemption on promocode.rewardRedemptionID = RewardRedemption.rewardRedemptionID WHERE MemberID = '$memberID' and rewardpoint.status = -1 and ((TIME_TO_SEC(timediff('$currentDateTime', rewardpoint.ModifiedDate)) < rewardredemption.WithInPeriod) or (rewardredemption.WithInPeriod = 0 and '$currentDateTime'<rewardRedemption.usingEndDate)) and promoCode.status = 1 and rewardRedemption.rewardRedemptionID in (select rewardRedemptionID from rewardRedemptionBranch where branchID = '$branchID');";
    }
    $arrPromotionListAndRewardRedemptionList = executeMultiQueryArray($sql);
    $showVoucherListButton = $arrPromotionListAndRewardRedemptionList[0][0]->PromotionCount+$arrPromotionListAndRewardRedemptionList[1][0]->RewardRedemptionCount;
    
    
    //calculate value
    $sql = "select * from $jummumOM.branch where branchID = '$branchID'";
    $selectedRow = getSelectedRow($sql);
    $priceIncludeVat = $selectedRow[0]["PriceIncludeVat"];
    $percentVat = $selectedRow[0]["PercentVat"];
    $serviceChargePercent = $selectedRow[0]["ServiceChargePercent"];
    $sql = "select * from $dbName.setting where keyName = 'luckyDrawSpend'";
    $selectedRow = getSelectedRow($sql);
    $luckyDrawSpend = $selectedRow[0]["Value"];
    
    
    //totalAmount
    $totalAmount = $totalAmount;

    //specialPriceDiscount
    $specialPriceDiscount = $totalAmount - $sumSpecialPrice;

    //discountProgram
    $discountProgramValue = round($discountProgramValue*100)/100;

    //voucherCodeDiscount
    $discountPromoCodeValue = round($discountPromoCodeValue*100)/100;

    //showVoucherListButton
    $showVoucherListButton = $showVoucherListButton>0?1:0;

    //price after discount
    $afterDiscount = ($sumSpecialPrice - $discountProgramValue - $discountPromoCodeValue);
    $afterDiscount = $afterDiscount < 0?0:$afterDiscount;
    $afterDiscount = round($afterDiscount*100)/100;

    //price before vat(before service)
    $priceBeforeVat = $afterDiscount;
    if($priceIncludeVat)
    {
        $priceBeforeVat = $afterDiscount / (($percentVat+100)*0.01);
        $priceBeforeVat = round($priceBeforeVat*100)/100;
    }

    //service charge
    $serviceChargeValue = $serviceChargePercent * $priceBeforeVat * 0.01;
    $serviceChargeValue = round($serviceChargeValue*100)/100;

    //vat
    $vatValue = ($priceBeforeVat+$serviceChargeValue)*$percentVat * 0.01;
    $vatValue = round($vatValue*100)/100;

    //net total
    $netTotal = $priceBeforeVat + $serviceChargeValue + $vatValue;
    $netTotal = round($netTotal * 100)/100;

    //luckyDrawSpend
    $luckyDrawCount = $luckyDrawSpend != 0?floor($netTotal/$luckyDrawSpend):0;

    //beforeVat after service
    $beforeVat = $priceBeforeVat + $serviceChargeValue;
    //---------------------------

    //show item
    $showTotalAmount = 1;
    $showSpecialPriceDiscount = $specialPriceDiscount > 0?1:0;
    $showDiscountProgram = $discountProgramValue > 0?1:0;
    $showAfterDiscount = $afterDiscount > 0?1:0;
    $applyVoucherCode = $applyVoucherCode;
    $showServiceCharge = $serviceChargePercent > 0?1:0;
    $showVat = $percentVat > 0?1:0;
    $showNetTotal = $serviceChargePercent + $percentVat > 0?1:0;
    $showLuckyDrawCount = $luckyDrawCount > 0?1:0;
    $showBeforeVat = ($showServiceCharge && $showVat) || ($serviceChargePercent == 0 && $percentVat > 0 && $priceIncludeVat)?1:0;
    //buffetButton
    $showPayBuffetButton = 2;//0=not show,1=pay,2=order obuffet
    for($i=0; $i<sizeof($arrOrderTaking); $i++)
    {
        $menuID = $arrOrderTaking[$i]["menuID"];
        $sql = "select AlacarteMenu from $dbName.Menu where menuID = '$menuID'";
        $selectedRow = getSelectedRow($sql);
        $alacarteMenu = $selectedRow[0]["AlacarteMenu"];
        if($alacarteMenu)
        {
            $showPayBuffetButton = 1;
        }
    }
    $showPayBuffetButton = $applyVoucherCode?1:$showPayBuffetButton;
    $showPayBuffetButton = $showPayBuffetButton && ($netTotal > 0)?1:$showPayBuffetButton;
    $showPayBuffetButton = sizeof($arrOrderTaking) > 0?$showPayBuffetButton:0;
    

    //title
    $noOfItem = sizeof($arrOrderTaking);
    $discountProgramTitle = $discountProgramTitle;
    $priceIncludeVat = $priceIncludeVat;
    $serviceChargePercent = $serviceChargePercent;
    $percentVat = $percentVat;
    
    
    $specialPriceDiscountTitle = "ส่วนลด";
    $afterDiscountTitle = $priceIncludeVat?"ยอดรวม (รวม Vat)":"ยอดรวม";
    $luckyDrawTitle = $luckyDrawCount > 0?"(คุณจะได้สิทธิ์ลุ้นรางวัล $luckyDrawCount ครั้ง)":"(คุณไม่ได้รับสิทธิ์ลุ้นรางวัลในครั้งนี้)";
    $discountPromoCodeTitle = "คูปองส่วนลด $voucherCode";
    //*********
    

    //creditCardAndOrderSummary
    $sql = "select '$totalAmount' TotalAmount, '$specialPriceDiscount' SpecialPriceDiscount, '$discountProgramValue' DiscountProgramValue, '$discountPromoCodeValue' DiscountPromoCodeValue, '$showVoucherListButton' ShowVoucherListButton, '$afterDiscount' AfterDiscount, '$serviceChargeValue' ServiceChargeValue, '$vatValue' VatValue, '$netTotal' NetTotal, '$luckyDrawCount' LuckyDrawCount, '$beforeVat' BeforeVat, '$showTotalAmount' ShowTotalAmount, '$showSpecialPriceDiscount' ShowSpecialPriceDiscount, '$showDiscountProgram' ShowDiscountProgram, '$applyVoucherCode' ApplyVoucherCode, '$showAfterDiscount' ShowAfterDiscount, '$showServiceCharge' ShowServiceCharge, '$showVat' ShowVat, '$showNetTotal' ShowNetTotal, '$showLuckyDrawCount' ShowLuckyDrawCount, '$showBeforeVat' ShowBeforeVat, '$showPayBuffetButton' ShowPayBuffetButton, '$noOfItem' NoOfItem, '$discountProgramTitle' DiscountProgramTitle, '$priceIncludeVat' PriceIncludeVat, '$serviceChargePercent' ServiceChargePercent, '$percentVat' PercentVat, '$specialPriceDiscountTitle' SpecialPriceDiscountTitle, '$afterDiscountTitle' AfterDiscountTitle, '$luckyDrawTitle' LuckyDrawTitle, '$discountPromoCodeTitle' DiscountPromoCodeTitle;";
    $arrCreditCardAndOrderSummary = executeQueryArray($sql);
    
    
    //return data to app******
    $dataList = array();
    {
        //make key capital letter for OrderTaking*****----> ย้ายไปตอน return ไป interface
        $arrOrderTakingNewCapitalKey = array();
        for($i=0; $i<sizeof($arrOrderTaking); $i++)
        {
            $orderTakingNewCapitalKey = array();
            $orderTakingNew = $arrOrderTaking[$i];
            foreach ($orderTakingNew as $key => $value)
            {
                $orderTakingNewCapitalKey[makeFirstLetterUpperCase($key)] = $value;
            }
            array_push($arrOrderTakingNewCapitalKey,$orderTakingNewCapitalKey);
        }


        //make key capital letter for orderNote
        $arrOrderNoteNewCapitalKey = array();
        for($i=0; $i<sizeof($arrOrderNote); $i++)
        {
            $orderNoteNewCapitalKey = array();
            $orderNoteNew = $arrOrderNote[$i];
            foreach ($orderNoteNew as $key => $value)
            {
                $orderNoteNewCapitalKey[makeFirstLetterUpperCase($key)] = $value;
            }
            array_push($arrOrderNoteNewCapitalKey,$orderNoteNewCapitalKey);
        }
        //*******
    }
    $dataList[] = $arrOrderTakingNewCapitalKey;
    $dataList[] = $arrOrderNoteNewCapitalKey;
    $dataList[] = $arrCreditCardAndOrderSummary;
    
    if(!$inOpeningTime)
    {
        $warningMsg = "ทางร้านไม่ได้เปิดระบบการสั่งอาหารด้วยตนเองตอนนี้ ขออภัยในความไม่สะดวกค่ะ";
        writeToLog("$warningMsg, file: " . basename(__FILE__) . ", user: " . $data['modifiedUser']);
        $response = array('success' => false, 'data' => $dataList, 'error' => "$warningMsg");

        echo json_encode($response);
        exit();
    }
    else if($orderChanged || $warningMsgVoucher)
    {
        $warningMsgOrderChanged = $warningMsgOrderChanged != ""?"-".$warningMsgOrderChanged:"";
        $warningMsgVoucher = $warningMsgVoucher != ""?"-".$warningMsgVoucher:"";
        
        $lineBreak = ($warningMsgOrderChanged != "") && ($warningMsgVoucher != "")?"\n":"";
        $warningMsg = $warningMsgOrderChanged . $lineBreak . $warningMsgVoucher;
        
        $success = $warningMsg == "";
        $response = array('success' => $success, 'data' => $dataList, 'error' => "$warningMsg");

        echo json_encode($response);
        exit();
    }
    
    
    //omise part
    $amount = $netTotal*100;
    if(($netTotal != 0) && ($paymentMethod == 2))
    {
        if($netTotal < 20)
        {
            $warningMsg = "ไม่สามารถชำระผ่านบัตรเครดิตได้ (จำนวนเงินขั้นต่ำ 20 บาท)";
            writeToLog("omise charge fail, file: " . basename(__FILE__) . ", user: " . $data['modifiedUser']);
            $response = array('success' => false, 'data' => $dataList, 'error'=>"$warningMsg");
            echo json_encode($response);
            exit();
        }
        
        
        require_once  dirname(__FILE__) . '/../omise-php/lib/Omise.php';
        
        
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
            $warningMsg = $e->getMessage();
            writeToLog("omise charge fail, file: " . basename(__FILE__) . ", user: " . $data['modifiedUser']);
            $response = array('success' => false, 'data' => $dataList, 'error'=>"$warningMsg");
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
        // Set autocommit to off
        mysqli_autocommit($con,FALSE);
        writeToLog("set auto commit to off");
        
        
        
        //query statement
//        $currentDateTime = date('Y-m-d');
        $sql = "select * from transactionFee where StartDate <= '$currentDateTime' and EndDate >= '$currentDateTime' and branchID = 0 and type = '$paymentMethod' order by modifiedDate desc";
        $selectedRow = getSelectedRow($sql);
        $transactionFee = $selectedRow[0]["Rate"];
        $transactionFeeValue = $amount * 0.01 * $transactionFee * 0.01;
        $transactionFeeValue = round($transactionFeeValue * 10000)/10000;
        $sql = "select * from transactionFee where StartDate <= '$currentDateTime' and EndDate >= '$currentDateTime' and branchID = '$branchID' and type = '$paymentMethod' order by modifiedDate desc";
        $selectedRow = getSelectedRow($sql);
        $transactionFeeBranch = $selectedRow[0]["Rate"];
        $transactionFeeValueBranch = $amount * 0.01 * $transactionFeeBranch * 0.01;
        $transactionFeeValueBranch = round($transactionFeeValueBranch * 10000)/10000;
        $jummumPayValue = $transactionFeeValue - $transactionFeeValueBranch;
        $sql = "INSERT INTO Receipt(BranchID, CustomerTableID, MemberID, ServingPerson, CustomerType, OpenTableDate, PaymentMethod, TotalAmount, CashAmount, CashReceive, CreditCardType, CreditCardNo, CreditCardAmount, TransferDate, TransferAmount, Remark, SpecialPriceDiscount, DiscountProgramType, DiscountProgramTitle, DiscountProgramValue, DiscountType, DiscountValue, DiscountReason, ServiceChargePercent, ServiceChargeValue, PriceIncludeVat, VatPercent, VatValue, NetTotal, LuckyDrawCount, BeforeVat, Status, StatusRoute, ReceiptNoID, ReceiptNoTaxID, ReceiptDate, SendToKitchenDate, DeliveredDate, MergeReceiptID, HasBuffetMenu, TimeToOrder, BuffetEnded, BuffetEndedDate, BuffetReceiptID, VoucherCode, ShopDiscount, JummumDiscount, TransactionFeeValue, JummumPayValue, ModifiedUser, ModifiedDate) VALUES ('$branchID', '$customerTableID', '$memberID', '$servingPerson', '$customerType', '$openTableDate', '$paymentMethod', '$totalAmount', '$cashAmount', '$cashReceive', '$creditCardType', '$creditCardNo', '$creditCardAmount', '$transferDate', '$transferAmount', '$remark', '$specialPriceDiscount', '$discountProgramType', '$discountProgramTitle', '$discountProgramValue', '$discountType', '$discountValue', '$discountReason', '$serviceChargePercent', '$serviceChargeValue', '$priceIncludeVat', '$percentVat', '$vatValue', '$netTotal', '$luckyDrawCount', '$beforeVat', '$status', '$status', '$receiptNoID', '$receiptNoTaxID', '$receiptDate', '$sendToKitchenDate', '$deliveredDate', '$mergeReceiptID', '$hasBuffetMenu', '$timeToOrder', '$buffetEnded', '$buffetEndedDate', '$buffetReceiptID', '$voucherCode', '$shopDiscount', '$jummumDiscount', '$transactionFeeValue', '$jummumPayValue', '$modifiedUser', '$modifiedDate')";
        $ret = doQueryTask($sql);
        if($ret != "")
        {
            mysqli_rollback($con);
//            putAlertToDevice();
            $ret["data"] = $dataList;
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
            $ret["data"] = $dataList;
            echo json_encode($ret);
            exit();
        }

        
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
                $sql = "INSERT INTO OrderTaking(BranchID, CustomerTableID, MenuID, Quantity, SpecialPrice, Price, TakeAway, TakeAwayPrice, NoteIDListInText, NotePrice, DiscountValue, OrderNo, Status, ReceiptID, ModifiedUser, ModifiedDate) VALUES ('$otBranchID[$k]', '$otCustomerTableID[$k]', '$otMenuID[$k]', '$otQuantity[$k]', '$otSpecialPrice[$k]', '$otPrice[$k]', '$otTakeAway[$k]', '$otTakeAwayPrice[$k]', '$otNoteIDListInText[$k]', '$otNotePrice[$k]', '$otDiscountValue[$k]', '$otOrderNo[$k]', '$otStatus[$k]', '$receiptID', '$otModifiedUser[$k]', '$otModifiedDate[$k]')";
                $ret = doQueryTask($sql);
                if($ret != "")
                {
                    mysqli_rollback($con);
                    //                    putAlertToDevice();
                    $ret["data"] = $dataList;
                    echo json_encode($ret);
                    exit();
                }
                
                
                
                //insert ผ่าน
                $newID = mysqli_insert_id($con);
                
                
                
                
                //select row ที่แก้ไข ขึ้นมาเก็บไว้
                $orderTakingOldNew[$otOrderTakingID[$k]] = $newID;
                $otOrderTakingID[$k] = $newID;
            }
            
        }
        //-----
        
        
        
        //orderNoteList
        if(sizeof($arrOrderNote) > 0)
        {
            for($k=0; $k<sizeof($arrOrderNote); $k++)
            {
                //query statement
                $onOrderTakingID[$k] = $orderTakingOldNew[$onOrderTakingID[$k]];
                $sql = "INSERT INTO OrderNote(OrderTakingID, NoteID, Quantity, ModifiedUser, ModifiedDate) VALUES ('$onOrderTakingID[$k]', '$onNoteID[$k]', '$onQuantity[$k]', '$onModifiedUser[$k]', '$onModifiedDate[$k]')";
                $ret = doQueryTask($sql);
                if($ret != "")
                {
                    mysqli_rollback($con);
                    //                    putAlertToDevice();
                    $ret["data"] = $dataList;
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
//        else
//        {
//            $sql = "select * from orderNote where 0;";
//            $sqlAll .= $sql;
//        }
        //------
        
        
        //lucky draw
        if($status == 2)//จ่ายตัง, บุฟเฟ่ต์
        {
            $sql = "select * from $dbName.setting where keyName = 'luckyDrawSpend'";
            $selectedRow = getSelectedRow($sql);
            $luckyDrawSpend = $selectedRow[0]["Value"];
            if($luckyDrawSpend)
            {
                $luckyDrawTimes = floor($amount/100/$luckyDrawSpend);
            }
            else
            {
                $luckyDrawTimes = 0;
            }
            writeToLog("luckyDrawTimes: " . $luckyDrawTimes);
            if($luckyDrawTimes > 0)
            {
                for($i=0; $i<$luckyDrawTimes; $i++)
                {
                    if($i==0)
                    {
                        $sql = "insert into LuckyDrawTicket (ReceiptID,MemberID, RewardRedemptionID,GetTicketDate,ModifiedUser,ModifiedDate) values ('$receiptID','$memberID',-1,'$modifiedDate','$modifiedUser','$modifiedDate')";
                    }
                    else
                    {
                        $sql .= ",('$receiptID','$memberID',-1,'$modifiedDate','$modifiedUser','$modifiedDate')";
                    }
                }
                $ret = doQueryTask($sql);
                if($ret != "")
                {
                    mysqli_rollback($con);
                    //                    putAlertToDevice();
                    $ret["data"] = $dataList;
                    echo json_encode($ret);
                    exit();
                }
            }

            
            
            $sql = "select * from setting where keyName = 'LuckyDrawTimeLimit';";
            $selectedRow = getSelectedRow($sql);
            $luckyDrawTimeLimit = $selectedRow[0]["Value"];
            $sql = "select count(*) LuckyDrawCount from luckyDrawTicket left join receipt on luckyDrawTicket.receiptID = receipt.receiptID where luckyDrawTicket.memberID = '$memberID' and receipt.branchID = '$branchID' and rewardRedemptionID = -1 and TIME_TO_SEC(timediff('$currentDateTime', luckyDrawTicket.modifiedDate)) <= '$luckyDrawTimeLimit';";
            $arrLuckyDrawTicket = executeQueryArray($sql);
        }
        else//โอนเงิน
        {
            $sql = "select 0 LuckyDrawCount;";
            $arrLuckyDrawTicket = executeQueryArray($sql);
//            $arrLuckyDrawTicket = array();
        }
        
        
        
        
        
        
        /* execute multi query */
//        $dataJson = executeMultiQueryArray($sqlAll);
        
        
        
        if($promotionID && $discountPromoCodeValue > 0)
        {
            $sql = "INSERT INTO UserPromotionUsed(UserAccountID, PromotionID, ReceiptID, ModifiedUser, ModifiedDate) VALUES ('$userAccountID', '$promotionID', '$receiptID', '$modifiedUser', '$modifiedDate')";
            $ret = doQueryTask($sql);
            if($ret != "")
            {
                mysqli_rollback($con);
                //                    putAlertToDevice();
                $ret["data"] = $dataList;
                echo json_encode($ret);
                exit();
            }
        }
        if($rewardRedemptionID && $discountPromoCodeValue > 0)
        {
            //query statement
            $sql = "INSERT INTO UserRewardRedemptionUsed(UserAccountID, RewardRedemptionID, ReceiptID, ModifiedUser, ModifiedDate) VALUES ('$userAccountID', '$rewardRedemptionID', '$receiptID', '$modifiedUser', '$modifiedDate')";
            $ret = doQueryTask($sql);
            if($ret != "")
            {
                mysqli_rollback($con);
                //                    putAlertToDevice();
                $ret["data"] = $dataList;
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
                $ret["data"] = $dataList;
                echo json_encode($ret);
                exit();
            }
        }
        if($discountProgramValue > 0)
        {
            $sql = "INSERT INTO $dbName.DiscountProgramUser(`DiscountProgramID`, `UserAccountID`, `ModifiedUser`, `ModifiedDate`) VALUES ('$discountProgramID','$userAccountID','$modifiedUser', '$modifiedDate')";
            $ret = doQueryTask($sql);
            if($ret != "")
            {
                mysqli_rollback($con);
                //                    putAlertToDevice();
                $ret["data"] = $dataList;
                echo json_encode($ret);
                exit();
            }
        }

        
        
        
        //reward เก็บแต้ม
        $sql = "SELECT * FROM `rewardprogram` WHERE StartDate <= '$currentDateTime' and EndDate >= '$currentDateTime' and type = 1 order by modifiedDate desc";
        $selectedRow = getSelectedRow($sql);
        if(sizeof($selectedRow)>0)
        {
            $salesSpent = $selectedRow[0]["SalesSpent"];
            $receivePoint = $selectedRow[0]["ReceivePoint"];
            $rewardPoint = $amount/100.0/$salesSpent*$receivePoint;
            
            
            if($rewardPoint > 0)
            {
                $sql = "INSERT INTO RewardPoint(MemberID, ReceiptID, Point, Status, PromoCodeID, ModifiedUser, ModifiedDate) VALUES ('$memberID', '$receiptID', '$rewardPoint', '1', '0', '$modifiedUser', '$modifiedDate')";
                $ret = doQueryTask($sql);
                if($ret != "")
                {
                    mysqli_rollback($con);
    //                putAlertToDevice();
                    $ret["data"] = $dataList;
                    echo json_encode($ret);
                    exit();
                }
            }
        }
        //-----********
        //-----****************************
        //****************send noti to shop (turn on light)
        //alarmShop
        //query statement
        if($paymentMethod == 2)
        {
            $ledStatus = 1;
            $sql = "update $dbName.Setting set value = '$ledStatus', ModifiedUser = '$modifiedUser', ModifiedDate = '$modifiedDate' where keyName = 'ledStatus'";
            $ret = doQueryTask($sql);
            if($ret != "")
            {
                mysqli_rollback($con);
                //        putAlertToDevice();
                $ret["data"] = $dataList;
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
            


            $msg = 'New order coming!! order no:' . $receiptNoID;
            $category = "printKitchenBill";
            $contentAvailable = 1;
            $data = array("receiptID" => $receiptID);
            sendPushNotificationJummumOM($pushSyncDeviceTokenReceiveOrder,$title,$msg,$category,$contentAvailable,$data);
        }
        else
        {
            mysqli_commit($con);
        }

        
        
        
        
        
        //return data to app
        //return receipt detail
        $referenceDate = date("ymd");
        $sql = "select `ReceiptID`, `BranchID`, `CustomerTableID`, `MemberID`, `TotalAmount`, `CreditCardType`, `CreditCardNo`, `CreditCardAmount`, `Remark`,`SpecialPriceDiscount`,DiscountProgramType,DiscountProgramTitle,DiscountProgramValue, `DiscountType`, `DiscountValue`, `ServiceChargePercent`, `ServiceChargeValue`, `PriceIncludeVat`, `VatPercent`, `VatValue`,NetTotal,LuckyDrawCount,BeforeVat, `Status`, `ReceiptNoID`, concat($referenceDate,ReceiptNoID) ReferenceNo, `ReceiptDate`, `SendToKitchenDate`, `DeliveredDate`, `BuffetReceiptID`,HasBuffetMenu,TimeToOrder,BuffetEnded,BuffetEndedDate, `VoucherCode`, case `Status` when 2 then 'Order sent' when 5 then 'Processing...' when 6 then 'Delivered' when 7 then 'Pending cancel' when 8 then 'Order dispute in process' when 9 then 'Order cancelled' when 10 then 'Order dispute finished' when 11 then 'Negotiate' when 12 then 'Review dispute' when 13 then 'Review dispute in process' when 14 then 'Order dispute finished' end as StatusText from receipt where receiptID = '$receiptID';";
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
                $sql3 = "select '$branchID' BranchID, menu.MenuID, `MenuCode`, `TitleThai`, `Price`, `MenuTypeID`, `BuffetMenu`, `BelongToMenuID`, `TimeToOrder`, `ImageUrl`, `OrderNo` from $eachDbName.Menu where menu.menuID = '$menuID'";
                $arrMenu = executeQueryArray($sql3);
                for($i=0; $i<sizeof($arrMenu); $i++)
                {
                    $menuID = $arrMenu[$i]->MenuID;
                    $sql = "select * from $dbName.SpecialPriceProgram left join $dbName.SpecialPriceProgramDay on specialPriceProgram.specialPriceProgramID = specialPriceProgramDay.specialPriceProgramID and specialPriceProgramDay.Day = weekday('$currentDateTime')+1 where menuID = '$menuID' AND '$currentDateTime' between startDate and endDate and specialPriceProgramDayID is not null order by StartDate desc, EndDate desc, SpecialPriceProgram.ModifiedDate desc";
                    $selectedRow = getSelectedRow($sql);
                    if(sizeof($selectedRow)>0)
                    {
                        $arrMenu[$i]->SpecialPrice = $selectedRow[0]["SpecialPrice"];
                    }
                    else
                    {
                        $arrMenu[$i]->SpecialPrice = $arrMenu[$i]->Price;
                    }
                }
    
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
        
        
        //JummumLogo
        $sql = "select * from setting where KeyName = 'JummumLogo'";
        $selectedRow = getSelectedRow($sql);
        $jummumLogo = $selectedRow[0]["Value"];
        $arrReceipt[0]->JummumLogo = $jummumLogo;


        $sql = "select (select VALUE from setting where keyName = 'GBPrimeQRPostUrl') GBPrimeQRPostUrl,(select VALUE from setting where keyName = 'GBPrimeQRToken') GBPrimeQRToken,(select VALUE from setting where keyName = 'ResponseUrl') ResponseUrl,(select VALUE from setting where keyName = 'BackgroundUrl') BackgroundUrl;";
        $GBPrimePay = executeMultiQueryArray($sql);
        

        //do script successful
        mysqli_close($con);
        
        
        $showQRToPay = ($paymentMethod == 1);
        $buffetList = array();
        $thankYouText = $showPayBuffetButton==1?"ชำระเงินสำเร็จ":"สั่งบุฟเฟ่ต์สำเร็จ";//2="สั่งบุฟเฟ่ต์สำเร็จ"
        $showOrderBuffetButton = $hasBuffetMenu || $buffetReceiptID;
        $buffetReceiptID = $hasBuffetMenu?$receiptID:$buffetReceiptID;
        array_push($buffetList,array("ShowQRToPay"=>$showQRToPay, "ThankYouText"=>$thankYouText, "ShowOrderBuffetButton"=>$showOrderBuffetButton, "BuffetReceiptID"=>$buffetReceiptID));
        $dataList[] = $arrReceipt;
        $dataList[] = $arrLuckyDrawTicket;
        $dataList[] = $buffetList;//new orderChange data, order summary, receipt for invoice,buffet ui
        $dataList[] = $GBPrimePay[0];
        
        


        $response = array('success' => true, 'data' => $dataList, 'error'=>'');
        echo json_encode($response);
        exit();
    }
    else
    {
        $warningMsg = "ตัดบัตรเครดิตไม่สำเร็จ กรุณาตรวจสอบข้อมูลบัตรเครดิตใหม่อีกครั้ง";
        writeToLog("omise charge fail, file: " . basename(__FILE__) . ", user: " . $data['modifiedUser']);
        $response = array('success' => false, 'data' => $dataList, 'error'=>"$warningMsg");
        echo json_encode($response);
        exit();
    }
    
?>
