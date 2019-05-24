<?php
    include_once("dbConnect.php");
    setConnectionValue("");
    writeToLog("file: " . basename(__FILE__) . ", user: " . $_POST["modifiedUser"]);
    printAllPost();
    
    
    
    if( isset($_POST["username"]) && isset($_POST["password"]) && isset($_POST["deviceToken"]) && isset($_POST["firstName"]) && isset($_POST["lastName"]) && isset($_POST["birthDate"]) && isset($_POST["email"]) && isset($_POST["phoneNo"]) && isset($_POST["modifiedUser"]) && isset($_POST["modifiedDate"]))
    {
        $username = $_POST["username"];
        $password = $_POST["password"];
        $deviceToken = $_POST["deviceToken"];
        $firstName = $_POST["firstName"];
        $lastName = $_POST["lastName"];
        $birthDate = $_POST["birthDate"];
        $email = $_POST["email"];
        $phoneNo = $_POST["phoneNo"];
        $modifiedUser = $username;
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
    
    
    //validate************
    if(trim($username) == "")
    {
        $error = "กรุณาระบุอีเมล";
        writeToLog("validate fail: $error, file: " . basename(__FILE__) . ", user: " . $_POST['modifiedUser']);
        $response = array('success' => FALSE, 'data' => null, 'error' => $error);
        echo json_encode($response);
        exit();
    }
    
    if(!filter_var($username, FILTER_VALIDATE_EMAIL))
    {
        $error = "อีเมลไม่ถูกต้อง";
        writeToLog("validate fail: $error, file: " . basename(__FILE__) . ", user: " . $_POST['modifiedUser']);
        $response = array('success' => FALSE, 'data' => null, 'error' => $error);
        echo json_encode($response);
        exit();
    }
    
    
    $sql = "select * from userAccount where username = '$username'";
    $selectedRow = getSelectedRow($sql);
    if(sizeof($selectedRow) > 0)
    {
        $error = "อีเมลนี้ถูกใช้แล้ว";
        writeToLog("validate fail: $error, file: " . basename(__FILE__) . ", user: " . $_POST['modifiedUser']);
        $response = array('success' => FALSE, 'data' => null, 'error' => $error);
        echo json_encode($response);
        exit();
    }
    
    
    $uppercase = preg_match('@[A-Z]@', $password);
    $lowercase = preg_match('@[a-z]@', $password);
    $number    = preg_match('@[0-9]@', $password);
    $keywords = "~[!@#$%^&*()\-_=+{};:,<.>]~";
    $specialCharacter = preg_match($keywords, $password);
    if(!$uppercase || !$lowercase || (!number && !$specialCharacter) || strlen($password) < 8)
    {
        $error = "พาสเวิร์ดต้องประกอบไปด้วย \n1.อักษรตัวเล็กอย่างน้อย 1 ตัว\n2.อักษรตัวใหญ่อย่างน้อย 1 ตัว\n3.ตัวเลขหรืออักษรพิเศษอย่างน้อย 1 ตัว\n4.ความยาวขั้นต่ำ 8 ตัวอักษร";        
        writeToLog("validate fail: $error, file: " . basename(__FILE__) . ", user: " . $_POST['modifiedUser']);
        $response = array('success' => FALSE, 'data' => null, 'error' => $error);
        echo json_encode($response);
        exit();
    }
    
    if(trim($firstName) == "")
    {
        $error = "กรุณาระบุชื่อ";
        writeToLog("validate fail: $error, file: " . basename(__FILE__) . ", user: " . $_POST['modifiedUser']);
        $response = array('success' => FALSE, 'data' => null, 'error' => $error);
        echo json_encode($response);
        exit();
    }
    
    if(trim($lastName) == "")
    {
        $error = "กรุณาระบุนามสกุล";
        writeToLog("validate fail: $error, file: " . basename(__FILE__) . ", user: " . $_POST['modifiedUser']);
        $response = array('success' => FALSE, 'data' => null, 'error' => $error);
        echo json_encode($response);
        exit();
    }
    ////*****
    
    
    
    
    //query statement
    $password = hash('sha256', "$password$salt");
    $sql = "INSERT INTO UserAccount(Username, Password, DeviceToken, FirstName, LastName, FullName, NickName, BirthDate, Email, PhoneNo, LineID, RoleID, ModifiedUser, ModifiedDate) VALUES ('$username', '$password', '$deviceToken', '$firstName', '$lastName', '$fullName', '$nickName', '$birthDate', '$email', '$phoneNo', '$lineID', '$roleID', '$modifiedUser', '$modifiedDate')";
    $ret = doQueryTask($sql);
    if($ret != "")
    {
        mysqli_rollback($con);
//        putAlertToDevice();
        echo json_encode($ret);
        exit();
    }
    
    
    
    
    //do script successful
    mysqli_commit($con);
    mysqli_close($con);
    
    
    
    writeToLog("query commit, file: " . basename(__FILE__) . ", user: " . $_POST['modifiedUser']);
    $response = array('success' => true, 'data' => null, 'error' => null);
    echo json_encode($response);
    exit();
?>
