<?php
    include_once("dbConnect.php");
    setConnectionValue("");
    writeToLog("file: " . basename(__FILE__) . ", user: " . $_POST["modifiedUser"]);
    printAllPost();
    
    
    
    if(isset($_POST["memberID"]) && isset($_POST["modifiedUser"]) && isset($_POST["modifiedDate"]))
    {
        $memberID = $_POST["memberID"];
        $modifiedUser = $_POST["modifiedUser"];
        $modifiedDate = $_POST["modifiedDate"];
        
        
        $status = -1;
    }
    if(isset($_POST["rewardRedemptionID"]))
    {
        $rewardRedemptionID = $_POST["rewardRedemptionID"];
    }


    
    
    
    // Check connection
    if (mysqli_connect_errno())
    {
        echo "Failed to connect to MySQL: " . mysqli_connect_error();
    }
    
    
    
    // Set autocommit to off
    mysqli_autocommit($con,FALSE);
    writeToLog("set auto commit to off");
    
    
    
    
    $sql = "select * from PromoCode where rewardRedemptionID = '$rewardRedemptionID' and status = 0";
    $selectedRow = getSelectedRow($sql);
    if(sizeof($selectedRow) == 0)
    {
        $error = "ไม่สามารถแลกของรางวัลได้ จำนวนสิทธิ์ครบแล้วค่ะ";
        writeToLog("validate fail: $error, file: " . basename(__FILE__) . ", user: " . $_POST['modifiedUser']);
        $response = array('success' => false, 'data' => null, 'error' => $error);
        echo json_encode($response);
        exit();
    }
    else
    {
        $sql = "select * from rewardRedemption where rewardRedemptionID = '$rewardRedemptionID'";
        $selectedRow = getSelectedRow($sql);
        $point = $selectedRow[0]["Point"];
        
        $sql = "SELECT ifnull(floor(sum(Status * Point)),0) Point FROM `rewardpoint` WHERE MemberID = '$memberID';";
        $selectedRow = getSelectedRow($sql);
        $remainingPoint = $selectedRow[0]["Point"];
        if($remainingPoint < $point)
        {
            $error = "แต้มไม่เพียงพอ";
            writeToLog("validate fail: $error, file: " . basename(__FILE__) . ", user: " . $_POST['modifiedUser']);
            $response = array('success' => false, 'data' => null, 'error' => $error);
            echo json_encode($response);
            exit();
        }
        
        
        //query statement
        $sql = "INSERT INTO RewardPoint(MemberID, ReceiptID, Point, Status, PromoCodeID, ModifiedUser, ModifiedDate) VALUES ('$memberID', '$receiptID', '$point', '$status', '$promoCodeID', '$modifiedUser', '$modifiedDate')";
        $ret = doQueryTask($sql);
        if($ret != "")
        {
            mysqli_rollback($con);
//            putAlertToDevice();
            echo json_encode($ret);
            exit();
        }
        
        
        //insert ผ่าน
        $newID = mysqli_insert_id($con);
        
        
        
        $sql = "select * from rewardPoint left join PromoCode on RewardPoint.PromoCodeID = PromoCode.PromoCodeID where rewardPointID <= '$newID' and PromoCode.rewardRedemptionID = '$rewardRedemptionID' order by rewardPointID";
        $selectedRow = getSelectedRow($sql);
        $num = sizeof($selectedRow)+1;
        
        
        $sql = "select PromoCodeID, Code, rewardRedemption.RewardRedemptionID, MainBranchID, rewardRedemption.Point,(SELECT ifnull(floor(sum(Status * Point)),0) Point FROM `rewardpoint` WHERE MemberID = '$memberID') RemainingPoint, DiscountGroupMenuID,(rewardRedemption.MainBranchID != 0) ShowOrderNow from PromoCode left join rewardRedemption on PromoCode.rewardRedemptionID = rewardRedemption.rewardRedemptionID where PromoCode.OrderNo = '$num' and rewardRedemption.rewardRedemptionID = '$rewardRedemptionID'";
        $selectedRow = getSelectedRow($sql);
        $promoCodeID = $selectedRow[0]["PromoCodeID"];
        
        
        /* execute multi query */
        $dataJson = executeMultiQueryArray($sql);
        
        
        
        
        $sql = "update rewardPoint set promoCodeID = '$promoCodeID' where rewardPointID = '$newID'";
        $ret = doQueryTask($sql);
        if($ret != "")
        {
            mysqli_rollback($con);
//            putAlertToDevice();
            echo json_encode($ret);
            exit();
        }
        
        
        
        
        $sql = "update PromoCode set status = 1,modifiedUser='$modifiedUser',modifiedDate='$modifiedDate' where OrderNo = '$num' and rewardRedemptionID = '$rewardRedemptionID'";
        $ret = doQueryTask($sql);
        if($ret != "")
        {
            mysqli_rollback($con);
//            putAlertToDevice();
            echo json_encode($ret);
            exit();
        }
    }
    
    
    

    
    
    //do script successful
    mysqli_commit($con);
    mysqli_close($con);
    
    
    
    writeToLog("query commit, file: " . basename(__FILE__) . ", user: " . $_POST['modifiedUser']);
    $response = array('success' => true, 'data' => $dataJson, 'error' => null);
    echo json_encode($response);
    exit();
?>
