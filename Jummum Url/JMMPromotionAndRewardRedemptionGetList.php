<?php
    include_once("dbConnect.php");
    setConnectionValue("");
    writeToLog("file: " . basename(__FILE__) . ", user: " . $_POST["modifiedUser"]);
    printAllPost();
    ini_set("memory_limit","-1");
    
    
    
    if(isset($_POST["branchID"]) && isset($_POST["memberID"]))
    {
        $branchID = $_POST["branchID"];
        $memberID = $_POST["memberID"];
    }
    
    
    
    // Check connection
    if (mysqli_connect_errno())
    {
        echo "Failed to connect to MySQL: " . mysqli_connect_error();
    }
    
    
    
    $currentDateTime = date('Y-m-d H:i:s');
    $sql = "select promotion.* from promotion left join promotionBranch on promotion.promotionID = promotionBranch.promotionID where promotionBranch.branchID = '$branchID' and date_format(now(),'%Y-%m-%d') between date_format(usingStartDate,'%Y-%m-%d') and date_format(usingEndDate,'%Y-%m-%d') and (promotion.NoOfLimitUsePerUser = 0 or promotion.NoOfLimitUsePerUser > (select count(*) from userPromotionUsed where promotionID = promotion.promotionID and userAccountID = '$memberID')) order by promotion.type, promotion.orderNo;";
    $sql .= "SELECT RewardRedemption.*,promoCode.Code VoucherCode FROM `rewardpoint` left join promoCode on rewardPoint.promoCodeID = promoCode.promoCodeID left join RewardRedemption on promocode.rewardRedemptionID = RewardRedemption.rewardRedemptionID WHERE MemberID = '$memberID' and rewardpoint.status = -1 and ((TIME_TO_SEC(timediff('$currentDateTime', rewardpoint.ModifiedDate)) < rewardredemption.WithInPeriod) or (rewardredemption.WithInPeriod = 0 and '$currentDateTime'<rewardRedemption.usingEndDate)) and promoCode.status = 1 and rewardRedemption.rewardRedemptionID in (select rewardRedemptionID from rewardRedemptionBranch where branchID = '$branchID');";
    
    
    
    /* execute multi query */
    $jsonEncode = executeMultiQueryArray($sql);
    $response = array('success' => true, 'data' => $jsonEncode, 'error' => null, 'status' => 1);
    echo json_encode($response);


    
    // Close connections
    mysqli_close($con);
    
?>
