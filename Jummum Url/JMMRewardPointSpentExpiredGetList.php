<?php
    include_once("dbConnect.php");
    setConnectionValue("");
    writeToLog("file: " . basename(__FILE__) . ", user: " . $_POST["modifiedUser"]);
    printAllPost();
    
    
    
//    if(isset($_POST["memberID"]))
//    {
//        $memberID = $_POST["memberID"];
//    }
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
    
    
    $oneDayInSec = 86400;
    $currentDateTime = date('Y-m-d H:i:s');
    $sql = "select * from (select (@row_number:=@row_number + 1) AS Num, a.* from (SELECT rewardpoint.*, rewardRedemption.SubTitle, rewardRedemption.RewardRedemptionID, case when rewardredemption.WithinPeriod = 0 then rewardredemption.UsingEndDate when rewardredemption.WithinPeriod/'$oneDayInSec' >= 1 then date_add(concat(date_format(rewardPoint.ModifiedDate,'%Y-%m-%d'), ' 23:59:59'),INTERVAL RewardRedemption.WithInPeriod/'$oneDayInSec' day) when date_add(RewardPoint.ModifiedDate,INTERVAL RewardRedemption.WithInPeriod second) > RewardRedemption.UsingEndDate then RewardRedemption.UsingEndDate else date_add(rewardPoint.modifiedDate,interval rewardRedemption.WithInPeriod second) end as ExpiredDate FROM `rewardpoint` left join promoCode on rewardPoint.promoCodeID = promoCode.promoCodeID left join RewardRedemption on promocode.rewardRedemptionID = RewardRedemption.rewardRedemptionID WHERE MemberID = '$memberID' and rewardpoint.status = -1 and !((rewardredemption.WithInPeriod < '$oneDayInSec' and TIME_TO_SEC(timediff('$currentDateTime', rewardpoint.ModifiedDate)) < rewardredemption.WithInPeriod and '$currentDateTime' < rewardredemption.UsingEndDate) or (rewardredemption.WithInPeriod >= '$oneDayInSec' and '$currentDateTime'<date_add(concat(date_format(rewardPoint.ModifiedDate,'%Y-%m-%d'), ' 23:59:59'),INTERVAL RewardRedemption.WithInPeriod/'$oneDayInSec' day)) or (rewardredemption.WithInPeriod = 0 and '$currentDateTime'<rewardRedemption.usingEndDate)) and promoCode.status = 1) a, (SELECT @row_number:=0) AS t where SubTitle rlike '$strPattern' order by ExpiredDate desc, RewardRedemptionID desc) b where Num > $perPage*($page-1) limit $perPage;";
    $sql .= "select * from (select (@row_number:=@row_number + 1) AS Num, a.* from (SELECT promoCode.*, rewardRedemption.SubTitle, rewardRedemption.RewardRedemptionID, case when rewardredemption.WithinPeriod = 0 then rewardredemption.UsingEndDate when rewardredemption.WithinPeriod/'$oneDayInSec' >= 1 then date_add(concat(date_format(rewardPoint.ModifiedDate,'%Y-%m-%d'), ' 23:59:59'),INTERVAL RewardRedemption.WithInPeriod/'$oneDayInSec' day) when date_add(RewardPoint.ModifiedDate,INTERVAL RewardRedemption.WithInPeriod second) > RewardRedemption.UsingEndDate then RewardRedemption.UsingEndDate else date_add(rewardPoint.modifiedDate,interval rewardRedemption.WithInPeriod second) end as ExpiredDate FROM `rewardpoint` left join promoCode on rewardPoint.promoCodeID = promoCode.promoCodeID left join RewardRedemption on promocode.rewardRedemptionID = RewardRedemption.rewardRedemptionID WHERE MemberID = '$memberID' and rewardpoint.status = -1 and !((rewardredemption.WithInPeriod < '$oneDayInSec' and TIME_TO_SEC(timediff('$currentDateTime', rewardpoint.ModifiedDate)) < rewardredemption.WithInPeriod and '$currentDateTime' < rewardredemption.UsingEndDate) or (rewardredemption.WithInPeriod >= '$oneDayInSec' and '$currentDateTime'<date_add(concat(date_format(rewardPoint.ModifiedDate,'%Y-%m-%d'), ' 23:59:59'),INTERVAL RewardRedemption.WithInPeriod/'$oneDayInSec' day)) or (rewardredemption.WithInPeriod = 0 and '$currentDateTime'<rewardRedemption.usingEndDate)) and promoCode.status = 1) a, (SELECT @row_number:=0) AS t where SubTitle rlike '$strPattern' order by ExpiredDate desc, RewardRedemptionID desc) b where Num > $perPage*($page-1) limit $perPage;";
    $sql .= "select * from (select (@row_number:=@row_number + 1) AS Num, a.* from (SELECT rewardredemption.RewardRedemptionID,rewardredemption.Header, rewardredemption.SubTitle, rewardredemption.TermsConditions, case when rewardredemption.WithinPeriod = 0 then 0 when rewardredemption.WithinPeriod/'$oneDayInSec' >= 1 then 0 when date_add(concat(date_format(rewardPoint.ModifiedDate,'%Y-%m-%d'), ' 23:59:59'),INTERVAL RewardRedemption.WithInPeriod second) > RewardRedemption.UsingEndDate then 0 else  rewardredemption.WithinPeriod end as WithInPeriod, case when rewardredemption.WithinPeriod = 0 then rewardredemption.UsingEndDate when rewardredemption.WithinPeriod/'$oneDayInSec' >= 1 then date_add(concat(date_format(rewardPoint.ModifiedDate,'%Y-%m-%d'), ' 23:59:59'),INTERVAL RewardRedemption.WithInPeriod/'$oneDayInSec' day) when date_add(RewardPoint.ModifiedDate,INTERVAL RewardRedemption.WithInPeriod second) > RewardRedemption.UsingEndDate then RewardRedemption.UsingEndDate else rewardredemption.UsingEndDate end as UsingEndDate, case when rewardredemption.WithinPeriod = 0 then rewardredemption.UsingEndDate when rewardredemption.WithinPeriod/'$oneDayInSec' >= 1 then date_add(concat(date_format(rewardPoint.ModifiedDate,'%Y-%m-%d'), ' 23:59:59'),INTERVAL RewardRedemption.WithInPeriod/'$oneDayInSec' day) when date_add(RewardPoint.ModifiedDate,INTERVAL RewardRedemption.WithInPeriod second) > RewardRedemption.UsingEndDate then RewardRedemption.UsingEndDate else date_add(rewardPoint.modifiedDate,interval rewardRedemption.WithInPeriod second) end as ExpiredDate, rewardredemption.MainBranchID, rewardredemption.DiscountGroupMenuID, RewardRedemption.ImageUrl FROM `rewardpoint` left join promoCode on rewardPoint.promoCodeID = promoCode.promoCodeID left join RewardRedemption on promocode.rewardRedemptionID = RewardRedemption.rewardRedemptionID WHERE MemberID = '$memberID' and rewardpoint.status = -1 and !((rewardredemption.WithInPeriod < '$oneDayInSec' and TIME_TO_SEC(timediff('$currentDateTime', rewardpoint.ModifiedDate)) < rewardredemption.WithInPeriod and '$currentDateTime' < rewardredemption.UsingEndDate) or (rewardredemption.WithInPeriod >= '$oneDayInSec' and '$currentDateTime'<date_add(concat(date_format(rewardPoint.ModifiedDate,'%Y-%m-%d'), ' 23:59:59'),INTERVAL RewardRedemption.WithInPeriod/'$oneDayInSec' day)) or (rewardredemption.WithInPeriod = 0 and '$currentDateTime'<rewardRedemption.usingEndDate)) and promoCode.status = 1) a, (SELECT @row_number:=0) AS t where SubTitle rlike '$strPattern' order by ExpiredDate desc, RewardRedemptionID desc) b where Num > $perPage*($page-1) limit $perPage;) a, (SELECT @row_number:=0) AS t where SubTitle rlike '$strPattern') b where Num > $perPage*($page-1) limit $perPage;";
    
    
    //ถ้า within == 0 -> usingEndDate
    //ถ้า within/oneDayInSec >= 1 -> date_add(concat(date_format(rewardPoint.ModifiedDate,'%Y-%m-%d'), ' 23:59:59'),INTERVAL RewardRedemption.WithInPeriod/'$oneDayInSec' day)
    //ถ้า within > usingEndDate -> usingEndDate
    //else rewardPoint.modifiedDate + within
    /* execute multi query */
    $jsonEncode = executeMultiQueryArray($sql);
    $response = array('success' => true, 'data' => $jsonEncode, 'error' => null, 'status' => 1);
    echo json_encode($response);
    
    
    
    // Close connections
    mysqli_close($con);
?>
