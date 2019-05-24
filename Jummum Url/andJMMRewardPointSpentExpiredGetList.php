<?php
    include_once("dbConnect.php");
    setConnectionValue("");
    writeToLog("file: " . basename(__FILE__) . ", user: " . $_POST["modifiedUser"]);
    printAllPost();
    
    
    
    if(isset($_POST["memberID"]) && isset($_POST["page"]) && isset($_POST["perPage"]))
    {
        $memberID = $_POST["memberID"];
        $page = $_POST["page"];
        $perPage = $_POST["perPage"];
    }
    
    
    
    // Check connection
    if (mysqli_connect_errno())
    {
        echo "Failed to connect to MySQL: " . mysqli_connect_error();
    }
    
    
    $oneDayInSec = 86400;
    $currentDateTime = date('Y-m-d H:i:s');
    $sql = "select * from (select @rownum := @rownum + 1 AS Num, b.* from (select a.* from (SELECT rewardpoint.MemberID, rewardpoint.Point, RewardRedemption.RewardRedemptionID, rewardredemption.Header, rewardredemption.SubTitle, rewardredemption.TermsConditions, case when RewardRedemption.WithinPeriod = 0 then RewardRedemption.UsingEndDate when rewardredemption.WithinPeriod/'$oneDayInSec' >= 1 then date_add(concat(date_format(rewardPoint.ModifiedDate,'%Y-%m-%d'), ' 23:59:59'),INTERVAL RewardRedemption.WithInPeriod/'$oneDayInSec' day) when DATE_ADD(RewardPoint.ModifiedDate, INTERVAL RewardRedemption.WithinPeriod second) > RewardRedemption.UsingEndDate then RewardRedemption.UsingEndDate else DATE_ADD(RewardPoint.ModifiedDate, INTERVAL RewardRedemption.WithinPeriod second) end as ExpiredDate, RewardPoint.ModifiedDate RedeemDate, rewardredemption.MainBranchID, rewardredemption.DiscountGroupMenuID, RewardRedemption.ImageUrl, promoCode.Code, 0 ShowOrderNow, (SELECT ifnull(floor(sum(Status * Point)),0) Point FROM `rewardpoint` WHERE MemberID = '$memberID') RemainingPoint FROM `rewardpoint` left join promoCode on rewardPoint.promoCodeID = promoCode.promoCodeID left join RewardRedemption on promocode.rewardRedemptionID = RewardRedemption.rewardRedemptionID WHERE MemberID = '$memberID' and rewardpoint.status = -1 and !((rewardredemption.WithInPeriod < '$oneDayInSec' and TIME_TO_SEC(timediff('$currentDateTime', rewardpoint.ModifiedDate)) < rewardredemption.WithInPeriod and '$currentDateTime' < rewardredemption.UsingEndDate) or (rewardredemption.WithInPeriod >= '$oneDayInSec' and '$currentDateTime'<date_add(concat(date_format(rewardPoint.ModifiedDate,'%Y-%m-%d'), ' 23:59:59'),INTERVAL RewardRedemption.WithInPeriod/'$oneDayInSec' day)) or (rewardredemption.WithInPeriod = 0 and '$currentDateTime'<rewardRedemption.usingEndDate)) and promoCode.status = 1) a order by ExpiredDate desc, RewardRedemptionID desc) b, (SELECT @rownum := 0) r) c where Num > $perPage*($page-1) limit $perPage;";
    
    
    
    /* execute multi query */
    $jsonEncode = executeMultiQueryArray($sql);
    
    
    //branch imageUrl
    $rewardRedemptionList = $jsonEncode[0];
    for($i=0; $i<sizeof($rewardRedemptionList); $i++)
    {
        $branchID = $rewardRedemptionList[$i]->MainBranchID;
        $sql = "select * from $jummumOM.branch where branchID = '$branchID'";
        $selectedRow = getSelectedRow($sql);
        $imageUrl = $selectedRow[0]["ImageUrl"];
        $rewardRedemptionList[$i]->BranchImageUrl = $imageUrl;
    }
    
    
    $response = array('success' => true, 'data' => $jsonEncode, 'error' => null);
    echo json_encode($response);
    
    
    
    // Close connections
    mysqli_close($con);
?>
