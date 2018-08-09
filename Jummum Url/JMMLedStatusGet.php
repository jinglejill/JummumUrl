<?php
    include_once("dbConnect.php");
    setConnectionValue($jummumOM);
    writeToLog("file: " . basename(__FILE__) . ", user: " . $_POST["modifiedUser"]);
    printAllPost();
    ini_set("memory_limit","-1");
    

    
    
    
    if(isset($_GET["ledID"]))
    {
        $ledID = $_GET["ledID"];
    }
    
    
    
    
    // Check connection
    if (mysqli_connect_errno())
    {
        echo "Failed to connect to MySQL: " . mysqli_connect_error();
    }
    
    $sql = "select LedStatus from Led left join Branch on Led.BranchID = Branch.BranchID where LedID = '$ledID'";    
    $selectedRow = getSelectedRow($sql);
    echo json_encode($selectedRow[0]);


    
    // Close connections
    mysqli_close($con);
    
?>
