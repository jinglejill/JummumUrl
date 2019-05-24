<?php
    include_once("dbConnect.php");
    setConnectionValue("");
    writeToLog("file: " . basename(__FILE__) . ", user: " . $_POST["modifiedUser"]);
    printAllPost();
    
    
    if(isset($_POST["searchText"]) && isset($_POST["page"]) && isset($_POST["perPage"]) && isset($_POST["memberID"]))
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
    $sql = "SELECT ifnull(floor(sum(Status * Point)),0) Point FROM `rewardpoint` WHERE MemberID = '$memberID';";
    $sqlRewardRedemption = "select * from (select @rownum := @rownum + 1 AS Num, c.* from (select sum(a.Frequency) Frequency,sum(b.Sales) Sales, rewardRedemption.RewardRedemptionID, rewardRedemption.MainBranchID,rewardRedemption.Header,rewardRedemption.SubTitle,rewardRedemption.TermsConditions,rewardRedemption.ImageUrl,rewardRedemption.OrderNo,rewardRedemption.Point,rewardRedemption.UsingEndDate,rewardRedemption.WithInPeriod,rewardRedemption.DiscountGroupMenuID,1 ShowOrderNow from rewardRedemption left join RewardRedemptionBranch ON RewardRedemption.RewardRedemptionID = RewardRedemptionBranch.RewardRedemptionID left join (select branchID,count(*) as Frequency from receipt where memberID = '$memberID' GROUP BY branchID) a on RewardRedemptionBranch.BranchID = a.branchID left join (select branchID,SUM(NetTotal) Sales from receipt where memberID = '$memberID' GROUP BY branchID) b on RewardRedemptionBranch.BranchID = b.branchID where RewardRedemption.status = 1 and now() between RewardRedemption.startDate and RewardRedemption.endDate and RewardRedemptionBranch.branchID in (select distinct branchID from receipt where memberID = '$memberID') and RewardRedemption.type in (0,2) and (Header rlike '$strPattern' or SubTitle rlike '$strPattern' or TermsConditions rlike '$strPattern' or Point rlike '$strPattern') GROUP BY rewardRedemption.RewardRedemptionID, rewardRedemption.MainBranchID,rewardRedemption.Header,rewardRedemption.SubTitle,rewardRedemption.TermsConditions,rewardRedemption.ImageUrl,rewardRedemption.OrderNo,rewardRedemption.Point,rewardRedemption.UsingEndDate,rewardRedemption.WithInPeriod order by sum(a.Frequency)desc,sum(b.Sales)desc,rewardRedemption.OrderNo) c,(SELECT @rownum := 0) r)d where Num > $perPage*($page-1) limit $perPage;";
    $sql .= $sqlRewardRedemption;
    
    
    /* execute multi query */
    $jsonEncode = executeMultiQueryArray($sql);
    
    
    //branch imageUrl
    $rewardRedemptionList = $jsonEncode[1];
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
