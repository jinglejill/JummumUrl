<?php
    include_once("dbConnect.php");
    setConnectionValue("");
    writeToLog("file: " . basename(__FILE__) . ", user: " . $_POST["modifiedUser"]);
    printAllPost();
    ini_set("memory_limit","-1");
    
    
    
    
    if(isset($_POST["memberID"]) && isset($_POST["branchID"]) && isset($_POST["rewardRedemptionListCount"]))
    {
        $memberID = $_POST["memberID"];
        $branchID = $_POST["branchID"];
        $rewardRedemptionListCount = $_POST["srewardRedemptionListCount"];
    }
    
    
    
    // Check connection
    if (mysqli_connect_errno())
    {
        echo "Failed to connect to MySQL: " . mysqli_connect_error();
    }
    
    
    $startRow = 1;
    $endRow = $rewardRedemptionListCount<=10?10:$rewardRedemptionListCount;
    
    $sql = "SELECT ifnull(sum(Status * Point),0) Point FROM `rewardpoint` WHERE MemberID = '$memberID';";
    $sql .= "select * from (select @rownum := @rownum + 1 AS rank, c.* from (select sum(a.Frequency) Frequency,sum(b.Sales) Sales, rewardRedemption.RewardRedemptionID, rewardRedemption.MainBranchID,rewardRedemption.Header,rewardRedemption.SubTitle,rewardRedemption.TermsConditions,rewardRedemption.ImageUrl,rewardRedemption.OrderNo,rewardRedemption.Point,rewardRedemption.UsingEndDate,rewardRedemption.WithInPeriod from rewardRedemption left join RewardRedemptionBranch ON RewardRedemption.RewardRedemptionID = RewardRedemptionBranch.RewardRedemptionID left join (select branchID,count(*) as Frequency from receipt where memberID = '$memberID' GROUP BY branchID) a on RewardRedemptionBranch.BranchID = a.branchID left join (select branchID,SUM(CashAmount+CreditCardAmount+TransferAmount) Sales from receipt where memberID = '$memberID' GROUP BY branchID) b on RewardRedemptionBranch.BranchID = b.branchID where RewardRedemption.status = 1 and date_format(now(),'%Y-%m-%d') between date_format(RewardRedemption.startDate,'%Y-%m-%d') and date_format(RewardRedemption.endDate,'%Y-%m-%d') and RewardRedemptionBranch.branchID in (select distinct branchID from receipt where memberID = '$memberID') GROUP BY rewardRedemption.RewardRedemptionID, rewardRedemption.MainBranchID,rewardRedemption.Header,rewardRedemption.SubTitle,rewardRedemption.TermsConditions,rewardRedemption.ImageUrl,rewardRedemption.OrderNo,rewardRedemption.Point,rewardRedemption.UsingEndDate,rewardRedemption.WithInPeriod order by sum(a.Frequency)desc,sum(b.Sales)desc,rewardRedemption.OrderNo) c,(SELECT @rownum := 0) r)d where rank between '$startRow' and '$endRow' and RewardRedemptionID in (select RewardRedemptionID from rewardRedemptionBranch where branchID = '$branchID');";
    /* execute multi query */
    $jsonEncode = executeMultiQuery($sql);
    echo $jsonEncode;


    
    // Close connections
    mysqli_close($con);
    
?>
