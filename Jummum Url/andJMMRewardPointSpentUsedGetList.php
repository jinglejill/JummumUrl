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
        echo "Failed to connect to MySQL:  " . mysqli_connect_error();
    }
    
    
    $currentDateTime = date('Y-m-d H:i:s');
    $sql = "select * from (select @rownum := @rownum + 1 AS Num, a.* from (SELECT rewardpoint.MemberID, rewardpoint.Point, rewardredemption.Header, rewardredemption.SubTitle, rewardredemption.TermsConditions, promoCode.ModifiedDate UsedDate, RewardPoint.ModifiedDate RedeemDate, rewardredemption.MainBranchID, rewardredemption.DiscountGroupMenuID, RewardRedemption.ImageUrl, promoCode.Code, 0 ShowOrderNow, (SELECT ifnull(floor(sum(Status * Point)),0) Point FROM `rewardpoint` WHERE MemberID = '$memberID') RemainingPoint FROM `rewardpoint` left join promoCode on rewardPoint.promoCodeID = promoCode.promoCodeID left join RewardRedemption on promocode.rewardRedemptionID = RewardRedemption.rewardRedemptionID WHERE MemberID = '$memberID' and rewardpoint.status = -1 and promoCode.status = 2 order by promoCode.modifiedDate desc, rewardRedemption.RewardRedemptionID desc) a, (SELECT @rownum := 0) r) b where Num > $perPage*($page-1) limit $perPage;";
    
    
//    echo "<br>sql:" . $sql;
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
