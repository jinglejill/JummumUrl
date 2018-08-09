<?php
    include_once("dbConnect.php");
    setConnectionValue("");
    writeToLog("file: " . basename(__FILE__) . ", user: " . $_POST["modifiedUser"]);
    printAllPost();
    ini_set("memory_limit","-1");
    
    
    
    if(isset($_POST["voucherCode"]))
    {
        $voucherCode = $_POST["voucherCode"];
    }
    if(isset($_POST["userAccountID"]))
    {
        $userAccountID = $_POST["userAccountID"];
    }
    if(isset($_POST["branchID"]))
    {
        $branchID = $_POST["branchID"];
    }
    if(isset($_POST["totalAmount"]))
    {
        $totalAmount = $_POST["totalAmount"];
    }
    
    
    
    
    // Check connection
    if (mysqli_connect_errno())
    {
        echo "Failed to connect to MySQL: " . mysqli_connect_error();
    }
    
    
    
    
    
    
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
        $sql = "select * from promotion where voucherCode = '$voucherCode' and date_format(now(),'%Y-%m-%d') < date_format(usingStartDate,'%Y-%m-%d');";
        $selectedRow = getSelectedRow($sql);
        if(sizeof($selectedRow) > 0)
        {
            //คูปองส่วนลดไม่ถูกต้อง -> voucher code นี้ยังไม่ได้เริ่มใช้
            $voucherValid = 0;
            $warningMsg = "Voucher Code นี้ยังไม่ได้เริ่มใช้";
        }
    }
    
    
    if($voucherValid)
    {
        $sql = "select * from promotion where voucherCode = '$voucherCode' and date_format(now(),'%Y-%m-%d') > date_format(usingEndDate,'%Y-%m-%d');";
        $selectedRow = getSelectedRow($sql);
        if(sizeof($selectedRow) > 0)
        {
            //คูปองส่วนลดไม่ถูกต้อง -> voucher code นี้หมดอายุแล้ว
            $voucherValid = 0;
            $warningMsg = "Voucher Code นี้หมดอายุแล้ว";
        }
    }
    
    
    $sql = "select * from promotion where voucherCode = '$voucherCode' and date_format(now(),'%Y-%m-%d') between date_format(usingStartDate,'%Y-%m-%d') and date_format(usingEndDate,'%Y-%m-%d');";
    $promotion = getSelectedRow($sql);
    $promotionID = $promotion[0]["PromotionID"];
    $noOfLimitUse = $promotion[0]["NoOfLimitUse"];
    $noOfLimitUsePerUser = $promotion[0]["NoOfLimitUsePerUser"];
    $noOfLimitUsePerUserPerDay = $promotion[0]["NoOfLimitUsePerUserPerDay"];
    $minimumSpending = $promotion[0]["MinimumSpending"];
    $maxDiscountAmountPerDay = $promotion[0]["MaxDiscountAmountPerDay"];
    $allowEveryone = $promotion[0]["AllowEveryone"];
    if($voucherValid)
    {
        $hasVoucherInPromotionTable = 1;
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
        $usedCount = $selectedRow[0]["UsedCount"];
        if($noOfLimitUsePerUser > 0 && $usedCount >= $noOfLimitUsePerUser)
        {
            //คูปองส่วนลดไม่ถูกต้อง -> คุณใช้สิทธิ์ครบแล้ว
            $voucherValid = 0;
            $warningMsg = "คุณใช้สิทธิ์ครบแล้ว";
        }
    }
    
    
    if($voucherValid)
    {
        $sql = "select count(*) UsedCount from userPromotionUsed where promotionID = '$promotionID' and userAccountID = '$userAccountID' and date_format(modifiedDate,'%Y-%m-%d') = date_format(now(),'%Y-%m-%d')";
        $selectedRow = getSelectedRow($sql);
        $usedCount = $selectedRow[0]["UsedCount"];
        if($noOfLimitUsePerUserPerDay > 0 && $usedCount >= $noOfLimitUsePerUserPerDay)
        {
            //คูปองส่วนลดไม่ถูกต้อง -> วันนี้คุณใช้สิทธิ์ครบแล้ว
            $voucherValid = 0;
            $warningMsg = "วันนี้คุณใช้สิทธิ์ครบแล้ว";
        }
    }
    
    
    if($voucherValid)
    {
        //minimumSpending
        if($totalAmount < $minimumSpending)
        {
            //คูปองส่วนลดไม่ถูกต้อง -> ยอดสั่งซื้อขั้นต่ำไม่ถึง
            $voucherValid = 0;
            $warningMsg = "ยอดสั่งซื้อขั้นต่ำไม่ถึง";
        }
    }
    
    
    if($voucherValid)
    {
        $sql = "select ifnull(sum(discountValue),0) SumDiscountValue from userPromotionUsed left join receipt on userPromotionUsed.receiptID = receipt.receiptID where promotionID = '$promotionID' and userAccountID = '$userAccountID' and date_format(userPromotionUsed.modifiedDate,'%Y-%m-%d') = date_format(now(),'%Y-%m-%d')";
        $selectedRow = getSelectedRow($sql);
        $sumDiscountValue = $selectedRow[0]["SumDiscountValue"];
        if($maxDiscountAmountPerDay > 0)
        {
            if($sumDiscountValue >= $maxDiscountAmountPerDay)
            {
                //คูปองส่วนลดไม่ถูกต้อง -> วันนี้คุณใช้สิทธิ์ครบแล้ว
                $voucherValid = 0;
                $warningMsg = "วันนี้คุณใช้สิทธิ์ครบแล้ว";
            }
            else
            {
                $moreDiscountToGo = $maxDiscountAmountPerDay - $sumDiscountValue;
            }
        }
        else
        {
            $moreDiscountToGo = -1;
        }
    }
    
    
    
    if(!$hasVoucherInPromotionTable)
    {
        //search at table rewardRedemption,rewardPoint, promoCode
        $warningMsg2 = "";
        $voucherValid2 = 1;
        $currentDateTime = date('Y-m-d H:i:s');
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
                    $voucherValid2 = 0;
                    $warningMsg2 = "ไม่มี Voucher Code นี้";
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
        
        
        
        if($voucherValid2)
        {
            //minimumSpending
            if($totalAmount < $minimumSpending)
            {
                //คูปองส่วนลดไม่ถูกต้อง -> ยอดสั่งซื้อขั้นต่ำไม่ถึง
                $voucherValid2 = 0;
                $warningMsg2 = "ยอดสั่งซื้อขั้นต่ำไม่ถึง";
            }
        }
        
        
        
        if($voucherValid2)
        {
            $sql = "select ifnull(sum(discountValue),0) SumDiscountValue from userRewardRedemptionUsed left join receipt on userRewardRedemptionUsed.receiptID = receipt.receiptID where RewardRedemptionID = '$rewardRedemptionID' and userAccountID = '$userAccountID' and date_format(userRewardRedemptionUsed.modifiedDate,'%Y-%m-%d') = date_format(now(),'%Y-%m-%d')";
            $selectedRow = getSelectedRow($sql);
            $sumDiscountValue = $selectedRow[0]["SumDiscountValue"];
            if($maxDiscountAmountPerDay > 0)
            {
                if($sumDiscountValue >= $maxDiscountAmountPerDay)
                {
                    //คูปองส่วนลดไม่ถูกต้อง -> วันนี้คุณใช้สิทธิ์ครบแล้ว
                    $voucherValid2 = 0;
                    $warningMsg2 = "วันนี้คุณใช้สิทธิ์ครบแล้ว";
                }
                else
                {
                    $moreDiscountToGo = $maxDiscountAmountPerDay - $sumDiscountValue;
                }
            }
            else
            {
                $moreDiscountToGo = -1;
            }
        }
    }
    
    
    
    if($voucherValid)
    {
        $sql = "select promotion.*, $moreDiscountToGo as MoreDiscountToGo,0 PromoCodeID from promotion where voucherCode = '$voucherCode' and date_format(now(),'%Y-%m-%d') between date_format(usingStartDate,'%Y-%m-%d') and date_format(usingEndDate,'%Y-%m-%d');";
        $sql .= "select '' as Text;";
        $sql .= "select 1 as Text";
    }
    else if(!$voucherValid && $hasVoucherInPromotionTable)
    {
        $sql = "select * from promotion where 0;";
        $sql .= "select '$warningMsg' as Text;";
        $sql .= "select 1 as Text";
    }
    else if(!$voucherValid && !$hasVoucherInPromotionTable)
    {
        if($voucherValid2)
        {
            $sql = "select $moreDiscountToGo as MoreDiscountToGo,RewardRedemptionID,AllowDiscountForAllMenuType,DiscountType,DiscountAmount,MainBranchID,DiscountMenuID,$promoCodeID PromoCodeID from rewardRedemption where rewardRedemptionID = '$rewardRedemptionID';";
            $sql .= "select '' as Text;";
            $sql .= "select 2 as Text";
        }
        else
        {
            $sql = "select * from promotion where 0;";
            $sql .= "select '$warningMsg2' as Text;";
            $sql .= "select 2 as Text";
        }
    }

    
    
    
    
    /* execute multi query */
    $jsonEncode = executeMultiQuery($sql);
    echo $jsonEncode;


    
    // Close connections
    mysqli_close($con);
    
?>
