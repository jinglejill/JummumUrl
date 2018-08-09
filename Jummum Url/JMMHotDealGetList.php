<?php
    include_once("dbConnect.php");
    setConnectionValue("");
    writeToLog("file: " . basename(__FILE__) . ", user: " . $_POST["modifiedUser"]);
    printAllPost();
    ini_set("memory_limit","-1");
    
    
    
    
    if(isset($_POST["memberID"]) && isset($_POST["promotionListCount"]))
    {
        $memberID = $_POST["memberID"];
        $promotionListCount = $_POST["promotionListCount"];
    }
    
    
    
    // Check connection
    if (mysqli_connect_errno())
    {
        echo "Failed to connect to MySQL: " . mysqli_connect_error();
    }
    
    
    $startRow = $promotionListCount + 1;
    $endRow = $promotionListCount + 10;
    //select table -> branch, customerTable
    $sql = "select * from (select @rownum := @rownum + 1 AS rank, c.* from (select sum(a.Frequency) Frequency,sum(b.Sales) Sales, promotion.PromotionID, promotion.MainBranchID,promotion.Type,promotion.Header,promotion.SubTitle,promotion.TermsConditions,promotion.ImageUrl,promotion.OrderNo from promotion left join promotionbranch ON promotion.PromotionID = promotionbranch.PromotionID left join (select branchID,count(*) as Frequency from receipt where memberID = '$memberID' GROUP BY branchID) a on promotionbranch.BranchID = a.branchID left join (select branchID,SUM(CashAmount+CreditCardAmount+TransferAmount) Sales from receipt where memberID = '$memberID' GROUP BY branchID) b on promotionbranch.BranchID = b.branchID where promotion.status = 1 and date_format(now(),'%Y-%m-%d') between date_format(promotion.startDate,'%Y-%m-%d') and date_format(promotion.endDate,'%Y-%m-%d') and promotionbranch.BranchID in (select distinct branchID from receipt where memberID = '$memberID') GROUP BY promotion.PromotionID, promotion.MainBranchID,promotion.Type,promotion.Header,promotion.SubTitle,promotion.TermsConditions,promotion.ImageUrl,promotion.OrderNo order by promotion.Type,sum(a.Frequency)desc,sum(b.Sales)desc,promotion.OrderNo) c,(SELECT @rownum := 0) r)d where rank between '$startRow' and '$endRow';";
    
    /* execute multi query */
    $jsonEncode = executeMultiQuery($sql);
    echo $jsonEncode;


    
    // Close connections
    mysqli_close($con);
    
?>
