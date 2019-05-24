<?php
    include_once("dbConnect.php");
    setConnectionValue($jummum);
    writeToLog("file: " . basename(__FILE__) . ", user: " . $_POST["modifiedUser"]);
    printAllPost();
    ini_set("memory_limit","-1");
    
    
    if(isset($_POST["decryptedMessage"]))
    {
        $decryptedMessage = $_POST["decryptedMessage"];
    }
    
    
    
    // Check connection
    if (mysqli_connect_errno())
    {
        echo "Failed to connect to MySQL: " . mysqli_connect_error();
    }
    
    
    
    $parts = explode("?",$decryptedMessage);
    if(sizeof($parts) > 0)
    {
        $paramPart = $parts[sizeof($parts)-1];
        
        
        //tableNo
        if(!(strpos($paramPart, "tableNo") === false))
        {
            $tableNoExplode = explode("=",$paramPart);
            $tableNoDecrypted = $tableNoExplode[1];
            $tableNoDecrypted = trim($tableNoDecrypted);
            
            
            $sql = "select aes_decrypt(unhex('$tableNoDecrypted'),'$encryptKey') as message;";
            $selectedRow = getSelectedRow($sql);
            $message = $selectedRow[0]["message"];


        //    //test
//            $message = "Shop:11,TableNo:1";
            
            
            $arrMessage = explode(",", $message);            
            if(sizeof($arrMessage) == 2)
            {
                $branchPart = $arrMessage[0];
                $customerTablePart = $arrMessage[1];
                
                //branch
                $arrBranchPart = explode(":", $branchPart);
                if(sizeof($arrBranchPart) == 2)
                {
                    $branchID = $arrBranchPart[1];
                }
                
                //customerTable
                $arrCustomerTablePart = explode(":", $customerTablePart);
                if(sizeof($arrCustomerTablePart) == 2)
                {
                    $customerTableID = $arrCustomerTablePart[1];
                }
            }
            
        //    //test
        //    if($branchID == 14)
        //    {
        //        $branchID = 19;
        //        $customerTableID = 1;
        //    }

            $sql = "select * from $jummumOM.branch where branchID = '$branchID'";
            $selectedRow =getSelectedRow($sql);
            $dbName = $selectedRow[0]["DbName"];
            
            
//            //luckyDraw
//            $sql = "select * from $dbName.setting where keyName = 'luckyDrawSpend'";
//            $selectedRow =getSelectedRow($sql);
//            $luckyDrawSpend = $selectedRow[0]["Value"];
//            $luckyDrawSpend = $luckyDrawSpend?$luckyDrawSpend:0;
            
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
            $sql = "SELECT branch.*,'$wordAdd' WordAdd, '$wordNo' WordNo FROM $jummumOM.Branch where status = 1 and customerApp = 1 and branchID = '$branchID';";
            

            //build sql statement for table
            $selectedRow = getSelectedRow($sql);
            if(sizeof($selectedRow)>0)
            {
                $eachDbName = $selectedRow[0]["DbName"];
                $sqlCustomerTable = "select $branchID as BranchID, $eachDbName.CustomerTable.* from $eachDbName.CustomerTable where customerTableID = '$customerTableID'";
            }
            else
            {
                $sqlCustomerTable = "select 1 from dual where false";
            }
            $sql .= $sqlCustomerTable . ";";
            
        }
        else if(!(strpos($paramPart, "shareMenuToOrder") === false))//shareMenuToOrder
        {
            $shareMenuToOrderExplode = explode("=",$paramPart);
            $shareMenuToOrderDecrypted = $shareMenuToOrderExplode[1];
            $shareMenuToOrderDecrypted = trim($shareMenuToOrderDecrypted);
            
            
            $sql = "select aes_decrypt(unhex('$shareMenuToOrderDecrypted'),'$encryptKey') as message;";
            $selectedRow = getSelectedRow($sql);
            $saveReceiptID = $selectedRow[0]["message"];
            
            
            //saveReceipt
            $sqlSaveReceipt = "select * from SaveReceipt where saveReceiptID = '$saveReceiptID';";
            $selectedRow = getSelectedRow($sqlSaveReceipt);
            $branchID = $selectedRow[0]["BranchID"];
            $customerTableID = $selectedRow[0]["CustomerTableID"];
            $buffetReceiptID = $selectedRow[0]["BuffetReceiptID"];
            
            
            //branch/////********************
            $sql = "select * from $jummumOM.Branch where branchID = '$branchID' and status = 1 and customerApp = 1;";
            $selectedRow = getSelectedRow($sql);
            $dbName = $selectedRow[0]["DbName"];
            
//            //luckyDraw
//            $sql = "select * $dbName.setting where keyName = 'luckyDrawSpend'";
//            $selectedRow =getSelectedRow($sql);
//            $luckyDrawSpend = $selectedRow[0]["Value"];
//            $luckyDrawSpend = $luckyDrawSpend?$luckyDrawSpend:0;
            
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
            
            
            //select table -> branch, customerTable
            $sqlBranch = "SELECT branch.*,'$wordAdd' WordAdd, '$wordNo' WordNo FROM $jummumOM.Branch where status = 1 and customerApp = 1 and branchID = '$branchID';";
            /////********************
            
            
            //customerTable
            $sqlCustomerTable = "select * from $dbName.CustomerTable where status = 1 and customerTableID = '$customerTableID';";
            
            
            //saveOrderTaking
            $sqlSaveOrderTaking = "select * from saveOrderTaking where saveReceiptID = '$saveReceiptID';";

            
            //saveOrderNote
            $sqlSaveOrderNote = "select * from saveOrderNote where saveOrderTakingID in (select saveOrderTakingID from saveOrderTaking where saveReceiptID = '$saveReceiptID');";
            
            
            //buffetReceipt
            $sqlBuffetReceipt = "select * from receipt where receiptID = '$buffetReceiptID';";
            
            
            
            $sql = $sqlBranch;
            $sql .= $sqlCustomerTable;
            $sql .= $sqlSaveReceipt;
            $sql .= $sqlSaveOrderTaking;
            $sql .= $sqlSaveOrderNote;
            $sql .= $sqlBuffetReceipt;
        }
        else
        {
            $sql = "select 0 from dual where 0;";
            $sql .= "select 0 from dual where 0";
        }
    }
    else
    {
        $sql = "select 0 from dual where 0;";
        $sql .= "select 0 from dual where 0";
    }
    
 
    
    /* execute multi query */
    $jsonEncode = executeMultiQueryArray($sql);
    $response = array('success' => true, 'data' => $jsonEncode, 'error' => null, 'status' => 1);
    echo json_encode($response);

    
    // Close connections
    mysqli_close($con);
    
?>
