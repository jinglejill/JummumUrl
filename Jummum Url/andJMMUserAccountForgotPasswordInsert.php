<?php
    $fullPath = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    $posLastSlash = strripos($fullPath,'/');
?>
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
    
    
    
    //query statement
    $sql = "select * from userAccount where username = '$username'";
    /* execute multi query */
    $dataJson = executeMultiQueryArray($sql);
    
    
    $selectedRow = getSelectedRow($sql);
    if(sizeof($selectedRow) > 0)
    {
        // Set autocommit to off
        mysqli_autocommit($con,FALSE);
        writeToLog("set auto commit to off");
        
        
        
        $requestDate = date('Y-m-d H:i:s', time());
        $randomString = generateRandomString();
        $codeReset = password_hash($username . $requestDate . $randomString, PASSWORD_DEFAULT);//
        $emailBody = file_get_contents('./htmlEmailTemplateForgotPassword.php');
        $emailBody = str_replace("#codereset#",$codeReset,$emailBody);
        $emailBody = str_replace("#jummumFilePath#",substr($fullPath,0,$posLastSlash),$emailBody);
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
        
        
        //do script successful
        mysqli_commit($con);
        mysqli_close($con);
        
        
        writeToLog("query commit, file: " . basename(__FILE__) . ", user: " . $_POST['modifiedUser']);
        $response = array('success' => true, 'data' => null, 'error' => null);
        echo json_encode($response);
        exit();        
    }
    else
    {
        $error = "ไม่มีอีเมลนี้ในระบบ";
        writeToLog("validate fail: $error, file: " . basename(__FILE__) . ", user: " . $_POST['modifiedUser']);
        $response = array('success' => false, 'data' => null, 'error' => $error);
        echo json_encode($response);
        exit();
    }

?>
