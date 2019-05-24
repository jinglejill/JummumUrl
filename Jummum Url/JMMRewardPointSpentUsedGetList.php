<?php
    include_once("dbConnect.php");
    setConnectionValue("");
    writeToLog("file: " . basename(__FILE__) . ", user: " . $_POST["modifiedUser"]);
    printAllPost();
    
    
    
    if(isset($_POST["memberID"]) && isset($_POST["searchText"]) && isset($_POST["page"]) && isset($_POST["perPage"]))
    {
        $searchText = $_POST["searchText"];
        $page = $_POST["page"];
        $perPage = $_POST["perPage"];
        $memberID = $_POST["memberID"];
    }
    
    
    // Check connection
    if (mysqli_connect_errno())
    {
        echo "Failed to connect to MySQL: " . mysqli_connect_error();
    }
    
    
    $searchText = trim($searchText);
    $strPattern = getRegExPattern($searchText);
    
    
    $currentDateTime = date('Y-m-d H:i:s');
    $sql = "select * from (select (@row_number:=@row_number + 1) AS Num, a.* from (SELECT rewardpoint.*,promoCode.ModifiedDate RedeemDate,RewardRedemption.RewardRedemptionID,RewardRedemption.SubTitle FROM `rewardpoint` left join promoCode on rewardPoint.promoCodeID = promoCode.promoCodeID left join RewardRedemption on promocode.rewardRedemptionID = RewardRedemption.rewardRedemptionID WHERE MemberID = '$memberID' and rewardpoint.status = -1 and promoCode.status = 2) a, (SELECT @row_number:=0) AS t where SubTitle rlike '$strPattern' order by RedeemDate desc, RewardRedemptionID desc) b where Num > $perPage*($page-1) limit $perPage;";
    $sql .= "select * from (select (@row_number:=@row_number + 1) AS Num, a.* from (SELECT promoCode.*,promoCode.ModifiedDate RedeemDate,RewardRedemption.SubTitle FROM `rewardpoint` left join promoCode on rewardPoint.promoCodeID = promoCode.promoCodeID left join RewardRedemption on promocode.rewardRedemptionID = RewardRedemption.rewardRedemptionID WHERE MemberID = '$memberID' and rewardpoint.status = -1 and promoCode.status = 2) a, (SELECT @row_number:=0) AS t where SubTitle rlike '$strPattern' order by RedeemDate desc, RewardRedemptionID desc) b where Num > $perPage*($page-1) limit $perPage;";
    $sql .= "select * from (select (@row_number:=@row_number + 1) AS Num, a.* from (SELECT RewardRedemption.*,promoCode.ModifiedDate RedeemDate FROM `rewardpoint` left join promoCode on rewardPoint.promoCodeID = promoCode.promoCodeID left join RewardRedemption on promocode.rewardRedemptionID = RewardRedemption.rewardRedemptionID WHERE MemberID = '$memberID' and rewardpoint.status = -1 and promoCode.status = 2 order by promoCode.modifiedDate desc) a, (SELECT @row_number:=0) AS t where SubTitle rlike '$strPattern' order by RedeemDate desc, RewardRedemptionID desc) b where Num > $perPage*($page-1) limit $perPage;";
    
    
    
    /* execute multi query */
    $jsonEncode = executeMultiQueryArray($sql);
    $response = array('success' => true, 'data' => $jsonEncode, 'error' => null, 'status' => 1);
    echo json_encode($response);
    
    
    
    // Close connections
    mysqli_close($con);
?>
