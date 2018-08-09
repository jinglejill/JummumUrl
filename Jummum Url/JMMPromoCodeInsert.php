<?php
    include_once("dbConnect.php");
    setConnectionValue("");
    writeToLog("file: " . basename(__FILE__) . ", user: " . $_POST["modifiedUser"]);
    printAllPost();
    
    

    
    if(isset($_POST["rewardRedemptionID"]) && isset($_POST["branchID"]) && isset($_POST["startDate"]) && isset($_POST["endDate"]) && isset($_POST["header"]) && isset($_POST["subTitle"]) && isset($_POST["imageUrl"]) && isset($_POST["point"]) && isset($_POST["prefixPromoCode"]) && isset($_POST["suffixPromoCode"]) && isset($_POST["rewardLimit"]) && isset($_POST["withInPeriod"]) && isset($_POST["detail"]) && isset($_POST["termsConditions"]) && isset($_POST["orderNo"]) && isset($_POST["status"]) && isset($_POST["modifiedUser"]) && isset($_POST["modifiedDate"]))
    {
        $rewardRedemptionID = $_POST["rewardRedemptionID"];
        $branchID = $_POST["branchID"];
        $startDate = $_POST["startDate"];
        $endDate = $_POST["endDate"];
        $header = $_POST["header"];
        $subTitle = $_POST["subTitle"];
        $imageUrl = $_POST["imageUrl"];
        $point = $_POST["point"];
        $prefixPromoCode = $_POST["prefixPromoCode"];
        $suffixPromoCode = $_POST["suffixPromoCode"];
        $rewardLimit = $_POST["rewardLimit"];
        $withInPeriod = $_POST["withInPeriod"];
        $detail = $_POST["detail"];
        $termsConditions = $_POST["termsConditions"];
        $orderNo = $_POST["orderNo"];
        $status = $_POST["status"];
        $modifiedUser = $_POST["modifiedUser"];
        $modifiedDate = $_POST["modifiedDate"];
    }
    else
    {
        $rewardRedemptionID = $_GET["rewardRedemptionID"];
        $prefixPromoCode = $_GET["prefixPromoCode"];
        $suffixPromoCode = $_GET["suffixPromoCode"];
        $number = $_GET["number"];
        $lastOrderNo = $_GET["lastOrderNo"];
    }
    
    
    
    // Check connection
    if (mysqli_connect_errno())
    {
        echo "Failed to connect to MySQL: " . mysqli_connect_error();
    }
    
    
    
    // Set autocommit to off
    mysqli_autocommit($con,FALSE);
    writeToLog("set auto commit to off");
    
    
    
    
    
    $arrRandomString = generate_strings($number,6);
    
    for($i=0; $i<sizeof($arrRandomString); $i++)
    {
        $randomString = $prefixPromoCode . $arrRandomString[$i] . $suffixPromoCode;
        //query statement
        $sql = "INSERT INTO PromoCode(Code, RewardRedemptionID, OrderNo, ModifiedUser, ModifiedDate) VALUES ('$randomString', '$rewardRedemptionID', $lastOrderNo+$i+1, '$modifiedUser', '$modifiedDate')";
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
    $response = array('status' => '1', 'sql' => $sql);
    echo json_encode($response);
    exit();
?>
