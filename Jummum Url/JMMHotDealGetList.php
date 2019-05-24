<?php
    include_once("dbConnect.php");
    setConnectionValue("");
    writeToLog("file: " . basename(__FILE__) . ", user: " . $_POST["modifiedUser"]);
    printAllPost();
    ini_set("memory_limit","-1");
    
    
    
    
//    if(isset($_POST["searchText"]) && isset($_POST["page"]) && isset($_POST["perPage"]) && isset($_POST["memberID"]))
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


    //get promotionList
    $sql = "select\
0 as ShopType,PromotionID,MainBranchID,0 as BranchID,0 as DiscountProgramID,Type,Header,SubTitle,TermsConditions,ImageUrl,OrderNo,DiscountGroupMenuID,VoucherCode,ModifiedDate\
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
        $sql = "select 1 as ShopType,0 as PromotionID,$branchID as MainBranchID,$branchID as BranchID,DiscountProgramID,Type,Header,SubTitle,TermsConditions,ImageUrl,0 OrderNo,DiscountGroupMenuID,'' as VoucherCode,ModifiedDate\
         from $dbName.discountProgram where '$currentDateTime' between startDate and endDate";
         $sql = str_replace('\\','',$sql);
        
        
         $discountProgramList = executeQueryArray($sql);
         for($j=0; $j<sizeof($discountProgramList); $j++)
         {
             $discountProgram = $discountProgramList[$j];
//             $discountProgram->ImageUrl = "../../".$dbName."Image/DiscountProgram/".$discountProgram->ImageUrl;
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
    
    
    
    //page
    $pageHotDealList = array();
    $startIndex = $perPage*($page-1);
    for($i=$startIndex; $i<sizeof($searchHotDealList) && $i<$perPage*$page; $i++)
    {
        $hotDeal = $searchHotDealList[$i];
        $pageHotDealList[] = $hotDeal;
    }
    
    
    
    //get branchList
    if(sizeof($branchList) > 0)
    {
        $branchIDListInText = $branchList[0]["BranchID"];
        for($i=1; $i<sizeof($branchList); $i++)
        {
            $branchIDListInText .= "," . $branchList[$i]["BranchID"];
        }
    }
    $sql = "select * from $jummumOM.branch where branchID in ($branchIDListInText)";
    $branchList = executeQueryArray($sql);
    
    
    
    //return dataList
    $dataList = array();
    $dataList[] = $pageHotDealList;
    $dataList[] = $branchList;

    
    
    //add note word to branch
    for($i=0; $i<sizeof($branchList); $i++)
    {
        $branch = $branchList[$i];
        $eachDbName = $branch->DbName;


        //note word เพิ่ม
        $sql = "select * from $eachDbName.setting where keyName = 'wordAdd'";
        $selectedRow = getSelectedRow($sql);
        $wordAdd = $selectedRow[0]["Value"];
        $branch->WordAdd = $wordAdd?$wordAdd:"เพิ่ม";


        //note word ไม่ใส่
        $sql = "select * from $eachDbName.setting where keyName = 'wordNo'";
        $selectedRow = getSelectedRow($sql);
        $wordNo = $selectedRow[0]["Value"];
        $branch->WordNo = $wordNo?$wordNo:"ไม่ใส่";
    }
    
    
    
    $response = array('success' => true, 'data' => $dataList, 'error' => null, 'status' => 1);
    echo json_encode($response);


    
    // Close connections
    mysqli_close($con);
    
?>
