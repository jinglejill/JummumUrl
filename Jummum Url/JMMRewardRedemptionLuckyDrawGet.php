<?php
    include_once("dbConnect.php");
    setConnectionValue("");
    writeToLog("file: " . basename(__FILE__) . ", user: " . $_POST["modifiedUser"]);
    printAllPost();
    ini_set("memory_limit","-1");
    
    
    
    
    if(isset($_POST["branchID"]) && isset($_POST["memberID"]) && isset($_POST["receiptID"]))
    {
        $branchID = $_POST["branchID"];
        $memberID = $_POST["memberID"];
        $receiptID = $_POST["receiptID"];
    }
    
    
    
    // Check connection
    if (mysqli_connect_errno())
    {
        echo "Failed to connect to MySQL: " . mysqli_connect_error();
    }
    
    $sql = "select * from useraccount where useraccountID = '$memberID'";
    $selectedRow = getSelectedRow($sql);
    $modifiedUser = $selectedRow[0]["Username"];
    $sql = "select now() ModifiedDate";
    $selectedRow = getSelectedRow($sql);
    $modifiedDate = $selectedRow[0]["ModifiedDate"];
    
    
//    $sql = "select * from luckyDrawTicket where receiptID = '$receiptID' and rewardRedemptionID = -1;";
    $currentDateTime = date('Y-m-d H:i:s');
    $sql = "select * from setting where keyName = 'LuckyDrawTimeLimit';";
    $selectedRow = getSelectedRow($sql);
    $luckyDrawTimeLimit = $selectedRow[0]["Value"];
    $sql = "select * from luckyDrawTicket left join receipt on luckyDrawTicket.receiptID = receipt.receiptID where luckyDrawTicket.memberID = '$memberID' and receipt.branchID = '$branchID' and rewardRedemptionID = -1 and TIME_TO_SEC(timediff('$currentDateTime', luckyDrawTicket.modifiedDate)) <= '$luckyDrawTimeLimit';";
    $selectedRow = getSelectedRow($sql);
    if(sizeof($selectedRow)==0)
    {
        /* execute multi query */
        $jsonEncode = executeMultiQueryArray($sql);
        $response = array('success' => "false", 'data' => null, 'error' => "คุณใช้สิทธิ์ครบแล้ว", 'status' => 2);
        echo json_encode($response);
        
        // Close connections
        mysqli_close($con);
    }
    else
    {
        $luckyDrawTicketID = $selectedRow[0]["LuckyDrawTicketID"];
        
        
        $sql = "select * from $jummumOM.branch where branchID = '$branchID'";
        $selectedRow = getSelectedRow($sql);
        $dbName = $selectedRow[0]["DbName"];
        
        //reward weight first
        $sql = "select * from $dbName.setting where KeyName = 'rewardWeightFirst'";
        $selectedRow = getSelectedRow($sql);
        $rewardWeightFirst = $selectedRow[0]["Value"];
        
        //reward weight second
        $sql = "select * from $dbName.setting where KeyName = 'rewardWeightSecond'";
        $selectedRow = getSelectedRow($sql);
        $rewardWeightSecond = $selectedRow[0]["Value"];
        
        //reward weight third
        $sql = "select * from $dbName.setting where KeyName = 'rewardWeightThird'";
        $selectedRow = getSelectedRow($sql);
        $rewardWeightThird = $selectedRow[0]["Value"];
        
        //reward weight fourth
        $sql = "select * from $dbName.setting where KeyName = 'rewardWeightFourth'";
        $selectedRow = getSelectedRow($sql);
        $rewardWeightFourth = $selectedRow[0]["Value"];
        
        
        $chance = 0;
        $randomText = "";
        
        //rank = 1
        $rank = 1;
        $sql = "select * from rewardRedemption LEFT JOIN rewardRedemptionBranch ON rewardRedemption.RewardRedemptionID = rewardRedemptionBranch.RewardRedemptionID left join promoCode on rewardRedemption.rewardRedemptionID = promoCode.rewardRedemptionID where rewardredemptionbranch.BranchID = '$branchID' and rewardredemption.Type in (1,2) and rewardRank = '$rank' and promoCode.status = 0";
        $selectedRow = getSelectedRow($sql);
        if(sizeof($selectedRow)>0)
        {
            $chance += $rewardWeightFirst;
            writeToLog("chance: " . $chance);
            for($i=0; $i<$rewardWeightFirst; $i++)
            {
                $randomText .= "1";
            }
        }
        
        //rank = 2
        $rank = 2;
        $sql = "select * from rewardRedemption LEFT JOIN rewardRedemptionBranch ON rewardRedemption.RewardRedemptionID = rewardRedemptionBranch.RewardRedemptionID left join promoCode on rewardRedemption.rewardRedemptionID = promoCode.rewardRedemptionID where rewardredemptionbranch.BranchID = '$branchID' and rewardredemption.Type in (1,2) and rewardRank = '$rank' and promoCode.status = 0";
        $selectedRow = getSelectedRow($sql);
        if(sizeof($selectedRow)>0)
        {
            $chance += $rewardWeightSecond;
            writeToLog("chance: " . $chance);
            for($i=0; $i<$rewardWeightSecond; $i++)
            {
                $randomText .= "2";
            }
        }
        
        //rank = 3
        $rank = 3;
        $sql = "select * from rewardRedemption LEFT JOIN rewardRedemptionBranch ON rewardRedemption.RewardRedemptionID = rewardRedemptionBranch.RewardRedemptionID left join promoCode on rewardRedemption.rewardRedemptionID = promoCode.rewardRedemptionID where rewardredemptionbranch.BranchID = '$branchID' and rewardredemption.Type in (1,2) and rewardRank = '$rank' and promoCode.status = 0";
        $selectedRow = getSelectedRow($sql);
        if(sizeof($selectedRow)>0)
        {
            $chance += $rewardWeightThird;
            writeToLog("chance: " . $chance);
            for($i=0; $i<$rewardWeightThird; $i++)
            {
                $randomText .= "3";
            }
        }
        
        
        $chance += $rewardWeightFourth;
        writeToLog("chance 4: " . $chance);
        for($i=0; $i<$rewardWeightFourth; $i++)
        {            
            $randomText .= "4";
        }
        
        $ranPosition = rand(0, $chance-1);
        $rewardGot = $randomText[$ranPosition];
        writeToLog("chance all: " . $chance);
        writeToLog("ranPosition: " . $ranPosition);
        writeToLog("randomText: " . $randomText);
        writeToLog("rewardGot: " . $rewardGot);
        
//        $rewardGot = 1;//test
        if($rewardGot == 4)
        {
            //update luckyDrawTicket
            $sql = "update luckyDrawTicket set rewardRedemptionID = '0', redeemDate = '$modifiedDate' where luckyDrawTicketID = '$luckyDrawTicketID'";
            $ret = doQueryTask($sql);
            if($ret != "")
            {
                mysqli_rollback($con);
                echo json_encode($ret);
                exit();
            }
            
            
            
            $currentDateTime = date('Y-m-d H:i:s');
            $sql = "select * from setting where keyName = 'LuckyDrawTimeLimit';";
            $selectedRow = getSelectedRow($sql);
            $luckyDrawTimeLimit = $selectedRow[0]["Value"];
            $sql = "select 'Try again next time' Header,4 RewardRank,'' VoucherCode;";
            $sql .= "select * from luckyDrawTicket left join receipt on luckyDrawTicket.receiptID = receipt.receiptID where luckyDrawTicket.memberID = '$memberID' and receipt.branchID = '$branchID' and rewardRedemptionID = -1 and TIME_TO_SEC(timediff('$currentDateTime', luckyDrawTicket.modifiedDate)) <= '$luckyDrawTimeLimit';";

            
            
            /* execute multi query */
            $jsonEncode = executeMultiQueryArray($sql);
            $response = array('success' => true, 'data' => $jsonEncode, 'error' => null, 'status' => 1);
            echo json_encode($response);
            
            
            
            // Close connections
            mysqli_close($con);
            return;
        }
        $sql = "select * from rewardredemption LEFT JOIN rewardredemptionbranch ON rewardredemption.RewardRedemptionID = rewardredemptionbranch.RewardRedemptionID where rewardredemptionbranch.BranchID = '$branchID' and rewardredemption.Type in (1,2) and rewardRank = '$rewardGot'";
        $selectedRow = getSelectedRow($sql);
        $rewardRedemption = $selectedRow[rand(0, sizeof($selectedRow)-1)];
        $rewardRedemptionID = $rewardRedemption["RewardRedemptionID"];
        
        
        
        $sql = "select * from PromoCode where rewardRedemptionID = '$rewardRedemptionID' and status = 0";
        $selectedRow = getSelectedRow($sql);

        {
            //insert rewardPoint
            $promoCodeID = $selectedRow[0]["PromoCodeID"];
            $promoCode = $selectedRow[0]["Code"];
            $num = $selectedRow[0]["OrderNo"];
            $point = 0;
            $status = -1;
            $sql = "INSERT INTO RewardPoint(MemberID, ReceiptID, Point, Status, PromoCodeID, ModifiedUser, ModifiedDate) VALUES ('$memberID', '$receiptID', '$point', '$status', '$promoCodeID', '$modifiedUser', '$modifiedDate')";
            $ret = doQueryTask($sql);
            if($ret != "")
            {
                mysqli_rollback($con);
                //            putAlertToDevice();
                echo json_encode($ret);
                exit();
            }
            
            
            //update promoCode
            $sql = "update PromoCode set status = 1,modifiedUser='$modifiedUser',modifiedDate='$modifiedDate' where OrderNo = '$num' and rewardRedemptionID = '$rewardRedemptionID'";
            $ret = doQueryTask($sql);
            if($ret != "")
            {
                mysqli_rollback($con);
                echo json_encode($ret);
                exit();
            }
            
            
            //update luckyDrawTicket
            $sql = "update luckyDrawTicket set rewardRedemptionID = '$rewardRedemptionID', redeemDate = '$modifiedDate' where luckyDrawTicketID = '$luckyDrawTicketID'";
            $ret = doQueryTask($sql);
            if($ret != "")
            {
                mysqli_rollback($con);
                echo json_encode($ret);
                exit();
            }
            
            

            $currentDateTime = date('Y-m-d H:i:s');
            $sql = "select * from setting where keyName = 'LuckyDrawTimeLimit';";
            $selectedRow = getSelectedRow($sql);
            $luckyDrawTimeLimit = $selectedRow[0]["Value"];
            $sql = "select rewardRedemption.*, '$promoCode' VoucherCode from rewardRedemption where rewardRedemptionID = '$rewardRedemptionID';";
            $sql .= "select * from luckyDrawTicket left join receipt on luckyDrawTicket.receiptID = receipt.receiptID where luckyDrawTicket.memberID = '$memberID' and receipt.branchID = '$branchID' and rewardRedemptionID = -1 and TIME_TO_SEC(timediff('$currentDateTime', luckyDrawTicket.modifiedDate)) <= '$luckyDrawTimeLimit';";

            
            
            
            /* execute multi query */
            $jsonEncode = executeMultiQueryArray($sql);
            $response = array('success' => true, 'data' => $jsonEncode, 'error' => null, 'status' => 1);
            echo json_encode($response);
            
            
            
            // Close connections
            mysqli_close($con);
        }
    }
    
    ?>
