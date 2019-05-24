<?php
    include_once("dbConnect.php");
    setConnectionValue("");
    writeToLog("file: " . basename(__FILE__) . ", user: " . $_POST["modifiedUser"]);
    printAllPost();
    ini_set("memory_limit","-1");
    
    
    
    
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
    
    $currentDateTime = date("Y-m-d H:i:s");
    $searchText = trim($searchText);
//    $strPattern = getRegExPattern($searchText);
//    $sql = "select * from (select @rownum := @rownum + 1 AS Num, c.* from (select ifnull(sum(a.Frequency),0) Frequency,ifnull(sum(b.Sales),0) Sales, promotion.PromotionID, promotion.MainBranchID,promotion.Type,promotion.Header,promotion.SubTitle,promotion.TermsConditions,promotion.ImageUrl,promotion.OrderNo,promotion.DiscountGroupMenuID,(Promotion.MainBranchID != 0) as ShowOrderNow,promotion.VoucherCode from promotion left join promotionbranch ON promotion.PromotionID = promotionbranch.PromotionID left join (select branchID,count(*) as Frequency from receipt where memberID = '$memberID' GROUP BY branchID) a on promotionbranch.BranchID = a.branchID left join (select branchID,SUM(NetTotal) Sales from receipt where memberID = '$memberID' GROUP BY branchID) b on promotionbranch.BranchID = b.branchID where promotion.status = 1 and '$currentDateTime' between promotion.startDate and promotion.endDate and ((promotionbranch.BranchID in (select distinct branchID from receipt where memberID = '$memberID' and promotion.type = 1)) or promotion.type = 0) and (promotion.NoOfLimitUsePerUser = 0 or promotion.NoOfLimitUsePerUser > (select count(*) from userPromotionUsed where promotionID = promotion.promotionID and userAccountID = '$memberID')) and (Header rlike '$strPattern' or SubTitle rlike '$strPattern' or TermsConditions rlike '$strPattern') GROUP BY promotion.PromotionID, promotion.MainBranchID,promotion.Type,promotion.Header,promotion.SubTitle,promotion.TermsConditions,promotion.ImageUrl,promotion.OrderNo,promotion.DiscountGroupMenuID,promotion.VoucherCode order by promotion.Type,sum(a.Frequency)desc,sum(b.Sales)desc,promotion.OrderNo) c,(SELECT @rownum := 0) r)d where Num > $perPage*($page-1) limit $perPage;";

    
    //get promotionList
    $sql = "select\
0 as ShopType,PromotionID,MainBranchID,0 as BranchID,0 as DiscountProgramID,Type,Header,SubTitle,TermsConditions,ImageUrl,OrderNo,DiscountGroupMenuID,(MainBranchID != 0) as ShowOrderNow,VoucherCode,ModifiedDate\
from promotion\
where status = 1 and type = 0 and '$currentDateTime' between startDate and endDate and\
PromotionID in (select PromotionID from promotionbranch where branchID in (select branchID from receipt where memberID = '$memberID'));";

    $sqlPromotion = str_replace('\\','',$sql);
    $hotDealList = executeQueryArray($sqlPromotion);
    
    
    //get dbNameList
    $sql = "select distinct branch.BranchID, DbName from receipt left join $jummumOM.Branch on receipt.branchID = branch.branchID where memberID = '$memberID'";
    $selectedRow = getSelectedRow($sql);
    $branchList = array();
    for($i=0; $i<sizeof($selectedRow); $i++)
    {
        $branchID = $selectedRow[$i]["BranchID"];
        $dbName = $selectedRow[$i]["DbName"];
        
        $sql = "select count(*) as Frequency, SUM(NetTotal) Sales from receipt where memberID = '$memberID' and branchID = '$branchID'";
        $selectedRow2 = getSelectedRow($sql);
        $frequency = $selectedRow2[0]["Frequency"];
        $sales = $selectedRow2[0]["Sales"];
        
        
        $branchList[] = array("BranchID"=>$branchID,"DbName"=>$dbName,"Frequency"=>$frequency,"Sales"=>$sales);
    }
    
    //sort frequency and sales
    usort($branchList, function($a, $b)
    {
        $retval = $b["Frequency"] <=> $a["Frequency"];
        if ($retval == 0) {
            $retval = $b["Sales"] <=> $a["Sales"];
        }
        return $retval;
    });
    
    
    
    //get discountProgramList
    for($i=0; $i<sizeof($branchList); $i++)
    {
        $branchList[$i]["SortBranch"] = $i;
        $branch = $branchList[$i];
        $dbName = $branch["DbName"];
        $branchID = $branch["BranchID"];
        $sql = "select 1 as ShopType,0 as PromotionID,$branchID as MainBranchID,$branchID as BranchID,DiscountProgramID,Type,Header,SubTitle,TermsConditions,ImageUrl,0 OrderNo,DiscountGroupMenuID,1 as ShowOrderNow,'' as VoucherCode,ModifiedDate\
         from $dbName.discountProgram where '$currentDateTime' between startDate and endDate";
         $sql = str_replace('\\','',$sql);
        
        
         $discountProgramList = executeQueryArray($sql);
         for($j=0; $j<sizeof($discountProgramList); $j++)
         {
             $discountProgram = $discountProgramList[$j];
             $discountProgram->ImageUrl = "../../".$dbName."/Image/DiscountProgram/".$discountProgram->ImageUrl;
             array_push($hotDealList,$discountProgram);
         }
    }
    
    
    //add key frequency and sales
    for($i=0; $i<sizeof($hotDealList); $i++)
    {
        $hotDeal = $hotDealList[$i];
        $hotDeal->SortBranch = 0;
        for($j=0; $j<sizeof($branchList); $j++)
        {
            $branch = $branchList[$j];
            if($hotDeal->BranchID == $branch["BranchID"])
            {
                $hotDeal->SortBranch = $branch["SortBranch"];
                break;
            }
        }
    }
    
    
    //sort
    usort($hotDealList, function($a, $b)
    {
        $retval = $a->ShopType <=> $b->ShopType;
        if ($retval == 0) {
            $retval = $a->Type <=> $b->Type;
            if ($retval == 0) {
                $retval = $a->SortBranch <=> $b->SortBranch;
                if ($retval == 0)
                {
                    $retval = $b->ModifiedDate <=> $a->ModifiedDate;
                }
            }
        }
        return $retval;
    });
    
    
    //search
    $searchHotDealList = array();
    for($i=0; $i<sizeof($hotDealList); $i++)
    {
        $hotDeal = $hotDealList[$i];
        if($searchText == "" || stripos($hotDeal->Header,$searchText) || stripos($hotDeal->SubTitle,$searchText) || stripos($hotDeal->TermsConditions,$searchText))
        {
            $searchHotDealList[] = $hotDeal;
        }
    }
    
    //put number to keep structure as before
    for($i=0; $i<sizeof($searchHotDealList); $i++)
    {
        $hotDeal = $searchHotDealList[$i];
        $hotDeal->Num = $i+1;
    }
    

    //page
    $pageHotDealList = array();
    $startIndex = $perPage*($page-1);
    for($i=$startIndex; $i<sizeof($searchHotDealList) && $i<$perPage*$page; $i++)
    {
        $hotDeal = $searchHotDealList[$i];
        $pageHotDealList[] = $hotDeal;
    }
    
    
    //return dataList
    $dataList = array();
    $dataList[] = $pageHotDealList;
    
    
    
    
    /* execute multi query */
//    $jsonEncode = executeMultiQueryArray($sql);
//    $success = sizeof($jsonEncode[0]) != 0;
    $success = sizeof($dataList[0]) != 0;
    $response = array('success' => $success, 'data' => $dataList, 'error' => "ไม่มีข้อมูล");
    echo json_encode($response);


    
    // Close connections
    mysqli_close($con);
    
?>
