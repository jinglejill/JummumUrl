<?php
    include_once("dbConnect.php");
    setConnectionValue("");
    writeToLog("file: " . basename(__FILE__) . ", user: " . $_POST["modifiedUser"]);
    printAllPost();
    
    
    
    if(isset($_POST["memberID"]))
    {
        $memberID = $_POST["memberID"];
    }
    if(isset($_POST["rewardPointID"]) && isset($_POST["modifiedDate"]))
    {
        $rewardPointID = $_POST["rewardPointID"];
        $modifiedDate = $_POST["modifiedDate"];
    }
    
    
    
    // Check connection
    if (mysqli_connect_errno())
    {
        echo "Failed to connect to MySQL: " . mysqli_connect_error();
    }
    
    
    $currentDateTime = date('Y-m-d H:i:s');
    $sql = "SELECT rewardpoint.* FROM `rewardpoint` left join promoCode on rewardPoint.promoCodeID = promoCode.promoCodeID left join RewardRedemption on promocode.rewardRedemptionID = RewardRedemption.rewardRedemptionID WHERE MemberID = '$memberID' and rewardpoint.status = -1 and ((TIME_TO_SEC(timediff('$currentDateTime', rewardpoint.ModifiedDate)) < rewardredemption.WithInPeriod) or (rewardredemption.WithInPeriod = 0 and '$currentDateTime'<rewardRedemption.usingEndDate)) and promoCode.status = 1 and rewardPoint.modifiedDate <= '$modifiedDate' and rewardPoint.RewardPointID < '$rewardPointID' order by rewardPoint.modifiedDate desc, rewardPointID desc limit 10;";
    $sql .= "SELECT promoCode.* FROM `rewardpoint` left join promoCode on rewardPoint.promoCodeID = promoCode.promoCodeID left join RewardRedemption on promocode.rewardRedemptionID = RewardRedemption.rewardRedemptionID WHERE MemberID = '$memberID' and rewardpoint.status = -1 and ((TIME_TO_SEC(timediff('$currentDateTime', rewardpoint.ModifiedDate)) < rewardredemption.WithInPeriod) or (rewardredemption.WithInPeriod = 0 and '$currentDateTime'<rewardRedemption.usingEndDate)) and promoCode.status = 1 and rewardPoint.modifiedDate <= '$modifiedDate' and rewardPoint.RewardPointID < '$rewardPointID' order by rewardPoint.modifiedDate desc, rewardPointID desc limit 10;";
    $sql .= "SELECT RewardRedemption.* FROM `rewardpoint` left join promoCode on rewardPoint.promoCodeID = promoCode.promoCodeID left join RewardRedemption on promocode.rewardRedemptionID = RewardRedemption.rewardRedemptionID WHERE MemberID = '$memberID' and rewardpoint.status = -1 and ((TIME_TO_SEC(timediff('$currentDateTime', rewardpoint.ModifiedDate)) < rewardredemption.WithInPeriod) or (rewardredemption.WithInPeriod = 0 and '$currentDateTime'<rewardRedemption.usingEndDate)) and promoCode.status = 1 and rewardPoint.modifiedDate <= '$modifiedDate' and rewardPoint.RewardPointID < '$rewardPointID' order by rewardPoint.modifiedDate desc, rewardPointID desc limit 10;";
    
    

    
    /* execute multi query */
    $jsonEncode = executeMultiQuery($sql);
    echo $jsonEncode;
    
    
    
    // Close connections
    mysqli_close($con);
?>
