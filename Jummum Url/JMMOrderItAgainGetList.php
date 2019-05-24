<?php
    include_once("dbConnect.php");
    setConnectionValue($jummum);
    writeToLog("file: " . basename(__FILE__) . ", user: " . $_POST["modifiedUser"]);
    printAllPost();
    ini_set("memory_limit","-1");
    
    
    if(isset($_POST["receiptID"]))
    {
        $receiptID = $_POST["receiptID"];
    }
    
    
    
    // Check connection
    if (mysqli_connect_errno())
    {
        echo "Failed to connect to MySQL: " . mysqli_connect_error();
    }
    

    $sql = "select * from receipt where receiptID = '$receiptID';";
    $selectedRow = getSelectedRow($sql);
    $branchID = $selectedRow[0]["BranchID"];
    $customerTableID = $selectedRow[0]["CustomerTableID"];
    $buffetReceiptID = $selectedRow[0]["BuffetReceiptID"];
    
    
    //saveReceipt
    $sqlSaveReceipt = "select Remark, '' as VoucherCode, $buffetReceiptID as BuffetReceiptID from Receipt where ReceiptID = '$receiptID';";
    

    //branch///***************
    $sql = "select * from $jummumOM.branch where branchID = '$branchID' and status = 1 and customerApp = 1;";
    $selectedRow =getSelectedRow($sql);
    $dbName = $selectedRow[0]["DbName"];

    //luckyDraw
    $sql = "select * from $dbName.setting where keyName = 'luckyDrawSpend'";
    $selectedRow =getSelectedRow($sql);
    $luckyDrawSpend = $selectedRow[0]["Value"];
    $luckyDrawSpend = $luckyDrawSpend?$luckyDrawSpend:0;

    //note word เพิ่ม
    $sql = "select * from $dbName.setting where keyName = 'wordAdd'";
    $selectedRow = getSelectedRow($sql);
    $wordAdd = $selectedRow[0]["Value"];
    $wordAdd = $wordAdd?$wordAdd:"เพิ่ม";

    //note word ไม่ใส่
    $sql = "select * from $dbName.setting where keyName = 'wordNo'";
    $selectedRow = getSelectedRow($sql);
    $wordNo = $selectedRow[0]["Value"];
    $wordNo = $wordNo?$wordNo:"ไม่ใส่";


    //select table -> branch
    $sqlBranch = "SELECT branch.*,'$luckyDrawSpend' LuckyDrawSpend,'$wordAdd' WordAdd, '$wordNo' WordNo FROM $jummumOM.Branch where status = 1 and customerApp = 1 and branchID = '$branchID';";
    $selectedRow = getSelectedRow($sqlBranch);
    $dbName = $selectedRow[0]["DbName"];
    ///***************
    
    

    //customerTable
    $sqlCustomerTable = "select * from $dbName.CustomerTable where status = 1 and customerTableID = '$customerTableID';";


    //saveOrderTaking
    $sqlSaveOrderTaking = "select orderTakingID as SaveOrderTakingID, OrderTaking.* from OrderTaking where ReceiptID = '$receiptID';";


    //saveOrderNote
    $sqlSaveOrderNote = "select orderNoteID as SaveOrderNoteID, orderTakingID as SaveOrderTakingID, OrderNote.* from OrderNote where OrderTakingID in (select OrderTakingID from OrderTaking where ReceiptID = '$receiptID');";


    //buffetReceipt
    $sqlBuffetReceipt = "select * from receipt where receiptID = '$buffetReceiptID';";



    $sql = $sqlBranch;
    $sql .= $sqlCustomerTable;
    $sql .= $sqlSaveReceipt;
    $sql .= $sqlSaveOrderTaking;
    $sql .= $sqlSaveOrderNote;
    $sql .= $sqlBuffetReceipt;
    

    /* execute multi query */
    $jsonEncode = executeMultiQueryArray($sql);
    $response = array('success' => true, 'data' => $jsonEncode, 'error' => null, 'status' => 1);
    echo json_encode($response);

    
    // Close connections
    mysqli_close($con);
    
?>
