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


    $searchText = trim($searchText);
    $strPattern = getRegExPattern($searchText);


    //select table -> branch, customerTable
    $sql = "select * from (SELECT (@row_number:=@row_number + 1) AS Num, Branch.* FROM $jummumOM.Branch, (SELECT @row_number:=0) AS t where status = 1 and customerApp = 1 and name rlike '$strPattern' order by name) a where Num > $perPage*($page-1) limit $perPage;";
    
    
    /* execute multi query */
    $jsonEncode = executeMultiQueryArray($sql);
    
    
    if(sizeof($jsonEncode) > 0)
    {
        $branchList = $jsonEncode[0];
        
        for($i=0; $i<sizeof($branchList); $i++)
        {
            $branch = $branchList[$i];
            $eachDbName = $branch->DbName;


            //note word เพิ่ม
            $sql = "select * from $eachDbName.setting where keyName = 'wordAdd'";
            $selectedRow = getSelectedRow($sql);
            $wordAdd = $selectedRow[0]["Value"];
            $branch->WordAdd = $wordAdd?$wordAdd:"เพิ่ม";


            //note word ไม่ใส่
            $sql = "select * from $eachDbName.setting where keyName = 'wordNo'";
            $selectedRow = getSelectedRow($sql);
            $wordNo = $selectedRow[0]["Value"];
            $branch->WordNo = $wordNo?$wordNo:"ไม่ใส่";
        }
    }
    $response = array('success' => true, 'data' => $jsonEncode, 'error' => null, 'status' => 1);
    echo json_encode($response);


    
    // Close connections
    mysqli_close($con);
    
?>
