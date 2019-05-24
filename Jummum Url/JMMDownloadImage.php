<?php
    include_once("dbConnect.php");
    setConnectionValue("");
    writeToLog("file: " . basename(__FILE__) . ", user: " . $_POST["modifiedUser"]);
    printAllPost();
    
    

    if(isset($_POST['imageFileName']))
    {
        $imageFileName = $_POST['imageFileName'];
        $type = $_POST['type'];
        $branchID = $_POST['branchID'];
    }
    else
    {
        $imageFileName = "/201508131130161.jpg";
    }
    
    
    if($type == 1 || $type == 2 || $type == 6)
    {
        $sql = "select * from $jummumOM.branch where branchID = '$branchID'";
        $selectedRow = getSelectedRow($sql);
        $dbName = $selectedRow[0]["DbName"];
        $mainBranchID = $selectedRow[0]["MainBranchID"];
        if($branchID != $mainBranchID)
        {
            $sql = "select * from $jummumOM.branch where branchID = '$mainBranchID'";
            $selectedRow = getSelectedRow($sql);
            $dbName = $selectedRow[0]["DbName"];
        }
    }
    
    
    
    $b64image = "";
    switch($type)
    {
        case 1://menu
            $filenameIn = "./../$masterFolder/$dbName/Image/Menu/$imageFileName";
            break;
        case 2://logo
            $filenameIn = "./../$masterFolder/$dbName/Image/Logo/$imageFileName";
            break;
        case 3://promotion
            $filenameIn = "./../$masterFolder/Image/Promotion/$imageFileName";
            break;
        case 4://reward
            $filenameIn = "./../$masterFolder/Image/Reward/$imageFileName";
            break;
        case 5://jummum material
            $filenameIn = "./../$masterFolder/Image/$imageFileName";
            break;
        case 6://discount program
            $filenameIn = "./../$masterFolder/$dbName/Image/DiscountProgram/$imageFileName";
            break;
    }
//    echo "<br>" . $type;
    writeToLog("fileNameIn: " . $filenameIn);
    
    
    // Check if file already exists
    if ($imageFileName != "" && file_exists($filenameIn))
    {
        $b64image = base64_encode(file_get_contents($filenameIn));
    }
    else
    {
        switch($type)
        {
            case 1:
            case 2:
            case 6:
            {
                $filenameIn = "./../$masterFolder/$dbName/Image/NoImage.jpg";
            }
                break;
            case 3:
            case 4:
            case 5:
            {
                $filenameIn = "./../$masterFolder/Image/NoImage.jpg";
            }
                break;
        }
        
        $b64image = base64_encode(file_get_contents($filenameIn));
    }
    
//    //test*****
//    $file = "http://www.jummum.co/App/icon-512.png";
//    $src = imagecreatefrompng($file);
//    header("Content-Type: image/png");
//    imagepng($src);
//    //*****
    
    
    
    echo json_encode(array('base64String' => $b64image, 'post_image_filename' => $imageFileName));
    exit();
?>
