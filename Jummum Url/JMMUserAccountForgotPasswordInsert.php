<?php
    include_once("dbConnect.php");
    setConnectionValue("");
    writeToLog("file: " . basename(__FILE__) . ", user: " . $_POST["modifiedUser"]);
    printAllPost();
    
    
    
    if(isset($_POST["username"]))
    {
        $username = $_POST["username"];
    }
    $modifiedUser = $_POST["modifiedUser"];
    
    
    // Check connection
    if (mysqli_connect_errno())
    {
        echo "Failed to connect to MySQL: " . mysqli_connect_error();
    }
    
    
    
    // Set autocommit to off
    mysqli_autocommit($con,FALSE);
    writeToLog("set auto commit to off");
    
    
    
    //query statement
    $sql = "select * from userAccount where username = '$username'";
    /* execute multi query */
    $dataJson = executeMultiQueryArray($sql);
    
    
    $selectedRow = getSelectedRow($sql);
    if(sizeof($selectedRow) > 0)
    {
        $requestDate = date('Y-m-d H:i:s', time());
        $randomString = generateRandomString();
        $codeReset = password_hash($username . $requestDate . $randomString, PASSWORD_DEFAULT);//
        $emailBody = file_get_contents('./htmlEmailTemplateForgotPassword.php');
        $emailBody = str_replace("#codereset#",$codeReset,$emailBody);
        
        
        
        
        $sql = "INSERT INTO `forgotpassword`(`CodeReset`, `Email`, `RequestDate`, `Status`, `ModifiedUser`, `ModifiedDate`) VALUES ('$codeReset','$username','$requestDate','1','$modifiedUser',now())";
        $ret = doQueryTask($sql);
        if($ret != "")
        {
            mysqli_rollback($con);
//            putAlertToDevice();
            echo json_encode($ret);
            exit();
        }
        
        
        sendEmail($username,"Reset password from Jummum",$emailBody);
    }
    
    
    
    
    //do script successful
    mysqli_commit($con);
    mysqli_close($con);
    
    
    
    writeToLog("query commit, file: " . basename(__FILE__) . ", user: " . $_POST['modifiedUser']);
    $response = array('status' => '1', 'sql' => $sql, 'tableName' => 'UserAccountForgotPassword', 'dataJson' => $dataJson);
    echo json_encode($response);
    exit();
?>
