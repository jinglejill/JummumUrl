<?php
    include_once("dbConnect.php");
    setConnectionValue("");
    writeToLog("file: " . basename(__FILE__) . ", user: " . $_POST["modifiedUser"]);
    printAllPost();
    
    
    
    if(isset($_POST["username"]) && isset($_POST["password"]))
    {
        $username = $_POST["username"];
        $password = $_POST["password"];
    }
    if(isset($_POST["logInID"]) && isset($_POST["username"]) && isset($_POST["status"]) && isset($_POST["deviceToken"]) && isset($_POST["model"]) && isset($_POST["modifiedUser"]) && isset($_POST["modifiedDate"]))
    {
        $logInID = $_POST["logInID"];
        $username = $_POST["username"];
        $status = $_POST["status"];
        $deviceToken = $_POST["deviceToken"];
        $model = $_POST["model"];
        $modifiedUser = $_POST["modifiedUser"];
        $modifiedDate = $_POST["modifiedDate"];
    }
    
    
    // Check connection
    if (mysqli_connect_errno())
    {
        echo "Failed to connect to MySQL: " . mysqli_connect_error();
    }
    
    
    
    // Set autocommit to off
    mysqli_autocommit($con,FALSE);
    writeToLog("set auto commit to off");
    
    
    
    
    $sql = "select * from UserAccount where username = '$username' and password = '$password'";
    $selectedRow = getSelectedRow($sql);
    if(sizeof($selectedRow)==0)
    {
        //download UserAccount
        $sql = "select * from UserAccount where 0";
        writeToLog("sql = " . $sql);
        
        
        /* execute multi query */
        $dataJson = executeMultiQueryArray($sql);
        
        
        
        writeToLog("query commit, file: " . basename(__FILE__) . ", user: " . $_POST['modifiedUser']);
        $response = array('status' => '1', 'sql' => $sql , 'tableName' => 'UserAccountValidate', 'dataJson' => $dataJson);
        echo json_encode($response);
        exit();
    }
    
    
    
    
    
    //login--------------------
    //query statement
    $sql = "INSERT INTO LogIn(Username, Status, DeviceToken, Model, ModifiedUser, ModifiedDate) VALUES ('$username', '$status', '$deviceToken', '$model', '$modifiedUser', '$modifiedDate')";
    $ret = doQueryTask($sql);
    if($ret != "")
    {
        mysqli_rollback($con);
//        putAlertToDevice();
        echo json_encode($ret);
        exit();
    }
    //-----
    
    
    
    

    //userAccount
    $sql = "select * from UserAccount where username = '$username' and password = '$password';";
    $selectedRow = getSelectedRow($sql);
    $userAccountID = $selectedRow[0]["UserAccountID"];
    $sqlAll = $sql;
    
    
    

    
    /* execute multi query */
    $dataJson = executeMultiQueryArray($sqlAll);
    
    
    
    //do script successful
    mysqli_commit($con);
    mysqli_close($con);
    
    
    
    writeToLog("query commit, file: " . basename(__FILE__) . ", user: " . $_POST['modifiedUser']);
    $response = array('status' => '1', 'sql' => $sql, 'tableName' => 'UserAccountValidate', dataJson => $dataJson);
    echo json_encode($response);
    exit();
?>
