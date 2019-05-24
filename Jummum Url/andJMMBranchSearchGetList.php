<?php
    include_once("dbConnect.php");
    setConnectionValue($jummumOM);
    writeToLog("file: " . basename(__FILE__) . ", user: " . $_POST["modifiedUser"]);
    printAllPost();
    ini_set("memory_limit","-1");
    
    
    if(isset($_POST["searchText"]) && isset($_POST["page"]) && isset($_POST["perPage"]))
    {
        $searchText = $_POST["searchText"];
        $page = $_POST["page"];
        $perPage = $_POST["perPage"];
    }
    
    
    
    // Check connection
    if (mysqli_connect_errno())
    {
        echo "Failed to connect to MySQL: " . mysqli_connect_error();
    }
    

    //select table -> branch, customerTable
    $sql = "select * from (SELECT (@row_number:=@row_number + 1) AS Num, `BranchID`, `Name`, `TakeAwayFee`, `ImageUrl` FROM $jummumOM.Branch, (SELECT @row_number:=0) AS t where status = 1 and customerApp = 1 and name like '%$searchText%' order by name) a where Num > $perPage*($page-1) limit $perPage;";

    /* execute multi query */
    $arrResult = executeQueryArray($sql);
    
    
    $response = array('success' => true, 'data' => $arrResult, 'error' => null);
    echo json_encode($response);


    
    // Close connections
    mysqli_close($con);
    
?>
