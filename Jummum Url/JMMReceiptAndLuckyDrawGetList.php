<?php
    include_once("dbConnect.php");
    setConnectionValue("");
    writeToLog("file: " . basename(__FILE__) . ", user: " . $_POST["modifiedUser"]);
    printAllPost();
    ini_set("memory_limit","-1");
    

    
    if(isset($_POST["receiptID"]))
    {
        $receiptID = $_POST["receiptID"];
    }
    
    
    // Check connection
    if (mysqli_connect_errno())
    {
        echo "Failed to connect to MySQL: " . mysqli_connect_error();
    }
    
    
    $sql = "select * from receipt where receiptID = '$receiptID';";
    $selectedRow = getSelectedRow($sql);
    
    
    $branchID = $selectedRow[0]["BranchID"];
    $memberID = $selectedRow[0]["MemberID"];
    
    
    $currentDateTime = date("Y-m-d H:i:s");
    $sql2 = "select * from setting where keyName = 'LuckyDrawTimeLimit';";
    $selectedRow = getSelectedRow($sql2);
    $luckyDrawTimeLimit = $selectedRow[0]["Value"];
    $sql .= "select luckyDrawTicket.* from luckyDrawTicket left join receipt on luckyDrawTicket.receiptID = receipt.receiptID where luckyDrawTicket.memberID = '$memberID' and receipt.branchID = '$branchID' and rewardRedemptionID = -1 and TIME_TO_SEC(timediff('$currentDateTime', luckyDrawTicket.modifiedDate)) <= '$luckyDrawTimeLimit';";
    
    
    /* execute multi query */
    $jsonEncode = executeMultiQueryArray($sql);
    $response = array('success' => true, 'data' => $jsonEncode, 'error' => null, 'status' => 1);
    echo json_encode($response);


    
    // Close connections
    mysqli_close($con);
?>
