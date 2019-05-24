<?php
    include_once("dbConnect.php");
    setConnectionValue("");
    writeToLog("file: " . basename(__FILE__) . ", user: " . $_POST["modifiedUser"]);
    printAllPost();
    
    
    
    if(isset($_POST["days"]))
    {
        $days = $_POST["days"];
    }
    
    
    
    // Check connection
    if (mysqli_connect_errno())
    {
        echo "Failed to connect to MySQL: " . mysqli_connect_error();
    }
    
    
    $oneDayInSec = 86400;
    $sql = "select date_add(date_format(now(),'%Y-%m-%d'),interval $days day) as ExpiredDate;";
    $selectedRow = getSelectedRow($sql);
    $expiredDate = $selectedRow[0]["ExpiredDate"];
    $currentDateTime = date('Y-m-d H:i:s');
    $sql = "select * from (SELECT rewardPoint.MemberID, rewardredemption.RewardRedemptionID, rewardredemption.Header, rewardredemption.SubTitle, rewardredemption.TermsConditions, case when rewardredemption.WithinPeriod/'$oneDayInSec' >= 1 then 0 else rewardredemption.WithinPeriod end as WithinPeriod, case when rewardredemption.WithinPeriod/'$oneDayInSec' >= 1 then date_add(concat(date_format(rewardPoint.ModifiedDate,'%Y-%m-%d'), ' 23:59:59'),INTERVAL RewardRedemption.WithInPeriod/'$oneDayInSec' day) else rewardredemption.UsingEndDate end as UsingEndDate, rewardredemption.MainBranchID, rewardredemption.DiscountGroupMenuID, RewardRedemption.ImageUrl FROM `rewardpoint` left join promoCode on rewardPoint.promoCodeID = promoCode.promoCodeID left join RewardRedemption on promocode.rewardRedemptionID = RewardRedemption.rewardRedemptionID WHERE rewardpoint.status = -1 and ((rewardredemption.WithInPeriod < '$oneDayInSec' and TIME_TO_SEC(timediff('$currentDateTime', rewardpoint.ModifiedDate)) < rewardredemption.WithInPeriod) or (rewardredemption.WithInPeriod >= '$oneDayInSec' and '$currentDateTime'<date_add(concat(date_format(rewardPoint.ModifiedDate,'%Y-%m-%d'), ' 23:59:59'),INTERVAL RewardRedemption.WithInPeriod/'$oneDayInSec' day)) or (rewardredemption.WithInPeriod = 0 and '$currentDateTime'<rewardRedemption.usingEndDate)) and promoCode.status = 1 order by rewardPoint.modifiedDate desc, rewardPointID desc)a where date_format(UsingEndDate,'%Y-%m-%d') = '$expiredDate';";
    $selectedRow = getSelectedRow($sql);
    for($i=0; $i<sizeof($selectedRow); $i++)
    {
        $rewardRedemptionID = $selectedRow[0]["RewardRedemptionID"];
        $memberID = $selectedRow[0]["MemberID"];
        $subTitle = $selectedRow[0]["SubTitle"];
        
        $sql = "select login.DeviceToken,login.ModifiedDate,login.Username from userAccount left join login on useraccount.username = login.username where login.status = 1 and userAccountID = '$memberID' order by login.modifiedDate desc limit 1";
        $selectedRow = getSelectedRow($sql);
        $customerDeviceToken = $selectedRow[0]["DeviceToken"];
        $logInModifiedDate = $selectedRow[0]["ModifiedDate"];
        $logInUsername = $selectedRow[0]["Username"];
        $sql = "select * from login where DeviceToken = '$customerDeviceToken' and Username != '$logInUsername' and status = 1 and modifiedDate > '$logInModifiedDate';";
        $selectedRow = getSelectedRow($sql);
        if(sizeof($selectedRow) == 0)
        {
            $arrCustomerDeviceToken = array();
            array_push($arrCustomerDeviceToken,$customerDeviceToken);
            $msg = "คูปองของคุณกำลังจะหมดอายุในอีก " . $days . " วัน";
            $category = "rewardReminder";
            $contentAvailable = 1;
            $data = array("rewardRedemptionID" => $rewardRedemptionID);
            sendPushNotificationJummum($arrCustomerDeviceToken,$title,$msg,$category,$contentAvailable,$data);
        }
    }
    
    
    
    /* execute multi query */
    $jsonEncode = executeMultiQueryArray($sql);
    $response = array('success' => true, 'data' => $jsonEncode, 'error' => null, 'status' => 1);
    echo json_encode($response);
    
    
    
    // Close connections
    mysqli_close($con);
?>
