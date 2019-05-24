<?php
    include_once("dbConnect.php");
    setConnectionValue($jummum);
    writeToLog("file: " . basename(__FILE__) . ", user: " . $_POST["modifiedUser"]);
    printAllPost();
    ini_set("memory_limit","-1");
    
    
    if(isset($_POST["memberID"]) && isset($_POST["page"]) && isset($_POST["perPage"]))
    {
        $memberID = $_POST["memberID"];
        $page = $_POST["page"];
        $perPage = $_POST["perPage"];
    }
   
   
    // Check connection
    if (mysqli_connect_errno())
    {
        echo "Failed to connect to MySQL: " . mysqli_connect_error();
    }
    
    $sql = "select * from (select (@row_number:=@row_number + 1) AS Num, a.* from (select receipt.* from orderJoining left join receipt on orderJoining.receiptID = receipt.receiptID where orderJoining.memberID = $memberID order by ReceiptDate desc)a, (SELECT @row_number:=0) AS t)b where Num > $perPage*($page-1) limit $perPage;";
    

    $selectedRow = getSelectedRow($sql);
    if(sizeof($selectedRow)>0)
    {
        $branchIDList = array();
        for($i=0; $i<sizeof($selectedRow); $i++)
        {
            array_push($branchIDList,$selectedRow[$i]["BranchID"]);
        }
        if(sizeof($branchIDList) > 0)
        {
            $branchIDListInText = $branchIDList[0];
            for($i=1; $i<sizeof($branchIDList); $i++)
            {
                $branchIDListInText .= "," . $branchIDList[$i];
            }
        }
        
        $receiptIDList = array();
        for($i=0; $i<sizeof($selectedRow); $i++)
        {
            array_push($receiptIDList,$selectedRow[$i]["ReceiptID"]);
        }
        if(sizeof($receiptIDList) > 0)
        {
            $receiptIDListInText = $receiptIDList[0];
            for($i=1; $i<sizeof($receiptIDList); $i++)
            {
                $receiptIDListInText .= "," . $receiptIDList[$i];
            }
        }
        $sqlAll = $sql;
        
        
        
        //customerTable
        for($i=0; $i<sizeof($selectedRow); $i++)
        {
            $customerTableID = $selectedRow[$i]["CustomerTableID"];
            $branchID = $selectedRow[$i]["BranchID"];
            $sql2 = "select * from $jummumOM.branch where branchID = '$branchID'";
            $selectedRow2 = getSelectedRow($sql2);
            $eachDbName = $selectedRow2[0]["DbName"];
            
            if($i == 0)
            {
                $sqlCustomerTable = "select '$branchID' BranchID, CustomerTable.* from $eachDbName.CustomerTable where CustomerTableID = '$customerTableID'";
            }
            else
            {
                $sqlCustomerTable .= " union select '$branchID' BranchID, CustomerTable.* from $eachDbName.CustomerTable where CustomerTableID = '$customerTableID'";
            }
        }
        $sqlCustomerTable .= ";";
        $sqlAll .= $sqlCustomerTable;
        
        
        
        //branch
        $sql = "select * from $jummumOM.branch where branchID in ($branchIDListInText);";
        $selectedRow = getSelectedRow($sql);
        $sqlAll .= $sql;
        
        
        
        //orderTaking
        $sql = "select * from $jummum.OrderTaking where receiptID in ($receiptIDListInText);";
        $selectedRow = getSelectedRow($sql);
        $sqlAll .= $sql;
        

        //menu
        if(sizeof($selectedRow)>0)
        {
            for($i=0; $i<sizeof($selectedRow); $i++)
            {
                $menuID = $selectedRow[$i]["MenuID"];
                $branchID = $selectedRow[$i]["BranchID"];
                $sql2 = "select * from $jummumOM.branch where branchID = '$branchID'";
                $selectedRow2 = getSelectedRow($sql2);
                $eachDbName = $selectedRow2[0]["DbName"];
                $mainBranchID = $selectedRow2[0]["MainBranchID"];
                if($branchID != $mainBranchID)
                {
                    $sql2 = "select * from $jummumOM.branch where branchID = '$mainBranchID'";
                    $selectedRow2 = getSelectedRow($sql2);
                    $eachDbName = $selectedRow2[0]["DbName"];
                }
                if($i == 0)
                {
                    $sqlMenu = "select '$mainBranchID' BranchID, Menu.* from $eachDbName.Menu where menuID = '$menuID'";
                }
                else
                {
                    $sqlMenu .= " union select '$mainBranchID' BranchID, Menu.* from $eachDbName.Menu where menuID = '$menuID'";
                }
            }
            $sqlMenu .= ";";
        }
        $sqlAll .= $sqlMenu;


        
        //orderNote
        $sql = "select OrderNote.*,OrderTaking.BranchID from OrderNote left join OrderTaking on OrderNote.orderTakingID = OrderTaking.orderTakingID where OrderNote.orderTakingID in (select orderTakingID from OrderTaking where receiptID in ($receiptIDListInText));";
        $selectedRow = getSelectedRow($sql);
        $sqlAll .= $sql;
        
        
        
        //note
        if(sizeof($selectedRow)>0)
        {
            for($i=0; $i<sizeof($selectedRow); $i++)
            {
                $noteID = $selectedRow[$i]["NoteID"];
                $branchID = $selectedRow[$i]["BranchID"];
                $sql2 = "select * from $jummumOM.branch where branchID = '$branchID'";
                $selectedRow2 = getSelectedRow($sql2);
                $eachDbName = $selectedRow2[0]["DbName"];
                $mainBranchID = $selectedRow2[0]["MainBranchID"];
                if($branchID != $mainBranchID)
                {
                    $sql2 = "select * from $jummumOM.branch where branchID = '$mainBranchID'";
                    $selectedRow2 = getSelectedRow($sql2);
                    $eachDbName = $selectedRow2[0]["DbName"];
                }
                if($i == 0)
                {
                    $sqlNote = "select '$mainBranchID' BranchID, Note.* from $eachDbName.Note where noteID = '$noteID'";
                }
                else
                {
                    $sqlNote .= " union select '$mainBranchID' BranchID, Note.* from $eachDbName.Note where noteID = '$noteID'";
                }
            }
            $sqlNote .= ";";
        }
        else
        {
            $sqlNote = "select 0 from dual where 0;";
        }
        $sqlAll .= $sqlNote;
    }
    else
    {
        $sqlAll = "select 0 from dual where 0;";
        $sqlAll .= "select 0 from dual where 0;";
        $sqlAll .= "select 0 from dual where 0;";
        $sqlAll .= "select 0 from dual where 0;";
        $sqlAll .= "select 0 from dual where 0;";
        $sqlAll .= "select 0 from dual where 0;";
        $sqlAll .= "select 0 from dual where 0;";
        $sqlAll .= "select 0 from dual where 0;";
        $sqlAll .= "select 0 from dual where 0;";
    }
    
    
    
    /* execute multi query */
    $jsonEncode = executeMultiQueryArray($sqlAll);
    
    
    //branch
    $branchList = $jsonEncode[2];
    for($i=0; $i<sizeof($branchList); $i++)
    {
        $branch = $branchList[$i];
        //luckyDraw
        $sql = "select * from $branch->DbName.setting where keyName = 'luckyDrawSpend'";
        $selectedRow = getSelectedRow($sql);
        $luckyDrawSpend = $selectedRow[0]["Value"];
        $luckyDrawSpend = $luckyDrawSpend?$luckyDrawSpend:0;
        $branch->LuckyDrawSpend = $luckyDrawSpend;

        //note word เพิ่ม
        $sql = "select * from $branch->DbName.setting where keyName = 'wordAdd'";
        $selectedRow = getSelectedRow($sql);
        $wordAdd = $selectedRow[0]["Value"];
        $wordAdd = $wordAdd?$wordAdd:"เพิ่ม";
        $branch->WordAdd = $wordAdd;

        //note word ไม่ใส่
        $sql = "select * from $branch->DbName.setting where keyName = 'wordNo'";
        $selectedRow = getSelectedRow($sql);
        $wordNo = $selectedRow[0]["Value"];
        $wordNo = $wordNo?$wordNo:"ไม่ใส่";
        $branch->WordNo = $wordNo;
    }
    
    
    $response = array('success' => true, 'data' => $jsonEncode, 'error' => null, 'status' => 1);
    echo json_encode($response);

    
    // Close connections
    mysqli_close($con);
    
?>
