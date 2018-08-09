<?php
    include_once("dbConnect.php");
    setConnectionValue("");
    writeToLog("file: " . basename(__FILE__) . ", user: " . $_POST["modifiedUser"]);
    printAllPost();
    
    
    
    if(isset($_POST["rewardPointID"]) && isset($_POST["memberID"]) && isset($_POST["receiptID"]) && isset($_POST["point"]) && isset($_POST["status"]) && isset($_POST["promoCodeID"]) && isset($_POST["modifiedUser"]) && isset($_POST["modifiedDate"]))
    {
        $rewardPointID = $_POST["rewardPointID"];
        $memberID = $_POST["memberID"];
        $receiptID = $_POST["receiptID"];
        $point = $_POST["point"];
        $status = $_POST["status"];
        $promoCodeID = $_POST["promoCodeID"];
        $modifiedUser = $_POST["modifiedUser"];
        $modifiedDate = $_POST["modifiedDate"];
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
        /* execute multi query */
        $sql = "select * from promoCode where 0";
        $dataJson = executeMultiQueryArray($sql);
    }
    else
    {
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
        
        
        $sql = "select * from PromoCode where OrderNo = '$num' and rewardRedemptionID = '$rewardRedemptionID'";
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
    $response = array('status' => '1', 'sql' => $sql, 'tableName' => 'RewardPoint', dataJson => $dataJson);
    echo json_encode($response);
    exit();
?>
