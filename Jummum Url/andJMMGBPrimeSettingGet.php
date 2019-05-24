<?php
    include_once("dbConnect.php");
    setConnectionValue("");
    writeToLog("file: " . basename(__FILE__) . ", user: " . $_POST["modifiedUser"]);
    printAllPost();
    
    
    // Check connection
    if (mysqli_connect_errno())
    {
        echo "Failed to connect to MySQL: " . mysqli_connect_error();
    }
//    $amount = $_POST["amount"];
//    $customerTableID = $_POST["customerTableID"];
//    $receiptNoID = $_POST["receiptNoID"];
//    $receiptID = $_POST["receiptID"];
//    $branchID = $_POST["branchID"];
//    $deviceToken = $_POST["deviceToken"];
//    $memberID = $_POST["memberID"];

    //-----
    $sql = "select (select VALUE from setting where keyName = 'GBPrimeQRPostUrl') GBPrimeQRPostUrl,(select VALUE from setting where keyName = 'GBPrimeQRToken') GBPrimeQRToken,(select VALUE from setting where keyName = 'ResponseUrl') ResponseUrl,(select VALUE from setting where keyName = 'BackgroundUrl') BackgroundUrl;";
    /* execute multi query */
    $jsonEncode = executeMultiQueryArray($sql);
    
    
//    $sql = "select Value from setting where keyName = 'GBPrimeQRPostUrl'";
//    $selectedRow = getSelectedRow($sql);
//    $GBPrimeQRPostUrl = $selectedRow[0]["Value"];
//
//
//    $sql = "select Value from setting where keyName = 'GBPrimeQRToken'";
//    $selectedRow = getSelectedRow($sql);
//    $GBPrimeQRToken = $selectedRow[0]["Value"];
//
//
//    $sql = "select Value from setting where keyName = 'ResponseUrl'";
//    $selectedRow = getSelectedRow($sql);
//    $responseUrl = $selectedRow[0]["Value"];
//
//
//    $sql = "select Value from setting where keyName = 'BackgroundUrl'";
//    $selectedRow = getSelectedRow($sql);
//    $backgroundUrl = $selectedRow[0]["Value"];
//
//
//
//
//
//
//    $referenceNo = date('Ymd') . $receiptNoID;
//    // Create map with request parameters
//    $params = array ('token' => "$GBPrimeQRToken", 'amount' => "$amount", 'detail' => "$customerTableID", 'referenceNo' => "$referenceNo", 'payType' => "F", 'backgroundUrl' => "$backgroundUrl", 'responseUrl' => "$responseUrl", 'merchantDefined1' => "$receiptID", 'merchantDefined2' => "$branchID", 'merchantDefined3' => "$deviceToken", 'merchantDefined4' => "$memberID", 'merchantDefined5' => "$receiptNoID");
//
////    echo "<br>" . json_encode($params);
//    // Build Http query using params
//    $query = http_build_query ($params);
//
//    // Create Http context details
//    $contextData = array (
//                'method' => 'POST',
//                'header' => "Connection: close\r\n".
//                            "Content-Length: ".strlen($query)."\r\n".
//                            "Content-Type: application/json\r\n",
//                'content'=> $query );
//
//    // Create context resource for our request
//    $context = stream_context_create (array ( 'http' => $contextData ));
//
////    echo "<br>postUrl:".$GBPrimeQRPostUrl;
//    // Read page rendered as result of your POST request
//    $result =  file_get_contents (
//                  "$GBPrimeQRPostUrl",  // page url
//                  false,
//                  $context);
//    $base64 = base64_encode($result);
//
//
//
//    // Server response is now stored in $result variable so you can process it
//
    
    
    
    
    
    
    
    
    
    $response = array('success' => true, 'data' => $jsonEncode, 'error' => null, 'status' => 1);
    echo json_encode($response);
    
    
    
    // Close connections
    mysqli_close($con);
?>
//
//<html>
//    <body>
//        <img src="data:image/png;base64,<?php echo $base64; ?>" alt="" />
//    </body>
//</html>
