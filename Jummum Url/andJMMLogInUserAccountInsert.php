<?php
    include_once("dbConnect.php");
    setConnectionValue("");
    writeToLog("file: " . basename(__FILE__) . ", user: " . $_POST["modifiedUser"]);
    printAllPost();
    
    
    
    if(isset($_POST["username"]) && isset($_POST["model"]) && isset($_POST["birthDate"]) && isset($_POST["phoneNo"]) && isset($_POST["firstName"]) && isset($_POST["lastName"]) && isset($_POST["fullName"]) && isset($_POST["email"]))
    {
        $username = $_POST["username"];
        $model = $_POST["model"];
        $birthDate = $_POST["birthDate"];
        $phoneNo = $_POST["phoneNo"];
        $firstName = $_POST["firstName"];
        $lastName = $_POST["lastName"];
        $fullName = $_POST["fullName"];
        $email = $_POST["email"];
    }

    if(isset($_POST["deviceToken"]) && isset($_POST["modifiedUser"]) && isset($_POST["modifiedDate"]))
    {
        $status = $_POST["status"];
        $deviceToken = $_POST["deviceToken"];
        $modifiedUser = $_POST["modifiedUser"];
        $modifiedDate = $_POST["modifiedDate"];
    }
    $status = 1;
    $deviceToken = $_POST["modifiedDeviceToken"];
    
    
    
    // Check connection
    if (mysqli_connect_errno())
    {
        echo "Failed to connect to MySQL: " . mysqli_connect_error();
    }
    
    
    if(!$username)
    {
        $error = "กรุณาระบุ Username";
        writeToLog("validate fail: $error, file: " . basename(__FILE__) . ", user: " . $_POST['modifiedUser']);
        $response = array('success' => false, 'data' => null, 'error' => $error);
        echo json_encode($response);
        exit();
    }
    
    
    
    
    // Set autocommit to off
    mysqli_autocommit($con,FALSE);
    writeToLog("set auto commit to off");
    
    
    
    //login--------------------
    //query statement
    $sql = "INSERT INTO LogIn(Username, Status, DeviceToken, Model , ModifiedUser, ModifiedDate) VALUES ('$username', '$status', '$deviceToken', '$model', '$modifiedUser', '$modifiedDate')";
    $ret = doQueryTask($sql);
    if($ret != "")
    {
        mysqli_rollback($con);
        //        putAlertToDevice();
        echo json_encode($ret);
        exit();
    }
    //-----
    
    
    
    //useraccount----------
    $sql = "select * from useraccount where username = '$username'";
    $selectedRow = getSelectedRow($sql);
    if(sizeof($selectedRow)==0)
    {
        //query statement
        $sql = "INSERT INTO UserAccount(Username, Password, DeviceToken, FirstName,LastName,FullName, NickName, BirthDate, Email, PhoneNo, LineID, RoleID, ModifiedUser, ModifiedDate) VALUES ('$username', '$password', '$deviceToken', '$firstName', '$lastName', '$fullName', '$nickName', '$birthDate', '$email', '$phoneNo', '$lineID', '$roleID', '$modifiedUser', '$modifiedDate')";
        $ret = doQueryTask($sql);
        if($ret != "")
        {
            mysqli_rollback($con);
            //            putAlertToDevice();
            echo json_encode($ret);
            exit();
        }
        //-----
    }


    //userAccount
    $sql = "select * from UserAccount where username = '$username';";
//    $selectedRow = getSelectedRow($sql);
//    $userAccountID = $selectedRow[0]["UserAccountID"];
    $sqlAll = $sql;


    /* execute multi query */
    $dataJson = executeMultiQueryArray($sqlAll);
    
    
    
    //do script successful
    mysqli_commit($con);
    mysqli_close($con);
    
    
    
    writeToLog("query commit, file: " . basename(__FILE__) . ", user: " . $_POST['modifiedUser']);
    $response = array('success' => true, 'data' => $dataJson, 'error' => null);
    echo json_encode($response);
    exit();
    ?>
