<?php
    include_once("dbConnect.php");
    setConnectionValue($jummumOM);
    writeToLog("file: " . basename(__FILE__) . ", user: " . $_POST["modifiedUser"]);
    printAllPost();
    ini_set("memory_limit","-1");
    
    
    if(isset($_POST["searchText"]) && isset($_POST["name"]))
    {
        $searchText = $_POST["searchText"];
        $name = $_POST["name"];
    }
    
    
    
    // Check connection
    if (mysqli_connect_errno())
    {
        echo "Failed to connect to MySQL: " . mysqli_connect_error();
    }
    
    
    
    
    //select table -> branch, customerTable
    $sql = "SELECT * FROM $jummumOM.Branch where status = 1 and customerApp = 1 and name like '%$searchText%' and name > '$name' order by name limit 10;";


    
    /* execute multi query */
    $jsonEncode = executeMultiQueryArray($sql);
    if(sizeof($jsonEncode) > 0)
    {
        $branchList = $jsonEncode[0];
        
        for($i=0; $i<sizeof($branchList); $i++)
        {
            $branch = $branchList[$i];
            $eachDbName = $branch->DbName;
            
            $sql = "select * from $eachDbName.setting where keyName = 'luckyDrawSpend'";
            $selectedRow = getSelectedRow($sql);
            $luckyDrawSpend = $selectedRow[0]["Value"];
            $branch->LuckyDrawSpend = $luckyDrawSpend?$luckyDrawSpend:0;
        }
    }
    
    
    $response = array('success' => true, 'data' => $jsonEncode, 'error' => null, 'status' => 1);
    echo json_encode($response);


    
    // Close connections
    mysqli_close($con);
    
?>
