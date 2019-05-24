<?php
    include_once("dbConnect.php");
    setConnectionValue($jummum);
    writeToLog("file: " . basename(__FILE__) . ", user: " . $_POST["modifiedUser"]);
    printAllPost();
    ini_set("memory_limit","-1");
    
    
    if(isset($_POST["decryptedMessage"]) && isset($_POST["memberID"]))
    {
        $decryptedMessage = $_POST["decryptedMessage"];
        $memberID = $_POST["memberID"];
    }
    $modifiedUser = $_POST["modifiedUser"];
    $modifiedDate = $_POST["modifiedDate"];
    
    
    // Check connection
    if (mysqli_connect_errno())
    {
        echo "Failed to connect to MySQL: " . mysqli_connect_error();
    }
    
    $sql = "select aes_decrypt(unhex('$decryptedMessage'),'$encryptKey') as DecryptedMessage;";
    $selectedRow = getSelectedRow($sql);
    $decryptedMessage = $selectedRow[0]["DecryptedMessage"];
    $parts = explode(",",$decryptedMessage);
    if(sizeof($parts)==2)
    {
        $receiptID = $parts[0];
        $second = time()-strtotime($parts[1]);
        if($second < 5*60)//5min
        {
            $sql = "select * from OrderJoining where memberID = '$memberID' and receiptID = '$receiptID'";
            $selectedRow = getSelectedRow($sql);
            if(sizeof($selectedRow)>0)
            {
                $sql = "select 1 from dual where false";
            }
            else
            {
                //insert joinOrder
                $sql = "INSERT INTO OrderJoining(ReceiptID, MemberID, ModifiedUser, ModifiedDate) VALUES ('$receiptID', '$memberID', '$modifiedUser', '$modifiedDate')";
                $ret = doQueryTask($sql);
                if($ret != "")
                {
                    mysqli_rollback($con);
                    echo json_encode($ret);
                    exit();
                }
                $newID = mysqli_insert_id($con);
                
                
                $sql = "select * from OrderJoining where OrderJoiningID = '$newID'";
            }
        }
        else
        {
            $sql = "select 1 from dual where false";
        }
    }
    else
    {
        $sql = "select 1 from dual where false";
    }
    
    
    
    /* execute multi query */
    $dataJson = executeMultiQueryArray($sql);
    
    
    mysqli_close($con);
    writeToLog("query commit, file: " . basename(__FILE__) . ", user: " . $_POST['modifiedUser']);
    $response = array('status' => '1', 'sql' => $sql, 'tableName' => 'OrderJoining', dataJson => $dataJson);
    echo json_encode($response);
    exit();
    
?>
