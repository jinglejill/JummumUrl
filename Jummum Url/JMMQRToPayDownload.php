<?php
    include_once("dbConnect.php");
    setConnectionValue("");
    writeToLog("file: " . basename(__FILE__) . ", user: " . $_POST["modifiedUser"]);
    printAllPost();
    
    
    $receiptID = $_POST["receiptID"];
    $branchID = $_POST["branchID"];
    $customerTableID = $_POST["customerTableID"];
    $memberID = $_POST["memberID"];
    
    
    
//    //test*****
//    $file = "http://www.jummum.co/App/icon-512.png";
//    $src = imagecreatefrompng($file);
//    header("Content-Type: image/png");
//    imagepng($src);
//    //*****
    
    
    
    echo json_encode(array('base64String' => $b64image, 'post_image_filename' => $imageFileName));
    exit();
?>
