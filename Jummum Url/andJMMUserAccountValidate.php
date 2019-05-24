<?php
    include_once("dbConnect.php");
    setConnectionValue("");
    writeToLog("file: " . basename(__FILE__) . ", user: " . $_POST["modifiedUser"]);
    printAllPost();



    if(isset($_POST["username"]) && isset($_POST["password"]) && isset($_POST["model"]))
    {
        $username = $_POST["username"];
        $password = $_POST["password"];
        $model = $_POST["model"];
    }

    if(isset($_POST["deviceToken"]) && isset($_POST["modifiedUser"]) && isset($_POST["modifiedDate"]))
    {
        $deviceToken = $_POST["deviceToken"];
        $modifiedUser = $_POST["modifiedUser"];
        $modifiedDate = $_POST["modifiedDate"];
    }
    $status = 1;
//    $deviceToken = $_POST["modifiedDeviceToken"];


    // Check connection
    if (mysqli_connect_errno())
    {
        echo "Failed to connect to MySQL: " . mysqli_connect_error();
    }


    // Set autocommit to off
    mysqli_autocommit($con,FALSE);
    writeToLog("set auto commit to off");



    $password = hash('sha256', "$password$salt");
    $sql = "select * from UserAccount where username = '$username' and password = '$password'";
    $selectedRow = getSelectedRow($sql);
    if(sizeof($selectedRow)==0)
    {
      //do script successful
      mysqli_commit($con);
      mysqli_close($con);
      
      writeToLog("query commit, file: " . basename(__FILE__) . ", user: " . $_POST['modifiedUser']);
      $response = array('success' => false, 'data' => null, 'error' => 'ไม่มี user นี้ในระบบ');
      echo json_encode($response);
      exit();
    }





    //login--------------------
    //query statement
    $sql = "INSERT INTO LogIn(Username, Status, DeviceToken, ModifiedUser, ModifiedDate) VALUES ('$username', '$status', '$deviceToken', '$modifiedUser', '$modifiedDate')";
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
    $sql = "select `UserAccountID`, `Username`, `DeviceToken`, `FirstName`, `LastName`, `BirthDate`, `Email`, `PhoneNo` from UserAccount where username = '$username' and password = '$password';";
    $arrMultiResult = executeMultiQueryArray($sql);


    //do script successful
    mysqli_commit($con);
    mysqli_close($con);


    writeToLog("query commit, file: " . basename(__FILE__) . ", user: " . $_POST['modifiedUser']);
    $response = array('success' => true, 'data' => $arrMultiResult, 'error' => null);
    echo json_encode($response);
    exit();
?>
